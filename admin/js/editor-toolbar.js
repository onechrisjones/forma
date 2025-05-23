/**
 * Editor Toolbar for CodeMirror
 * 
 * This script adds a floating toolbar to CodeMirror editors with
 * dropdown menus for inserting links and shortcodes.
 */

(function() {
    // Add hidden class style
    const style = document.createElement('style');
    style.textContent = `
        .editor-toolbar .dropdown.hidden {
            display: none !important;
        }
    `;
    document.head.appendChild(style);
    
    // Track initialized editors to prevent duplicate toolbars
    const initializedEditors = new WeakSet();
    
    // Initialize the toolbar when the document is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('CodeMirror Toolbar: DOM content loaded');
        // Wait a bit for sections to load
        setTimeout(initializeToolbar, 500);
    });
    
    // Track when new editors are created
    if (typeof window.CodeMirror !== 'undefined') {
        console.log('CodeMirror Toolbar: CodeMirror found, overriding fromTextArea');
        const originalFromTextArea = CodeMirror.fromTextArea;
        CodeMirror.fromTextArea = function(textarea, options) {
            console.log('CodeMirror Toolbar: Creating new editor from textarea', textarea);
            const cm = originalFromTextArea.call(this, textarea, options);
            
            // Set a timeout to make sure the editor is fully initialized
            setTimeout(() => {
                // Create a toolbar for this specific editor
                createToolbarForEditor(cm);
            }, 200);
            
            return cm;
        };
    } else {
        console.warn('CodeMirror Toolbar: CodeMirror not found on window object');
    }
    
    /**
     * Initialize the toolbar for each editor
     */
    function initializeToolbar() {
        console.log('Initializing editor toolbars...');
        
        // First, remove any duplicate toolbars
        document.querySelectorAll('.editor-toolbar').forEach(toolbar => {
            // Check if this toolbar is a duplicate (more than one in the same parent)
            const parent = toolbar.parentElement;
            const toolbarsInParent = parent.querySelectorAll('.editor-toolbar');
            if (toolbarsInParent.length > 1 && toolbar !== toolbarsInParent[0]) {
                console.log('CodeMirror Toolbar: Removing duplicate toolbar');
                toolbar.remove();
            }
        });
        
        // Find all CodeMirror instances in the document
        const cmElements = document.querySelectorAll('.CodeMirror');
        console.log('CodeMirror Toolbar: Found', cmElements.length, 'CodeMirror instances');
        
        cmElements.forEach(cmElement => {
            const cm = cmElement.CodeMirror;
            if (cm && !initializedEditors.has(cm) && !cmElement.querySelector('.editor-toolbar')) {
                console.log('CodeMirror Toolbar: Creating toolbar for existing editor');
                createToolbarForEditor(cm);
            } else if (!cm) {
                console.warn('CodeMirror Toolbar: CodeMirror instance not found on element', cmElement);
            } else {
                console.log('CodeMirror Toolbar: Toolbar already exists for this editor');
            }
        });
    }
    
    /**
     * Create toolbar for a specific editor
     */
    function createToolbarForEditor(cm) {
        if (!cm) {
            console.error('CodeMirror Toolbar: Cannot create toolbar - no editor instance provided');
            return;
        }
        
        // Skip if we've already initialized this editor
        if (initializedEditors.has(cm)) {
            console.log('CodeMirror Toolbar: Editor already has a toolbar (tracked in WeakSet)');
            return;
        }
        
        // Find the CodeMirror wrapper element
        const cmElement = cm.getWrapperElement();
        console.log('CodeMirror Toolbar: Creating toolbar for', cmElement);
        
        // Remove any existing static toolbar that might be in the parent container
        // This is to fix the issue with toolbars hardcoded in the HTML of some sections
        const editorContainer = cmElement.closest('.editor-container, .form-group');
        if (editorContainer) {
            const staticToolbars = editorContainer.querySelectorAll('.editor-toolbar:not(.dynamic-toolbar)');
            staticToolbars.forEach(toolbar => {
                console.log('CodeMirror Toolbar: Removing static toolbar found in the HTML', toolbar);
                toolbar.remove();
            });
        }
        
        // Check if a toolbar already exists for this editor
        if (cmElement.querySelector('.editor-toolbar')) {
            console.log('Toolbar already exists for this editor');
            initializedEditors.add(cm); // Mark as initialized
            return;
        }
        
        console.log('Creating toolbar for editor');
        
        // Create toolbar container
        const toolbar = document.createElement('div');
        toolbar.className = 'editor-toolbar dynamic-toolbar'; // Add dynamic-toolbar class to distinguish
        
        // Add dropdowns
        toolbar.innerHTML = `
            <div class="dropdown">
                <button type="button" class="toolbar-btn" data-dropdown="pages">
                    <i class="fas fa-file-alt"></i> Pages
                </button>
            </div>
            <div class="dropdown">
                <button type="button" class="toolbar-btn" data-dropdown="blog">
                    <i class="fas fa-blog"></i> Blog
                </button>
            </div>
            <div class="dropdown">
                <button type="button" class="toolbar-btn" data-dropdown="uploads">
                    <i class="fas fa-upload"></i> Uploads
                </button>
            </div>
            <div class="dropdown${isSnippetsSection() ? ' hidden' : ''}">
                <button type="button" class="toolbar-btn" data-dropdown="snippets">
                    <i class="fas fa-code"></i> Snippets
                </button>
            </div>
        `;
        
        // Append toolbar to the CodeMirror wrapper element
        cmElement.appendChild(toolbar);
        
        // Position the toolbar in the top-right of the CodeMirror instance
        toolbar.style.position = 'absolute';
        toolbar.style.top = '5px';
        toolbar.style.right = '5px';
        toolbar.style.zIndex = '10';
        
        // Setup dropdown functionality
        setupToolbarDropdowns(toolbar, cm);
        
        // Load data for dropdowns
        loadPagesData(toolbar, cm);
        loadBlogData(toolbar, cm);
        loadUploadsData(toolbar, cm);
        loadSnippetsData(toolbar, cm);
        
        // Mark this editor as initialized
        initializedEditors.add(cm);
    }
    
    /**
     * Check if we're currently in the snippets section
     */
    function isSnippetsSection() {
        // Check if we're in the snippets section by looking at the URL or section content
        return window.location.href.includes('/admin/?section=snippets') || 
               document.querySelector('.section-container #snippet-form') !== null;
    }
    
    /**
     * Setup toolbar dropdown functionality
     */
    function setupToolbarDropdowns(toolbar, cm) {
        const dropdowns = toolbar.querySelectorAll('.dropdown');
        
        dropdowns.forEach(dropdown => {
            const btn = dropdown.querySelector('.toolbar-btn');
            
            // Create dropdown content if it doesn't exist
            if (!dropdown.querySelector('.dropdown-content')) {
                const content = document.createElement('div');
                content.className = 'dropdown-content';
                content.innerHTML = '<div class="dropdown-header">Loading...</div>';
                dropdown.appendChild(content);
            }
            
            // Toggle dropdown on button click
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                // Close all other dropdowns
                dropdowns.forEach(d => {
                    if (d !== dropdown) {
                        d.classList.remove('active');
                    }
                });
                
                // Toggle this dropdown
                dropdown.classList.toggle('active');
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown')) {
                dropdowns.forEach(d => d.classList.remove('active'));
            }
        });
    }
    
    /**
     * Load pages data for the Pages dropdown
     */
    function loadPagesData(toolbar, cm) {
        fetch('/admin/api/pages.php')
            .then(response => response.json())
            .then(data => {
                const files = data.files || [];
                if (!files.length) return;
                
                // Get all Pages dropdowns in this toolbar
                const pagesDropdowns = toolbar.querySelectorAll('[data-dropdown="pages"]');
                
                // Get page details including slugs
                const pagePromises = files.map(file => {
                    return fetch('/admin/api/pages.php?file=' + encodeURIComponent(file))
                        .then(response => response.json())
                        .catch(() => ({
                            filename: file.replace(/\.(html|md)$/, ''),
                            meta: {}
                        }));
                });
                
                Promise.all(pagePromises)
                    .then(pages => {
                        pagesDropdowns.forEach(btn => {
                            const content = btn.closest('.dropdown').querySelector('.dropdown-content');
                            
                            // Create content HTML
                            let html = '<div class="dropdown-header">Pages</div>';
                            
                            pages.forEach(page => {
                                if (page.filename === '.DS_Store') return;
                                const name = page.filename;
                                // Use slug if available, otherwise use the filename
                                const path = page.meta && page.meta.slug ? '/' + page.meta.slug : '/' + name;
                                
                                html += `<div class="dropdown-item" data-action="insert-link" data-type="page" data-path="${path}" data-name="${name}">
                                    <i class="fas fa-file-alt"></i> ${name}${page.meta && page.meta.slug ? ' (' + page.meta.slug + ')' : ''}
                                </div>`;
                            });
                            
                            content.innerHTML = html;
                            
                            // Add click handlers for items
                            content.querySelectorAll('[data-action="insert-link"]').forEach(item => {
                                item.addEventListener('click', () => {
                                    const path = item.dataset.path;
                                    
                                    // Insert plain link
                                    insertAtCursor(path, cm);
                                    
                                    // Close the dropdown
                                    btn.closest('.dropdown').classList.remove('active');
                                });
                            });
                        });
                    });
            })
            .catch(error => {
                console.error('Error loading pages:', error);
                updateDropdownError('pages', toolbar);
            });
    }
    
    /**
     * Load blog data for the Blog dropdown
     */
    function loadBlogData(toolbar, cm) {
        fetch('/admin/api/blog.php')
            .then(response => response.json())
            .then(data => {
                const files = data.files || [];
                if (!files.length) return;
                
                // Get all Blog dropdowns in this toolbar
                const blogDropdowns = toolbar.querySelectorAll('[data-dropdown="blog"]');
                
                // Get blog post details including slugs
                const postPromises = files.map(file => {
                    return fetch('/admin/api/blog.php?file=' + encodeURIComponent(file))
                        .then(response => response.json())
                        .catch(() => ({
                            filename: file.replace(/\.md$/, ''),
                            frontMatter: {}
                        }));
                });
                
                Promise.all(postPromises)
                    .then(posts => {
                        blogDropdowns.forEach(btn => {
                            const content = btn.closest('.dropdown').querySelector('.dropdown-content');
                            
                            // Create content HTML
                            let html = '<div class="dropdown-header">Blog Posts</div>';
                            
                            posts.forEach(post => {
                                if (post.filename === '.DS_Store') return;
                                const name = post.filename;
                                // Use slug if available, otherwise use the filename
                                const slug = post.frontMatter.slug || name;
                                const path = `/blog/${slug}`;
                                
                                html += `<div class="dropdown-item" data-action="insert-link" data-type="blog" data-path="${path}" data-name="${name}">
                                    <i class="fas fa-blog"></i> ${post.frontMatter.title || name}${post.frontMatter.slug ? ' (' + post.frontMatter.slug + ')' : ''}
                                </div>`;
                            });
                            
                            content.innerHTML = html;
                            
                            // Add click handlers for items
                            content.querySelectorAll('[data-action="insert-link"]').forEach(item => {
                                item.addEventListener('click', () => {
                                    const path = item.dataset.path;
                                    
                                    // Insert plain link
                                    insertAtCursor(path, cm);
                                    
                                    // Close the dropdown
                                    btn.closest('.dropdown').classList.remove('active');
                                });
                            });
                        });
                    });
            })
            .catch(error => {
                console.error('Error loading blog posts:', error);
                updateDropdownError('blog', toolbar);
            });
    }
    
    /**
     * Load uploads data for the Uploads dropdown
     */
    function loadUploadsData(toolbar, cm) {
        fetch('/admin/api/uploads.php')
            .then(response => response.json())
            .then(files => {
                if (!files.length) return;
                
                // Group files by type
                const images = files.filter(f => /\.(jpe?g|png|gif|svg|webp)$/i.test(f));
                const audio = files.filter(f => /\.(mp3|wav|ogg|m4a)$/i.test(f));
                const video = files.filter(f => /\.(mp4|webm|mov)$/i.test(f));
                const docs = files.filter(f => /\.(pdf|doc|docx|xls|xlsx|ppt|pptx|txt)$/i.test(f));
                const other = files.filter(f => 
                    !images.includes(f) && 
                    !audio.includes(f) && 
                    !video.includes(f) && 
                    !docs.includes(f) &&
                    f !== '.DS_Store'
                );
                
                // Get all Uploads dropdowns in this toolbar
                const uploadsDropdowns = toolbar.querySelectorAll('[data-dropdown="uploads"]');
                
                uploadsDropdowns.forEach(btn => {
                    const content = btn.closest('.dropdown').querySelector('.dropdown-content');
                    
                    // Create content HTML
                    let html = '<div class="dropdown-header">Media Files</div>';
                    
                    if (images.length) {
                        html += '<div class="dropdown-divider"></div>';
                        html += '<div class="dropdown-item dropdown-subheader">Images</div>';
                        
                        images.forEach(file => {
                            html += `<div class="dropdown-item" data-action="insert-media" data-type="image" data-path="/uploads/${file}" data-name="${file}">
                                <i class="fas fa-image"></i> ${file}
                            </div>`;
                        });
                    }
                    
                    if (audio.length) {
                        html += '<div class="dropdown-divider"></div>';
                        html += '<div class="dropdown-item dropdown-subheader">Audio</div>';
                        
                        audio.forEach(file => {
                            html += `<div class="dropdown-item" data-action="insert-media" data-type="audio" data-path="/uploads/${file}" data-name="${file}">
                                <i class="fas fa-music"></i> ${file}
                            </div>`;
                        });
                    }
                    
                    if (video.length) {
                        html += '<div class="dropdown-divider"></div>';
                        html += '<div class="dropdown-item dropdown-subheader">Video</div>';
                        
                        video.forEach(file => {
                            html += `<div class="dropdown-item" data-action="insert-media" data-type="video" data-path="/uploads/${file}" data-name="${file}">
                                <i class="fas fa-video"></i> ${file}
                            </div>`;
                        });
                    }
                    
                    if (docs.length) {
                        html += '<div class="dropdown-divider"></div>';
                        html += '<div class="dropdown-item dropdown-subheader">Documents</div>';
                        
                        docs.forEach(file => {
                            html += `<div class="dropdown-item" data-action="insert-media" data-type="document" data-path="/uploads/${file}" data-name="${file}">
                                <i class="fas fa-file"></i> ${file}
                            </div>`;
                        });
                    }
                    
                    if (other.length) {
                        html += '<div class="dropdown-divider"></div>';
                        html += '<div class="dropdown-item dropdown-subheader">Other Files</div>';
                        
                        other.forEach(file => {
                            html += `<div class="dropdown-item" data-action="insert-media" data-type="file" data-path="/uploads/${file}" data-name="${file}">
                                <i class="fas fa-file-alt"></i> ${file}
                            </div>`;
                        });
                    }
                    
                    content.innerHTML = html;
                    
                    // Add click handlers for items
                    content.querySelectorAll('[data-action="insert-media"]').forEach(item => {
                        item.addEventListener('click', () => {
                            const path = item.dataset.path;
                            
                            // Insert plain path for all media types
                            insertAtCursor(path, cm);
                            
                            // Close the dropdown
                            btn.closest('.dropdown').classList.remove('active');
                        });
                    });
                });
            })
            .catch(error => {
                console.error('Error loading uploads:', error);
                updateDropdownError('uploads', toolbar);
            });
    }
    
    /**
     * Load snippets data for the Snippets dropdown
     */
    function loadSnippetsData(toolbar, cm) {
        console.log('CodeMirror Toolbar: Loading snippets data');
        
        fetch('/admin/api/snippets.php')
            .then(response => {
                console.log('CodeMirror Toolbar: Snippets API response status:', response.status);
                if (!response.ok) {
                    throw new Error('Snippets API returned ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('CodeMirror Toolbar: Snippets data received:', data);
                const files = data.files || data; // Handle both array directly or {files: [...]} format
                
                if (!files || !Array.isArray(files) || !files.length) {
                    console.log('CodeMirror Toolbar: No snippet files found');
                    updateDropdownError('snippets', toolbar, 'No snippets found');
                    return;
                }
                
                console.log('CodeMirror Toolbar: Processing', files.length, 'snippet files');
                
                // Get all Snippets dropdowns in this toolbar
                const snippetsDropdowns = toolbar.querySelectorAll('[data-dropdown="snippets"]');
                console.log('CodeMirror Toolbar: Found', snippetsDropdowns.length, 'snippets dropdowns');
                
                // Load the shortcodes map to get the correct shortcode names
                fetch('/admin/api/snippets.php?get_shortcodes=1')
                    .then(response => response.json())
                    .catch(() => ({})) // Default to empty object if fails
                    .then(shortcodesMap => {
                        // Reverse the shortcodes map to look up by filename
                        const filenameToShortcode = {};
                        for (const shortcode in shortcodesMap) {
                            filenameToShortcode[shortcodesMap[shortcode]] = shortcode;
                        }
                        
                        snippetsDropdowns.forEach(btn => {
                            const content = btn.closest('.dropdown').querySelector('.dropdown-content');
                            
                            // Create content HTML
                            let html = '<div class="dropdown-header">Snippets</div>';
                            
                            files.forEach(file => {
                                if (file === '.DS_Store') return;
                                const name = file.replace(/\.(html|twig)$/, '');
                                // Use the shortcode if available, otherwise use the filename
                                const shortcode = filenameToShortcode[file] || name;
                                
                                html += `<div class="dropdown-item" data-action="insert-snippet" data-name="${name}" data-shortcode="${shortcode}">
                                    <i class="fas fa-code"></i> ${name}
                                </div>`;
                            });
                            
                            content.innerHTML = html;
                            console.log('CodeMirror Toolbar: Updated snippets dropdown content');
                            
                            // Add click handlers for items
                            content.querySelectorAll('[data-action="insert-snippet"]').forEach(item => {
                                item.addEventListener('click', () => {
                                    const shortcode = item.dataset.shortcode;
                                    console.log('CodeMirror Toolbar: Inserting snippet:', shortcode);
                                    
                                    // Create a shortcode with double square brackets
                                    const insertText = `[[${shortcode}]]`;
                                    
                                    // Insert the shortcode
                                    insertAtCursor(insertText, cm);
                                    
                                    // Close the dropdown
                                    btn.closest('.dropdown').classList.remove('active');
                                });
                            });
                        });
                    });
            })
            .catch(error => {
                console.error('CodeMirror Toolbar: Error loading snippets:', error);
                updateDropdownError('snippets', toolbar, 'Failed to load');
            });
    }
    
    /**
     * Update dropdown content with error message
     */
    function updateDropdownError(type, toolbar, message = 'Failed to load') {
        const dropdowns = toolbar.querySelectorAll(`[data-dropdown="${type}"]`);
        
        dropdowns.forEach(btn => {
            const content = btn.closest('.dropdown').querySelector('.dropdown-content');
            content.innerHTML = `
                <div class="dropdown-header">Error</div>
                <div class="dropdown-item">${message} ${type}</div>
            `;
        });
    }
    
    /**
     * Insert text at cursor position in the specified CodeMirror editor
     */
    function insertAtCursor(text, cm) {
        if (cm) {
            // Insert at cursor position
            cm.replaceSelection(text);
            // Focus the editor
            cm.focus();
        } else {
            console.error('No CodeMirror instance provided for insertion');
        }
    }
})(); 