@php
    $seoTitle = $title ?? 'tickIt';
    $seoDescription = $description ?? 'tickIt helps businesses automate phone calls, create tickets, capture call details, and keep follow-up moving.';
    $seoCanonical = $canonical ?? url()->current();
    $seoRobots = $robots ?? 'index,follow';
    $seoType = $type ?? 'website';
    $seoSiteName = $siteName ?? 'tickIt';
    $seoImage = $image ?? asset('og-image.svg');
    $seoTwitterCard = $twitterCard ?? 'summary_large_image';
    $structuredItems = $structuredData ?? [];

    if (!is_array($structuredItems) || array_is_list($structuredItems) === false) {
        $structuredItems = [$structuredItems];
    }
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<meta name="robots" content="{{ $seoRobots }}">
<link rel="canonical" href="{{ $seoCanonical }}">
<meta name="theme-color" content="#f97316">
<meta property="og:site_name" content="{{ $seoSiteName }}">
<meta property="og:type" content="{{ $seoType }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta property="og:image:alt" content="{{ $seoTitle }}">
<meta name="twitter:card" content="{{ $seoTwitterCard }}">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">
@foreach($structuredItems as $structuredItem)
    @if(!empty($structuredItem))
        <script type="application/ld+json">{!! json_encode($structuredItem, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
    @endif
@endforeach