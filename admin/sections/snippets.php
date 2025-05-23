<!-- Snippets Section -->
<div class="section-container">
    <!-- File List -->
    <div class="file-list">
        <div class="file-item new-file" id="new-snippet-btn">
            <i class="fas fa-plus"></i> Add New Snippet
        </div>
        <div class="file-list-content">
            <div class="loading-state" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i> Loading snippets...
            </div>
            <div class="no-files" style="display: none;">
                <i class="fas fa-folder-open"></i>
                <p>No snippets found</p>
                <p class="hint">Click "Add New Snippet" to create your first snippet</p>
            </div>
            <!-- Files will be loaded here -->
        </div>
    </div>

    <!-- Editor -->
    <div class="editor-container">
        <form id="snippet-form" class="editor-form">
            <div class="form-group">
                <label for="filename">Filename</label>
                <input type="text" id="filename" name="filename" required>
                <span class="hint">.html extension will be added automatically</span>
            </div>

            <div class="form-group">
                <label for="shortcode">Shortcode</label>
                <div class="shortcode-input">
                    <span class="shortcode-prefix">[[</span>
                    <input type="text" id="shortcode" name="shortcode" required>
                    <span class="shortcode-suffix">]]</span>
                </div>
                <span class="hint">This is how you'll reference the snippet in your content</span>
            </div>

            <div class="form-group">
                <label for="content">Content</label>
                <textarea id="content" name="content" class="code-editor" data-mode="xml"></textarea>
            </div>
        </form>

        <!-- Footer -->
        <footer>
            <div class="buttons">
                <div class="button-group">
                    <button type="submit" form="snippet-form" class="standard-btn">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button type="button" class="delete-btn" id="delete-snippet" style="display: none;">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </footer>
    </div>
</div>

<style>
/* Make the CodeMirror instance taller */
.CodeMirror {
    height: 400px !important;
    min-height: 400px;
}
</style>

<script>
let currentFile = null;
let snippetsInitialized = false;

function initializeSnippetsSection() {
    // Prevent multiple initializations
    if (snippetsInitialized) {
        console.log('Snippets section already initialized, skipping...');
        return;
    }
    
    console.log('Initializing snippets section...');
    snippetsInitialized = true;
    
    // Add a small delay to ensure DOM elements are fully loaded
    setTimeout(() => {
        console.log('Starting delayed initialization...');
        loadSnippetsList();
        setupFormHandlers();
    }, 250);
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeSnippetsSection);
} else {
    initializeSnippetsSection();
}

function loadSnippetsList() {
    console.log('Loading snippets list...');
    const container = document.querySelector('.file-list-content');
    
    fetch('/admin/api/snippets.php')
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(files => {
            console.log('Files received:', files);
            
            if (!Array.isArray(files) || files.length === 0) {
                container.innerHTML = `
                    <div class="file-item" style="opacity: 0.5;">
                        <i class="fas fa-info-circle"></i> No snippets found
                    </div>
                `;
                return;
            }
            
            container.innerHTML = files.map(file => `
                <div class="file-item" data-file="${file}">
                    <i class="fas fa-code"></i> ${file}
                </div>
            `).join('');

            // Add click handlers
            container.querySelectorAll('.file-item').forEach(item => {
                item.addEventListener('click', () => {
                    // Remove active class from all items
                    container.querySelectorAll('.file-item').forEach(i => i.classList.remove('active'));
                    // Add active class to clicked item
                    item.classList.add('active');
                    loadSnippet(item.dataset.file);
                });
            });
        })
        .catch(error => {
            console.error('Error loading snippets:', error);
            container.innerHTML = `
                <div class="file-item" style="color: var(--error);">
                    <i class="fas fa-exclamation-circle"></i> Error loading snippets: ${error.message}
                </div>
            `;
        });
}

function loadSnippet(filename) {
    currentFile = filename;
    console.log('Loading snippet:', filename);
    fetch(`/admin/api/snippets.php?file=${encodeURIComponent(filename)}`)
        .then(response => response.json())
        .then(data => {
            console.log('Snippet data received:', data);
            const filenameInput = document.getElementById('filename');
            const shortcodeInput = document.getElementById('shortcode');
            
            filenameInput.value = data.filename;
            shortcodeInput.value = data.shortcode;
            
            // Initialize user-edited flags based on whether we have explicit values
            filenameInput.dataset.userEdited = 'true'; // Always true for existing files
            shortcodeInput.dataset.userEdited = data.shortcode ? 'true' : 'false';
            
            // Set content in CodeMirror editor
            const editor = document.querySelector('.code-editor');
            if (editor && editor.codemirror) {
                // Set mode based on file extension
                const ext = filename.split('.').pop().toLowerCase();
                const mode = ext === 'twig' ? 'twig' : 'xml';
                editor.codemirror.setOption('mode', mode);
                editor.codemirror.setValue(data.content || '');
            }
            
            // Show delete button
            document.getElementById('delete-snippet').style.display = 'inline-block';
        })
        .catch(error => {
            console.error('Error loading snippet:', error);
            // Just log the error, no need for button alert since this isn't triggered by a button
            console.log('Snippet loading failed');
        });
}

