# Forma CMS

Forma is a modern, flat-file CMS with a beautiful dark-themed admin interface. Built with PHP, it offers a lightweight yet powerful solution for managing content, blogs, podcasts, and more - all without the need for a database.

![Forma Admin Interface](screenshots/admin.png)

## Features

- 🌙 Beautiful dark-themed admin interface
- 📄 Page management with Markdown/HTML support
- 📝 Blog system with YAML front matter
- 🎙️ Podcast hosting with RSS feed generation
- 🧩 Reusable content snippets with Twig templating
- 📁 File upload and management
- ⚡ Fast, database-free operation
- 🔒 Secure session-based authentication
- 🎨 Customizable themes and layouts
- 🔍 SEO-friendly URLs and metadata
- 📱 Responsive design for all devices

## Requirements

- PHP 7.4 or higher
- Apache/Nginx web server
- `mod_rewrite` enabled (for Apache)
- PHP extensions:
  - json
  - fileinfo
  - session

## Installation

1. **Download or Clone**
   ```bash
   git clone https://github.com/onechrisjones/forma.git
   cd forma
   ```

2. **Set Permissions**
   ```bash
   chmod -R 755 .
   chmod -R 777 content uploads feeds config
   ```

3. **Web Server Configuration**

   For Apache, ensure your `.htaccess` file contains:
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php [QSA,L]
   ```

   For Nginx, add to your server block:
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```

4. **First Login**
   - Navigate to `/admin` in your browser
   - Default credentials:
     - Username: `admin`
     - Password: `password`
   - **Important**: Change these credentials immediately after first login

## Architecture Overview

Forma follows a flat-file architecture with no database requirements. Here's how it works:

### Request Flow

1. **URL Routing**: All requests are routed through `index.php` via `.htaccess` rewrite rules
2. **Route Resolution**: The system determines the content type (page, blog post, podcast episode)
3. **Content Loading**: Files are read from the appropriate directory in `/content`
4. **Template Processing**: Content is processed through Twig templates if available
5. **Shortcode Processing**: Any embedded shortcodes are replaced with snippet content
6. **Output**: Final HTML is served to the browser

### File-Based Storage

- **No Database**: All content is stored as files (Markdown, HTML, JSON)
- **YAML Front Matter**: Metadata is embedded in content files
- **JSON Configuration**: Settings stored in `/config` directory
- **Flat Structure**: Simple, portable file organization

## Directory Structure

```
forma/
├── admin/                  # Admin Panel Interface
│   ├── index.php          # Admin dashboard entry point
│   ├── login.php          # Authentication handler
│   ├── sections/          # Admin panel sections
│   │   ├── pages.php      # Page management interface
│   │   ├── blog.php       # Blog management interface
│   │   ├── snippets.php   # Snippet management interface
│   │   ├── uploads.php    # File upload interface
│   │   └── settings.php   # Settings configuration
│   ├── api/               # RESTful API endpoints
│   │   ├── pages.php      # Pages API (CRUD operations)
│   │   ├── blog.php       # Blog API (CRUD + RSS generation)
│   │   ├── snippets.php   # Snippets API (CRUD + Twig processing)
│   │   ├── uploads.php    # File upload API
│   │   ├── settings.php   # Settings API
│   │   └── podcast.php    # Podcast API
│   └── css/               # Admin interface styles
│       └── core.css       # Main admin stylesheet
├── content/               # Content Storage (File-based)
│   ├── pages/            # Static pages (.html, .md)
│   ├── blog/             # Blog posts (.md with YAML front matter)
│   ├── snippets/         # Reusable content blocks (.html, .twig)
│   │   └── .shortcodes.json  # Shortcode-to-file mapping
│   ├── templates/        # Custom page templates
│   └── podcast.json      # Podcast episodes data
├── uploads/              # User-uploaded media files
├── feeds/                # Generated RSS/XML feeds
│   ├── blog.xml         # Blog RSS feed
│   └── podcast.xml      # Podcast RSS feed
├── config/               # Configuration files
│   ├── config.json      # Main site configuration
│   └── users.json       # User authentication data
├── lib/                  # Core libraries and dependencies
│   ├── Parsedown.php    # Markdown parser
│   └── Twig/            # Twig templating engine
├── index.php            # Front-end router and content processor
├── config.php           # PHP configuration and constants
├── checklist.php        # System diagnostic tool
└── .htaccess           # Apache URL rewriting rules
```

