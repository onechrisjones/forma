<!-- Podcast Section -->
<style>
.episode-number {
    display: inline-block;
    min-width: 24px;
    text-align: center;
    background-color: var(--accent-color);
    color: white;
    border-radius: 4px;
    padding: 0 4px;
    margin-right: 8px;
    font-weight: bold;
    font-size: 0.8em;
}
</style>
<div class="section-container">
    <!-- File List -->
    <div class="file-list">
        <div class="file-item new-file">
            <i class="fas fa-plus"></i> Add New Episode
        </div>
        <div class="file-list-content">
            <!-- Episodes will be loaded here -->
            <div class="loading">Loading episodes...</div>
        </div>
    </div>

    <!-- Editor -->
    <div class="editor-container">
        <form id="podcast-form" class="editor-form">
            <input type="hidden" id="id" name="id">
            <div class="form-group">
                <label for="title">Episode Title</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="episode_number">Episode Number</label>
                    <input type="number" id="episode_number" name="episode_number" required>
                </div>
                <div class="form-group">
                    <label for="season_number">Season Number</label>
                    <input type="number" id="season_number" name="season_number" value="1">
                </div>
                <div class="form-group">
                    <label for="publish_date">Publish Date</label>
                    <input type="date" id="publish_date" name="publish_date" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="duration">Duration (HH:MM:SS)</label>
                    <input type="text" id="duration" name="duration" pattern="[0-9]{2}:[0-9]{2}:[0-9]{2}" placeholder="00:30:00" required>
                    <span class="hint">Will be auto-filled when audio is selected</span>
                </div>
                <div class="form-group">
                    <label for="explicit">Explicit Content</label>
                    <select id="explicit" name="explicit">
                        <option value="false">No</option>
                        <option value="true">Yes</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Episode Description</label>
                <textarea id="description" name="description" rows="3" required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="audio_file">Audio File</label>
                    <div style="display: flex; gap: 8px;">
                        <select id="audio_file" name="audio_file" required style="flex: 1;">
                            <option value="">Select Audio File</option>
                            <!-- Audio files will be loaded here -->
                        </select>
                        <button type="button" class="standard-btn" id="upload-audio-btn">
                            <i class="fas fa-upload"></i> Upload
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="episode_type">Episode Type</label>
                    <select id="episode_type" name="episode_type">
                        <option value="full">Full</option>
                        <option value="trailer">Trailer</option>
                        <option value="bonus">Bonus</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="episode_art">Episode Artwork (Optional)</label>
                    <div style="display: flex; gap: 8px;">
                        <select id="episode_art" name="episode_art" style="flex: 1;">
                            <option value="">No Custom Artwork</option>
                            <!-- Image files will be loaded here -->
                        </select>
                        <button type="button" class="standard-btn" id="upload-art-btn">
                            <i class="fas fa-upload"></i> Upload
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="keywords">Keywords</label>
                <input type="text" id="keywords" name="keywords" placeholder="Comma separated">
            </div>

            <div class="form-group">
                <label for="show_notes">Show Notes</label>
                <textarea id="show_notes" name="show_notes" class="code-editor" data-mode="markdown"></textarea>
            </div>
        </form>
        
        <!-- Footer -->
        <footer>
            <div class="buttons">
                <div class="button-group">
                    <button type="submit" form="podcast-form" class="standard-btn">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button type="button" class="delete-btn" id="podcast-delete-btn" style="display: none;">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Direct loading script -->
