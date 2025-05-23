<!-- Uploads Section -->
<div class="section-container">
    <!-- File List -->
    <div class="file-list">
        <div class="file-item new-file" id="upload-trigger">
            <i class="fas fa-cloud-upload-alt"></i> Upload Files
        </div>
        <div class="file-list-content">
            <!-- Files will be loaded here -->
        </div>
    </div>

    <!-- Editor -->
    <div class="editor-container">
        <div id="dropzone" class="dropzone">
            <div class="dz-message">
                <i class="fas fa-cloud-upload-alt"></i>
                <h3>Drop files here or click to upload</h3>
                <p>Files will be uploaded to the uploads directory</p>
                <a href="#" class="browse-btn">Browse Files</a>
            </div>
        </div>

        <!-- File Preview -->
        <form id="file-preview" class="editor-form" style="display: none;">
            <div class="form-group">
                <label for="filename-edit">Filename</label>
                <input type="text" id="filename-edit" name="filename-edit" required>
            </div>
            
            <div class="form-group preview-content">
                <!-- Preview content will be loaded here -->
            </div>

            <!-- Text Editor (for text files) -->
            <div class="form-group text-editor" style="display: none;">
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
                <textarea class="code-editor"></textarea>
            </div>
        </form>
        
        <!-- Footer -->
        <footer>
            <div class="buttons">
                <div class="button-group">
                    <button type="button" class="standard-btn" id="save-file-btn">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button type="button" class="delete-btn" id="delete-file-btn">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Rename Modal -->
<div id="rename-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Rename File</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="rename-form">
                <div class="form-group">
                    <label for="new-filename">New Filename</label>
                    <input type="text" id="new-filename" name="new-filename" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="standard-btn">Rename</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Upload-specific styles that should stay in this file */
.dropzone::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        45deg,
        var(--primary) 0%,
        transparent 50%,
        transparent 50%,
        var(--primary) 100%
    );
    opacity: 0.8;
    animation: gradientPulse 2s ease-in-out infinite;
    background-size: 120% 120%;
}

.dropzone.dz-drag-hover::before {
    animation: gradientPulse 0.6s ease-in-out infinite;
    opacity: 0.2;
}

@keyframes gradientPulse {
    0% {
        opacity: 0.1;
        background-position: 0% 0%;
    }
    50% {
        opacity: 0.15;
        background-position: 100% 100%;
    }
    100% {
        opacity: 0.1;
        background-position: 0% 0%;
    }
}

/* Media preview styles */
.preview-content {
    display: flex;
    justify-content: center;
    align-items: center;
    max-height: calc(100vh - 250px);
    overflow: auto;
    border-radius: 4px;
}

.preview-content > div {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.preview-content img {
    max-width: 100%;
    max-height: calc(100vh - 250px);
    object-fit: contain;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.preview-content video,
.preview-content audio {
    width: 100%;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.preview-content .no-preview {
    padding: 40px;
    text-align: center;
    background: var(--bg-light);
    border-radius: 4px;
    color: var(--text-muted);
    font-style: italic;
    width: 100%;
}

/* Fix CodeMirror height */
#file-preview.editor-form {
    display: flex;
    flex-direction: column;
    height: calc(100% - 56px);
}

#file-preview .form-group.text-editor {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
}

#file-preview .text-editor .CodeMirror {
    flex: 1;
    height: auto !important;
    min-height: calc(100vh - 300px);
}

#file-preview .text-editor .CodeMirror-scroll {
    min-height: calc(100vh - 300px);
}
</style>

<script>
let currentFile = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('Uploads section: DOM ready');
    initializeUploads();
});

// Also initialize when section is loaded dynamically
if (document.readyState === 'complete') {
    console.log('Uploads section: Document already complete');
    initializeUploads();
}

function initializeUploads() {
    console.log('Uploads section: Initializing...');
    loadFileList();
    setupDropzone();
    setupFooter();
    setupModal();
}

