<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- Dynamic Meta Tags for Social Sharing -->
        <title>{{ $pageTitle ?? 'Ashkanani Sport | أشكناني سبورت' }}</title>
        <meta name="description" content="{{ $metaDescription ?? 'Ashkanani Sport - Professional Athlete Management' }}">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="profile">
        <meta property="og:url" content="{{ request()->fullUrl() }}">
        <meta property="og:title" content="{{ $ogTitle ?? ($pageTitle ?? 'Ashkanani Sport') }}">
        <meta property="og:description" content="{{ $ogDescription ?? ($metaDescription ?? 'Professional Athlete Management') }}">
        <meta property="og:image" content="{{ $ogImage ?? asset('logo3.png') }}">

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:url" content="{{ request()->fullUrl() }}">
        <meta property="twitter:title" content="{{ $ogTitle ?? ($pageTitle ?? 'Ashkanani Sport') }}">
        <meta property="twitter:description" content="{{ $ogDescription ?? ($metaDescription ?? 'Professional Athlete Management') }}">
        <meta property="twitter:image" content="{{ $ogImage ?? asset('logo3.png') }}">

        <script>
            // If a real user visits this API URL directly, redirect them to the frontend SPA
            var frontendDomain = "https://ashkananitransfer.com";
            var path = window.location.pathname;
            var search = window.location.search;
            // Also hash if any, though it usually isn't sent to server
            window.location.replace(frontendDomain + path + search + window.location.hash);
        </script>
    </head>
    <body style="font-family: sans-serif; padding: 2rem; text-align: center;">
        <p>Redirecting to profile...</p>
        <p><a href="https://ashkananitransfer.com">Click here if you are not redirected</a></p>
    </body>
</html>
