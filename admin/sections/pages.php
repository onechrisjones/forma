<!-- Pages Section -->
<div class="section-container">
    <!-- File List -->
    <div class="file-list">
        <div class="file-item new-file">
            <i class="fas fa-plus"></i> Add New Page
        </div>
        <div class="file-list-content">
            <!-- Files will be loaded here -->
        </div>
    </div>

    <!-- Editor -->
    <div class="editor-container">
        <form id="page-form" class="editor-form">
            <div class="form-group">
                <label for="filename">Filename</label>
                <input type="text" id="filename" name="filename" required>
                <span class="hint">.html or .md extension will be added automatically</span>
            </div>
            
            <div class="form-group">
                <label for="slug">Slug</label>
                <input type="text" id="slug" name="slug">
                <span class="hint">Used in URLs: /your-slug (leave blank to use filename)</span>
            </div>

            <div class="form-group">
                <!--label for="content">Content</label-->
                <textarea id="content" name="content" class="code-editor" data-mode="markdown"></textarea>
            </div>
        </form>
        
        <!-- Footer -->
        <footer>
            <div class="buttons">
                <div class="button-group">
                    <button type="button" class="standard-btn" id="btn-save"><i class="small fas fa-save"></i> Save</button>
                    <button type="button" class="delete-btn" id="btn-delete"><i class="small fas fa-trash"></i> Delete</button>
                </div>
            </div>
        </footer>
    </div>
</div>


<script>
// Function to generate a slug from a filename or title
function generateSlugFromText(text) {
    if (!text) return '';
    
    // Remove extension if present
    text = text.replace(/\.(html|md)$/, '');
    
    // Convert to lowercase, replace spaces with hyphens
    let slug = text.toLowerCase()
                   .replace(/[^\w\s-]/g, '') // Remove special characters
                   .replace(/\s+/g, '-')     // Replace spaces with hyphens
                   .replace(/-+/g, '-');     // Remove consecutive hyphens
    
    return slug;
}

// Functions
function loadPages() {
    console.log('Loading pages...');
    fetch('/admin/api/pages.php')
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Error response:', text);
                    throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
                });
            }
            return response.text().then(text => {
                console.log('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error(`Failed to parse JSON response: ${e.message}, raw text: ${text}`);
                }
            });
        })
        .then(data => {
            console.log('Data received:', data);
            const container = document.querySelector('.file-list-content');
            
            // Log debug info if available
            if (data.debug) {
                console.log('Debug info:', data.debug);
            }
            
            const files = data.files || data;
            if (files && files.length > 0) {
                container.innerHTML = files.map(file => {
                    // Use house icon for home.html
                    const icon = file === 'home.html' ? 'fa-house' : 'fa-file-alt';
                    return `
                        <div class="file-item" data-file="${file}">
                            <i class="fas ${icon}"></i> ${file}
                        </div>
                    `;
                }).join('');

                // Add click handlers
                container.querySelectorAll('.file-item').forEach(item => {
                    item.addEventListener('click', () => {
                        // Remove active class from all items
                        container.querySelectorAll('.file-item').forEach(i => i.classList.remove('active'));
                        // Add active class to clicked item
                        item.classList.add('active');
                        loadPage(item.dataset.file);
                    });
                });
            } else {
                container.innerHTML = '<div class="file-item" style="opacity: 0.5;"><i class="fas fa-info-circle"></i> No pages found</div>';
            }
        })
        .catch(error => {
            console.error('Error loading pages:', error);
            const container = document.querySelector('.file-list-content');
            container.innerHTML = `<div class="file-item" style="color: var(--error);"><i class="fas fa-exclamation-circle"></i> Error loading pages: ${error.message}</div>`;
        });
}

