<?php
/**
 * Forma CMS Admin Panel
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['forma_user'])) {
    require 'login.php';
    exit;
}

// Get section from URL parameter or default to 'pages'
$section = isset($_GET['section']) ? $_GET['section'] : 'pages';
$subsection = isset($_GET['subsection']) ? $_GET['subsection'] : null;

// Validate section
$valid_sections = ['pages', 'blog', 'podcast', 'uploads', 'snippets', 'settings'];
if (!in_array($section, $valid_sections)) {
    $section = 'pages';
}

// Load admin interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo ucfirst($section); ?></title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/core.css">
    
</head>
<body>
    <!-- Fixed App Bar -->
    <header>
        <nav>
            <div class="button-group">
                <button data-section="pages" <?php echo $section === 'pages' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-file-alt"></i> Pages
                </button>
                <button data-section="blog" <?php echo $section === 'blog' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-blog"></i> Blog
                </button>
                <button data-section="podcast" <?php echo $section === 'podcast' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-podcast"></i> Podcast
                </button>
                <button data-section="uploads" <?php echo $section === 'uploads' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-upload"></i> Uploads
                </button>
                <button data-section="snippets" <?php echo $section === 'snippets' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-code"></i> Snippets
                </button>
                <button data-section="settings" <?php echo $section === 'settings' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-cog"></i> Settings
                </button>
            </div>
            <button id="app-close-btn"><i class="fas fa-xmark"></i></button>
        </nav>
    </header>

    <!-- Main Content Container -->
    <div class="main-container">
        <!-- Content will be loaded here via JavaScript -->
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/mode/markdown/markdown.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/mode/yaml/yaml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/js-yaml/4.1.0/js-yaml.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/theme/monokai.min.css">
    
    <!-- Editor Toolbar Script -->
    <script src="js/editor-toolbar.js"></script>
    
    <script>
        // Current section and subsection
        let currentSection = '<?php echo $section; ?>';
        let currentSubsection = '<?php echo $subsection; ?>';
        
        // Load section content
        function loadSection(section, subsection = null) {
            console.log('Loading section:', section, 'subsection:', subsection);
            const mainContainer = document.querySelector('.main-container');
            mainContainer.innerHTML = '<div class="loading">Loading...</div>';
            
            // Update URL without reloading
            const url = new URL(window.location);
            url.searchParams.set('section', section);
            if (subsection) {
                url.searchParams.set('subsection', subsection);
            } else {
                url.searchParams.delete('subsection');
            }
            window.history.pushState({}, '', url);
            
            // Update active state in navigation
            document.querySelectorAll('nav button').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.section === section);
            });
            
            // Load section content
            fetch(`sections/${section}.php${subsection ? `?subsection=${subsection}` : ''}`)
                .then(response => {
                    console.log(`Section ${section} response:`, response.status);
                    if (!response.ok) {
                        return response.text().then(text => {
                            console.error('Error loading section:', text);
                            throw new Error(`Failed to load section: ${response.status}`);
                        });
                    }
                    return response.text();
                })
                .then(html => {
                    console.log(`Section ${section} HTML loaded, length:`, html.length);
                    mainContainer.innerHTML = html;
                    
                    // Execute any scripts in the loaded content
                    const scripts = mainContainer.getElementsByTagName('script');
                    console.log(`Found ${scripts.length} scripts to execute`);
                    
                    for(let i = 0; i < scripts.length; i++) {
                        const script = scripts[i];
                        console.log(`Executing script #${i+1}`);
                        eval(script.innerHTML);
                    }
                    
                    console.log(`Section ${section} loaded and scripts executed`);
                    initializeSection(section);
                })
                .catch(error => {
                    console.error('Error loading section:', error);
                    mainContainer.innerHTML = `<div class="error">Error loading section: ${error.message}</div>`;
                });
        }

        // Initialize navigation
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handlers to navigation buttons
            document.querySelectorAll('nav button[data-section]').forEach(button => {
                button.addEventListener('click', function() {
                    const section = this.dataset.section;
                    loadSection(section);
                });
            });
            
            // Handle browser back/forward buttons
            window.addEventListener('popstate', function() {
                const url = new URL(window.location);
                const section = url.searchParams.get('section') || 'pages';
                const subsection = url.searchParams.get('subsection');
                loadSection(section, subsection);
            });
            
            // Load initial section
            loadSection(currentSection, currentSubsection);
        });

        // Initialize section-specific functionality
        function initializeSection(section) {
            console.log(`Initializing section: ${section}`);
            
            // First, clear any existing CodeMirror instances that might be orphaned
            document.querySelectorAll('.CodeMirror').forEach(cm => {
                if (!cm.CodeMirror) {
                    console.log('Removing orphaned CodeMirror element');
                    cm.remove();
                }
            });
            
            // Initialize CodeMirror instances
            document.querySelectorAll('.code-editor').forEach(editor => {
                // Check if this editor already has a CodeMirror instance
                if (editor.codemirror) {
                    console.log('Editor already has CodeMirror instance');
                    return;
                }
                
                const mode = editor.dataset.mode || 'xml';
                console.log(`Creating new CodeMirror for ${section} with mode ${mode}`);
                
                const cm = CodeMirror.fromTextArea(editor, {
                    mode: mode,
                    theme: 'monokai',
                    lineNumbers: true,
                    lineWrapping: true,
                    tabSize: 2,
                    indentWithTabs: false,
                    viewportMargin: Infinity
                });
                editor.codemirror = cm;
            });

            // Initialize footer buttons
            const footer = document.querySelector('footer');
            if (footer) {
                const previewBtn = footer.querySelector('#btn-preview');
                const publishBtn = footer.querySelector('#btn-publish');
                const saveBtn = footer.querySelector('#btn-save');
                const deleteBtn = footer.querySelector('#btn-delete');

                // Default state - all disabled
                if (previewBtn) previewBtn.disabled = true;
                if (publishBtn) publishBtn.disabled = true;
                if (saveBtn) saveBtn.disabled = true;
                if (deleteBtn) deleteBtn.disabled = true;

                // Enable/disable buttons based on section
                switch(section) {
                    case 'pages':
                    case 'blog':
                        if (previewBtn) previewBtn.disabled = false;
                        if (publishBtn) publishBtn.disabled = false;
                        if (saveBtn) saveBtn.disabled = false;
                        if (deleteBtn) deleteBtn.disabled = false;
                        break;
                    case 'podcast':
                        if (publishBtn) publishBtn.disabled = false;
                        if (saveBtn) saveBtn.disabled = false;
                        if (deleteBtn) deleteBtn.disabled = false;
                        break;
                    case 'snippets':
                        if (previewBtn) previewBtn.disabled = false;
                        if (saveBtn) saveBtn.disabled = false;
                        if (deleteBtn) deleteBtn.disabled = false;
                        break;
                    case 'uploads':
                        if (deleteBtn) deleteBtn.disabled = false;
                        break;
                    case 'settings':
                        if (saveBtn) saveBtn.disabled = false;
                        break;
                }

                // Add click handlers
                if (previewBtn) {
                    previewBtn.onclick = () => {
                        if (!previewBtn.disabled) {
                            // Handle preview action
                            const content = document.querySelector('.code-editor')?.codemirror?.getValue();
                            if (content) {
                                // Open preview in new window
                                const previewWindow = window.open('', '_blank');
                                previewWindow.document.write(content);
                                previewWindow.document.close();
                            }
                        }
                    };
                }

                if (publishBtn) {
                    publishBtn.onclick = () => {
                        if (!publishBtn.disabled) {
                            // Handle publish action
                            const form = document.querySelector('.editor-form');
                            if (form) {
                                const formData = new FormData(form);
                                formData.append('action', 'publish');
                                // Submit to appropriate endpoint based on section
                                fetch(`api/${section}.php`, {
                                    method: 'POST',
                                    body: formData
                                }).then(response => response.json())
                                  .then(data => {
                                      if (data.success) {
                                          alert('Published successfully!');
                                      } else {
                                          alert('Error publishing: ' + data.error);
                                      }
                                  });
                            }
                        }
                    };
                }

                if (saveBtn) {
                    // Check if this section has its own save handler
                    const hasCustomSaveHandler = section === 'pages' || section === 'blog' || section === 'snippets' || section === 'settings';
                    
                    if (!hasCustomSaveHandler) {
                        saveBtn.onclick = () => {
                            if (!saveBtn.disabled) {
                                // Handle save action
                                const form = document.querySelector('.editor-form');
                                if (form) {
                                    const formData = new FormData();
                                    formData.append('filename', document.getElementById('filename').value);
                                    formData.append('content', document.querySelector('.code-editor').codemirror.getValue());

                                    fetch(`api/${section}.php`, {
                                        method: 'POST',
                                        body: formData
                                    })
                                    .then(response => response.json())
                                    .then(result => {
                                        if (result.success) {
                                            loadSection(section);
                                            alert('Saved successfully!');
                                        } else {
                                            alert('Error saving: ' + result.error);
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error saving:', error);
                                        alert('Error saving');
                                    });
                                }
                            }
                        };
                    } else {
                        console.log(`Section ${section} has custom save handler, skipping global handler`);
                    }
                }

                if (deleteBtn) {
                    // Check if this section has its own delete handler
                    const hasCustomDeleteHandler = section === 'pages' || section === 'blog' || section === 'snippets' || section === 'settings';
                    
                    if (!hasCustomDeleteHandler) {
                        deleteBtn.onclick = () => {
                            if (!deleteBtn.disabled && confirm('Are you sure you want to delete this item?')) {
                                // Handle delete action
                                let filename = document.getElementById('filename').value;
                                
                                // Add extension if not present
                                if (!filename.match(/\.(html|md)$/)) {
                                    filename += '.html'; // Default to HTML
                                }
                                
                                fetch(`api/${section}.php?file=${encodeURIComponent(filename)}`, {
                                    method: 'DELETE'
                                })
                                .then(response => response.json())
                                .then(result => {
                                    if (result.success) {
                                        loadSection(section);
                                    } else {
                                        alert('Error deleting: ' + result.error);
                                    }
                                })
                                .catch(error => {
                                    console.error('Error deleting:', error);
                                    alert('Error deleting');
                                });
                            }
                        };
                    } else {
                        console.log(`Section ${section} has custom delete handler, skipping global handler`);
                    }
                }
            }
        }

        // Set up close button
        document.getElementById('app-close-btn').addEventListener('click', () => {
            window.location.href = 'logout.php';
        });
    </script>
</body>
</html> 