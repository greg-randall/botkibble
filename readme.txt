=== Markdown for Agents ===
Contributors: gregrandall
Tags: markdown, ai, agents, crawlers, api
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.1.2
License: GPL-2.0-only
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Serve published posts and pages as clean Markdown with YAML frontmatter — built for AI agents and crawlers.

== Description ==

Markdown for Agents converts any published post or page on your WordPress site to Markdown. It caches the output and serves it with `text/markdown` headers.

[GitHub Repository](https://github.com/greg-randall/markdown-for-agents)

**Three ways to request Markdown:**

* **`.md` suffix** — append `.md` to any post or page URL (e.g. `example.com/my-post.md`)
* **Query parameter** — add `?format=markdown` to any post or page URL
* **Content negotiation** — send `Accept: text/markdown` in the request header

**What you get:**

* YAML frontmatter with title, date, categories, tags, `word_count`, `char_count`, and `tokens` (estimate)
* Clean Markdown converted from the fully-rendered post HTML
* `Content-Type: text/markdown` response header
* `Content-Length` header for precise payload size
* `Content-Signal` header (`ai-train`, `search`, `ai-input`)
* `<link rel="alternate" type="text/markdown">` tag in `<head>` for discovery
* Static file offloading with automatic invalidation on post update
* Rate limiting for cache-miss regenerations (20 per minute by default)

**What it does NOT do:**

* Expose drafts, private posts, or password-protected content
* Serve non-post/page content types by default
* Require any configuration — activate it and it works

== Why Markdown? ==

HTML is expensive for AI systems to process. [Cloudflare measured](https://blog.cloudflare.com/markdown-for-agents/) an 80% reduction in token usage when converting a blog post from HTML to Markdown (16,180 tokens down to 3,150).

Cloudflare now offers [Markdown for Agents](https://developers.cloudflare.com/fundamentals/reference/markdown-for-agents/) at the CDN edge via the `Accept: text/markdown` header, available on Pro, Business, and Enterprise plans.

This plugin does the same thing at the origin, so it works on any host. It also adds `.md` suffix URLs, `?format=markdown` query parameters, YAML frontmatter, static file caching, and server-level offloading.

If you use Cloudflare, both share the same `Accept: text/markdown` header, `Content-Signal` headers, and `X-Markdown-Tokens` response headers.

== Performance & Static Offloading ==

This plugin supports static file offloading by writing Markdown content to `/wp-content/uploads/mfa-cache/`. 

=== Nginx Configuration ===
To bypass PHP entirely and have Nginx serve the files directly:

`
location ~* ^/(.+)\.md$ {
    default_type text/markdown;
    try_files /wp-content/uploads/mfa-cache/$1.md /index.php?$args;
}
`

=== Apache (.htaccess) ===
Add this to your `.htaccess` before the WordPress rules:

`
RewriteEngine On
RewriteCond %{DOCUMENT_ROOT}/wp-content/uploads/mfa-cache/$1.md -f
RewriteRule ^(.*)\.md$ /wp-content/uploads/mfa-cache/$1.md [L,T=text/markdown]
`

Even without these rules, the plugin uses a "Fast-Path" that serves cached files from PHP before the main database query is executed.

== Installation ==

1. Upload the `markdown-for-agents` directory to `wp-content/plugins/`.
2. Run `composer install` inside the plugin directory to install dependencies.
3. Activate the plugin through the **Plugins** menu in WordPress.
4. That's it. No settings page needed.

**Test it:**

    curl https://gregr.org/great-hvac-meltdown/?format=markdown
    curl https://gregr.org/great-hvac-meltdown.md
    curl -H "Accept: text/markdown" https://gregr.org/great-hvac-meltdown/

== Frequently Asked Questions ==

= How do I add support for custom post types? =

The plugin only serves posts and pages by default. To add a custom post type, use the `markdown_served_post_types` filter in your theme or a custom plugin:

    add_filter( 'markdown_served_post_types', function ( $types ) {
        $types[] = 'docs';
        return $types;
    } );

Be careful — only add post types that contain public content. Do not expose post types that may contain private or sensitive data (e.g. WooCommerce orders).

= How do I add custom fields to the frontmatter? =

Use the `markdown_frontmatter` filter:

    add_filter( 'markdown_frontmatter', function ( $data, $post ) {
        $data['excerpt'] = get_the_excerpt( $post );
        return $data;
    }, 10, 2 );

= How do I change the Content-Signal header? =

Use the `markdown_content_signal` filter:

    add_filter( 'markdown_content_signal', function ( $signal, $post ) {
        return 'ai-train=no, search=yes, ai-input=yes';
    }, 10, 2 );

Return an empty string to omit the header entirely.

= Can I change the token count estimation? =

Yes, use the `markdown_token_multiplier` filter. The default multiplier of `1.3` (word count × 1.3) comes from [Cloudflare's implementation](https://developers.cloudflare.com/fundamentals/reference/markdown-for-agents/):

    add_filter( 'markdown_token_multiplier', function () {
        return 1.5; // Adjusted for a different model's tokenizer
    } );

= How do I adjust the rate limit? =

Cache misses (when a post needs to be converted) are limited to 20 per minute globally. You can change this with the `mfa_regen_rate_limit` filter:

    add_filter( 'mfa_regen_rate_limit', function () {
        return 50; 
    } );

= Can I add custom HTML cleanup rules? =

Yes, use the `markdown_clean_html` filter. This runs after the default cleanup and before conversion:

    add_filter( 'markdown_clean_html', function ( $html ) {
        // Remove a plugin's wrapper divs
        $html = preg_replace( '/<div class="my-plugin-wrapper">(.*?)<\/div>/s', '$1', $html );
        return $html;
    } );

= How do I modify the final Markdown output? =

Use the `markdown_output` filter to append or modify the text after conversion:

    add_filter( 'markdown_output', function ( $markdown, $post ) {
        return $markdown . "\n\n---\nServed by Markdown for Agents";
    }, 10, 2 );

= Can I disable the Accept header detection? =

Yes, if you only want to serve Markdown via explicit URLs (.md or ?format=markdown), use the `markdown_enable_accept_header` filter:

    add_filter( 'markdown_enable_accept_header', '__return_false' );

= Does the .md suffix work with all permalink structures? =

It works with the most common structures (post name, page hierarchy). Complex date-based permalink structures may require the query parameter or Accept header method instead.

= What about password-protected posts? =

They return a `403 Forbidden` response. There's no point serving a password form to a bot.

= What are the response headers? =

* `Content-Type: text/markdown; charset=utf-8`
* `Content-Length: <bytes>` — standard payload size
* `Vary: Accept` — tells caches that responses vary by Accept header
* `X-Markdown-Tokens: <count>` — estimated token count (word_count × 1.3)
* `X-Robots-Tag: noindex` — prevents search engines from indexing the Markdown version
* `Link: <url>; rel="canonical"` — points search engines to the original HTML post
* `Content-Signal: ai-train=yes, search=yes, ai-input=yes` — see [contentsignals.org](https://contentsignals.org/)

== Changelog ==

= 1.1.2 =
* Fixed routing issues for posts by implementing a custom mfa_path resolver.
* Disabled canonical redirects for .md URLs to prevent 301 trailing slash loops.
* Added automatic version-based rewrite rule flushing.

= 1.1.0 =
* Replaced manual YAML encoder with symfony/yaml for security.
* Replaced regex-based shortcode removal with native strip_shortcodes().
* Added token estimation based on 1.3 word count heuristic.
* Replaced transients with static file offloading in /uploads/.
* Added SEO protection with noindex and canonical headers.
* Added "Fast-Path" serving to bypass main DB queries for cached content.
* Added support for direct server offloading (Nginx/Apache).

= 1.0.0 =
* Initial release.
* HTML-to-Markdown conversion via league/html-to-markdown.
* .md suffix, query parameter, and Accept header support.
* YAML frontmatter with title, date, categories, tags.
* Static file caching with automatic invalidation.
* Content-Signal and X-Markdown-Tokens response headers.
* Discovery via alternate link tag.