function setupFormHandlers() {
    console.log('Setting up snippets form handlers...');
    
    const form = document.getElementById('snippet-form');
    const newBtn = document.getElementById('new-snippet-btn');
    const deleteBtn = document.getElementById('delete-snippet');
    
    console.log('Form found:', !!form);
    console.log('New button found:', !!newBtn);
    console.log('Delete button found:', !!deleteBtn);
    
    if (!form) {
        console.error('Could not find snippet form');
        return;
    }
    
    // Prevent form submission
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        console.log('Form submit prevented');
        return false;
    });

    // New snippet button
    if (newBtn) {
        newBtn.addEventListener('click', () => {
            console.log('New snippet clicked');
            currentFile = null;
            form.reset();
            const editor = document.querySelector('.code-editor');
            if (editor && editor.codemirror) {
                editor.codemirror.setOption('mode', 'xml');
                editor.codemirror.setValue('');
            }
            document.getElementById('delete-snippet').style.display = 'none';
            
            // Remove active class from all file items
            document.querySelectorAll('.file-list-content .file-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Initialize user-edited flags for auto-suggestion
            const filenameInput = document.getElementById('filename');
            const shortcodeInput = document.getElementById('shortcode');
            if (filenameInput) filenameInput.dataset.userEdited = 'false';
            if (shortcodeInput) shortcodeInput.dataset.userEdited = 'false';
        });
    }
    
    // Set up auto-suggestion for shortcode based on filename
    const filenameInput = document.getElementById('filename');
    const shortcodeInput = document.getElementById('shortcode');
    
    if (filenameInput && shortcodeInput) {
        // Auto-generate shortcode when filename changes
        filenameInput.addEventListener('blur', function() {
            const filename = this.value.trim();
            
            // Auto-generate shortcode if it's empty or hasn't been manually edited
            if (!shortcodeInput.value.trim() || shortcodeInput.dataset.userEdited !== 'true') {
                // Remove .html extension and convert to shortcode format
                let shortcode = filename.replace(/\.html$/, '');
                // Convert hyphens and underscores to camelCase or keep simple
                shortcode = shortcode.replace(/[-_]/g, '');
                shortcodeInput.value = shortcode.toLowerCase();
                shortcodeInput.dataset.userEdited = 'false';
            }
        });
        
        // Mark shortcode as user-edited when manually changed
        shortcodeInput.addEventListener('input', function() {
            this.dataset.userEdited = 'true';
        });
    }

    // Save button handler - try multiple selectors to find the button
    let saveBtn = form.querySelector('.standard-btn');
    if (!saveBtn) {
        saveBtn = form.querySelector('button[type="submit"]');
    }
    if (!saveBtn) {
        saveBtn = document.querySelector('button.standard-btn');
    }
    
    console.log('Save button found:', !!saveBtn);
    console.log('Save button element:', saveBtn);
    
    if (saveBtn) {
        saveBtn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            console.log('Save button clicked');
            
            const filename = document.getElementById('filename').value.trim();
            const shortcode = document.getElementById('shortcode').value.trim();
            const editor = document.querySelector('.code-editor');
            const content = editor && editor.codemirror ? editor.codemirror.getValue() : '';
            
            if (!filename || !shortcode) {
                this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Please fill all fields';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-save"></i> Save';
                }, 2000);
                return;
            }
            
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            const formData = new FormData();
            formData.append('filename', filename);
            formData.append('shortcode', shortcode);
            formData.append('content', content);

            fetch('/admin/api/snippets.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Save response received:', data);
                if (data.success) {
                    this.innerHTML = '<i class="fas fa-check"></i> Saved!';
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-save"></i> Save';
                        loadSnippetsList(); // Reload the file list
                    }, 2000);
                } else {
                    this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error saving';
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-save"></i> Save';
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error saving snippet:', error);
                this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Network error';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-save"></i> Save';
                }, 2000);
            });
        });
    } else {
        console.error('Could not find save button');
        console.log('Available buttons in form:', form.querySelectorAll('button'));
        console.log('Available .standard-btn elements:', document.querySelectorAll('.standard-btn'));
    }

    // Delete button
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            if (!currentFile) {
                this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> No file selected';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-trash"></i> Delete';
                }, 2000);
                return;
            }
            
            if (!confirm(`Are you sure you want to delete '${currentFile}'? This cannot be undone.`)) {
                return;
            }
            
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
            
            fetch(`/admin/api/snippets.php?file=${encodeURIComponent(currentFile)}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.innerHTML = '<i class="fas fa-check"></i> Deleted!';
                    
                    // Reset form and editor
                    form.reset();
                    const editor = document.querySelector('.code-editor');
                    if (editor && editor.codemirror) {
                        editor.codemirror.setValue('');
                    }
                    this.style.display = 'none';
                    currentFile = null;
                    
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-trash"></i> Delete';
                        loadSnippetsList(); // Reload the file list
                    }, 2000);
                } else {
                    this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error deleting';
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-trash"></i> Delete';
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error deleting snippet:', error);
                this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Network error';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-trash"></i> Delete';
                }, 2000);
            });
        });
    } else {
        console.error('Could not find delete button');
    }
}
</script> 