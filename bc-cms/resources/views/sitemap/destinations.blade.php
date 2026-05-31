<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach($destinations as $destination)
    <url>
        <loc>{{ url('/destination/' . $destination->slug . '?id=' . $destination->id) }}</loc>
        <lastmod>{{ $destination->updated_at->toAtomString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    @endforeach
</urlset>
