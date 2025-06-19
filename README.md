# Forma CMS

A lightweight flat-file content management system built with PHP. Forma CMS provides a simple and intuitive admin interface for managing your website content without the need for a database.

## Features

- **Flat-file architecture**: No database required, all content is stored in files
- **Markdown support**: Write content using Markdown for easy formatting
- **Twig templates**: Flexible templating with Twig
- **Blog system**: Built-in blog functionality with categories and tags
- **Podcast support**: Integrated podcast hosting with RSS feeds
- **Mobile-friendly admin**: Responsive admin interface works on all devices
- **Page caching**: Optional page caching for improved performance

## Caching System

Forma CMS includes a flexible caching system to improve site performance:

### How It Works

1. **Page Caching**: Rendered HTML pages are cached as static files
2. **Cache Duration**: Set how long pages should be cached before refreshing
3. **Path Exclusions**: Specify which pages should never be cached
4. **Admin Controls**: Clear or rebuild the cache from the admin interface

### Cache Settings

Access cache settings in the admin panel under "Settings > Cache":

- **Enable Page Caching**: Toggle caching on/off
- **Cache Duration**: How many seconds before cache expires (e.g., 3600 = 1 hour)
- **Excluded Paths**: Paths that should never be cached (e.g., /contact-form)

### Cache Management

The admin panel provides tools to manage the cache:

- **Clear Cache**: Remove all cached files
- **Rebuild Cache**: Clear and regenerate cache for common pages
- **Cache Status**: View cache size and page count

## Installation

1. Upload the files to your web server
2. Make sure the following directories are writable by the web server:
   - /content
   - /cache
   - /uploads
   - /config
3. Visit your site's URL to start using Forma CMS

## Credits

- **Twig**: Template Engine
- **Parsedown**: Markdown Parser
- **Font Awesome**: Icons
- **CodeMirror**: Code Editor

---

Created by Chris Jones [@onechrisjones](https://github.com/onechrisjones) 