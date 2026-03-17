@php
    $metaTitle = (string) (@$seoContents->meta_title ?: gs()->siteName(__($pageTitle ?? gs('site_name'))));
    $metaDescription = (string) (@$seoContents->description ?? ($seo->description ?? gs('site_name')));
    $metaKeywords = implode(',', (array) (@$seoContents->keywords ?? ($seo->keywords ?? [])));
    $socialTitle = (string) (@$seoContents->social_title ?? ($seo->social_title ?? $metaTitle));
    $socialDescription = (string) (@$seoContents->social_description ?? ($seo->social_description ?? $metaDescription));
    $defaultSeoImage = isset($seo) && !empty($seo->image)
        ? getImage(getFilePath('seo') . '/' . $seo->image)
        : siteLogo();
    $socialImage = $seoImage ?? $defaultSeoImage;
    $socialImageSize = explode('x', getFileSize('seo'));
@endphp

<meta name="title" content="{{ $metaTitle }}">
<meta name="description" content="{{ $metaDescription }}">
<meta name="keywords" content="{{ $metaKeywords }}">
<link rel="shortcut icon" href="{{ siteFavicon() }}" type="image/x-icon">

<link rel="apple-touch-icon" href="{{ siteLogo() }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="apple-mobile-web-app-title" content="{{ $metaTitle }}">

<meta itemprop="name" content="{{ $metaTitle }}">
<meta itemprop="description" content="{{ $metaDescription }}">
<meta itemprop="image" content="{{ $socialImage }}">

<meta property="og:type" content="website">
<meta property="og:title" content="{{ $socialTitle }}">
<meta property="og:description" content="{{ $socialDescription }}">
<meta property="og:image" content="{{ $socialImage }}">
<meta property="og:image:type" content="image/{{ pathinfo($socialImage, PATHINFO_EXTENSION) ?: 'png' }}">
<meta property="og:image:width" content="{{ $socialImageSize[0] ?? 1200 }}">
<meta property="og:image:height" content="{{ $socialImageSize[1] ?? 630 }}">
<meta property="og:url" content="{{ url()->current() }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $socialTitle }}">
<meta name="twitter:description" content="{{ $socialDescription }}">
<meta name="twitter:image" content="{{ $socialImage }}">
