<!-- Blog Section -->
<div class="section-container">
    <!-- File List -->
    <div class="file-list">
        <div class="file-item new-file">
            <i class="fas fa-plus"></i> Add New Post
        </div>
        <div class="file-list-content">
            <!-- Files will be loaded here -->
        </div>
    </div>

    <!-- Editor -->
    <div class="editor-container">
        <form id="blog-form" class="editor-form">
            <div class="form-group">
                <label for="filename">Filename</label>
                <input type="text" id="filename" name="filename" required>
                <span class="hint">.md extension will be added automatically</span>
            </div>

            <!-- Front Matter - Hidden Field -->
            <input type="hidden" id="frontMatter" name="frontMatter" value="">

            <!-- Front Matter Display (just for UI) -->
            <div class="front-matter">
                <hr>
                <div class="front-matter-header">
                    <h4>Front Matter</h4>
                    <button type="button" id="toggleFrontMatter" class="toggle-btn">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                </div>
                <div class="front-matter-content">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title">
                    </div>
                    <div class="form-group">
                        <label for="slug">Slug</label>
                        <input type="text" id="slug" name="slug">
                        <span class="hint">Used in URLs: /blog/your-slug (leave blank to auto-generate from title)</span>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date">Date</label>
                            <input type="date" id="date" name="date">
                        </div>
                        <div class="form-group">
                            <label for="author">Author</label>
                            <input type="text" id="author" name="author">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="2"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="categories">Categories</label>
                            <input type="text" id="categories" name="categories" placeholder="Comma separated">
                        </div>
                        <div class="form-group">
                            <label for="tags">Tags</label>
                            <input type="text" id="tags" name="tags" placeholder="Comma separated">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Editor -->
            <div class="form-group content-editor">
                <div class="editor-toolbar">
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
                    <div class="dropdown">
                        <button type="button" class="toolbar-btn" data-dropdown="snippets">
                            <i class="fas fa-code"></i> Snippets
                        </button>
                    </div>
                </div>
                <textarea id="content" name="content" class="code-editor" data-mode="markdown"></textarea>
            </div>
        </form>

        <!-- Footer -->
        <footer>
            <div class="buttons">
                <div class="button-group">
                    <button type="button" class="standard-btn" id="btn-save"><i class="small fas fa-save"></i> Save</button>
                    <button type="button" class="delete-btn" id="forma-delete-btn"><i class="small fas fa-trash"></i> Delete</button>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Add CSS for the collapsible front matter -->
<style>
/* Blog-specific collapsible styles that can remain */
.front-matter-collapsed .front-matter-content {
    max-height: 0;
    opacity: 0;
}
</style>

<script>
// DOM element cache for performance
const domCache = {
    title: document.getElementById('title'),
    slug: document.getElementById('slug'),
    date: document.getElementById('date'),
    author: document.getElementById('author'),
    description: document.getElementById('description'),
    categories: document.getElementById('categories'),
    tags: document.getElementById('tags'),
    filename: document.getElementById('filename'),
    frontMatter: document.getElementById('frontMatter'),
    blogForm: document.getElementById('blog-form'),
    saveBtn: document.getElementById('btn-save'),
    deleteBtn: document.getElementById('forma-delete-btn')
};

// Helper function to get cached element or query if not cached
function getElement(id) {
    return domCache[id] || document.getElementById(id);
}

// Define updateFrontMatter at the top to ensure it's available
function updateFrontMatter() {
    console.log('Updating front matter...');
    
    try {
        const frontMatter = {
            title: domCache.title.value.trim(),
            slug: domCache.slug.value.trim(),
            date: domCache.date.value,
            author: domCache.author.value.trim(),
            description: domCache.description.value.trim(),
            categories: domCache.categories.value.split(',').map(function(s) { return s.trim(); }).filter(function(s) { return Boolean(s); }),
            tags: domCache.tags.value.split(',').map(function(s) { return s.trim(); }).filter(function(s) { return Boolean(s); })
        };

        domCache.frontMatter.value = JSON.stringify(frontMatter);
        console.log('Front matter updated successfully');
    } catch (error) {
        console.error('Error updating front matter:', error);
    }
}

