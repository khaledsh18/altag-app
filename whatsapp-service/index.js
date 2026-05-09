const express = require('express');
const cors = require('cors');
const qrcode = require('qrcode');
const { Client, LocalAuth } = require('whatsapp-web.js');

const app = express();
app.use(cors());
app.use(express.json());

const port = 3000;

let qrCodeData = null;
let isReady = false;
let isAuthenticated = false;
let loadingPercent = 0;

// تهيئة عميل واتساب مع حفظ الجلسة محلياً
const client = new Client({
    authStrategy: new LocalAuth(),
    puppeteer: {
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    }
});

// عند توليد QR Code
client.on('qr', (qr) => {
    console.log('تم إنشاء QR Code جديد. يرجى مسحه لربط الحساب.');
    qrCodeData = qr;
});

// تتبع نسبة التحميل
client.on('loading_screen', (percent, message) => {
    console.log(`جاري التحميل: ${percent}% - ${message}`);
    loadingPercent = percent;
});

// عند المصادقة بنجاح
client.on('authenticated', () => {
    console.log('تمت المصادقة بنجاح!');
    isAuthenticated = true;
    qrCodeData = null; // مسح الرمز بعد المصادقة
});

// عند جاهزية العميل
client.on('ready', () => {
    console.log('واتساب جاهز الآن للعمل!');
    isReady = true;
    isAuthenticated = true;
    qrCodeData = null;
});

// عند قطع الاتصال
client.on('disconnected', (reason) => {
    console.log('تم قطع الاتصال بالواتساب:', reason);
    isReady = false;
    isAuthenticated = false;
    qrCodeData = null;
});

client.initialize();

// مسار للحصول على حالة الاتصال ورمز QR (إن وجد)
app.get('/status', async (req, res) => {
    if (isReady) {
        return res.json({ status: 'ready', message: 'واتساب متصل وجاهز.' });
    }

    if (isAuthenticated) {
        return res.json({
            status: 'loading',
            message: `تمت المصادقة بنجاح. جاري تنزيل المحادثات والتهيئة... (${loadingPercent}%)`
        });
    }

    if (qrCodeData) {
        try {
            // تحويل رمز QR إلى صورة Base64 لتسهيل عرضها في لارافل
            const qrImage = await qrcode.toDataURL(qrCodeData);
            return res.json({ status: 'needs_scan', qr_image: qrImage });
        } catch (err) {
            return res.status(500).json({ status: 'error', message: 'فشل في توليد صورة الـ QR.' });
        }
    }

    return res.json({ status: 'starting', message: 'جاري تهيئة الواتساب...' });
});

// مسار لإرسال رسالة
app.post('/send', async (req, res) => {
    if (!isReady) {
        return res.status(503).json({ success: false, message: 'واتساب غير جاهز بعد.' });
    }

    const { phone, message } = req.body;

    if (!phone || !message) {
        return res.status(400).json({ success: false, message: 'يرجى توفير رقم الهاتف ونص الرسالة.' });
    }

    try {
        // تأكد من أن الرقم ينتهي بـ @c.us
        const chatId = `${phone}@c.us`;
        const response = await client.sendMessage(chatId, message);
        return res.json({ success: true, message: 'تم إرسال الرسالة بنجاح.', response });
    } catch (error) {
        console.error('خطأ أثناء إرسال الرسالة:', error);
        return res.status(500).json({ success: false, message: 'حدث خطأ أثناء الإرسال.', error: error.message });
    }
});

app.listen(port, () => {
    console.log(`خدمة الواتساب تعمل على المنفذ http://localhost:${port}`);
});
