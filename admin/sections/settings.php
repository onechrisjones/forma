<!-- Settings Section -->
<div class="section-container">
    <!-- File List -->
    <div class="file-list">
        <div class="file-list-content">
            <div class="file-item" data-section="general">
                <i class="fas fa-cog"></i> General
            </div>
            <div class="file-item" data-section="blog">
                <i class="fas fa-blog"></i> Blog
            </div>
            <div class="file-item" data-section="podcast">
                <i class="fas fa-podcast"></i> Podcast
            </div>
            <div class="file-item" data-section="cache">
                <i class="fas fa-bolt"></i> Cache
            </div>
            <div class="file-item" data-section="about">
                <i class="fas fa-info-circle"></i> About
            </div>
        </div>
    </div>

    <!-- Editor -->
    <div class="editor-container">
        <!-- General Settings -->
        <form id="general-settings" class="settings-form section-content">
            <h3>General Settings</h3>
            <div class="form-group">
                <label for="site_title">Site Title</label>
                <input type="text" id="site_title" name="site_title" required>
                <span class="hint">Your website title, used in RSS feeds and browser tabs</span>
            </div>

            <div class="form-group">
                <label for="site_description">Site Description</label>
                <textarea id="site_description" name="site_description" rows="3"></textarea>
                <span class="hint">A brief description of your site, used in RSS feeds and SEO</span>
            </div>

            <div class="form-group">
                <label for="site_url">Site URL</label>
                <input type="url" id="site_url" name="site_url" required>
                <span class="hint">Your full website URL (e.g., https://mysite.com)</span>
            </div>

            <div class="form-group">
                <label for="language">Language</label>
                <input type="text" id="language" name="language" value="en">
                <span class="hint">Primary language code (e.g., en, fr, es)</span>
            </div>

            <div class="form-group">
                <label for="timezone">Timezone</label>
                <select id="timezone" name="timezone">
                    <option value="UTC">UTC</option>
                    <option value="America/New_York">America/New_York</option>
                    <option value="America/Chicago">America/Chicago</option>
                    <option value="America/Denver">America/Denver</option>
                    <option value="America/Los_Angeles">America/Los_Angeles</option>
                    <option value="Europe/London">Europe/London</option>
                    <option value="Europe/Paris">Europe/Paris</option>
                    <option value="Europe/Berlin">Europe/Berlin</option>
                    <option value="Europe/Moscow">Europe/Moscow</option>
                    <option value="Asia/Tokyo">Asia/Tokyo</option>
                    <option value="Asia/Shanghai">Asia/Shanghai</option>
                    <option value="Asia/Kolkata">Asia/Kolkata</option>
                    <option value="Australia/Sydney">Australia/Sydney</option>
                    <option value="Pacific/Auckland">Pacific/Auckland</option>
                </select>
            </div>
        </form>

        <!-- Blog Settings -->
        <form id="blog-settings" class="settings-form section-content">
            <h3>Blog Settings</h3>
            
            <div class="form-group">
                <label for="default_author">Default Author</label>
                <input type="text" id="default_author" name="default_author">
                <span class="hint">Default author name for blog posts</span>
            </div>
            
            <!-- Posts per page is not implemented yet, hiding for now -->
            <!--
            <div class="form-group">
                <label for="posts_per_page">Posts Per Page</label>
                <input type="number" id="posts_per_page" name="posts_per_page" min="1" max="50" value="10">
                <span class="hint">Number of posts to display per page</span>
            </div>
            -->
            
            <div class="form-group">
                <label for="excerpt_length">Excerpt Length</label>
                <input type="number" id="excerpt_length" name="excerpt_length" min="50" max="1000" value="250">
                <span class="hint">Number of characters for post excerpts (used in RSS feeds when no description is provided)</span>
            </div>
            
            <div class="form-group">
                <label for="feed_posts">Feed Posts</label>
                <input type="number" id="feed_posts" name="feed_posts" min="1" max="100" value="20">
                <span class="hint">Number of posts to include in RSS feed</span>
            </div>
        </form>

        <!-- Podcast Settings -->
        <form id="podcast-settings" class="settings-form section-content">
            <h3>Podcast Settings</h3>
            <div class="form-group">
                <label for="podcast_title">Podcast Title</label>
                <input type="text" id="podcast_title" name="title" required>
            </div>

            <div class="form-group">
                <label for="podcast_description">Description</label>
                <textarea id="podcast_description" name="description" rows="3" required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="podcast_author">Author</label>
                    <input type="text" id="podcast_author" name="author" required>
                </div>
                <div class="form-group">
                    <label for="podcast_email">Email</label>
                    <input type="email" id="podcast_email" name="email" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="podcast_language">Language</label>
                    <input type="text" id="podcast_language" name="language" value="en-us">
                </div>
                <div class="form-group">
                    <label for="podcast_explicit">Explicit Content</label>
                    <select id="podcast_explicit" name="explicit">
                        <option value="no">No</option>
                        <option value="yes">Yes</option>
                        <option value="clean">Clean</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="podcast_image">Cover Art</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" id="podcast_image" name="image" required style="flex: 1;">
                    <button type="button" class="standard-btn btn-browse">
                        <i class="fas fa-folder-open"></i> Browse
                    </button>
                    <button type="button" class="standard-btn" id="upload-cover-btn">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="podcast_category">Category</label>
                    <select id="podcast_category" name="category">
                        <option value="">Select Category</option>
                        <!-- Categories will be populated via JavaScript -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="podcast_subcategory">Subcategory</label>
                    <select id="podcast_subcategory" name="subcategory">
                        <option value="">Select Subcategory</option>
                        <!-- Subcategories will be populated via JavaScript -->
                    </select>
                </div>
            </div>
        </form>

        <!-- Cache Settings -->
        <form id="cache-settings" class="settings-form section-content">
            <h3>Cache Settings</h3>
            
            <div class="form-group">
                <label class="switch-label">
                    <span>Enable Page Caching</span>
                    <div class="switch-control">
                        <input type="checkbox" id="cache_enabled" name="enabled">
                        <span class="switch"></span>
                    </div>
                </label>
                <span class="hint">When enabled, page rendering results will be cached for faster loading</span>
            </div>
            
            <div class="form-group">
                <label for="cache_ttl">Cache Duration (seconds)</label>
                <input type="number" id="cache_ttl" name="ttl" min="60" max="2592000" value="3600">
                <span class="hint">How long to keep cached pages before refreshing (3600 = 1 hour)</span>
            </div>
            
            <div class="form-group">
                <label for="cache_excluded_paths">Excluded Paths</label>
                <textarea id="cache_excluded_paths" name="excluded_paths" rows="3" placeholder="One path per line, e.g.: /admin, /dynamic-page"></textarea>
                <span class="hint">Pages that should never be cached (one per line)</span>
            </div>
            
            <div class="cache-status">
                <h4>Cache Status</h4>
                <div class="status-display">
                    <div class="status-item">
                        <span class="label">Cache Size:</span>
                        <span class="value" id="cache_size">Checking...</span>
                    </div>
                    <div class="status-item">
                        <span class="label">Cached Pages:</span>
                        <span class="value" id="cache_count">Checking...</span>
                    </div>
                    <div class="status-item">
                        <span class="label">Last Rebuild:</span>
                        <span class="value" id="cache_last_rebuild">Never</span>
                    </div>
                </div>
            </div>
            
            <div class="cache-files">
                <h4>Cached Files</h4>
                <div id="cache_files_container">
                    <table id="cache_files_table">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Size</th>
                                <th>Last Modified</th>
                            </tr>
                        </thead>
                        <tbody id="cache_files_list">
                            <tr>
                                <td colspan="3" class="text-center">Loading cache files...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="no_cache_files" style="display: none;">
                    <p>No cached files found.</p>
                </div>
            </div>
            
            <div class="server-info">
                <h4>Server Information</h4>
                <div class="status-display">
                    <div class="status-item">
                        <span class="label">PHP Version:</span>
                        <span class="value" id="php_version">Checking...</span>
                    </div>
                    <div class="status-item">
                        <span class="label">Server Software:</span>
                        <span class="value" id="server_software">Checking...</span>
                    </div>
                    <div class="status-item">
                        <span class="label">Cache Directory:</span>
                        <span class="value" id="cache_directory">Checking...</span>
                    </div>
                    <div class="status-item">
                        <span class="label">Directory Writable:</span>
                        <span class="value" id="directory_writable">Checking...</span>
                    </div>
                </div>
            </div>
        </form>

        <!-- About Section -->
        <div id="about-section" class="settings-form section-content">
            <h3>About Forma CMS</h3>
            
            <div class="about-content">
                <div class="about-section">
                    <h4>About</h4>
                    <p>Forma is a modern flat file CMS built with simplicity and flexibility in mind. It provides a clean, intuitive interface for managing your content without the complexity of a database.</p>
                </div>

                <div class="about-section">
                    <h4>Developer</h4>
                    <p>Created by <a href="https://github.com/onechrisjones" target="_blank">Chris Jones</a></p>
                </div>

                <div class="about-section">
                    <h4>Libraries & Technologies</h4>
                    <ul>
                        <li><a href="https://twig.symfony.com/" target="_blank">Twig</a> - Template Engine</li>
                        <li><a href="https://parsedown.org/" target="_blank">Parsedown</a> - Markdown Parser</li>
                        <li><a href="https://fontawesome.com/" target="_blank">Font Awesome</a> - Icons</li>
                        <li><a href="https://codemirror.net/" target="_blank">CodeMirror</a> - Code Editor</li>
                    </ul>
                </div>

                <div class="about-section">
                    <h4>Links</h4>
                    <ul>
                        <li><a href="https://github.com/onechrisjones/forma" target="_blank">GitHub Repository</a></li>
                        <li><a href="https://github.com/onechrisjones/forma/issues" target="_blank">Issue Tracker</a></li>
                        <li><a href="https://github.com/onechrisjones/forma/wiki" target="_blank">Documentation</a></li>
                    </ul>
                </div>

                <div class="about-section">
                    <h4>Version</h4>
                    <p>Forma CMS v1.0.0</p>
                    <div id="version-details" style="display: none; margin-top: 10px;">
                        <span class="version-badge">v<span id="version-number">1.0.0</span></span>
                        <span class="version-date" id="version-date"></span>
                        <span class="version-mode" id="version-mode"></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer>
            <div class="buttons">
                <!-- General Settings Buttons -->
                <div id="general-buttons" class="button-group">
                    <button type="button" class="standard-btn" id="btn-save-general"><i class="small fas fa-save"></i> Save Changes</button>
                </div>
                
                <!-- Blog Settings Buttons -->
                <div id="blog-buttons" class="button-group" style="display: none;">
                    <button type="button" class="standard-btn" id="btn-copy-blog-feed"><i class="small fas fa-copy"></i> Copy Feed URL</button>
                    <button type="button" class="standard-btn" id="btn-regenerate-blog-feed"><i class="small fas fa-sync"></i> Regenerate Feed</button>
                    <button type="button" class="standard-btn" id="btn-save-blog"><i class="small fas fa-save"></i> Save Changes</button>
                </div>
                
                <!-- Podcast Settings Buttons -->
                <div id="podcast-buttons" class="button-group" style="display: none;">
                    <button type="button" class="standard-btn" id="btn-copy-podcast-feed"><i class="small fas fa-copy"></i> Copy Feed URL</button>
                    <button type="button" class="standard-btn" id="btn-regenerate-podcast-feed"><i class="small fas fa-sync"></i> Regenerate Feed</button>
                    <button type="button" class="standard-btn" id="btn-save-podcast"><i class="small fas fa-save"></i> Save Changes</button>
                </div>
                
                <!-- Cache Settings Buttons -->
                <div id="cache-buttons" class="button-group" style="display: none;">
                    <button type="button" class="standard-btn" id="btn-clear-cache"><i class="small fas fa-trash"></i> Clear Cache</button>
                    <button type="button" class="standard-btn" id="btn-rebuild-cache"><i class="small fas fa-sync"></i> Rebuild Cache</button>
                    <button type="button" class="standard-btn" id="btn-save-cache"><i class="small fas fa-save"></i> Save Settings</button>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- File Selection Modal -->
<div id="file-select-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Select Image</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="file-browser">
                <!-- Files will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
/* Upload specific styles that can remain here */
.dropzone-container {
    margin-top: 20px;
    border: 2px dashed var(--border);
    border-radius: 8px;
    padding: .5rem;
    transition: all 0.2s ease;
}
.dropzone-container:hover {
    border-color: var(--primary);
}

/* Settings form styling to replace lost editor-form styles */
.settings-form {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
    padding: 20px;
    padding-bottom: 76px;
    overflow-y: auto;
    margin: 0;
}

/* Simple section visibility for settings */
.settings-form.section-content {
    display: none;
}

.settings-form.section-content.active {
    display: flex;
    flex-direction: column;
}

/* About section styles */
.readme-content {
    margin-top: 20px;
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 15px;
    background-color: var(--card-bg);
}

.readme-content h1, .readme-content h2, .readme-content h3,
.readme-content h4, .readme-content h5, .readme-content h6 {
    color: var(--text-color);
    margin-top: 1em;
    margin-bottom: 0.5em;
}

.readme-content h1 { font-size: 1.6em; }
.readme-content h2 { font-size: 1.4em; }
.readme-content h3 { font-size: 1.2em; }
.readme-content h4 { font-size: 1.1em; }

.readme-content p {
    margin-bottom: 1em;
    line-height: 1.5;
}

.readme-content ul, .readme-content ol {
    margin-left: 1.5em;
    margin-bottom: 1em;
}

.readme-content li {
    margin-bottom: 0.5em;
}

.readme-content code {
    background-color: var(--code-bg);
    padding: 2px 4px;
    border-radius: 3px;
    font-family: monospace;
}

.readme-content pre {
    background-color: var(--code-bg);
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
    margin-bottom: 1em;
}

.readme-content pre code {
    background-color: transparent;
    padding: 0;
}

.readme-content a {
    color: var(--primary);
    text-decoration: none;
}

.readme-content a:hover {
    text-decoration: underline;
}

.system-info {
    margin-top: 20px;
}

.version-badge {
    display: inline-block;
    background-color: var(--primary);
    color: var(--light-text);
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
    margin-right: 8px;
}

.version-date {
    color: var(--muted-text);
    font-size: 12px;
    margin-right: 8px;
}

.version-mode {
    display: inline-block;
    background-color: var(--warning);
    color: var(--dark-text);
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: bold;
}

/* Switch styling for cache toggle */
.switch-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.switch-control {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 24px;
}

.switch-control input {
    opacity: 0;
    width: 0;
    height: 0;
}

.switch {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.switch:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .switch {
    background-color: var(--primary);
}

input:checked + .switch:before {
    transform: translateX(24px);
}

/* Cache status display */
.cache-status {
    margin-top: 20px;
    padding: 15px;
    background-color: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 8px;
}

.status-display {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 10px;
}

.status-item {
    display: flex;
    justify-content: space-between;
}

.status-item .label {
    font-weight: bold;
}

/* Cache files table styling */
.cache-files, .server-info {
    margin-top: 20px;
    padding: 15px;
    background-color: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 8px;
}

#cache_files_table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

#cache_files_table th,
#cache_files_table td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid var(--border);
}

#cache_files_table th {
    background-color: var(--input-bg);
    font-weight: bold;
}