## Core System Files

### Front-End Core

- **`index.php`** - Main router that handles all public requests
  - Processes URL routing for pages, blog, podcast
  - Loads and renders content through Twig templates
  - Handles shortcode processing
  - Manages content caching and optimization

- **`config.php`** - Core configuration and constants
  - Defines directory paths
  - Sets up error handling
  - Initializes core libraries

- **`.htaccess`** - Apache configuration
  - URL rewriting for clean URLs
  - Security headers
  - Compression and caching rules

### Admin Panel

- **`admin/index.php`** - Admin dashboard controller
  - Handles authentication
  - Loads admin panel sections
  - Manages admin navigation
  - Provides unified admin interface

- **`admin/login.php`** - Authentication system
  - Session management
  - Password verification
  - Login/logout handling

### Admin Sections (UI Components)

- **`admin/sections/pages.php`** - Page management interface
  - Visual editor for static pages
  - Metadata editing
  - Live preview functionality

- **`admin/sections/blog.php`** - Blog management interface
  - Markdown editor with front matter
  - Category/tag management
  - Auto-suggestion for titles and slugs

- **`admin/sections/snippets.php`** - Snippet management
  - Code editor for reusable content
  - Shortcode management
  - Twig syntax support

- **`admin/sections/uploads.php`** - File management
  - Drag-and-drop file uploads
  - File browser and management
  - Image/media preview

- **`admin/sections/settings.php`** - Configuration management
  - Site settings configuration
  - Blog and podcast settings
  - User preferences

### API Layer (RESTful Endpoints)

All API endpoints follow RESTful conventions:

- **`admin/api/pages.php`** - Pages CRUD API
  - `GET` - List pages or get specific page
  - `POST` - Create/update page
  - `DELETE` - Remove page
  - Handles META section parsing for page metadata

- **`admin/api/blog.php`** - Blog CRUD API + RSS Generation
  - `GET` - List posts or get specific post
  - `POST` - Create/update blog post with YAML front matter
  - `DELETE` - Remove blog post
  - Automatic RSS feed regeneration
  - Front matter validation and processing

- **`admin/api/snippets.php`** - Snippets CRUD API
  - `GET` - List snippets or get specific snippet
  - `POST` - Create/update snippet with Twig processing
  - `DELETE` - Remove snippet
  - Shortcode mapping management

- **`admin/api/uploads.php`** - File Upload API
  - `POST` - Handle file uploads with validation
  - `GET` - List uploaded files
  - `DELETE` - Remove uploaded files
  - File type validation and security

- **`admin/api/settings.php`** - Configuration API
  - `GET` - Retrieve settings by section
  - `POST` - Update configuration settings
  - JSON configuration file management

- **`admin/api/podcast.php`** - Podcast Management API
  - Podcast episode CRUD operations
  - RSS feed generation for podcasts
  - Episode metadata management

## Content Management

### Pages

Pages are stored in `content/pages/` as HTML or Markdown files.

**Features:**
- Support for both Markdown and HTML
- META section for metadata (title, slug, description)
- Custom templates and layouts
- SEO metadata support
- Automatic slug generation

**File Structure:**
```html
<!--META
title: Page Title
slug: custom-url-slug
description: Page description for SEO
-->
<h1>Page Content</h1>
<p>Your content here...</p>
```

### Blog Posts

Blog posts are stored in `content/blog/` as Markdown files with YAML front matter.

**Features:**
- Markdown-based content creation
- YAML front matter for rich metadata
- Categories and tags support
- Automatic RSS feed generation
- Author attribution
- Excerpt support
- Auto-slug generation from titles

**File Structure:**
```markdown
---
title: "Post Title"
slug: "custom-post-slug"
date: "2024-01-15"
author: "Author Name"
description: "Post description"
categories:
  - Technology
  - Web Development
tags:
  - PHP
  - CMS
---

# Post Content

Your markdown content here...
```