// Function to generate a slug from a title
function generateSlugFromTitle(title) {
    if (!title) return '';
    
    // Convert to lowercase, replace spaces with hyphens
    let slug = title.toLowerCase()
                    .replace(/[^\w\s-]/g, '') // Remove special characters
                    .replace(/\s+/g, '-')     // Replace spaces with hyphens
                    .replace(/-+/g, '-');     // Remove consecutive hyphens
    
    return slug;
}

// Function to generate a readable title from a filename
function generateTitleFromFilename(filename) {
    if (!filename) return '';
    
    // Remove .md extension if present
    let title = filename.replace(/\.md$/, '');
    
    // Replace hyphens and underscores with spaces
    title = title.replace(/[-_]/g, ' ');
    
    // Capitalize first letter of each word
    title = title.replace(/\b\w/g, l => l.toUpperCase());
    
    return title;
}

// Functions
function loadBlogList() {
    console.log('Loading blog posts...');
    fetch('/admin/api/blog.php')
        .then(function(response) {
            console.log('Response status:', response.status);
            if (!response.ok) {
                return response.text().then(function(text) {
                    console.error('Error response:', text);
                    throw new Error('HTTP error! status: ' + response.status + ', body: ' + text);
                });
            }
            return response.text().then(function(text) {
                console.log('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Failed to parse JSON response: ' + e.message + ', raw text: ' + text);
                }
            });
        })
        .then(function(data) {
            console.log('Data received:', data);
            const container = document.querySelector('.file-list-content');
            
            const files = data.files || [];
            console.log('Files to display:', files);
            
            if (files && files.length > 0) {
                container.innerHTML = files.map(function(file) {
                    return '<div class="file-item" data-file="' + file + '">' +
                        '<i class="fas fa-file-alt"></i> ' + file +
                        '</div>';
                }).join('');

                console.log('File list HTML updated, adding click handlers...');
                
                // Add click handlers
                container.querySelectorAll('.file-item').forEach(function(item) {
                    item.addEventListener('click', function() {
                        // Remove active class from all items
                        container.querySelectorAll('.file-item').forEach(function(i) {
                            i.classList.remove('active');
                        });
                        // Add active class to clicked item
                        item.classList.add('active');
                        loadPost(item.dataset.file);
                    });
                });
            } else {
                console.log('No files found, showing empty state');
                container.innerHTML = '<div class="file-item" style="opacity: 0.5;"><i class="fas fa-info-circle"></i> No blog posts found</div>';
            }
        })
        .catch(function(error) {
            console.error('Error loading blog posts:', error);
            const container = document.querySelector('.file-list-content');
            container.innerHTML = '<div class="file-item" style="color: var(--error);"><i class="fas fa-exclamation-circle"></i> Error loading posts: ' + error.message + '</div>';
        });
}

function loadPost(filename) {
    fetch('/admin/api/blog.php?file=' + encodeURIComponent(filename))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            console.log("Loaded post data:", data);
            
            // Set form fields from front matter
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');
            
            titleInput.value = data.frontMatter.title || '';
            
            // Set slug, preferring the explicit slug or falling back to filename
            const slug = data.frontMatter.slug || data.filename.replace(/\.[^/.]+$/, ""); // Remove extension
            slugInput.value = slug;
            
            // Initialize user-edited flags based on whether we have explicit values
            titleInput.dataset.userEdited = data.frontMatter.title ? 'true' : 'false';
            slugInput.dataset.userEdited = data.frontMatter.slug ? 'true' : 'false';
            
            document.getElementById('date').value = data.frontMatter.date || '';
            document.getElementById('author').value = data.frontMatter.author || '';
            document.getElementById('description').value = data.frontMatter.description || '';
            
            // Handle categories and tags (which might be arrays or strings)
            let categories = data.frontMatter.categories || [];
            if (typeof categories === 'string') {
                categories = [categories];
            }
            document.getElementById('categories').value = categories.join(', ');
            
            let tags = data.frontMatter.tags || [];
            if (typeof tags === 'string') {
                tags = [tags];
            }
            document.getElementById('tags').value = tags.join(', ');
            
            document.getElementById('filename').value = data.filename;
            
            // Set content - this should be just the content without the front matter
            const editor = document.querySelector('.code-editor');
            if (editor && editor.codemirror) {
                editor.codemirror.setValue(data.content || '');
            }
            
            // Update the frontMatter hidden field
            updateFrontMatter();
            
            // Show delete button
            const deleteBtn = document.getElementById('forma-delete-btn');
            if (deleteBtn) deleteBtn.style.display = 'flex';
            else document.querySelector('.delete-btn').style.display = 'flex';
        })
        .catch(function(error) {
            console.error('Error loading post:', error);
            // Just log the error, no need for button alert since this isn't triggered by a button
            console.log('Post loading failed');
        });
}