#cache_files_table tr:nth-child(even) {
    background-color: var(--input-bg);
}

#cache_files_table tr:hover {
    background-color: var(--hover-bg);
}

.text-center {
    text-align: center;
}
</style>

<script>
// Make sure we're not conflicting with any other scripts
(function() {
    console.log('Settings module initializing...');
    
    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM ready, initializing settings...');
        initializeSettings();
    });
    
    // If DOM is already loaded, initialize immediately
    if (document.readyState === 'interactive' || document.readyState === 'complete') {
        console.log('DOM already ready, initializing settings immediately...');
        initializeSettings();
    }
    
    function initializeSettings() {
        setupNavigation();
        setupPodcastCategories();
        setupModal();
        loadSettings();
        setupButtonHandlers();
        handleInitialSection();
    }

    function setupNavigation() {
        console.log('Setting up navigation...');
        const items = document.querySelectorAll('.file-list-content .file-item');
        
        items.forEach(item => {
            item.addEventListener('click', function() {
                // Remove active class from all items
                items.forEach(i => i.classList.remove('active'));
                
                // Add active class to clicked item
                this.classList.add('active');
                
                // Get section from data attribute
                const section = this.dataset.section;
                
                // Update URL hash
                window.location.hash = section;
                
                // Show/hide forms based on section
                const sections = document.querySelectorAll('.section-content');
                sections.forEach(s => {
                    if (s.id === `${section}-settings` || s.id === `${section}-section`) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
                
                // Show/hide button groups
                const generalButtons = document.getElementById('general-buttons');
                const blogButtons = document.getElementById('blog-buttons');
                const podcastButtons = document.getElementById('podcast-buttons');
                const cacheButtons = document.getElementById('cache-buttons');
                
                if (generalButtons) generalButtons.style.display = section === 'general' ? 'flex' : 'none';
                if (blogButtons) blogButtons.style.display = section === 'blog' ? 'flex' : 'none';
                if (podcastButtons) podcastButtons.style.display = section === 'podcast' ? 'flex' : 'none';
                if (cacheButtons) cacheButtons.style.display = section === 'cache' ? 'flex' : 'none';
            });
        });
        
        // Listen for hash changes
        window.addEventListener('hashchange', handleHashChange);
    }

    function handleInitialSection() {
        const hash = window.location.hash.slice(1); // Remove the # symbol
        if (hash) {
            const item = document.querySelector(`.file-item[data-section="${hash}"]`);
            if (item) {
                item.click();
            }
        } else {
            // Activate general settings by default
            const items = document.querySelectorAll('.file-list-content .file-item');
            if (items.length > 0) {
                items[0].click();
            }
        }
    }

    function handleHashChange() {
        const hash = window.location.hash.slice(1);
        const item = document.querySelector(`.file-item[data-section="${hash}"]`);
        if (item) {
            item.click();
        }
    }

    function setupPodcastCategories() {
        console.log('Setting up podcast categories...');
        const categories = {
            'Arts': ['Books', 'Design', 'Fashion & Beauty', 'Food', 'Performing Arts', 'Visual Arts'],
            'Business': ['Careers', 'Entrepreneurship', 'Investing', 'Management', 'Marketing', 'Non-Profit'],
            'Comedy': ['Comedy Interviews', 'Improv', 'Stand-Up'],
            'Education': ['Courses', 'How To', 'Language Learning', 'Self-Improvement'],
            'Fiction': ['Comedy Fiction', 'Drama', 'Science Fiction'],
            'Government': ['Local', 'National', 'Non-Profit'],
            'History': ['Ancient History', 'Military History', 'Natural History'],
            'Health & Fitness': ['Alternative Health', 'Fitness', 'Medicine', 'Mental Health', 'Nutrition'],
            'Kids & Family': ['Education for Kids', 'Parenting', 'Pets & Animals', 'Stories for Kids'],
            'Leisure': ['Animation & Manga', 'Automotive', 'Aviation', 'Crafts', 'Games', 'Hobbies'],
            'Music': ['Music Commentary', 'Music History', 'Music Interviews'],
            'News': ['Business News', 'Daily News', 'Entertainment News', 'News Commentary', 'Politics', 'Tech News'],
            'Religion & Spirituality': ['Buddhism', 'Christianity', 'Hinduism', 'Islam', 'Judaism', 'Spirituality'],
            'Science': ['Astronomy', 'Chemistry', 'Earth Sciences', 'Life Sciences', 'Mathematics', 'Natural Sciences', 'Physics', 'Social Sciences'],
            'Society & Culture': ['Documentary', 'Personal Journals', 'Philosophy', 'Places & Travel', 'Relationships'],
            'Sports': ['Baseball', 'Basketball', 'Cricket', 'Fantasy Sports', 'Football', 'Golf', 'Hockey', 'Rugby', 'Soccer', 'Swimming'],
            'Technology': ['Cryptocurrency', 'Gadgets', 'Programming', 'Software How-To', 'Tech News'],
            'True Crime': ['True Crime'],
            'TV & Film': ['After Shows', 'Film History', 'Film Interviews', 'Film Reviews', 'TV Reviews']
        };
        
        const categorySelect = document.getElementById('podcast_category');
        const subcategorySelect = document.getElementById('podcast_subcategory');
        
        if (!categorySelect || !subcategorySelect) {
            console.error('Podcast category selects not found');
            return;
        }
        
        // Populate categories
        Object.keys(categories).forEach(category => {
            const option = document.createElement('option');
            option.value = category;
            option.textContent = category;
            categorySelect.appendChild(option);
        });
        
        // Update subcategories when category changes
        categorySelect.addEventListener('change', () => {
            const category = categorySelect.value;
            subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
            
            if (category && categories[category]) {
                categories[category].forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub;
                    option.textContent = sub;
                    subcategorySelect.appendChild(option);
                });
            }
        });
    }

    function setupModal() {
        console.log('Setting up file modal...');
        const modal = document.getElementById('file-select-modal');
        const closeBtn = modal.querySelector('.modal-close');
        const browseBtn = document.querySelector('.btn-browse');
        
        if (!browseBtn) {
            console.error('Browse button not found');
            return;
        }
        
        // Show modal
        browseBtn.addEventListener('click', () => {
            loadUploadedFiles();
            modal.style.display = 'flex';
        });
        
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
        
        // Set up upload button
        const uploadBtn = document.getElementById('upload-cover-btn');
        if (uploadBtn) {
            uploadBtn.addEventListener('click', () => {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                input.onchange = e => {
                    const file = e.target.files[0];
                    if (file) {
                        uploadFile(file);
                    }
                };
                input.click();
            });
        }
    }

    function loadUploadedFiles() {
        console.log('Loading uploaded files...');
        fetch('/admin/api/uploads.php')
            .then(response => response.json())
            .then(files => {
                const container = document.querySelector('.file-browser');
                container.innerHTML = files
                    .filter(file => file.match(/\.(jpg|jpeg|png|gif)$/i))
                    .map(file => `
                        <div class="file-browser-item" data-file="${file}">
                            <img src="/uploads/${file}" alt="${file}">
                            <div class="filename">${file}</div>
                        </div>
                    `).join('');
                
                // Add click handlers
                container.querySelectorAll('.file-browser-item').forEach(item => {
                    item.addEventListener('click', () => {
                        document.getElementById('podcast_image').value = item.dataset.file;
                        document.getElementById('file-select-modal').style.display = 'none';
                    });
                });
            })
            .catch(error => {
                console.error('Error loading files:', error);
            });
    }

    function uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        
        // Show loading indicator
        const btn = document.getElementById('upload-cover-btn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        btn.disabled = true;
        
        fetch('/admin/api/uploads.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            btn.disabled = false;
            
            if (result.success) {
                document.getElementById('podcast_image').value = file.name;
                btn.innerHTML = '<i class="fas fa-check"></i> Uploaded!';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            } else {
                btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error: ' + (result.error || 'Upload failed');
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            }
        })
        .catch(error => {
            btn.disabled = false;
            console.error('Error:', error);
            btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Network error';
            setTimeout(() => {
                btn.innerHTML = originalText;
            }, 2000);
        });
    }

    function loadSettings() {
        console.log('Loading settings...');
        
        // Load general settings
        fetch('/admin/api/settings.php?section=general')
            .then(response => response.json())
            .then(settings => {
                console.log('Loaded general settings:', settings);
                document.getElementById('site_title').value = settings.title || '';
                document.getElementById('site_description').value = settings.description || '';
                document.getElementById('site_url').value = settings.url || '';
                document.getElementById('language').value = settings.language || 'en';
                document.getElementById('timezone').value = settings.timezone || 'UTC';
            })
            .catch(error => {
                console.error('Error loading general settings:', error);
            });
        
        // Load blog settings
        fetch('/admin/api/settings.php?section=blog')
            .then(response => response.json())
            .then(settings => {
                console.log('Loaded blog settings:', settings);
                document.getElementById('default_author').value = settings.default_author || '';
                document.getElementById('excerpt_length').value = settings.excerpt_length || 250;
                document.getElementById('feed_posts').value = settings.feed_posts || 20;
            })
            .catch(error => {
                console.error('Error loading blog settings:', error);
            });
        
        // Load podcast settings
        fetch('/admin/api/settings.php?section=podcast')
            .then(response => response.json())
            .then(settings => {
                console.log('Loaded podcast settings:', settings);
                document.getElementById('podcast_title').value = settings.title || '';
                document.getElementById('podcast_description').value = settings.description || '';
                document.getElementById('podcast_author').value = settings.author || '';
                document.getElementById('podcast_email').value = settings.email || '';
                document.getElementById('podcast_language').value = settings.language || 'en-us';
                document.getElementById('podcast_explicit').value = settings.explicit || 'no';
                document.getElementById('podcast_image').value = settings.image || '';
                document.getElementById('podcast_category').value = settings.category || '';
                
                // Trigger category change to load subcategories
                if (settings.category) {
                    const event = new Event('change');
                    document.getElementById('podcast_category').dispatchEvent(event);
                    
                    // Set subcategory after options are populated
                    setTimeout(() => {
                        document.getElementById('podcast_subcategory').value = settings.subcategory || '';
                    }, 100);
                }
            })
            .catch(error => {
                console.error('Error loading podcast settings:', error);
            });
            
        // Load about section content
        fetch('/admin/api/settings.php?section=about')
            .then(response => response.json())
            .then(data => {
                console.log('Loaded about section data:', data);
                
                // Update the static content with the README content
                const aboutContent = document.querySelector('#about-section .about-content');
                if (aboutContent && data.content) {
                    // Add the README content to the about section
                    const readmeSection = document.createElement('div');
                    readmeSection.className = 'about-section readme-content';
                    readmeSection.innerHTML = '<h4>Documentation</h4>' + data.content;
                    
                    // Replace any existing readme content or append to the end
                    const existingReadme = aboutContent.querySelector('.readme-content');
                    if (existingReadme) {
                        aboutContent.replaceChild(readmeSection, existingReadme);
                    } else {
                        aboutContent.appendChild(readmeSection);
                    }
                }
                
                // Update version information
                if (data.system && data.system.cms_version) {
                    const versionInfo = document.querySelector('#about-section .about-section:last-child p');
                    if (versionInfo) {
                        versionInfo.textContent = `Forma CMS v${data.system.cms_version}`;
                        
                        // Show version details
                        const versionDetails = document.getElementById('version-details');
                        if (versionDetails) {
                            versionDetails.style.display = 'block';
                            
                            // Update version number
                            const versionNumber = document.getElementById('version-number');
                            if (versionNumber) {
                                versionNumber.textContent = data.system.cms_version;
                            }
                            
                            // Update version date if available
                            if (data.system.version_date) {
                                const versionDate = document.getElementById('version-date');
                                if (versionDate) {
                                    versionDate.textContent = `Released: ${data.system.version_date}`;
                                }
                            }
                            
                            // Show dev mode badge if applicable
                            if (data.system.dev_mode) {
                                const versionMode = document.getElementById('version-mode');
                                if (versionMode) {
                                    versionMode.textContent = 'DEVELOPMENT';
                                    versionMode.style.display = 'inline-block';
                                }
                            }
                        }
                    }
                    
                    // Add system info section
                    const systemInfo = document.createElement('div');
                    systemInfo.className = 'about-section system-info';
                    systemInfo.innerHTML = `
                        <h4>System Information</h4>
                        <ul>
                            <li>PHP Version: ${data.system.php_version}</li>
                            <li>Server: ${data.system.server_software}</li>
                            <li>Database: ${data.system.database_type}</li>
                        </ul>
                    `;
                    
                    // Replace any existing system info or append after version info
                    const existingSystemInfo = aboutContent.querySelector('.system-info');
                    if (existingSystemInfo) {
                        aboutContent.replaceChild(systemInfo, existingSystemInfo);
                    } else {
                        const versionSection = aboutContent.querySelector('.about-section:last-child');
                        if (versionSection) {
                            aboutContent.insertBefore(systemInfo, versionSection.nextSibling);
                        } else {
                            aboutContent.appendChild(systemInfo);
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error loading about section:', error);
            });
        
        // Load cache settings
        fetch('/admin/api/settings.php?section=cache')
            .then(response => response.json())
            .then(settings => {
                console.log('Loaded cache settings:', settings);
                document.getElementById('cache_enabled').checked = settings.enabled === true || settings.enabled === 'true';
                document.getElementById('cache_ttl').value = settings.ttl || 3600;
                document.getElementById('cache_excluded_paths').value = Array.isArray(settings.excluded_paths) 
                    ? settings.excluded_paths.join('\n') 
                    : settings.excluded_paths || '';
                
                // Update cache status info
                if (settings.status) {
                    document.getElementById('cache_size').textContent = settings.status.size || 'Unknown';
                    document.getElementById('cache_count').textContent = settings.status.count || '0';
                    document.getElementById('cache_last_rebuild').textContent = settings.status.last_rebuild || 'Never';
                    
                    // Update server info
                    if (settings.status.server_info) {
                        const serverInfo = settings.status.server_info;
                        document.getElementById('php_version').textContent = serverInfo.php_version || 'Unknown';
                        document.getElementById('server_software').textContent = serverInfo.server_software || 'Unknown';
                        document.getElementById('cache_directory').textContent = serverInfo.cache_directory || 'Unknown';
                        document.getElementById('directory_writable').textContent = serverInfo.directory_writable || 'Unknown';
                    }
                    
                    // Update cache files table
                    const cacheFiles = settings.status.files || [];
                    const cacheFilesList = document.getElementById('cache_files_list');
                    const cacheFilesContainer = document.getElementById('cache_files_container');
                    const noCacheFiles = document.getElementById('no_cache_files');
                    
                    if (cacheFiles.length > 0) {
                        let html = '';
                        cacheFiles.forEach(file => {
                            html += `<tr>
                                <td>${file.path}</td>
                                <td>${file.size}</td>
                                <td>${file.modified}</td>
                            </tr>`;
                        });
                        cacheFilesList.innerHTML = html;
                        cacheFilesContainer.style.display = 'block';
                        noCacheFiles.style.display = 'none';
                    } else {
                        cacheFilesContainer.style.display = 'none';
                        noCacheFiles.style.display = 'block';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading cache settings:', error);
            });
    }

    function setupButtonHandlers() {
        console.log('Setting up button handlers...');
        
        // General settings save button
        const saveGeneralBtn = document.getElementById('btn-save-general');
        if (saveGeneralBtn) {
            console.log('Found save general button, adding handler');
            saveGeneralBtn.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                console.log('=== GENERAL SETTINGS SAVE CLICKED ===');
                console.log('Event target:', event.target);
                console.log('Event currentTarget:', event.currentTarget);
                console.log('Button element:', this);
                console.log('Button ID:', this.id);
                console.log('About to call settings API...');
                
                this.innerHTML = '<i class="small fas fa-spinner fa-spin"></i> Saving...';
                
                const settings = {
                    title: document.getElementById('site_title').value,
                    description: document.getElementById('site_description').value,
                    url: document.getElementById('site_url').value,
                    language: document.getElementById('language').value,
                    timezone: document.getElementById('timezone').value
                };
                
                console.log('Settings data:', settings);
                console.log('Making fetch call to /admin/api/settings.php?section=general');
                
                fetch('/admin/api/settings.php?section=general', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(settings)
                })
                .then(response => {
                    console.log('=== RESPONSE RECEIVED ===');
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    console.log('Response URL:', response.url);
                    return response.text();
                })
                .then(text => {
                    console.log('=== RAW RESPONSE TEXT ===');
                    console.log('Response text:', text);
                    
                    let result;
                    try {
                        result = JSON.parse(text);
                        console.log('=== PARSED JSON ===');
                        console.log('Parsed result:', result);
                    } catch (e) {
                        console.error('=== JSON PARSE ERROR ===');
                        console.error('Parse error:', e);
                        console.error('Raw text that failed to parse:', text);
                        throw new Error('Invalid JSON response: ' + text);
                    }
                    
                    if (result.success) {
                        console.log('=== SUCCESS ===');
                        this.innerHTML = '<i class="small fas fa-check"></i> Saved!';
                        setTimeout(() => {
                            this.innerHTML = '<i class="small fas fa-save"></i> Save Changes';
                        }, 2000);
                    } else {
                        console.log('=== API ERROR ===');
                        console.error('API returned error:', result.error);
                        this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Error: ' + (result.error || 'Unknown error');
                        setTimeout(() => {
                            this.innerHTML = '<i class="small fas fa-save"></i> Save Changes';
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('=== FETCH ERROR ===');
                    console.error('Error saving settings:', error);
                    this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Network error';
                    setTimeout(() => {
                        this.innerHTML = '<i class="small fas fa-save"></i> Save Changes';
                    }, 2000);
                });
            });
        } else {
            console.error('Save general button not found!');
        }
        
        // Blog settings save button
        const saveBlogBtn = document.getElementById('btn-save-blog');
        if (saveBlogBtn) {
            saveBlogBtn.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                console.log('Submitting blog settings...');
                this.innerHTML = '<i class="small fas fa-spinner fa-spin"></i> Saving...';
                
                const settings = {
                    default_author: document.getElementById('default_author').value,
                    excerpt_length: parseInt(document.getElementById('excerpt_length').value) || 250,
                    feed_posts: parseInt(document.getElementById('feed_posts').value) || 20
                };
                
                fetch('/admin/api/settings.php?section=blog', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(settings)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        this.innerHTML = '<i class="small fas fa-check"></i> Saved!';
                        setTimeout(() => {
                            this.innerHTML = '<i class="small fas fa-save"></i> Save Changes';
                        }, 2000);
                    } else {
                        this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Error: ' + (result.error || 'Unknown error');
                        setTimeout(() => {
                            this.innerHTML = '<i class="small fas fa-save"></i> Save Changes';
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error saving blog settings:', error);
                    this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Network error';
                    setTimeout(() => {
                        this.innerHTML = '<i class="small fas fa-save"></i> Save Changes';
                    }, 2000);
                });
            });
        }
        
        // Regenerate feed button
        const regenerateFeedBtn = document.getElementById('btn-regenerate-blog-feed');
        if (regenerateFeedBtn) {
            regenerateFeedBtn.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                console.log('Regenerating RSS feed...');
                this.innerHTML = '<i class="small fas fa-spinner fa-spin"></i> Regenerating...';
                
                fetch('/admin/api/blog.php?action=regenerateFeed', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        this.innerHTML = '<i class="small fas fa-check"></i> Regenerated!';
                        setTimeout(() => {
                            this.innerHTML = '<i class="small fas fa-sync"></i> Regenerate Feed';
                        }, 2000);
                    } else {
                        this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Error';
                        setTimeout(() => {
                            this.innerHTML = '<i class="small fas fa-sync"></i> Regenerate Feed';
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error regenerating feed:', error);
                    this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Network error';
                    setTimeout(() => {
                        this.innerHTML = '<i class="small fas fa-sync"></i> Regenerate Feed';
                    }, 2000);
                });
            });
        }
        
        // Podcast settings save button
        const savePodcastBtn = document.getElementById('btn-save-podcast');
        if (savePodcastBtn) {
            savePodcastBtn.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                console.log('Submitting podcast settings...');
                this.innerHTML = '<i class="small fas fa-spinner fa-spin"></i> Saving...';
                
                const settings = {
                    title: document.getElementById('podcast_title').value,
                    description: document.getElementById('podcast_description').value,
                    author: document.getElementById('podcast_author').value,
                    email: document.getElementById('podcast_email').value,
                    language: document.getElementById('podcast_language').value,
                    explicit: document.getElementById('podcast_explicit').value,
                    image: document.getElementById('podcast_image').value,
                    category: document.getElementById('podcast_category').value,
                    subcategory: document.getElementById('podcast_subcategory').value
                };
                
                fetch('/admin/api/settings.php?section=podcast', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(settings)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        this.innerHTML = '<i class="small fas fa-check"></i> Saved!';
                        setTimeout(() => {
                            this.innerHTML = '<i class="small fas fa-save"></i> Save Changes';
                        }, 2000);
                    } else {
                        this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Error: ' + (result.error || 'Unknown error');
                        setTimeout(() => {
                            this.innerHTML = '<i class="small fas fa-save"></i> Save Changes';
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error saving podcast settings:', error);
                    this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Network error';
                    setTimeout(() => {
                        this.innerHTML = '<i class="small fas fa-save"></i> Save Changes';
                    }, 2000);
                });
            });
        }
        
        // Copy blog feed URL button
        const copyBlogFeedBtn = document.getElementById('btn-copy-blog-feed');
        if (copyBlogFeedBtn) {
            copyBlogFeedBtn.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                const siteUrl = document.getElementById('site_url').value || window.location.origin;
                const feedUrl = `${siteUrl.replace(/\/$/, '')}/feeds/feed.xml`;
                
                navigator.clipboard.writeText(feedUrl).then(() => {
                    this.innerHTML = '<i class="small fas fa-check"></i> Copied!';
                    setTimeout(() => {
                        this.innerHTML = '<i class="small fas fa-copy"></i> Copy Feed URL';
                    }, 2000);
                }).catch(err => {
                    console.error('Could not copy text: ', err);
                    this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Copy failed';
                    setTimeout(() => {
                        this.innerHTML = '<i class="small fas fa-copy"></i> Copy Feed URL';
                    }, 2000);
                });
            });
        }
        
        // Copy podcast feed URL button
        const copyPodcastFeedBtn = document.getElementById('btn-copy-podcast-feed');
        if (copyPodcastFeedBtn) {
            copyPodcastFeedBtn.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                const siteUrl = document.getElementById('site_url').value || window.location.origin;
                const feedUrl = `${siteUrl.replace(/\/$/, '')}/feeds/podcast.xml`;
                
                navigator.clipboard.writeText(feedUrl).then(() => {
                    this.innerHTML = '<i class="small fas fa-check"></i> Copied!';
                    setTimeout(() => {
                        this.innerHTML = '<i class="small fas fa-copy"></i> Copy Feed URL';
                    }, 2000);
                }).catch(err => {
                    console.error('Could not copy text: ', err);
                    this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Copy failed';
                    setTimeout(() => {
                        this.innerHTML = '<i class="small fas fa-copy"></i> Copy Feed URL';
                    }, 2000);
                });
            });
        }
        
        // Regenerate podcast feed button
        const regeneratePodcastFeedBtn = document.getElementById('btn-regenerate-podcast-feed');
        if (regeneratePodcastFeedBtn) {
            regeneratePodcastFeedBtn.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                console.log('Regenerating podcast RSS feed...');
                this.innerHTML = '<i class="small fas fa-spinner fa-spin"></i> Regenerating...';
                
                fetch('/admin/api/podcast.php?action=regenerateFeed', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        this.innerHTML = '<i class="small fas fa-check"></i> Regenerated!';
                        setTimeout(() => {
                            this.innerHTML = '<i class="small fas fa-sync"></i> Regenerate Feed';
                        }, 2000);
                    } else {
                        this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Error';
                        setTimeout(() => {
                            this.innerHTML = '<i class="small fas fa-sync"></i> Regenerate Feed';
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error regenerating podcast feed:', error);
                    this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Network error';
                    setTimeout(() => {
                        this.innerHTML = '<i class="small fas fa-sync"></i> Regenerate Feed';
                    }, 2000);
                });
            });
        }
        
        // Cache settings save button
        const saveCacheBtn = document.getElementById('btn-save-cache');
        if (saveCacheBtn) {
            saveCacheBtn.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                console.log('Submitting cache settings...');
                this.innerHTML = '<i class="small fas fa-spinner fa-spin"></i> Saving...';
                
                const settings = {
                    enabled: document.getElementById('cache_enabled').checked,
                    ttl: parseInt(document.getElementById('cache_ttl').value) || 3600,
                    excluded_paths: document.getElementById('cache_excluded_paths').value.split('\n')
                        .map(line => line.trim())
                        .filter(line => line.length > 0)
                };
                
                fetch('/admin/api/settings.php?section=cache', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(settings)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        this.innerHTML = '<i class="small fas fa-check"></i> Saved!';
                        setTimeout(() => {
                            this.innerHTML = '<i class="small fas fa-save"></i> Save Settings';
                        }, 2000);
                    } else {
                        this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Error: ' + (result.error || 'Unknown error');
                        setTimeout(() => {
                            this.innerHTML = '<i class="small fas fa-save"></i> Save Settings';
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error saving cache settings:', error);
                    this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Network error';
                    setTimeout(() => {
                        this.innerHTML = '<i class="small fas fa-save"></i> Save Settings';
                    }, 2000);
                });
            });
        }
        
        // Clear cache button
        const clearCacheBtn = document.getElementById('btn-clear-cache');
        if (clearCacheBtn) {
            clearCacheBtn.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                console.log('Clearing cache...');
                this.innerHTML = '<i class="small fas fa-spinner fa-spin"></i> Clearing...';
                
                fetch('/admin/api/cache.php?action=clear', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        this.innerHTML = '<i class="small fas fa-check"></i> Cache Cleared!';
                        
                        // Update status if available
                        if (result.status) {
                            document.getElementById('cache_size').textContent = result.status.size || '0 bytes';
                            document.getElementById('cache_count').textContent = result.status.count || '0';
                            
                            // Update server info
                            if (result.status.server_info) {
                                const serverInfo = result.status.server_info;
                                document.getElementById('php_version').textContent = serverInfo.php_version || 'Unknown';
                                document.getElementById('server_software').textContent = serverInfo.server_software || 'Unknown';
                                document.getElementById('cache_directory').textContent = serverInfo.cache_directory || 'Unknown';
                                document.getElementById('directory_writable').textContent = serverInfo.directory_writable || 'Unknown';
                            }
                            
                            // Update cache files table
                            const cacheFiles = result.status.files || [];
                            const cacheFilesList = document.getElementById('cache_files_list');
                            const cacheFilesContainer = document.getElementById('cache_files_container');
                            const noCacheFiles = document.getElementById('no_cache_files');
                            
                            if (cacheFiles.length > 0) {
                                let html = '';
                                cacheFiles.forEach(file => {
                                    html += `<tr>
                                        <td>${file.path}</td>
                                        <td>${file.size}</td>
                                        <td>${file.modified}</td>
                                    </tr>`;
                                });
                                cacheFilesList.innerHTML = html;
                                cacheFilesContainer.style.display = 'block';
                                noCacheFiles.style.display = 'none';
                            } else {
                                cacheFilesContainer.style.display = 'none';
                                noCacheFiles.style.display = 'block';
                            }
                        }
                        
                        setTimeout(() => {
                            this.innerHTML = '<i class="small fas fa-trash"></i> Clear Cache';
                        }, 2000);
                    } else {
                        this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Error';
                        setTimeout(() => {
                            this.innerHTML = '<i class="small fas fa-trash"></i> Clear Cache';
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error clearing cache:', error);
                    this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Network error';
                    setTimeout(() => {
                        this.innerHTML = '<i class="small fas fa-trash"></i> Clear Cache';
                    }, 2000);
                });
            });
        }
        
        // Rebuild cache button
        const rebuildCacheBtn = document.getElementById('btn-rebuild-cache');
        if (rebuildCacheBtn) {
            rebuildCacheBtn.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                console.log('Rebuilding cache...');
                this.innerHTML = '<i class="small fas fa-spinner fa-spin"></i> Rebuilding...';
                
                fetch('/admin/api/cache.php?action=rebuild', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        this.innerHTML = '<i class="small fas fa-check"></i> Cache Rebuilt!';
                        
                        // Update status if available
                        if (result.status) {
                            document.getElementById('cache_size').textContent = result.status.size || '0 bytes';
                            document.getElementById('cache_count').textContent = result.status.count || '0';
                            document.getElementById('cache_last_rebuild').textContent = result.status.last_rebuild || 'Now';
                            
                            // Update server info
                            if (result.status.server_info) {
                                const serverInfo = result.status.server_info;
                                document.getElementById('php_version').textContent = serverInfo.php_version || 'Unknown';
                                document.getElementById('server_software').textContent = serverInfo.server_software || 'Unknown';
                                document.getElementById('cache_directory').textContent = serverInfo.cache_directory || 'Unknown';
                                document.getElementById('directory_writable').textContent = serverInfo.directory_writable || 'Unknown';
                            }
                            
                            // Update cache files table
                            const cacheFiles = result.status.files || [];
                            const cacheFilesList = document.getElementById('cache_files_list');
                            const cacheFilesContainer = document.getElementById('cache_files_container');
                            const noCacheFiles = document.getElementById('no_cache_files');
                            
                            if (cacheFiles.length > 0) {
                                let html = '';
                                cacheFiles.forEach(file => {
                                    html += `<tr>
                                        <td>${file.path}</td>
                                        <td>${file.size}</td>
                                        <td>${file.modified}</td>
                                    </tr>`;
                                });
                                cacheFilesList.innerHTML = html;
                                cacheFilesContainer.style.display = 'block';
                                noCacheFiles.style.display = 'none';
                            } else {
                                cacheFilesContainer.style.display = 'none';
                                noCacheFiles.style.display = 'block';
                            }
                        }
                        
                        setTimeout(() => {
                            this.innerHTML = '<i class="small fas fa-sync"></i> Rebuild Cache';
                        }, 2000);
                    } else {
                        this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Error';
                        setTimeout(() => {
                            this.innerHTML = '<i class="small fas fa-sync"></i> Rebuild Cache';
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error rebuilding cache:', error);
                    this.innerHTML = '<i class="small fas fa-exclamation-triangle"></i> Network error';
                    setTimeout(() => {
                        this.innerHTML = '<i class="small fas fa-sync"></i> Rebuild Cache';
                    }, 2000);
                });
            });
        }
    }
})();
</script> 