**RSS Feed Generation:**
- Automatic generation on post save/delete
- Configurable number of posts in feed
- Uses excerpt_length setting for auto-generated descriptions
- iTunes-compatible for podcast feeds

### Podcast Management

Podcast episodes are stored in `content/podcast.json` with associated audio files in `uploads/`.

**Features:**
- Episode management with metadata
- Audio file hosting
- iTunes-compatible RSS feed generation
- Show notes in Markdown
- Episode artwork support
- Categories and tags

**Episode Structure:**
```json
{
  "episodes": [
    {
      "id": "episode-001",
      "title": "Episode Title",
      "description": "Episode description",
      "audio_file": "episode-001.mp3",
      "publish_date": "2024-01-15",
      "duration": "45:30",
      "episode_number": 1,
      "season_number": 1,
      "show_notes": "Markdown formatted show notes..."
    }
  ]
}
```

### Snippets & Shortcodes

Snippets are reusable content blocks stored in `content/snippets/`.

**Features:**
- Twig templating support
- Shortcode embedding: `[[shortcode-name]]`
- Dynamic content rendering
- Access to global site configuration
- Custom functions and filters

**Shortcode Mapping:**
The `.shortcodes.json` file maps shortcode names to files:
```json
{
  "contact-form": "contact-form.html",
  "social-media": "social-links.twig"
}
```

**Twig Integration:**
```twig
<!-- In snippet file -->
<div class="site-header">
    <h1>{{ config.general.site_title }}</h1>
    {% if config.general.site_description %}
        <p>{{ config.general.site_description }}</p>
    {% endif %}
</div>
```

## Special Template Pages

Forma uses special template pages for dynamic content sections. These are regular page files with special slug identifiers.

### Required Templates

1. **Blog Archive** (`blog-archive.html`)
   - Slug: `blog-archive-template`
   - Renders `/blog` URL
   - Access to `posts` array

2. **Blog Single** (`blog-single.html`)
   - Slug: `blog-single-template`
   - Renders `/blog/{slug}` URLs
   - Access to `post` object

3. **Podcast Archive** (`podcast-archive.html`)
   - Slug: `podcast-archive-template`
   - Renders `/podcast` URL
   - Access to `episodes` array

4. **Podcast Single** (`podcast-single.html`)
   - Slug: `podcast-single-template`
   - Renders `/podcast/{id}` URLs
   - Access to `episode` object

### Template Variables

Templates have access to these Twig variables:

- **`site`** - Site configuration (title, description, URL)
- **`config`** - Full configuration object
- **`post`** - Current blog post (single templates)
- **`posts`** - Array of blog posts (archive templates)
- **`episode`** - Current podcast episode (single templates)
- **`episodes`** - Array of podcast episodes (archive templates)
- **`podcast`** - Podcast configuration

## Configuration System

### Main Configuration (`config/config.json`)

```json
{
  "general": {
    "site_title": "My Site",
    "site_description": "Site description",
    "site_url": "https://example.com",
    "language": "en",
    "timezone": "UTC"
  },
  "blog": {
    "default_author": "Author Name",
    "excerpt_length": 250,
    "feed_posts": 20
  },
  "podcast": {
    "title": "Podcast Name",
    "description": "Podcast description",
    "author": "Podcast Author",
    "email": "contact@example.com",
    "category": "Technology",
    "language": "en-us"
  }
}
```

### User Authentication (`config/users.json`)

```json
{
  "users": [
    {
      "username": "admin",
      "password": "$2y$10$hashed_password_here",
      "role": "admin"
    }
  ]
}
```

## Security Features

- **Session-based authentication** with secure session handling
- **Password hashing** using PHP's `password_hash()` function
- **File upload validation** with file type and size restrictions
- **XSS protection** through proper output escaping
- **Input sanitization** for all user inputs
- **Secure file handling** preventing directory traversal
- **CSRF protection** through form tokens

## Performance Optimizations

### Recent Performance Improvements

1. **Optimized Blog Directory Scanning**
   - Eliminated triple directory scans (3x faster blog loading)
   - Single scan handles both archive listing and post lookup

2. **Cached File Operations**
   - Reduced excessive `clearstatcache()` calls
   - Batch cache clearing for better performance

