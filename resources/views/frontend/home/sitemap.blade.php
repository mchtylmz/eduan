<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
			    http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ now()->format('Y-m-d') }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>{{ route('frontend.home') }}</loc>
        <lastmod>{{ now()->format('Y-m-d') }}</lastmod>
        <priority>0.5</priority>
    </url>
    <url>
        <loc>{{ route('frontend.blog') }}</loc>
        <lastmod>{{ now()->format('Y-m-d') }}</lastmod>
        <priority>0.5</priority>
    </url>
    <url>
        <loc>{{ route('frontend.lessons') }}</loc>
        <lastmod>{{ now()->format('Y-m-d') }}</lastmod>
        <priority>0.5</priority>
    </url>
    <url>
        <loc>{{ route('frontend.faqs') }}</loc>
        <lastmod>{{ now()->format('Y-m-d') }}</lastmod>
        <priority>0.5</priority>
    </url>
    <url>
        <loc>{{ route('frontend.contact') }}</loc>
        <lastmod>{{ now()->format('Y-m-d') }}</lastmod>
        <priority>0.5</priority>
    </url>
    <url>
        <loc>{{ route('frontend.search') }}</loc>
        <lastmod>{{ now()->format('Y-m-d') }}</lastmod>
        <priority>0.5</priority>
    </url>
    @foreach(\App\Models\Page::all() as $page)
        <url>
            <loc>{{ route('frontend.page', $page->slug) }}</loc>
            <lastmod>{{ $page->updated_at }}</lastmod>
            <priority>0.3</priority>
        </url>
    @endforeach
    @foreach(\App\Models\Blog::all() as $blog)
        <url>
            <loc>{{ route('frontend.blog.detail', $blog->slug) }}</loc>
            <lastmod>{{ $blog->updated_at }}</lastmod>
            <priority>0.4</priority>
        </url>
    @endforeach
    @foreach(\App\Models\Lesson::all() as $lesson)
        <url>
            <loc>{{ route('frontend.lesson', $lesson->code) }}</loc>
            <lastmod>{{ $lesson->updated_at }}</lastmod>
            <priority>0.5</priority>
        </url>
    @endforeach
</urlset>