function loadFileList() {
    console.log('Uploads section: Loading file list...');
    fetch('/admin/api/uploads.php')
        .then(response => {
            console.log('Uploads section: Response status:', response.status);
            return response.json();
        })
        .then(files => {
            console.log('Uploads section: Files received:', files);
            const container = document.querySelector('.file-list-content');
            if (!container) {
                console.error('Uploads section: Could not find .file-list-content container');
                return;
            }
            
            // Remove the redundant "Upload Files" item
            container.innerHTML = '';
            
            // Add the files
            container.innerHTML += files.map(file => {
                const icon = getFileIcon(file);
                return `
                    <div class="file-item" data-file="${file}">
                        <i class="${icon}"></i> ${file}
                    </div>
                `;
            }).join('');

            // Add click handlers to file items
            container.querySelectorAll('.file-item').forEach(item => {
                item.addEventListener('click', () => loadFilePreview(item.dataset.file));
            });
        })
        .catch(error => {
            console.error('Uploads section: Error loading file list:', error);
        });
}

function getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    switch (ext) {
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return 'fas fa-file-image';
        case 'mp3':
        case 'm4a':
        case 'wav':
            return 'fas fa-file-audio';
        case 'mp4':
        case 'webm':
        case 'ogg':
            return 'fas fa-file-video';
        case 'txt':
        case 'md':
        case 'html':
            return 'fas fa-file-alt';
        case 'js':
        case 'css':
        case 'php':
            return 'fas fa-file-code';
        default:
            return 'fas fa-file';
    }
}

function showDropzone() {
    const dropzone = document.getElementById('dropzone');
    const filePreview = document.getElementById('file-preview');
    
    dropzone.style.display = 'block';
    filePreview.style.display = 'none';
    
    // Highlight the upload trigger
    document.querySelectorAll('.file-item').forEach(item => item.classList.remove('active'));
    document.getElementById('upload-trigger').classList.add('active');
}

function loadFilePreview(filename) {
    currentFile = filename;
    const dropzone = document.getElementById('dropzone');
    const filePreview = document.getElementById('file-preview');
    const previewContent = filePreview.querySelector('.preview-content');
    const textEditor = filePreview.querySelector('.text-editor');
    
    // Show preview, hide dropzone
    dropzone.style.display = 'none';
    filePreview.style.display = 'block';
    
    // Reset previous content and display states
    previewContent.innerHTML = '';
    previewContent.style.display = 'block'; // Ensure preview content is visible by default
    textEditor.style.display = 'none';
    
    // Highlight the selected file
    document.querySelectorAll('.file-item').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.file === filename) {
            item.classList.add('active');
        }
    });
    
    const ext = filename.split('.').pop().toLowerCase();
    
    // Set the filename in the input field
    const filenameInput = document.getElementById('filename-edit');
    filenameInput.value = filename;
    
    // Handle different file types
    if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
        // Image files
        previewContent.innerHTML = `<div><img src="/uploads/${filename}" alt="${filename}"></div>`;
    } else if (['mp4', 'webm', 'ogg'].includes(ext)) {
        // Video files
        previewContent.innerHTML = `<div><video src="/uploads/${filename}" controls></video></div>`;
    } else if (['mp3', 'm4a', 'wav'].includes(ext)) {
        // Audio files
        previewContent.innerHTML = `<div><audio src="/uploads/${filename}" controls></audio></div>`;
    } else if (['txt', 'md', 'html', 'js', 'css', 'php'].includes(ext)) {
        // Text files - hide preview content, show text editor
        previewContent.style.display = 'none';
        textEditor.style.display = 'block';
        
        // Load text content
        fetch(`/admin/api/uploads.php?file=${encodeURIComponent(filename)}`)
            .then(response => response.text())
            .then(content => {
                const editor = textEditor.querySelector('.code-editor');
                
                // Initialize CodeMirror if not already done
                if (!editor.codemirror) {
                    editor.codemirror = CodeMirror.fromTextArea(editor, {
                        mode: getCodeMirrorMode(ext),
                        theme: 'default',
                        lineNumbers: true,
                        indentUnit: 4,
                        lineWrapping: true
                    });
                } else {
                    editor.codemirror.setValue(content);
                    editor.codemirror.setOption('mode', getCodeMirrorMode(ext));
                }
            });
    } else {
        // Other file types
        previewContent.innerHTML = `<div class="no-preview">No preview available for this file type</div>`;
    }
}

function getCodeMirrorMode(ext) {
    const modes = {
        'md': 'markdown',
        'html': 'xml',
        'js': 'javascript',
        'css': 'css',
        'php': 'php',
        'txt': 'text'
    };
    return modes[ext] || 'text';
}

