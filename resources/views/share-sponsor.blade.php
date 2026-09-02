<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <title>{{ $ogTitle }}</title>
    <style>
        body { margin: 0; padding: 0; background: #0a0a0a; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; overflow: hidden; position: relative; }
        .bg-image { position: absolute; inset: 0; overflow: hidden; }
        .bg-image img { width: 100%; height: 100%; object-fit: cover; opacity: 0.2; filter: blur(4px) brightness(0.4); }
        .content { position: relative; z-index: 10; display: flex; flex-direction: column; align-items: center; gap: 32px; }
        .logo { height: 72px; object-fit: contain; animation: fadeInScale 0.5s ease-out forwards; }
        .spinner-container { position: relative; width: 48px; height: 48px; animation: fadeIn 0.3s ease-out forwards; animation-delay: 0.3s; opacity: 0; }
        .spinner-outer { width: 48px; height: 48px; border-radius: 50%; border: 3px solid rgba(201,162,77,0.15); border-top-color: #C9A24D; position: absolute; inset: 0; animation: spin 1.2s linear infinite; }
        .spinner-inner { width: 32px; height: 32px; border-radius: 50%; border: 2px solid rgba(201,162,77,0.08); border-bottom-color: rgba(201,162,77,0.5); position: absolute; top: 8px; left: 8px; animation: spinReverse 2s linear infinite; }
        .brand-name { text-align: center; color: rgba(255,255,255,0.9); font-weight: 900; font-size: 16px; letter-spacing: 0.15em; text-transform: uppercase; animation: slideUp 0.5s ease-out forwards; animation-delay: 0.4s; opacity: 0; transform: translateY(8px); }
        .brand-name span { color: #C9A24D; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        @keyframes spinReverse { 100% { transform: rotate(-360deg); } }
        @keyframes fadeInScale { 0% { opacity: 0; transform: scale(0.8); } 100% { opacity: 1; transform: scale(1); } }
        @keyframes fadeIn { 100% { opacity: 1; } }
        @keyframes slideUp { 100% { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <div class="bg-image"><img src="https://ashkananitransfer.com/hero-bg.png" alt=""></div>
    <div class="content">
        <img src="https://ashkananitransfer.com/logo3.png" alt="Ashkanani Sport" class="logo">
        <div class="spinner-container"><div class="spinner-outer"></div><div class="spinner-inner"></div></div>
        <div class="brand-name">ASHKANANI SPORT <span>TRANSFER</span></div>
    </div>
    <script>
        setTimeout(function () {
            window.location.href = "https://ashkananitransfer.com/sponsors/{{ $id }}";
        }, 500);
    </script>
</body>
</html>
