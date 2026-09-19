<?php
// ملف إعدادات الشاشة الكاملة وتثبيت الـ PWA
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="icon" type="image/png" href="logo.png">
<link rel="apple-touch-icon" href="logo.png">
<link rel="manifest" href="manifest.json">

<style>
    html, body {
        height: 100vh;
        height: -webkit-fill-available;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        background-color: #f8fafc;
    }
</style>

<script>
    // تفعيل الـ Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('sw.js')
                .then((reg) => { console.log('Service Worker registered successfully', reg); })
                .catch((err) => { console.log('Service Worker registration failed', err); });
        });
    }

    // التقاط حدث التثبيت للـ PWA لتشغيله عبر زر مخصص
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        // منع ظهور النافذة التلقائية للمتصفح
        e.preventDefault();
        // الاحتفاظ بالحدث لاستخدامه لاحقاً
        deferredPrompt = e;
        
        // إظهار زر التثبيت إذا كان موجوداً في الصفحة
        let installBtn = document.getElementById('installAppBtn');
        if (installBtn) {
            installBtn.style.display = 'block';
        }
    });

    // دالة يتم استدعاؤها عند الضغط على زر التثبيت
    function installPWA() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User accepted the install prompt');
                } else {
                    console.log('User dismissed the install prompt');
                }
                deferredPrompt = null;
            });
        }
    }
</script>