function saveFileContent(filename, content) {
    fetch('/admin/api/uploads.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `file=${encodeURIComponent(filename)}&content=${encodeURIComponent(content)}`
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            console.log('File content saved successfully');
            alert('File saved successfully');
        } else {
            console.error('Error saving file content:', result.error);
            alert('Error saving file: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error saving file content:', error);
        alert('Error saving file: ' + error);
    });
}

function renameFile(oldFilename, newFilename) {
    fetch('/admin/api/uploads.php', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            oldFilename: oldFilename,
            newFilename: newFilename
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            loadFileList();
            loadFilePreview(newFilename);
            alert('File renamed successfully');
        } else {
            console.error('Error renaming file:', result.error);
            alert('Error renaming file: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error renaming file:', error);
        alert('Error renaming file: ' + error);
    });
}

function setupDropzone() {
    const dropzone = document.getElementById('dropzone');
    
    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    // Highlight drop zone when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, unhighlight, false);
    });

    // Handle dropped files
    dropzone.addEventListener('drop', handleDrop, false);
    
    // Handle click to upload
    dropzone.addEventListener('click', () => {
        const input = document.createElement('input');
        input.type = 'file';
        input.multiple = true;
        input.onchange = e => handleFiles(e.target.files);
        input.click();
    });

    // Handle browse button click
    document.querySelector('.browse-btn').addEventListener('click', (e) => {
        e.preventDefault();
        const input = document.createElement('input');
        input.type = 'file';
        input.multiple = true;
        input.onchange = e => handleFiles(e.target.files);
        input.click();
    });
    
    // Prevent form submission
    document.getElementById('file-preview').addEventListener('submit', function(e) {
        e.preventDefault();
        return false;
    });
}

function preventDefaults (e) {
    e.preventDefault();
    e.stopPropagation();
}

function highlight(e) {
    document.getElementById('dropzone').classList.add('dz-drag-hover');
}

function unhighlight(e) {
    document.getElementById('dropzone').classList.remove('dz-drag-hover');
}

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    handleFiles(files);
}

function handleFiles(files) {
    [...files].forEach(uploadFile);
}

function uploadFile(file) {
    const formData = new FormData();
    formData.append('file', file);
    
    fetch('/admin/api/uploads.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            loadFileList();
        } else {
            alert('Error uploading file: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error uploading file');
    });
}

function setupFooter() {
    // Save button event handler
    document.getElementById('save-file-btn').addEventListener('click', function() {
        if (!currentFile) return;
        
        // If text editor is visible, save text content
        const textEditor = document.querySelector('.text-editor');
        if (textEditor && textEditor.style.display !== 'none') {
            const content = textEditor.querySelector('.code-editor').codemirror.getValue();
            saveFileContent(currentFile, content);
        } else {
            // Otherwise save filename change
            const newFilename = document.getElementById('filename-edit').value;
            if (newFilename !== currentFile) {
                renameFile(currentFile, newFilename);
            } else {
                alert('No changes to save');
            }
        }
    });
    
    // Delete button event handler
    document.getElementById('delete-file-btn').addEventListener('click', function() {
        if (!currentFile) return;
        
        if (confirm('Are you sure you want to delete this file?')) {
            fetch(`/admin/api/uploads.php?file=${encodeURIComponent(currentFile)}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showDropzone();
                    loadFileList();
                    alert('File deleted successfully');
                } else {
                    alert('Error deleting file: ' + result.error);
                }
            });
        }
    });
    
    // Upload trigger click handler
    document.getElementById('upload-trigger').addEventListener('click', showDropzone);
}

function setupModal() {
    const modal = document.getElementById('rename-modal');
    const closeBtn = modal.querySelector('.modal-close');
    const form = document.getElementById('rename-form');
    
    // Hide modal
    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', e => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Handle rename form submission
    form.addEventListener('submit', e => {
        e.preventDefault();
        
        const newFilename = document.getElementById('new-filename').value;
        
        fetch('/admin/api/uploads.php', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                oldFilename: currentFile,
                newFilename: newFilename
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                modal.style.display = 'none';
                loadFileList();
                loadFilePreview(newFilename);
            } else {
                alert('Error renaming file: ' + result.error);
            }
        });
    });
}
</script>