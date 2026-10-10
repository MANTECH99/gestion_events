@php
    $siteName = config('seo.site_name');
    $metaTitle = $title ? $title.' — '.$siteName : config('seo.default_title');
    $metaDescription = $description ?: config('seo.default_description');
    $canonicalUrl = $canonical ?: url()->current();
    $robotsContent = $robots ?: config('seo.default_robots');
    $ogTypeContent = $ogType ?: 'website';
    $imageUrl = \App\Support\Seo::absoluteImage($image)
        ?? \App\Support\Seo::absoluteImage(config('seo.default_image'));
    $structuredData = array_merge([\App\Support\Seo::organization()], $schema ?? []);
@endphp

<title>{{ $metaTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
<meta name="robots" content="{{ $robotsContent }}">
<link rel="canonical" href="{{ $canonicalUrl }}">
@if (config('seo.verification.google'))
    <meta name="google-site-verification" content="{{ config('seo.verification.google') }}">
@endif
@if (config('seo.verification.bing'))
    <meta name="msvalidate.01" content="{{ config('seo.verification.bing') }}">
@endif

{{-- Open Graph --}}
<meta property="og:type" content="{{ $ogTypeContent }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:locale" content="{{ config('seo.locale') }}">
@if ($imageUrl)
    <meta property="og:image" content="{{ $imageUrl }}">
    <meta property="og:image:alt" content="{{ $metaTitle }}">
@endif
@if (config('seo.twitter_handle'))
    <meta name="twitter:site" content="{{ config('seo.twitter_handle') }}">
@endif

{{-- Twitter Cards --}}
<meta name="twitter:card" content="{{ $imageUrl ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
@if ($imageUrl)
    <meta name="twitter:image" content="{{ $imageUrl }}">
@endif

{{-- Données structurées --}}
@foreach ($structuredData as $data)
    <script type="application/ld+json">{!! json_encode($data, JSON_UNESCAPED_UNICODE) !!}</script>
@endforeach