// Override the save button
console.log("Setting up DOMContentLoaded event listener");

// Add a direct save handler using proper DOM methods instead of document.write
(function() {
    console.log("Creating direct save handler script");
    
    // Create a flag to indicate we're in the blog section
    window.currentSection = 'blog';
    
    // Make sure the blog tab is active
    document.querySelectorAll('.button-group button').forEach(function(b) {
        b.classList.remove('active');
    });
    document.querySelector('[data-section="blog"]').classList.add('active');
    
    const script = document.createElement('script');
    script.textContent = `
    // Direct save and delete handler that runs immediately
    (function() {
        console.log("Direct handler executing");
        
        // Wait for DOM to be ready
        function ready(fn) {
            if (document.readyState !== 'loading') {
                fn();
            } else {
                document.addEventListener('DOMContentLoaded', fn);
            }
        }
        
        // Function to fetch default author from settings
        function getDefaultAuthor() {
            return new Promise((resolve) => {
                fetch('/admin/api/settings.php?section=blog')
                    .then(response => response.json())
                    .then(settings => {
                        resolve(settings.default_author || '');
                    })
                    .catch(error => {
                        console.error('Error loading default author:', error);
                        resolve('');
                    });
            });
        }
        
        // Setup the save and delete handlers
        ready(function() {
            console.log("DOM ready, setting up direct handlers");
            
            // Set up slug generation when title changes
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');
            
            if (titleInput && slugInput) {
                titleInput.addEventListener('blur', function() {
                    // Only auto-generate slug if the slug field is empty or hasn't been manually edited
                    if (!slugInput.value.trim() || !slugInput.dataset.userEdited) {
                        slugInput.value = generateSlugFromTitle(titleInput.value);
                        // Update front matter when slug is auto-generated
                        updateFrontMatter();
                    }
                });
                
                // Mark the slug as user-edited when the user changes it
                slugInput.addEventListener('input', function() {
                    slugInput.dataset.userEdited = 'true';
                });
            }
            
            // Wait a bit to ensure everything is loaded
            setTimeout(function() {
                // SAVE BUTTON HANDLER
                const saveBtn = document.getElementById('btn-save');
                if (saveBtn) {
                    console.log("Found save button, attaching click handler");
                    
                    // Remove all event listeners by creating a clone
                    const newSaveBtn = saveBtn.cloneNode(true);
                    saveBtn.parentNode.replaceChild(newSaveBtn, saveBtn);
                    
                    // Add our handler
                    newSaveBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        console.log("Save button clicked");
                        this.innerHTML = '<i class="small fas fa-spinner fa-spin"></i> Saving...';
                        
                        try {
                            // Debug each element individually
                            const filenameInput = document.getElementById('filename');
                            console.log("Filename input:", filenameInput);
                            console.log("Filename value:", filenameInput ? filenameInput.value : "Not found");
                            
                            // Get all the form values more carefully
                            const filename = filenameInput ? filenameInput.value.trim() : "";
                            
                            // Get the editor
                            const editor = document.querySelector('.code-editor');
                            if (!editor || !editor.codemirror) {
                                this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Editor not initialized';
                                setTimeout(() => {
                                    this.innerHTML = '<i class="small fas fa-save"></i> Save';
                                }, 2000);
                                return false;
                            }
                            
                            const content = editor.codemirror.getValue() || '';
                            
                            // Get form fields with extra validation
                            const titleInput = document.getElementById('title');
                            const slugInput = document.getElementById('slug');
                            const dateInput = document.getElementById('date');
                            const authorInput = document.getElementById('author');
                            const descriptionInput = document.getElementById('description');
                            const categoriesInput = document.getElementById('categories');
                            const tagsInput = document.getElementById('tags');
                            
                            const title = titleInput ? titleInput.value.trim() : "";
                            let slug = slugInput ? slugInput.value.trim() : "";
                            const date = dateInput ? dateInput.value : "";
                            let author = authorInput ? authorInput.value.trim() : "";
                            const description = descriptionInput ? descriptionInput.value.trim() : "";
                            const categories = categoriesInput ? categoriesInput.value.trim() : "";
                            const tags = tagsInput ? tagsInput.value.trim() : "";
                            
                            if (!filename) {
                                this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Please enter filename';
                                setTimeout(() => {
                                    this.innerHTML = '<i class="small fas fa-save"></i> Save';
                                }, 2000);
                                return false;
                            }
                            
                            // Generate slug from title if empty
                            if (!slug && title) {
                                slug = generateSlugFromTitle(title);
                            }
                            
                            // Create the form data explicitly
                            console.log("Form values collected:");
                            console.log({
                                filename,
                                title,
                                slug,
                                date,
                                author,
                                description,
                                categories,
                                tags,
                                contentLength: content.length
                            });
                            
                            const formData = new FormData();
                            formData.append('filename', filename);
                            formData.append('content', content);
                            formData.append('title', title || filename);
                            formData.append('slug', slug);
                            formData.append('date', date || new Date().toISOString().split('T')[0]);
                            formData.append('use_separate_fields', 'true');
                            
                            if (author) formData.append('author', author);
                            if (description) formData.append('description', description);
                            if (categories) formData.append('categories', categories);
                            if (tags) formData.append('tags', tags);
                            
                            // Log what we're sending to verify
                            console.log("FormData entries:");
                            for (const pair of formData.entries()) {
                                console.log(pair[0] + ': ' + pair[1]);
                            }
                            
                            // Store reference to avoid scope issues
                            const saveButton = this;
                            
                            // Send directly to the blog API
                            fetch('/admin/api/blog.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => {
                                console.log("Response status:", response.status);
                                console.log("Response ok:", response.ok);
                                console.log("Response headers:", response.headers.get('Content-Type'));
                                return response.text();
                            })
                            .then(text => {
                                console.log("Response received:", text);
                                console.log("Response type:", typeof text);
                                console.log("Response length:", text.length);
                                console.log("First 200 chars:", text.substring(0, 200));
                                console.log("Last 200 chars:", text.substring(Math.max(0, text.length - 200)));
                                
                                // Check if response starts with HTML or other non-JSON content
                                if (text.trim().startsWith('<')) {
                                    console.error("Response appears to be HTML, not JSON");
                                    console.error("Full HTML response:", text);
                                    saveButton.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Server returned HTML';
                                    setTimeout(() => {
                                        saveButton.innerHTML = '<i class="small fas fa-save"></i> Save';
                                        // Always try to reload the list since the save might have worked
                                        console.log("Attempting to reload blog list despite parse error...");
                                        loadBlogList();
                                    }, 2000);
                                    return;
                                }
                                
                                try {
                                    const data = JSON.parse(text);
                                    console.log("Parsed data:", data);
                                    if (data.success) {
                                        console.log("File saved successfully");
                                        saveButton.innerHTML = '<i class="small fas fa-check"></i> Saved!';
                                        
                                        // Reset button after delay
                                        setTimeout(() => {
                                            saveButton.innerHTML = '<i class="small fas fa-save"></i> Save';
                                            loadBlogList(); // Reload the file list
                                        }, 2000);
                                    } else {
                                        console.error("Error saving file:", data.error || "Unknown error");
                                        saveButton.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> ' + (data.error || 'Error saving');
                                        setTimeout(() => {
                                            saveButton.innerHTML = '<i class="small fas fa-save"></i> Save';
                                        }, 2000);
                                    }
                                } catch (e) {
                                    console.error("Error parsing response:", e);
                                    console.error("Raw response:", text);
                                    saveButton.innerHTML = '<i class="small fas fa-check"></i> Saved!';
                                    setTimeout(() => {
                                        saveButton.innerHTML = '<i class="small fas fa-save"></i> Save';
                                        // Always try to reload the list since the save might have worked
                                        loadBlogList();
                                    }, 2000);
                                }
                            })
                            .catch(error => {
                                console.error("Request error:", error);
                                saveButton.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Network error';
                                setTimeout(() => {
                                    saveButton.innerHTML = '<i class="small fas fa-save"></i> Save';
                                }, 2000);
                            });
                        } catch (error) {
                            console.error("Error in save handler:", error);
                            this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Error in save handler';
                            setTimeout(() => {
                                this.innerHTML = '<i class="small fas fa-save"></i> Save';
                            }, 2000);
                        }
                        
                        return false;
                    });
                    
                    console.log("Save handler attached");
                } else {
                    console.error("Save button not found");
                }
                
                // DELETE BUTTON HANDLER - using new ID
                const deleteBtn = document.getElementById('forma-delete-btn');
                if (deleteBtn) {
                    console.log("Found delete button with new ID, attaching click handler");
                    
                    // No need to clone since we're using a new ID that should be clean
                    
                    // Set up global variable to track deletion in progress
                    window.__deleteInProgress = false;
                    
                    // Add our single delete handler
                    deleteBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // Prevent multiple clicks
                        if (window.__deleteInProgress) {
                            console.log("Delete already in progress, ignoring click");
                            return false;
                        }
                        
                        console.log("Delete button clicked");
                        const filename = document.getElementById('filename').value.trim();
                        console.log("Filename to delete:", filename);
                        
                        if (!filename) {
                            this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> No file selected';
                            setTimeout(() => {
                                this.innerHTML = '<i class="small fas fa-trash"></i> Delete';
                            }, 2000);
                            return false;
                        }
                        
                        if (!confirm("Are you sure you want to delete '" + filename + "'? This cannot be undone.")) {
                            console.log("Delete cancelled by user");
                            return false;
                        }
                        
                        // Set deletion in progress flag
                        window.__deleteInProgress = true;
                        
                        // Show loading state
                        this.innerHTML = '<i class="small fas fa-spinner fa-spin"></i> Deleting...';
                        
                        // Ensure the filename has .md extension
                        let filenameWithExt = filename;
                        if (!filenameWithExt.endsWith('.md')) {
                            filenameWithExt += '.md';
                        }
                        console.log("Sending delete request for:", filenameWithExt);
                        
                        // Enhanced error handling for delete operation
                        try {
                            // Make sure the filename is properly encoded to handle spaces and special characters
                            const safeFilename = encodeURIComponent(filenameWithExt);
                            console.log("Encoded filename for deletion:", safeFilename);
                            
                            // Use DELETE method for proper RESTful API design with better error handling
                            fetch('/admin/api/blog.php?file=' + safeFilename, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json'
                                }
                            })
                            .then(response => {
                                console.log("Delete response status:", response.status);
                                console.log("Delete response headers:", response.headers.get('Content-Type'));
                                return response.text().then(text => {
                                    console.log("Delete raw response:", text);
                                    console.log("Delete response type:", typeof text);
                                    console.log("Delete response length:", text.length);
                                    
                                    if (text.trim().startsWith('<')) {
                                        console.error("Delete response appears to be HTML, not JSON");
                                        console.error("Full HTML response:", text);
                                        throw new Error("Server returned HTML instead of JSON");
                                    }
                                    
                                    try {
                                        return JSON.parse(text);
                                    } catch (e) {
                                        throw new Error("Failed to parse JSON response: " + e.message + ", raw text: " + text);
                                    }
                                });
                            })
                            .then(data => {
                                console.log("Delete response data:", data);
                                if (data.success) {
                                    console.log("Post deleted successfully");
                                    this.innerHTML = '<i class="small fas fa-check"></i> Deleted!';
                                    
                                    // Reset form completely
                                    document.getElementById('blog-form').reset();
                                    const editor = document.querySelector('.code-editor');
                                    if (editor && editor.codemirror) {
                                        editor.codemirror.setValue('');
                                    }
                                    this.style.display = 'none';
                                    
                                    // Clear any active file selections
                                    document.querySelectorAll('.file-list-content .file-item').forEach(function(item) {
                                        item.classList.remove('active');
                                    });
                                    
                                    // Reset button after delay  
                                    const deleteButton = this; // Capture reference to avoid scope issues
                                    setTimeout(() => {
                                        deleteButton.innerHTML = '<i class="small fas fa-trash"></i> Delete';
                                        loadBlogList(); // Reload the file list
                                    }, 2000);
                                } else {
                                    console.error("Delete failed with error:", data.error);
                                    this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> ' + (data.error || 'Error deleting');
                                    setTimeout(() => {
                                        this.innerHTML = '<i class="small fas fa-trash"></i> Delete';
                                    }, 2000);
                                }
                                
                                // Reset flag
                                window.__deleteInProgress = false;
                            })
                            .catch(error => {
                                console.error("Delete request error:", error);
                                this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Network error';
                                setTimeout(() => {
                                    this.innerHTML = '<i class="small fas fa-trash"></i> Delete';
                                    // Try to reload list anyway in case delete actually worked
                                    loadBlogList();
                                }, 2000);
                                
                                // Reset flag
                                window.__deleteInProgress = false;
                            });
                        } catch (error) {
                            console.error("Error in delete handler:", error);
                            this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Error in delete handler';
                            setTimeout(() => {
                                this.innerHTML = '<i class="small fas fa-trash"></i> Delete';
                            }, 2000);
                            window.__deleteInProgress = false;
                        }
                        
                        return false;
                    });
                    
                    console.log("Delete handler attached");
                } else {
                    console.error("Delete button not found");
                }
            }, 500); // Wait 500ms to ensure everything is loaded
        });
    })();
    `;
    
    // Append the script to the document
    document.head.appendChild(script);
})();

