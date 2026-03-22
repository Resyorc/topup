<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ config('app.url') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ config('app.url') }}/syarat-ketentuan</loc>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc>{{ config('app.url') }}/kebijakan-privasi</loc>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>
    @foreach($games as $game)
    <url>
        <loc>{{ config('app.url') }}/order/{{ $game->slug }}</loc>
        <lastmod>{{ $game->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach
</urlset>
