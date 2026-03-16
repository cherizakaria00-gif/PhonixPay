@if($seo)
    @php
        $metaTitle = @$seoContents->meta_title ?: gs()->siteName(__($pageTitle));
        $metaDescription = @$seoContents->description ?? $seo->description;
        $metaKeywords = implode(',', @$seoContents->keywords ?? $seo->keywords);
        $socialTitle = @$seoContents->social_title ?? $seo->social_title;
        $socialDescription = @$seoContents->social_description ?? $seo->social_description;
    @endphp
    <meta name="title" Content="{{ $metaTitle }}">
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="keywords" content="{{ $metaKeywords }}">
    <link rel="shortcut icon" href="{{ siteFavicon() }}" type="image/x-icon">

    {{--<!-- Apple Stuff -->--}}
    <link rel="apple-touch-icon" href="{{ siteLogo() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="{{ $metaTitle }}">
    {{--<!-- Google / Search Engine Tags -->--}}
    <meta itemprop="name" content="{{ $metaTitle }}">
    <meta itemprop="description" content="{{ $metaDescription }}">
    <meta itemprop="image" content="{{ $seoImage ?? getImage(getFilePath('seo') .'/'. $seo->image) }}">
    {{--<!-- Facebook Meta Tags -->--}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $socialTitle }}">
    <meta property="og:description" content="{{ $socialDescription }}">
    <meta property="og:image" content="{{ $seoImage ?? getImage(getFilePath('seo') .'/'. $seo->image) }}">
    <meta property="og:image:type" content="image/{{ pathinfo(getImage(getFilePath('seo')) .'/'. $seo->image)['extension'] }}">
    @php $socialImageSize = explode('x', getFileSize('seo')) @endphp
    <meta property="og:image:width" content="{{ $socialImageSize[0] }}">
    <meta property="og:image:height" content="{{ $socialImageSize[1] }}">
    <meta property="og:url" content="{{ url()->current() }}">
    {{--<!-- Twitter Meta Tags -->--}}
    <meta name="twitter:card" content="summary_large_image">
@endif
