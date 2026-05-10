const express = require('express');
const cors = require('cors');
const qrcode = require('qrcode');
const { Client, LocalAuth } = require('whatsapp-web.js');

const app = express();
app.use(cors());
app.use(express.json());

const port = 3000;

// تخزين جميع الجلسات: Map من clientId -> بيانات الجلسة
const sessions = new Map();

/**
 * إنشاء أو استرجاع جلسة واتساب لمستخدم معين
 */
function getOrCreateSession(clientId) {
    if (sessions.has(clientId)) {
        return sessions.get(clientId);
    }

    const sessionData = {
        client: null,
        status: 'starting',
        qrCode: null,
        loadingPercent: 0,
    };

    const client = new Client({
        authStrategy: new LocalAuth({ clientId }),
        puppeteer: {
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
        },
    });

    client.on('qr', (qr) => {
        console.log(`[${clientId}] QR Code جديد.`);
        sessionData.qrCode = qr;
        sessionData.status = 'needs_scan';
    });

    client.on('loading_screen', (percent) => {
        sessionData.loadingPercent = percent;
        sessionData.status = 'loading';
    });

    client.on('authenticated', () => {
        console.log(`[${clientId}] تمت المصادقة.`);
        sessionData.status = 'loading';
        sessionData.qrCode = null;
    });

    client.on('ready', () => {
        console.log(`[${clientId}] جاهز للإرسال.`);
        sessionData.status = 'ready';
        sessionData.qrCode = null;
    });

    client.on('disconnected', (reason) => {
        console.log(`[${clientId}] قُطع الاتصال: ${reason}`);
        sessionData.status = 'disconnected';
        sessionData.qrCode = null;
        // إعادة التهيئة تلقائياً بعد 5 ثوانٍ
        setTimeout(() => {
            sessions.delete(clientId);
        }, 5000);
    });

    client.initialize();
    sessionData.client = client;
    sessions.set(clientId, sessionData);

    return sessionData;
}

// ── GET /status/:clientId ─────────────────────────────────────────────────────
app.get('/status/:clientId', async (req, res) => {
    const { clientId } = req.params;
    const session = getOrCreateSession(clientId);

    if (session.status === 'ready') {
        return res.json({ status: 'ready', message: 'واتساب متصل وجاهز.' });
    }

    if (session.status === 'loading') {
        return res.json({
            status: 'loading',
            message: `تمت المصادقة. جاري التهيئة... (${session.loadingPercent}%)`,
        });
    }

    if (session.status === 'needs_scan' && session.qrCode) {
        try {
            const qrImage = await qrcode.toDataURL(session.qrCode);
            return res.json({ status: 'needs_scan', qr_image: qrImage });
        } catch (err) {
            return res.status(500).json({ status: 'error', message: 'فشل في توليد QR.' });
        }
    }

    if (session.status === 'disconnected') {
        return res.json({ status: 'disconnected', message: 'انقطع الاتصال. جاري إعادة التهيئة...' });
    }

    return res.json({ status: 'starting', message: 'جاري تهيئة الواتساب...' });
});

// ── POST /send ────────────────────────────────────────────────────────────────
app.post('/send', async (req, res) => {
    const { clientId, phone, message } = req.body;

    if (!clientId || !phone || !message) {
        return res.status(400).json({ success: false, message: 'يرجى توفير clientId ورقم الهاتف والرسالة.' });
    }

    const session = sessions.get(clientId);

    if (!session || session.status !== 'ready') {
        return res.status(503).json({ success: false, message: `الجلسة [${clientId}] غير جاهزة بعد.` });
    }

    try {
        const chatId = `${phone}@c.us`;
        await session.client.sendMessage(chatId, message);
        return res.json({ success: true, message: 'تم الإرسال بنجاح.' });
    } catch (error) {
        console.error(`[${clientId}] خطأ في الإرسال:`, error.message);
        return res.status(500).json({ success: false, message: 'حدث خطأ أثناء الإرسال.', error: error.message });
    }
});

// ── POST /disconnect/:clientId ────────────────────────────────────────────────
app.post('/disconnect/:clientId', async (req, res) => {
    const { clientId } = req.params;
    const session = sessions.get(clientId);

    if (!session) {
        return res.json({ success: true, message: 'الجلسة غير موجودة.' });
    }

    try {
        await session.client.destroy();
        sessions.delete(clientId);
        return res.json({ success: true, message: 'تم قطع الاتصال.' });
    } catch (error) {
        return res.status(500).json({ success: false, error: error.message });
    }
});

app.listen(port, () => {
    console.log(`خدمة الواتساب تعمل على المنفذ http://localhost:${port}`);
});
