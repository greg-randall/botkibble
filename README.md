# Markdown for Agents

[![WordPress Version](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/download/)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-8892bf.svg)](https://www.php.net/downloads)
[![License](https://img.shields.io/badge/License-GPL--2.0-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

**Markdown for Agents** is a hardened WordPress plugin that serves your published posts and pages as clean Markdown with YAML frontmatter. It is optimized for AI agents, LLMs, and high-performance crawlers.

## Why Markdown?

HTML is rich but "noisy" for AI systems. Converting a blog post from HTML to Markdown can result in an **80% reduction in token usage** ([Cloudflare data](https://blog.cloudflare.com/markdown-for-agents/)), making it faster and significantly cheaper for AI agents to process your content.

This plugin implements origin-level Markdown serving, similar to Cloudflare's edge implementation, but with added benefits like physical file caching, YAML frontmatter, and custom filters.

## Key Features

- **Triple-Method Access:**
  - **.md suffix** (e.g., `example.com/blog-post.md`)
  - **Query parameter** (e.g., `example.com/blog-post/?format=markdown`)
  - **Content Negotiation** (e.g., `Accept: text/markdown` header)
- **Rich YAML Frontmatter:** Includes title, date, categories, tags, `word_count`, `char_count`, and an estimated `tokens` count.
- **High-Performance Caching:** 
  - **Fast-Path Serving:** Bypasses the main WordPress query and template redirect for cached content.
  - **Static Offloading:** Caches Markdown as physical files in `wp-content/uploads/mfa-cache/`.
- **SEO & Security:**
  - Sends `X-Robots-Tag: noindex` to prevent Markdown versions from appearing in search results.
  - Sends `Link: <url>; rel="canonical"` to point search engines back to the HTML version.
  - Rate limits cache-miss regenerations to mitigate DOS attacks.
  - Blocks access to drafts, private posts, and password-protected content.

## Installation

1. Upload the `markdown-for-agents` directory to your `wp-content/plugins/` directory.
2. Run `composer install` inside the plugin directory to install dependencies (`league/html-to-markdown` and `symfony/yaml`).
3. Activate the plugin through the **Plugins** menu in WordPress.
4. (Optional) Configure Nginx or Apache to serve the static cache files directly (see Performance section).

## Performance & Static Serving

For maximum performance, you can configure your web server to serve the Markdown files directly from the cache directory, bypassing PHP entirely.

### Nginx Configuration
```nginx
location ~* ^/(.+)\.md$ {
    default_type text/markdown;
    try_files /wp-content/uploads/mfa-cache/$1.md /index.php?$args;
}
```

### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{DOCUMENT_ROOT}/wp-content/uploads/mfa-cache/$1.md -f
RewriteRule ^(.*)\.md$ /wp-content/uploads/mfa-cache/$1.md [L,T=text/markdown]
```

## Developer Hooks (Customization)

The plugin is highly extensible via WordPress filters:

| Filter | Purpose |
| :--- | :--- |
| `markdown_served_post_types` | Add custom post types (e.g., `docs`, `product`). |
| `markdown_frontmatter` | Add or remove fields in the YAML block. |
| `markdown_clean_html` | Clean up HTML (remove specific divs/styles) before conversion. |
| `markdown_output` | Modify the final Markdown string before it's cached/served. |
| `markdown_token_multiplier` | Adjust the word-to-token estimation (default `1.3`). |
| `mfa_regen_rate_limit` | Change the global regeneration rate limit (default `20/min`). |
| `markdown_content_signal` | Customize the `Content-Signal` header. |
| `markdown_enable_accept_header` | Toggle `Accept: text/markdown` detection. |

### Example: Adding Custom Post Types
```php
add_filter( 'markdown_served_post_types', function ( $types ) {
    $types[] = 'knowledge_base';
    return $types;
} );
```

## Requirements

- **PHP:** 8.0+
- **WordPress:** 6.0+
- **Dependencies:** Managed via Composer (`league/html-to-markdown`, `symfony/yaml`).

## License

This project is licensed under the GPL-2.0 License.