<script type="text/javascript">
// Immediately executing script to load episodes and media files
(function() {
    console.log('Directly loading episodes...');
    
    // Get the container element
    const container = document.querySelector('.file-list-content');
    
    // Load media files for dropdowns
    loadMediaFiles();
    
    // Set up "Add New Episode" functionality
    document.querySelector('.new-file').addEventListener('click', function() {
        console.log('New episode clicked');
        
        // Reset form
        document.getElementById('podcast-form').reset();
        document.getElementById('id').value = ''; // Clear ID for new episode
        
        // Reset CodeMirror if available
        if (document.querySelector('.code-editor').codemirror) {
            document.querySelector('.code-editor').codemirror.setValue('');
        } else {
            document.getElementById('show_notes').value = '';
        }
        
        // Set default date to today
        document.getElementById('publish_date').valueAsDate = new Date();
        
        // Hide delete button
        document.getElementById('podcast-delete-btn').style.display = 'none';
        
        // Remove active class from all episodes and activate "Add New Episode"
        document.querySelectorAll('.file-item').forEach(i => i.classList.remove('active'));
        document.querySelector('.new-file').classList.add('active');
    });
    
    // Trigger the "Add New Episode" by default to start with an empty form
    setTimeout(() => {
        document.querySelector('.new-file').click();
    }, 100);
    
    // Function to load media files into dropdown selects
    function loadMediaFiles() {
        fetch('/admin/api/uploads.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to load files');
                }
                return response.json();
            })
            .then(files => {
                console.log(`Loaded ${files.length} files from uploads`);
                
                // Filter audio files
                const audioFiles = files.filter(f => /\.(mp3|m4a|wav|ogg|mp4)$/i.test(f));
                const audioSelect = document.getElementById('audio_file');
                
                // Add audio options
                audioSelect.innerHTML = '<option value="">Select Audio File</option>';
                audioFiles.forEach(file => {
                    const option = document.createElement('option');
                    option.value = file;
                    option.textContent = file;
                    audioSelect.appendChild(option);
                });
                
                // Add event listener to fill duration automatically when audio file is selected
                audioSelect.addEventListener('change', function() {
                    if (this.value) {
                        fetchAudioDuration(this.value);
                    }
                });
                
                // Filter image files
                const imageFiles = files.filter(f => /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(f));
                const imageSelect = document.getElementById('episode_art');
                
                // Add image options
                imageSelect.innerHTML = '<option value="">No Custom Artwork</option>';
                imageFiles.forEach(file => {
                    const option = document.createElement('option');
                    option.value = file;
                    option.textContent = file;
                    imageSelect.appendChild(option);
                });
                
                // Set up upload buttons
                setupUploadButtons();
            })
            .catch(error => {
                console.error('Error loading files:', error);
            });
    }
    
    // Function to set up upload buttons
    function setupUploadButtons() {
        // Set up audio upload button
        document.getElementById('upload-audio-btn').addEventListener('click', function() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'audio/*';
            input.onchange = e => {
                const file = e.target.files[0];
                if (file) {
                    uploadFile(file, 'audio');
                }
            };
            input.click();
        });
        
        // Set up artwork upload button
        document.getElementById('upload-art-btn').addEventListener('click', function() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = e => {
                const file = e.target.files[0];
                if (file) {
                    uploadFile(file, 'image');
                }
            };
            input.click();
        });
    }
    
    // Function to upload a file
    function uploadFile(file, type) {
        const formData = new FormData();
        formData.append('file', file);
        
        // Show loading indicator
        const btn = document.getElementById(type === 'audio' ? 'upload-audio-btn' : 'upload-art-btn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        btn.disabled = true;
        
        fetch('/admin/api/uploads.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            if (result.success) {
                // Reload media files
                loadMediaFiles();
                
                // Select newly uploaded file
                setTimeout(() => {
                    if (type === 'audio') {
                        const audioSelect = document.getElementById('audio_file');
                        for (let i = 0; i < audioSelect.options.length; i++) {
                            if (audioSelect.options[i].value === file.name) {
                                audioSelect.selectedIndex = i;
                                fetchAudioDuration(file.name);
                                break;
                            }
                        }
                    } else {
                        const imageSelect = document.getElementById('episode_art');
                        for (let i = 0; i < imageSelect.options.length; i++) {
                            if (imageSelect.options[i].value === file.name) {
                                imageSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }
                }, 500);
            } else {
                alert('Error uploading file: ' + result.error);
            }
        })
        .catch(error => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            console.error('Error:', error);
            alert('Error uploading file');
        });
    }
    
    // Function to fetch audio duration
    function fetchAudioDuration(filename) {
        const audio = new Audio(`/uploads/${filename}`);
        const durationInput = document.getElementById('duration');
        
        // Show loading indicator
        durationInput.value = 'Loading...';
        
        audio.addEventListener('loadedmetadata', function() {
            const duration = audio.duration;
            const hours = Math.floor(duration / 3600);
            const minutes = Math.floor((duration % 3600) / 60);
            const seconds = Math.floor(duration % 60);
            
            // Format as HH:MM:SS
            durationInput.value = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        });
        
        audio.addEventListener('error', function() {
            console.error('Error loading audio file');
            durationInput.value = '00:30:00'; // Default fallback
        });
    }
    
    // Fetch episodes from API
    fetch('/admin/api/podcast.php')
        .then(response => {
            console.log('Direct load - API status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Direct load - Raw response:', text);
            try {
                const episodes = JSON.parse(text);
                console.log('Direct load - Parsed episodes:', episodes);
                
                if (!Array.isArray(episodes) || episodes.length === 0) {
                    container.innerHTML = '<div class="no-items">No episodes found</div>';
                    return;
                }
                
                // Sort episodes by publish date (newest first)
                episodes.sort((a, b) => new Date(b.publish_date) - new Date(a.publish_date));
                
                // Generate HTML
                const html = episodes.map(episode => `
                    <div class="file-item" data-id="${episode.id}">
                        <span class="episode-number">${episode.episode_number}</span> ${episode.title}
                    </div>
                `).join('');
                
                container.innerHTML = html;
                
                // Add click handlers using event listeners
                container.querySelectorAll('.file-item[data-id]').forEach(item => {
                    item.addEventListener('click', function() {
                        // Highlight selected episode
                        document.querySelectorAll('.file-item').forEach(i => i.classList.remove('active'));
                        document.querySelector('.new-file').classList.remove('active');
                        this.classList.add('active');
                        
                        // Load episode data
                        loadEpisode(this.dataset.id);
                    });
                });
            } catch (e) {
                console.error('Direct load - Error parsing JSON:', e);
                container.innerHTML = '<div class="error">Error loading episodes</div>';
            }
        })
        .catch(error => {
            console.error('Direct load - Fetch error:', error);
            container.innerHTML = '<div class="error">Error loading episodes</div>';
        });
        
    // Load episode function
    function loadEpisode(id) {
        fetch(`/admin/api/podcast.php?id=${encodeURIComponent(id)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Episode not found');
                }
                return response.json();
            })
            .then(episode => {
                console.log('Episode loaded:', episode);
                
                // Set form values
                for (const key in episode) {
                    const element = document.getElementById(key);
                    if (element) element.value = episode[key];
                }
                
                // Set show notes in CodeMirror if available
                if (document.querySelector('.code-editor').codemirror) {
                    document.querySelector('.code-editor').codemirror.setValue(episode.show_notes || '');
                } else {
                    document.getElementById('show_notes').value = episode.show_notes || '';
                }
                
                // Show delete button
                document.getElementById('podcast-delete-btn').style.display = 'flex';
            })
            .catch(error => {
                console.error('Error loading episode:', error);
                alert('Error loading episode');
            });
    }
    
    // Set up form submission
    document.getElementById('podcast-form').addEventListener('submit', function(e) {
        e.preventDefault();
        console.log('Form submitted');
        
        // Create FormData from form
        const formData = new FormData(this);
        
        // Add show notes from CodeMirror if available
        if (document.querySelector('.code-editor').codemirror) {
            formData.set('show_notes', document.querySelector('.code-editor').codemirror.getValue());
        }
        
        // If ID is empty, generate a new one
        if (!formData.get('id') || formData.get('id').trim() === '') {
            formData.set('id', generateUniqueId());
        }
        
        console.log('Saving episode with ID:', formData.get('id'));

        // Submit form data to API
        fetch('/admin/api/podcast.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to save episode');
            }
            return response.json();
        })
        .then(result => {
            if (result.success) {
                alert('Episode saved successfully');
                
                // Reload the page to refresh the episode list
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                alert('Failed to save episode');
            }
        })
        .catch(error => {
            console.error('Error saving episode:', error);
            alert('Error saving episode');
        });
    });
    
    // Set up delete button
    document.getElementById('podcast-delete-btn').addEventListener('click', function() {
        const id = document.getElementById('id').value;
        
        if (!id) {
            alert('No episode selected');
            return;
        }
        
        if (confirm('Are you sure you want to delete this episode?')) {
            fetch(`/admin/api/podcast.php?id=${encodeURIComponent(id)}`, {
                method: 'DELETE'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to delete episode');
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    alert('Episode deleted successfully');
                    
                    // Reload the page to refresh the episode list
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    alert('Failed to delete episode');
                }
            })
            .catch(error => {
                console.error('Error deleting episode:', error);
                alert('Error deleting episode');
            });
        }
    });
    
    // Helper function to generate a unique ID
    function generateUniqueId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2, 5);
    }
})();
</script> 