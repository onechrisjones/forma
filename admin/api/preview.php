<?php
/**
 * Preview API endpoint
 * Renders a blog post preview with the provided content and front matter
 */

require_once __DIR__ . '/../../lib/Parsedown.php';

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Get the content and front matter from the request
$content = $_POST['content'] ?? '';
$frontMatter = json_decode($_POST['frontMatter'], true) ?? [];

// Create the preview HTML
$parsedown = new Parsedown();
$html = $parsedown->text($content);

// Load the blog template if it exists
$template = '';
if (file_exists(__DIR__ . '/../../content/templates/blog.html')) {
    $template = file_get_contents(__DIR__ . '/../../content/templates/blog.html');
} else {
    // Default template
    $template = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{title}}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            color: #333;
        }
        .post-meta {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 2rem;
        }
        .post-content img {
            max-width: 100%;
            height: auto;
        }
        .post-tags {
            margin-top: 2rem;
            font-size: 0.9em;
        }
        .post-tags span {
            display: inline-block;
            background: #f0f0f0;
            padding: 0.2rem 0.5rem;
            margin: 0.2rem;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <article class="post">
        <h1>{{title}}</h1>
        <div class="post-meta">
            Posted on {{date}} by {{author}}
            {{#categories}}
            in {{categories}}
            {{/categories}}
        </div>
        <div class="post-content">
            {{content}}
        </div>
        {{#tags}}
        <div class="post-tags">
            Tags: {{tags}}
        </div>
        {{/tags}}
    </article>
</body>
</html>';
}

// Replace template variables
$replacements = [
    '{{title}}' => htmlspecialchars($frontMatter['title'] ?? 'Preview'),
    '{{date}}' => date('F j, Y', strtotime($frontMatter['date'] ?? 'now')),
    '{{author}}' => htmlspecialchars($frontMatter['author'] ?? 'Anonymous'),
    '{{content}}' => $html,
    '{{categories}}' => implode(', ', array_map('htmlspecialchars', $frontMatter['categories'] ?? [])),
    '{{tags}}' => implode(', ', array_map('htmlspecialchars', $frontMatter['tags'] ?? [])),
];

// Handle conditional sections
foreach (['categories', 'tags'] as $section) {
    if (empty($frontMatter[$section])) {
        $template = preg_replace("/\{\{#$section\}\}.*?\{\{\/$section\}\}/s", '', $template);
    } else {
        $template = str_replace("{{#$section}}", '', $template);
        $template = str_replace("{{/$section}}", '', $template);
    }
}

$html = str_replace(array_keys($replacements), array_values($replacements), $template);

// Output the preview
echo $html; 