function loadPage(filename) {
    fetch(`/admin/api/pages.php?file=${encodeURIComponent(filename)}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('filename').value = data.filename;
            
            // Set the slug, either from the meta data or generate from filename
            const slug = data.meta?.slug || generateSlugFromText(data.filename);
            document.getElementById('slug').value = slug;
            // Reset the user-edited flag
            document.getElementById('slug').dataset.userEdited = data.meta?.slug ? 'true' : 'false';
            
            const editor = document.querySelector('.code-editor');
            if (editor && editor.codemirror) {
                editor.codemirror.setValue(data.content || '');
            }
            
            // Show delete button
            document.querySelector('.delete-btn').style.display = 'flex';
        })
        .catch(error => {
            console.error('Error loading page:', error);
            // Just log the error, no need for button alert since this isn't triggered by a button
            console.log('Page loading failed');
        });
}

// Implement a simple flag to prevent duplicate saves and multiple setups
let isSaving = false;
let pagesInitialized = false;

function initializePagesSection() {
    // Prevent multiple initializations
    if (pagesInitialized) {
        console.log('Pages section already initialized, skipping...');
        return;
    }
    
    console.log('Initializing pages section...');
    pagesInitialized = true;
    
    loadPages();
    setupFormHandlers();
}

function setupFormHandlers() {
    console.log('Setting up form handlers...');

    // Prevent any form submissions
    document.getElementById('page-form').addEventListener('submit', function(event) {
        event.preventDefault();
        console.log('Form submit prevented');
        return false;
    });

    // New page handler
    document.querySelector('.new-file').addEventListener('click', () => {
        document.getElementById('page-form').reset();
        document.querySelector('.code-editor').codemirror.setValue('');
        document.querySelector('.delete-btn').style.display = 'none';
        document.getElementById('slug').dataset.userEdited = 'false';
    });
    
    // Set up slug generation when filename changes
    const filenameInput = document.getElementById('filename');
    const slugInput = document.getElementById('slug');
    
    if (filenameInput && slugInput) {
        filenameInput.addEventListener('blur', function() {
            // Only auto-generate slug if the slug field is empty or hasn't been manually edited
            if (!slugInput.value.trim() || slugInput.dataset.userEdited !== 'true') {
                slugInput.value = generateSlugFromText(filenameInput.value);
            }
        });
        
        // Mark the slug as user-edited when the user changes it
        slugInput.addEventListener('input', function() {
            slugInput.dataset.userEdited = 'true';
        });
    }
    
    // Save button handler - use direct reference to avoid cloning issues
    const saveButton = document.getElementById('btn-save');
    saveButton.addEventListener('click', function(event) {
        event.preventDefault();
        event.stopPropagation();
        console.log('Save button clicked');
        
        // Prevent double-saving
        if (isSaving) {
            console.log('Already saving, ignoring duplicate click');
            return;
        }
        
        isSaving = true;
        console.log('Save process started');
        
        const filename = document.getElementById('filename').value.trim();
        const slug = document.getElementById('slug').value.trim();
        const content = document.querySelector('.code-editor').codemirror.getValue();
        
        if (!filename) {
            this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Please enter filename';
            setTimeout(() => {
                this.innerHTML = '<i class="small fas fa-save"></i> Save';
            }, 2000);
            isSaving = false;
            return;
        }
        
        // Add meta data with the slug at the top of the content
        let pageContent = content;
        if (slug) {
            // Check if content already has meta section
            if (pageContent.startsWith('<!--META')) {
                // Replace the existing meta section
                pageContent = pageContent.replace(/<!--META[\s\S]*?-->/, `<!--META
slug: ${slug}
-->`);
            } else {
                // Add new meta section
                pageContent = `<!--META
slug: ${slug}
-->\n${pageContent}`;
            }
        }
        
        console.log('Sending save request to server');
        
        this.innerHTML = '<i class="small fas fa-spinner fa-spin"></i> Saving...';
        
        const formData = new FormData();
        formData.append('filename', filename);
        formData.append('content', pageContent);
        
        fetch('/admin/api/pages.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Save response received:', data);
            if (data.success) {
                this.innerHTML = '<i class="small fas fa-check"></i> Saved!';
                setTimeout(() => {
                    this.innerHTML = '<i class="small fas fa-save"></i> Save';
                    loadPages(); // Reload the file list
                }, 2000);
            } else {
                this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Error saving';
                setTimeout(() => {
                    this.innerHTML = '<i class="small fas fa-save"></i> Save';
                }, 2000);
            }
            isSaving = false;
        })
        .catch(error => {
            console.error('Error saving page:', error);
            this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Error saving';
            setTimeout(() => {
                this.innerHTML = '<i class="small fas fa-save"></i> Save';
            }, 2000);
            isSaving = false;
        });
    });
    
    // Delete button handler - use direct reference
    const deleteButton = document.getElementById('btn-delete');
    deleteButton.addEventListener('click', function(event) {
        event.preventDefault();
        event.stopPropagation();
        
        // Get the actual filename from the active file item, not the input field
        const activeFileItem = document.querySelector('.file-list-content .file-item.active');
        let filename;
        
        if (activeFileItem) {
            // Use the full filename from the active file item
            filename = activeFileItem.dataset.file;
            console.log('Using filename from active file item:', filename);
        } else {
            // Fallback to input field and add extension
            const filenameInput = document.getElementById('filename').value;
            if (!filenameInput) {
                this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> No file selected';
                setTimeout(() => {
                    this.innerHTML = '<i class="small fas fa-trash"></i> Delete';
                }, 2000);
                return;
            }
            
            // Add extension if not present
            if (filenameInput.endsWith('.html') || filenameInput.endsWith('.md')) {
                filename = filenameInput;
            } else {
                filename = filenameInput + '.html'; // Default to HTML
            }
            console.log('Using filename from input with extension:', filename);
        }
        
        if (!confirm(`Are you sure you want to delete '${filename}'? This cannot be undone.`)) {
            return;
        }
        
        console.log('Attempting to delete file:', filename);
        this.innerHTML = '<i class="small fas fa-spinner fa-spin"></i> Deleting...';
        
        fetch(`/admin/api/pages.php?file=${encodeURIComponent(filename)}`, {
            method: 'DELETE'
        })
        .then(response => {
            console.log('Delete response status:', response.status);
            console.log('Delete response ok:', response.ok);
            return response.text();
        })
        .then(text => {
            console.log('Delete raw response:', text);
            try {
                const data = JSON.parse(text);
                console.log('Delete parsed response:', data);
                
                if (data.success) {
                    this.innerHTML = '<i class="small fas fa-check"></i> Deleted!';
                    document.getElementById('page-form').reset();
                    document.querySelector('.code-editor').codemirror.setValue('');
                    document.querySelector('.delete-btn').style.display = 'none';
                    setTimeout(() => {
                        this.innerHTML = '<i class="small fas fa-trash"></i> Delete';
                        loadPages(); // Reload the file list
                    }, 2000);
                } else {
                    console.error('Delete failed with error:', data.error);
                    console.error('Debug info:', data.debug);
                    this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> ' + (data.error || 'Error deleting');
                    setTimeout(() => {
                        this.innerHTML = '<i class="small fas fa-trash"></i> Delete';
                    }, 2000);
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Raw response text:', text);
                this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Parse error';
                setTimeout(() => {
                    this.innerHTML = '<i class="small fas fa-trash"></i> Delete';
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error deleting page:', error);
            this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Network error';
            setTimeout(() => {
                this.innerHTML = '<i class="small fas fa-trash"></i> Delete';
            }, 2000);
        });
    });
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePagesSection);
} else {
    initializePagesSection();
}
</script> 