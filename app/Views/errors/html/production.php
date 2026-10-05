<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Kesalahan Internal Server | EduNusa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Plus Jakarta Sans", sans-serif;
            background: #ffffff;
            color: #374151;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        header { padding: 36px 48px; display: flex; justify-content: center; }
        .logo {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 20px; font-weight: 800; color: #111827;
            text-decoration: none; letter-spacing: -0.03em;
        }
        .logo i { color: #4f46e5; font-size: 22px; }
        main {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 40px 24px 80px; text-align: center;
        }
        .error-code {
            font-size: 120px; font-weight: 800; letter-spacing: -0.05em;
            color: #f3f4f6; line-height: 1; margin-bottom: -20px; position: relative; z-index: 0;
        }
        .illustration-wrap {
            position: relative; z-index: 1; height: 180px;
            width: 100%; max-width: 700px;
            display: flex; align-items: center; justify-content: center; margin-bottom: 32px;
        }
        h1 { font-size: 32px; font-weight: 800; color: #111827; letter-spacing: -0.02em; margin-bottom: 12px; }
        p.subtitle { font-size: 15px; color: #6b7280; max-width: 440px; line-height: 1.7; margin-bottom: 36px; }
        .btn-home {
            display: inline-flex; align-items: center; gap: 8px;
            background: #111827; color: #fff; font-family: "Plus Jakarta Sans", sans-serif;
            font-size: 14px; font-weight: 600; padding: 12px 28px;
            border-radius: 8px; text-decoration: none; transition: background 0.2s;
        }
        .btn-home:hover { background: #374151; }
        footer {
            border-top: 1px solid #f3f4f6; padding: 24px 48px;
            display: flex; justify-content: space-between; align-items: center;
            color: #9ca3af; font-size: 13px;
        }
        .footer-contact a { color: #6b7280; text-decoration: none; font-weight: 500; }
        .footer-contact a:hover { color: #111827; }
        .social-icons { display: flex; gap: 10px; }
        .social-icons a {
            width: 30px; height: 30px; border: 1px solid #e5e7eb; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #9ca3af; text-decoration: none; font-size: 12px; transition: all 0.2s;
        }
        .social-icons a:hover { border-color: #6b7280; color: #374151; }
        /* 404 Magnifier */
        .magnifier { animation: sway 3s ease-in-out infinite; transform-origin: top right; }
        .mag-circle { width: 80px; height: 80px; border: 10px solid #6366f1; border-radius: 50%; background: #eef2ff; position: relative; }
        .mag-question { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 28px; font-weight: 800; color: #6366f1; }
        .mag-handle { width: 44px; height: 10px; background: #6366f1; border-radius: 5px; transform: rotate(45deg); margin-top: 8px; margin-left: 44px; }
        .search-line { height: 8px; background: #e5e7eb; border-radius: 4px; animation: shimmer 2s ease-in-out infinite; }
        @keyframes sway { 0%, 100% { transform: rotate(-10deg); } 50% { transform: rotate(10deg); } }
        @keyframes shimmer { 0%, 100% { opacity: 0.4; } 50% { opacity: 1; } }
        /* 403 Lock */
        .lock-body { width: 80px; height: 64px; background: #f59e0b; border-radius: 12px; position: relative; display: flex; align-items: center; justify-content: center; animation: shake 4s ease-in-out infinite; }
        .lock-shackle { width: 44px; height: 44px; border: 10px solid #d97706; border-bottom: none; border-radius: 22px 22px 0 0; position: absolute; top: -36px; }
        .lock-keyhole { width: 14px; height: 20px; background: #d97706; border-radius: 7px 7px 4px 4px; }
        @keyframes shake { 0%, 85%, 100% { transform: rotate(0deg); } 88% { transform: rotate(-3deg); } 92% { transform: rotate(3deg); } 96% { transform: rotate(-2deg); } }
        /* 500 Server */
        .server-unit { width: 120px; height: 20px; background: #374151; border-radius: 4px; display: flex; align-items: center; padding: 0 8px; gap: 4px; }
        .server-led { width: 6px; height: 6px; border-radius: 50%; }
        .led-green { background: #10b981; animation: blink 1s infinite; }
        .led-red { background: #ef4444; animation: blink 0.3s infinite; }
        .led-off { background: #6b7280; }
        .server-bar { flex: 1; height: 4px; background: #4b5563; border-radius: 2px; }
        .spark { position: absolute; animation: spark-fly 2s ease-out infinite; }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.2; } }
        @keyframes spark-fly { 0% { opacity: 1; transform: translateY(0) scale(1); } 100% { opacity: 0; transform: translateY(-30px) scale(0.5); } }
        @media (max-width: 640px) { .error-code { font-size: 80px; } h1 { font-size: 24px; } footer { flex-direction: column; gap: 16px; text-align: center; } }</style>
</head>
<body>
    <header>
        <a href="/" class="logo"><i class="fas fa-graduation-cap"></i> EduNusa</a>
    </header>
    <main>
        <div class="error-code">500</div>
        <div class="illustration-wrap"><div style="display:flex;align-items:center;gap:40px;">
    <div style="position:relative;">
        <div style="display:flex;flex-direction:column;gap:6px;">
            <div class="server-unit"><div class="server-led led-red"></div><div class="server-bar"></div><div class="server-led led-off"></div></div>
            <div class="server-unit"><div class="server-led led-red"></div><div class="server-bar"></div><div class="server-led led-red"></div></div>
            <div class="server-unit"><div class="server-led led-off"></div><div class="server-bar"></div><div class="server-led led-off"></div></div>
            <div class="server-unit"><div class="server-led led-red"></div><div class="server-bar"></div><div class="server-led led-off"></div></div>
        </div>
        <div class="spark" style="top:5px;right:-24px;font-size:24px;">⚡</div>
        <div class="spark" style="top:-5px;left:-10px;animation-delay:.7s;font-size:16px;">✦</div>
        <div class="spark" style="bottom:0;right:10px;animation-delay:1.4s;font-size:14px;">✦</div>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px;">
        <div style="font-size:13px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Server Log</div>
        <div style="height:8px;width:160px;background:#fee2e2;border-radius:4px;"></div>
        <div style="height:8px;width:100px;background:#fee2e2;border-radius:4px;"></div>
        <div style="height:8px;width:130px;background:#f3f4f6;border-radius:4px;"></div>
    </div>
</div></div>
        <h1>Kesalahan Internal Server</h1>
        <p class="subtitle">Terjadi sesuatu yang tidak beres di sisi server kami. Tim teknis kami sedang menangani masalah ini. Coba kembali beberapa saat lagi.</p>
        <a href="/" class="btn-home"><i class="fas fa-arrow-left"></i> Kembali ke Beranda</a>
    </main>
    <footer>
        <div class="footer-contact">Butuh bantuan? &nbsp; <a href="mailto:support@edunusa.edu.id">support@edunusa.edu.id</a> &nbsp;|&nbsp; <a href="tel:+6281234567890">+62 812-3456-7890</a></div>
        <div class="social-icons">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-telegram-plane"></i></a>
        </div>
    </footer>
</body>
</html>