3. **Static Caching**
   - Shortcode map caching per request
   - Twig filter registration caching
   - DOM element caching in admin interface

4. **Optimized JavaScript**
   - DOM element caching to reduce repeated queries
   - Event handler optimization
   - Reduced redundant API calls

### File System Optimizations

- **Efficient file reading** with minimal I/O operations
- **Smart caching** of frequently accessed files
- **Optimized directory traversal** algorithms
- **Reduced file system calls** through batching

## Development Guidelines

### Adding New Content Types

1. Create API endpoint in `admin/api/`
2. Add admin section in `admin/sections/`
3. Update routing in `index.php`
4. Add configuration section if needed

### Creating Custom Templates

1. Add template file to `content/pages/`
2. Include META section with appropriate slug
3. Use Twig variables for dynamic content
4. Test with different content scenarios

### Extending the Admin Panel

1. Follow existing section patterns
2. Use consistent API structure (RESTful)
3. Implement proper error handling
4. Add button alerts instead of popups
5. Cache DOM elements for performance

## Troubleshooting

### Common Issues

1. **Permissions Problems**
   - Ensure write access to `content/`, `uploads/`, `feeds/`, `config/`
   - Check file ownership and group permissions

2. **URL Rewriting Issues**
   - Verify `mod_rewrite` is enabled
   - Check `.htaccess` file syntax
   - Ensure `AllowOverride` is set correctly

3. **Performance Issues**
   - Check file permissions (avoid 777 in production)
   - Monitor file system cache efficiency
   - Review large directory listings

### System Diagnostics

Visit `/checklist.php` for comprehensive system checks:
- File permissions verification
- PHP extension requirements
- Server configuration validation
- Directory structure verification
- Write access testing

## API Documentation

### Authentication

All admin API endpoints require session authentication:
```php
session_start();
if (!isset($_SESSION['forma_user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
```

### Response Format

All APIs return JSON responses:
```json
{
  "success": true,
  "data": {...}
}
```

Or for errors:
```json
{
  "success": false,
  "error": "Error message",
  "debug": {...}
}
```

### Error Handling

- Consistent error responses across all APIs
- Proper HTTP status codes
- Debug information in development mode
- Graceful fallbacks for critical operations

## Libraries Used

- **[Twig](https://twig.symfony.com/)** - Template Engine for dynamic content
- **[Parsedown](https://parsedown.org/)** - Markdown Parser for blog posts
- **[Font Awesome](https://fontawesome.com/)** - Icon library for UI
- **[CodeMirror](https://codemirror.net/)** - Code Editor for admin interface

## Contributing

### Development Setup

1. Clone the repository
2. Set up a local web server (Apache/Nginx)
3. Configure permissions as described in installation
4. Enable error reporting for development
5. Use `/checklist.php` to verify setup

### Code Standards

- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Comment complex logic and algorithms
- Maintain consistent API response formats
- Add error handling for all operations

### Pull Request Guidelines

1. Create feature branch from main
2. Test all functionality thoroughly
3. Update documentation if needed
4. Ensure no breaking changes
5. Add performance considerations

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For issues or questions:

1. Check `/checklist.php` for system diagnostics
2. Review this documentation
3. Check existing GitHub issues
4. Create new issue with detailed information

## Credits

Created by [Chris Jones](https://github.com/onechrisjones)

Special thanks to all contributors and the open-source community for their amazing tools and libraries.

---

## Apache Configuration Requirements

This project uses URL rewriting for clean URLs. For proper operation:

1. **Apache mod_rewrite module must be enabled**
   - In MAMP, edit `/Applications/MAMP/conf/apache/httpd.conf`
   - Uncomment: `LoadModule rewrite_module modules/mod_rewrite.so`
   - Restart MAMP after changes

2. **The .htaccess file includes:**
   - URL rewriting rules directing requests to index.php
   - UTF-8 character encoding settings
   - Performance optimizations (compression, caching)
   - Security configurations and headers

### Troubleshooting Apache Issues

If you encounter "Internal Server Error":
1. Verify mod_rewrite is enabled
2. Check .htaccess file syntax
3. Ensure AllowOverride All is set
4. Review Apache error logs for specifics 