// Remove the setupFormHandlers function's event handlers for save and delete
function setupFormHandlers() {
    // New post handler
    document.querySelector('.new-file').addEventListener('click', function() {
        // Remove active class from all items
        document.querySelectorAll('.file-item').forEach(function(i) { i.classList.remove('active'); });
        
        // Reset form
        document.getElementById('blog-form').reset();
        const editor = document.querySelector('.code-editor');
        if (editor && editor.codemirror) {
            editor.codemirror.setValue('');
        }
        // Hide delete button (using either class or ID to be sure)
        const deleteBtn = document.getElementById('forma-delete-btn');
        if (deleteBtn) deleteBtn.style.display = 'none';
        document.querySelector('.delete-btn').style.display = 'none';
        
        // Set default date to today
        document.getElementById('date').valueAsDate = new Date();
        
        // Initialize user-edited flags for auto-suggestion
        const titleInput = document.getElementById('title');
        const slugInput = document.getElementById('slug');
        if (titleInput) titleInput.dataset.userEdited = 'false';
        if (slugInput) slugInput.dataset.userEdited = 'false';
        
        // Set default author from settings
        fetchDefaultAuthor();
        
        // Update the frontMatter hidden field
        updateFrontMatter();
    });
    
    // Front matter toggle handler
    document.getElementById('toggleFrontMatter').addEventListener('click', function() {
        const frontMatter = document.querySelector('.front-matter');
        frontMatter.classList.toggle('front-matter-collapsed');
        
        // Save state to localStorage
        localStorage.setItem('frontMatterCollapsed', 
            frontMatter.classList.contains('front-matter-collapsed'));
    });
    
    // Load saved collapsed state or default to collapsed
    const savedState = localStorage.getItem('frontMatterCollapsed');
    // If no saved state or saved state is 'true', collapse it
    if (savedState === null || savedState === 'true') {
        document.querySelector('.front-matter').classList.add('front-matter-collapsed');
    }
    
    // Auto-suggestion functions
    function setupAutoSuggestion() {
        // Auto-fill title from filename
        domCache.filename.addEventListener('input', function() {
            if (!domCache.title.dataset.userEdited || domCache.title.dataset.userEdited === 'false') {
                const title = this.value
                    .replace(/\.md$/, '')
                    .replace(/[-_]/g, ' ')
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                    .join(' ');
                domCache.title.value = title;
            }
            updateFrontMatter();
        });
        
        // Auto-fill slug from title
        domCache.title.addEventListener('input', function() {
            if (!domCache.slug.dataset.userEdited || domCache.slug.dataset.userEdited === 'false') {
                const slug = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
                domCache.slug.value = slug;
            }
            updateFrontMatter();
        });
        
        // Mark as user-edited when manually changed
        domCache.title.addEventListener('focus', function() {
            this.dataset.userEdited = 'true';
        });
        
        domCache.slug.addEventListener('focus', function() {
            this.dataset.userEdited = 'true';
        });
    }
    
    // Set up auto-suggestion for title and slug based on filename
    const filenameInput = document.getElementById('filename');
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    
    if (filenameInput && titleInput && slugInput) {
        // Auto-generate title and slug when filename changes
        filenameInput.addEventListener('blur', function() {
            const filename = this.value.trim();
            
            // Auto-generate title if it's empty or hasn't been manually edited
            if (!titleInput.value.trim() || titleInput.dataset.userEdited !== 'true') {
                const generatedTitle = generateTitleFromFilename(filename);
                titleInput.value = generatedTitle;
                titleInput.dataset.userEdited = 'false';
                updateFrontMatter(); // Update front matter with new title
            }
            
            // Auto-generate slug if it's empty or hasn't been manually edited
            if (!slugInput.value.trim() || slugInput.dataset.userEdited !== 'true') {
                // Use the title if available, otherwise use filename
                const titleForSlug = titleInput.value.trim() || generateTitleFromFilename(filename);
                const generatedSlug = generateSlugFromTitle(titleForSlug);
                slugInput.value = generatedSlug;
                slugInput.dataset.userEdited = 'false';
                updateFrontMatter(); // Update front matter with new slug
            }
        });
        
        // Auto-generate slug when title changes (if slug hasn't been manually edited)
        titleInput.addEventListener('blur', function() {
            if (!slugInput.value.trim() || slugInput.dataset.userEdited !== 'true') {
                const generatedSlug = generateSlugFromTitle(this.value.trim());
                slugInput.value = generatedSlug;
                slugInput.dataset.userEdited = 'false';
                updateFrontMatter(); // Update front matter with new slug
            }
        });
        
        // Mark fields as user-edited when manually changed
        titleInput.addEventListener('input', function() {
            this.dataset.userEdited = 'true';
        });
        
        slugInput.addEventListener('input', function() {
            this.dataset.userEdited = 'true';
        });
    }
    
    // Set up input event listeners for front matter fields
    document.getElementById('title').addEventListener('input', updateFrontMatter);
    document.getElementById('date').addEventListener('input', updateFrontMatter);
    document.getElementById('author').addEventListener('input', updateFrontMatter);
    document.getElementById('description').addEventListener('input', updateFrontMatter);
    document.getElementById('categories').addEventListener('input', updateFrontMatter);
    document.getElementById('tags').addEventListener('input', updateFrontMatter);
}

// Function to fetch default author from settings
function fetchDefaultAuthor() {
    fetch('/admin/api/settings.php?section=blog')
        .then(response => response.json())
        .then(settings => {
            if (settings.default_author) {
                // Only set if the field is empty
                const authorField = document.getElementById('author');
                if (!authorField.value) {
                    authorField.value = settings.default_author;
                    updateFrontMatter(); // Update front matter with the new author
                }
            }
        })
        .catch(error => {
            console.error('Error loading default author:', error);
        });
}

// Initialize immediately
console.log('Initializing blog section...');
loadBlogList();
setupFormHandlers();
// Fetch default author on initialization
fetchDefaultAuthor();
</script> 