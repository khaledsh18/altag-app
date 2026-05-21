<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>بوابة مجمع التاج القرآني الرقمية</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in-up {
            opacity: 0;
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .delay-100 { animation-delay: 150ms; }
        .delay-200 { animation-delay: 300ms; }
        .delay-300 { animation-delay: 450ms; }
        .delay-400 { animation-delay: 600ms; }
        .delay-500 { animation-delay: 750ms; }
    </style>
</head>

<body
    class="bg-zinc-50 dark:bg-accent-dark text-[#1b1b18] dark:text-[#EDEDEC] flex flex-col min-h-screen font-sans antialiased relative overflow-x-hidden selection:bg-maroon selection:text-white">
    
    <!-- الخلفية الزخرفية الإسلامية الفاخرة -->
    <div class="absolute inset-0 opacity-[0.03] dark:opacity-[0.06] pointer-events-none z-0">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
            <pattern id="islamic-pattern" width="80" height="80" patternUnits="userSpaceOnUse">
                <path d="M40 0 L80 40 L40 80 L0 40 Z M40 10 L70 40 L40 70 L10 40 Z" fill="none" stroke="currentColor" stroke-width="1"/>
                <circle cx="40" cy="40" r="8" fill="none" stroke="currentColor" stroke-width="0.75"/>
                <path d="M0 0 L80 80 M80 0 L0 80" fill="none" stroke="currentColor" stroke-width="0.5" stroke-dasharray="4 4"/>
            </pattern>
            <rect width="100%" height="100%" fill="url(#islamic-pattern)"/>
        </svg>
    </div>

    <!-- هالات ضوئية متدرجة لخلق عمق بصري ملهم -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-[600px] pointer-events-none z-0 overflow-hidden">
        <div class="absolute top-[-20%] left-[10%] w-[50%] h-[60%] rounded-full bg-gradient-to-tr from-maroon/10 to-transparent blur-3xl opacity-60 dark:opacity-40"></div>
        <div class="absolute top-[-10%] right-[10%] w-[40%] h-[50%] rounded-full bg-gradient-to-tl from-red-secondary/10 to-transparent blur-3xl opacity-55 dark:opacity-35"></div>
    </div>

    <!-- رأس الصفحة (Header) -->
    <header
        class="w-full bg-white/70 dark:bg-accent-dark/50 border-b border-zinc-200/60 dark:border-zinc-800/80 p-4 sticky top-0 z-50 backdrop-blur-xl transition-all duration-300">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="bg-maroon p-2.5 rounded-xl shadow-md shadow-maroon/20 hover:scale-105 transition-transform">
                    <x-app-logo-icon class="size-6 text-white" />
                </div>
                <div class="flex flex-col">
                    <span class="font-bold text-lg leading-tight tracking-tight">مجمع التاج القرآني</span>
                    <span class="text-xs text-neutral-grey dark:text-zinc-400 font-medium">بجامع الزبيدي</span>
                </div>
            </div>
            
            <!-- زر إرشادي خفيف -->
            <div class="hidden sm:block">
                <a href="#portals" class="text-sm font-semibold text-neutral-grey dark:text-zinc-300 hover:text-maroon dark:hover:text-red-secondary transition-colors">
                    بوابات الدخول ↓
                </a>
            </div>
        </div>
    </header>

    <!-- المحتوى الرئيسي (Main) -->
    <main class="grow flex flex-col items-center px-4 py-12 md:py-20 z-10">
        
        <!-- قسم الترحيب الرئيسي (Hero) -->
        <div class="w-full max-w-3xl mx-auto text-center space-y-6 mb-16 animate-fade-in-up">
            <!-- الشارة العلوية المتحركة لخيركم -->
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-maroon/5 dark:bg-red-secondary/15 border border-maroon/10 dark:border-red-secondary/35 text-maroon dark:text-red-secondary text-xs md:text-sm font-bold shadow-sm backdrop-blur-md">
                <span class="relative flex size-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-maroon dark:bg-red-secondary opacity-75"></span>
                    <span class="relative inline-flex rounded-full size-2 bg-maroon dark:bg-red-secondary"></span>
                </span>
                {{ __('جمعية خيركم لتحفيظ القرآن بجدة') }}
            </div>

            <!-- العنوان الفخم -->
            <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight leading-tight">
                أهلاً بكم في <br class="md:hidden"> 
                <span class="bg-gradient-to-r from-maroon via-red-secondary to-maroon dark:from-white dark:via-zinc-200 dark:to-white bg-clip-text text-transparent">بوابة مجمع التاج</span>
                <span class="block text-2xl md:text-3xl mt-3 text-red-secondary font-bold font-sans">المنصة التعليمية الرقمية الشاملة</span>
            </h1>

            <p class="max-w-xl mx-auto text-neutral-grey dark:text-zinc-300 text-sm md:text-base leading-relaxed font-medium">
                بوابتك الرقمية الذكية لمتابعة شؤون الحفظ والمراجعة، إدارة الحلقات، ورصد المستويات والتميز التعليمي في جامع الزبيدي بجدة.
            </p>
        </div>

        <!-- شبكة البوابات الأربع (Portals Grid) -->
        <div id="portals" class="w-full max-w-3xl mx-auto grid grid-cols-1 sm:grid-cols-2 gap-6">
            
            <!-- بوابة المعلم -->
            <a href="{{ route('teacher.login') }}"
                class="animate-fade-in-up delay-100 group relative overflow-hidden flex flex-col justify-between p-8 border rounded-3xl bg-white/70 dark:bg-zinc-900/60 border-zinc-200/60 dark:border-zinc-800/80 hover:border-maroon/50 dark:hover:border-red-secondary/50 shadow-sm hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 backdrop-blur-xl">
                <div class="absolute -right-10 -top-10 w-24 h-24 bg-maroon/5 dark:bg-maroon/10 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                
                <div>
                    <div class="bg-maroon/10 dark:bg-maroon/20 p-4 rounded-2xl w-fit mb-6 text-maroon dark:text-red-secondary group-hover:scale-110 transition-transform duration-300 shadow-inner">
                        <!-- لوحة عرض تقديمي للمعلم -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" />
                        </svg>
                    </div>
                    <h2 class="font-bold text-2xl mb-2 text-zinc-900 dark:text-white">بوابة المعلم</h2>
                    <p class="text-xs md:text-sm text-neutral-grey dark:text-zinc-400 leading-relaxed">إدارة الحلقات والطلاب، رصد الحضور والغياب، وتدشين خطط التسميع والمراجعة اليومية.</p>
                </div>

                <div class="mt-8 flex items-center gap-1.5 text-sm font-bold text-maroon dark:text-red-secondary">
                    <span>دخول البوابة</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4 group-hover:-translate-x-1.5 transition-transform duration-300">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </div>
            </a>

            <!-- بوابة المشرف -->
            <a href="{{ route('supervisor.login') }}"
                class="animate-fade-in-up delay-200 group relative overflow-hidden flex flex-col justify-between p-8 border rounded-3xl bg-white/70 dark:bg-zinc-900/60 border-zinc-200/60 dark:border-zinc-800/80 hover:border-blue-600/50 dark:hover:border-blue-400/50 shadow-sm hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 backdrop-blur-xl">
                <div class="absolute -right-10 -top-10 w-24 h-24 bg-blue-500/5 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                
                <div>
                    <div class="bg-blue-500/10 p-4 rounded-2xl w-fit mb-6 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform duration-300 shadow-inner">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                        </svg>
                    </div>
                    <h2 class="font-bold text-2xl mb-2 text-zinc-900 dark:text-white">بوابة المشرف</h2>
                    <p class="text-xs md:text-sm text-neutral-grey dark:text-zinc-400 leading-relaxed">متابعة سير المجمع التعليمي، الإشراف على المعلمين والحلقات، ومراجعة تقارير الإنجاز الدقيقة والذكية.</p>
                </div>

                <div class="mt-8 flex items-center gap-1.5 text-sm font-bold text-blue-600 dark:text-blue-400">
                    <span>دخول البوابة</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4 group-hover:-translate-x-1.5 transition-transform duration-300">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </div>
            </a>

            <!-- بوابة الطالب -->
            <a href="{{ route('student.login') }}"
                class="animate-fade-in-up delay-300 group relative overflow-hidden flex flex-col justify-between p-8 border rounded-3xl bg-white/70 dark:bg-zinc-900/60 border-zinc-200/60 dark:border-zinc-800/80 hover:border-orange-500/50 dark:hover:border-orange-400/50 shadow-sm hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 backdrop-blur-xl">
                <div class="absolute -right-10 -top-10 w-24 h-24 bg-orange-500/5 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                
                <div>
                    <div class="bg-orange-500/10 p-4 rounded-2xl w-fit mb-6 text-orange-600 dark:text-orange-400 group-hover:scale-110 transition-transform duration-300 shadow-inner">
                        <!-- شخص واحد (طالب) -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                    <h2 class="font-bold text-2xl mb-2 text-zinc-900 dark:text-white">بوابة الطالب</h2>
                    <p class="text-xs md:text-sm text-neutral-grey dark:text-zinc-400 leading-relaxed">مراجعة جدول الحفظ الفردي، متابعة رصيد النقاط ولوحات الشرف والتحديات، وطلبات التعديل على الخطة.</p>
                </div>

                <div class="mt-8 flex items-center gap-1.5 text-sm font-bold text-orange-600 dark:text-orange-400">
                    <span>دخول البوابة</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4 group-hover:-translate-x-1.5 transition-transform duration-300">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </div>
            </a>

            <!-- بوابة ولي الأمر -->
            <a href="{{ route('parent.login') }}"
                class="animate-fade-in-up delay-400 group relative overflow-hidden flex flex-col justify-between p-8 border rounded-3xl bg-white/70 dark:bg-zinc-900/60 border-zinc-200/60 dark:border-zinc-800/80 hover:border-purple-600/50 dark:hover:border-purple-400/50 shadow-sm hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 backdrop-blur-xl">
                <div class="absolute -right-10 -top-10 w-24 h-24 bg-purple-500/5 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
                
                <div>
                    <div class="bg-purple-500/10 p-4 rounded-2xl w-fit mb-6 text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform duration-300 shadow-inner">
                        <!-- منزل / أسرة (ولي الأمر) -->
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                    </div>
                    <h2 class="font-bold text-2xl mb-2 text-zinc-900 dark:text-white">بوابة ولي الأمر</h2>
                    <p class="text-xs md:text-sm text-neutral-grey dark:text-zinc-400 leading-relaxed">متابعة إنجاز الأبناء اليومي، الاطلاع على تقارير الغياب والدرجات الفورية، والتواصل مع إدارة ومعلم الحلقة.</p>
                </div>

                <div class="mt-8 flex items-center gap-1.5 text-sm font-bold text-purple-600 dark:text-purple-400">
                    <span>دخول البوابة</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="size-4 group-hover:-translate-x-1.5 transition-transform duration-300">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </div>
            </a>

        </div>

        <!-- شريط الإحصاءات التفاعلي والحي المربوط بقاعدة البيانات -->
        <div class="animate-fade-in-up delay-500 mt-20 max-w-3xl w-full mx-auto p-6 md:p-8 rounded-3xl bg-maroon/[0.02] dark:bg-white/[0.01] border border-maroon/5 dark:border-white/5 backdrop-blur-md shadow-2xl">
            <div class="text-center mb-6">
                <span class="text-xs md:text-sm text-maroon dark:text-red-secondary font-bold tracking-widest uppercase">نبض الإنجاز الفعلي للمجمع</span>
                <h3 class="text-lg md:text-xl font-bold mt-1">إحصاءات حية لإنجاز مجمع التاج</h3>
            </div>
            
            <div class="grid grid-cols-3 gap-4 md:gap-8 text-center divide-x divide-x-reverse divide-zinc-200/50 dark:divide-zinc-800/40">
                <div class="flex flex-col items-center justify-center space-y-1 md:space-y-2">
                    <span class="text-3xl md:text-5xl font-black text-maroon dark:text-white font-sans tracking-tight">
                        {{ $stats['circles'] ?? 8 }}
                    </span>
                    <span class="text-xs md:text-sm text-neutral-grey dark:text-zinc-400 font-bold">حلقة نشطة</span>
                </div>
                
                <div class="flex flex-col items-center justify-center space-y-1 md:space-y-2">
                    <span class="text-3xl md:text-5xl font-black text-maroon dark:text-white font-sans tracking-tight">
                        {{ $stats['students'] ?? 134 }}
                    </span>
                    <span class="text-xs md:text-sm text-neutral-grey dark:text-zinc-400 font-bold">طالباً مستفيداً</span>
                </div>
                
                <div class="flex flex-col items-center justify-center space-y-1 md:space-y-2">
                    <span class="text-3xl md:text-5xl font-black text-maroon dark:text-white font-sans tracking-tight">
                        {{ $stats['teachers'] ?? 8 }}
                    </span>
                    <span class="text-xs md:text-sm text-neutral-grey dark:text-zinc-400 font-bold">معلمين أكفاء</span>
                </div>
            </div>
        </div>

        <!-- تذييل تفصيلي (Location & Contact Info) -->
        <div
            class="animate-fade-in-up delay-500 mt-16 md:mt-24 w-full max-w-3xl grid grid-cols-1 md:grid-cols-3 gap-8 px-4 border-t border-zinc-200/60 dark:border-zinc-800/80 pt-12">
            <div class="flex flex-col items-center text-center space-y-2 group">
                <div
                    class="w-12 h-12 rounded-2xl bg-maroon/5 dark:bg-maroon/10 border border-maroon/10 dark:border-maroon/20 flex items-center justify-center text-maroon dark:text-red-secondary group-hover:scale-110 transition-transform duration-300 shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                        <circle cx="12" cy="10" r="3" />
                    </svg>
                </div>
                <h4 class="font-bold text-sm">موقع المجمع</h4>
                <p class="text-xs text-neutral-grey dark:text-zinc-400 font-medium leading-relaxed">جدة، حي الواحة، خلف هيئة المساحة الجيولوجية</p>
            </div>
            
            <div class="flex flex-col items-center text-center space-y-2 group">
                <div
                    class="w-12 h-12 rounded-2xl bg-maroon/5 dark:bg-maroon/10 border border-maroon/10 dark:border-maroon/20 flex items-center justify-center text-maroon dark:text-red-secondary group-hover:scale-110 transition-transform duration-300 shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                    </svg>
                </div>
                <h4 class="font-bold text-sm">الاتصال المباشر</h4>
                <p class="text-xs text-neutral-grey dark:text-zinc-400 font-medium leading-relaxed font-sans">0508822794</p>
            </div>
            
            <div class="flex flex-col items-center text-center space-y-2 group">
                <div
                    class="w-12 h-12 rounded-2xl bg-maroon/5 dark:bg-maroon/10 border border-maroon/10 dark:border-maroon/20 flex items-center justify-center text-maroon dark:text-red-secondary group-hover:scale-110 transition-transform duration-300 shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17Z" />
                        <path d="m10 15 5-3-5-3v6Z" />
                    </svg>
                </div>
                <h4 class="font-bold text-sm">شبكات التواصل</h4>
                <p class="text-xs text-neutral-grey dark:text-zinc-400 font-medium leading-relaxed">تويتر (X): @altag_jeddah</p>
            </div>
        </div>
    </main>

    <!-- تذييل الصفحة الأخير -->
    <footer class="w-full bg-white dark:bg-accent-dark border-t border-zinc-200/60 dark:border-zinc-800/80 py-6 text-center z-10">
        <p class="text-xs md:text-sm text-neutral-grey dark:text-zinc-500 font-medium">
            &copy; {{ date('Y') }} مجمع التاج القرآني. جميع الحقوق محفوظة.
        </p>
    </footer>
</body>

</html>