/**
 * ACF Clone Fields - Admin JavaScript
 *
 * Handles modal functionality, AJAX requests, and user interface interactions
 * for the ACF field cloning system.
 *
 * @package SilverAssist\ACFCloneFields
 * @since 1.0.0
 * @version 1.1.0
 * @author Silver Assist
 * 
 * ============================================================================
 * DATA STRUCTURES REFERENCE
 * ============================================================================
 * 
 * This file interacts with several server endpoints. Below are the expected
 * data structures for all AJAX communications:
 * 
 * 1. LOAD SOURCE POSTS (action: acf_clone_get_source_posts)
 *    Response: {
 *      success: boolean,
 *      data: {
 *        posts: Array<{
 *          id: number,
 *          title: string,
 *          stats: {
 *            total_fields: number,
 *            cloneable_fields: number,
 *            fields_with_values: number,
 *            group_fields: number,
 *            repeater_fields: number,
 *            total_groups: number
 *          }
 *        }>,
 *        target_post: {
 *          id: number,
 *          title: string,
 *          stats: {...same as above}
 *        },
 *        message?: string
 *      }
 *    }
 * 
 * 2. LOAD SOURCE FIELDS (action: acf_clone_get_source_fields)
 *    Response: {
 *      success: boolean,
 *      data: {
 *        fields: Array<{
 *          key: string,
 *          title: string,
 *          fields: Array<{
 *            key: string,
 *            name: string,
 *            label: string,
 *            type: string,
 *            has_value: boolean,
 *            will_overwrite: boolean
 *          }>
 *        }>,
 *        source_post: { id: number, title: string, stats: {...} },
 *        target_post: { id: number, title: string, stats: {...} },
 *        message?: string
 *      }
 *    }
 * 
 * 3. EXECUTE CLONE (action: acf_clone_execute_clone)
 *    Response: {
 *      success: boolean,
 *      data: {
 *        cloned_count: number,
 *        skipped_count: number,
 *        cloned_fields: Array<string>,
 *        skipped_fields: Array<string>,
 *        source_post: { id: number, title: string },
 *        target_post: { id: number, title: string },
 *        backup_info?: { backup_id: string, created_at: string },
 *        operation_summary: {
 *          total_requested: number,
 *          successful: number,
 *          failed: number
 *        },
 *        message?: string
 *      }
 *    }
 * 
 * ============================================================================
 */

(function($) {
    'use strict';

    /**
     * Main ACF Clone Fields object
     */
    const ACFCloneFields = {
        
        // Initialization flag to prevent multiple inits
        initialized: false,
        
        // Configuration
        config: {
            nonce: acfCloneFields.nonce || '',
            ajaxUrl: acfCloneFields.ajaxUrl || '',
            postId: acfCloneFields.postId || 0,
            postType: acfCloneFields.postType || '',
            debugMode: acfCloneFields.debugMode || false
        },

        // State management
        state: {
            modal: null,
            selectedSource: null,
            selectedFields: [],
            sourceFields: {},
            isLoading: false,
            currentStep: 1
        },

        /**
         * Initialize the plugin
         */
        init: function() {
            // Prevent multiple initialization
            if (this.initialized) {
                this.log('ACF Clone Fields already initialized');
                return;
            }
            
            this.bindEvents();
            this.setupModal();
            this.initialized = true;
            this.log('ACF Clone Fields initialized', this.config);
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Meta box button
            $(document).on('click', '.acf-clone-open-modal', this.openModal.bind(this));
            
            // Modal events
            $(document).on('click', '.acf-clone-modal-close, .acf-clone-modal-overlay', this.closeModal.bind(this));
            $(document).on('click', '.acf-clone-modal', function(e) {
                e.stopPropagation();
            });
            
            // Source post selection
            $(document).on('change', 'input[name="acf_clone_source_post"]', this.onSourcePostSelect.bind(this));
            
            // Field group toggle
            $(document).on('click', '.acf-clone-group-header', this.toggleFieldGroup.bind(this));
            
            // Field selection
            $(document).on('change', '.acf-clone-field-checkbox input', this.onFieldSelect.bind(this));
            
            // Modal buttons
            $(document).on('click', '.acf-clone-next-step', this.nextStep.bind(this));
            $(document).on('click', '.acf-clone-prev-step', this.previousStep.bind(this));
            $(document).on('click', '.acf-clone-execute', this.executeClone.bind(this));
            $(document).on('click', '.acf-clone-cancel', this.closeModal.bind(this));

            // Keyboard shortcuts
            $(document).on('keydown', this.handleKeyboard.bind(this));

            // Window resize for modal positioning
            $(window).on('resize', this.positionModal.bind(this));
        },

        /**
         * Setup modal HTML structure
         */
        setupModal: function() {
            // Check if modal already exists
            let existingModal = $('#acf-clone-modal-overlay');
            if (existingModal.length) {
                this.state.modal = existingModal;
                return;
            }

            const modalHTML = `
                <div id="acf-clone-modal-overlay" class="acf-clone-modal-overlay">
                    <div class="acf-clone-modal">
                        <div class="acf-clone-modal-header">
                            <h2 class="acf-clone-modal-title">Clone Custom Fields</h2>
                            <button class="acf-clone-modal-close">&times;</button>
                        </div>
                        <div class="acf-clone-modal-body">
                            <!-- Dynamic content will be loaded here -->
                        </div>
                        <div class="acf-clone-modal-footer">
                            <button type="button" class="button acf-clone-cancel">Cancel</button>
                            <button type="button" class="button button-primary acf-clone-next-step" disabled>Next</button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHTML);
            this.state.modal = $('#acf-clone-modal-overlay');
            
            // Verify modal was created successfully
            if (!this.state.modal.length) {
                console.error('[ACF Clone Fields] Failed to create modal');
            }
        },

        /**
         * Open the clone modal
         */
        openModal: function(e) {
            e.preventDefault();
            
            this.log('Opening clone modal');
            this.resetState();
            this.loadSourcePosts();
            this.showModal();
        },

        /**
         * Close the modal
         */
        closeModal: function(e) {
            if (e) {
                e.preventDefault();
            }
            
            this.log('Closing clone modal');
            this.hideModal();
            this.resetState();
        },

        /**
         * Show modal with animation
         */
        showModal: function() {
            if (!this.state.modal || !this.state.modal.length) {
                this.setupModal();
            }
            this.state.modal.fadeIn(200);
            this.positionModal();
            $('body').addClass('modal-open');
        },

        /**
         * Hide modal with animation
         */
        hideModal: function() {
            if (!this.state.modal || !this.state.modal.length) {
                return;
            }
            this.state.modal.fadeOut(200);
            $('body').removeClass('modal-open');
        },

        /**
         * Position modal in center of screen
         */
        positionModal: function() {
            // CSS handles positioning, but we can add scroll handling if needed
            const modal = $('.acf-clone-modal');
            const overlay = this.state.modal;
            
            if (modal.height() > $(window).height() * 0.9) {
                modal.css({
                    'max-height': $(window).height() * 0.9,
                    'overflow-y': 'auto'
                });
            }
        },

        /**
         * Reset modal state
         */
        resetState: function() {
            this.state.selectedSource = null;
            this.state.selectedFields = [];
            this.state.sourceFields = {};
            this.state.currentStep = 1;
            this.state.isLoading = false;
        },

        /**
         * Load source posts via AJAX
         * 
         * Server Response Structure:
         * @typedef {Object} LoadSourcePostsResponse
         * @property {boolean} success - Whether the request was successful
         * @property {Object} data - Response data object
         * @property {Array} data.posts - Array of available posts
         * @property {number} data.posts[].id - Post ID
         * @property {string} data.posts[].title - Post title
         * @property {Object} data.posts[].stats - Field statistics
         * @property {number} data.posts[].stats.total_fields - Total number of fields
         * @property {number} data.posts[].stats.cloneable_fields - Number of cloneable fields
         * @property {number} data.posts[].stats.fields_with_values - Number of fields with values
         * @property {number} data.posts[].stats.group_fields - Number of group fields
         * @property {number} data.posts[].stats.repeater_fields - Number of repeater fields
         * @property {number} data.posts[].stats.total_groups - Total number of field groups
         * @property {Object} data.target_post - Current target post info
         * @property {number} data.target_post.id - Target post ID
         * @property {string} data.target_post.title - Target post title
         * @property {Object} data.target_post.stats - Target post field statistics
         * @property {string} [data.message] - Error message if success is false
         */
        loadSourcePosts: function() {
            this.log('Loading source posts');
            this.showLoading('Loading available posts...');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acf_clone_get_source_posts',
                    nonce: this.config.nonce,
                    post_id: this.config.postId,
                    post_type: this.config.postType
                },
                success: this.onSourcePostsLoaded.bind(this),
                error: this.onAjaxError.bind(this)
            });
        },

        /**
         * Handle source posts loaded
         * 
         * @param {LoadSourcePostsResponse} response - Server response from loadSourcePosts
         * @see loadSourcePosts for complete response structure
         */
        onSourcePostsLoaded: function(response) {
            this.log('Source posts loaded', response);

            if (!response.success) {
                this.showError(response.data.message || 'Failed to load source posts');
                return;
            }

            // Pass posts array - each item has: {id, title, stats: {total_fields, cloneable_fields, etc.}}
            this.renderSourcePostsStep(response.data.posts);
        },

        /**
         * Render source posts selection step
         */
        renderSourcePostsStep: function(posts) {
            if (!posts || posts.length === 0) {
                this.showError('No source posts available for cloning.');
                return;
            }

            let html = `
                <div class="acf-clone-step" data-step="1">
                    <h3 class="acf-clone-step-title">Step 1: Select Source Post</h3>
                    <p class="acf-clone-step-description">Choose the post you want to copy custom fields from:</p>
                    
                    <div class="acf-clone-source-posts">
            `;

            posts.forEach(post => {
                html += `
                    <div class="acf-clone-source-post" data-post-id="${post.id}">
                        <input type="radio" name="acf_clone_source_post" value="${post.id}" id="source_${post.id}">
                        <div class="acf-clone-post-info">
                            <div class="acf-clone-post-title">${this.escapeHtml(post.title)}</div>
                            <div class="acf-clone-post-meta">
                                ID: ${post.id} | Modified: ${post.modified}
                            </div>
                        </div>
                        <div class="acf-clone-post-stats">
                            <span class="acf-clone-post-field-count">${post.field_count}</span> fields
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;

            this.setModalBody(html);
            this.updateFooterButtons([
                { text: 'Cancel', class: 'button acf-clone-cancel' },
                { text: 'Next', class: 'button button-primary acf-clone-next-step', disabled: true }
            ]);
        },

        /**
         * Handle source post selection
         */
        onSourcePostSelect: function(e) {
            const postId = $(e.target).val();
            this.state.selectedSource = parseInt(postId);
            
            this.log('Source post selected:', postId);
            
            // Enable next button
            $('.acf-clone-next-step').prop('disabled', false);
            
            // Update visual selection
            $('.acf-clone-source-post').removeClass('selected');
            $(e.target).closest('.acf-clone-source-post').addClass('selected');
        },

        /**
         * Move to next step
         */
        nextStep: function(e) {
            e.preventDefault();
            
            if (this.state.currentStep === 1) {
                if (!this.state.selectedSource) {
                    this.showNotice('Please select a source post first.');
                    return;
                }
                this.loadSourceFields();
            } else if (this.state.currentStep === 2) {
                this.showConfirmationStep();
            }
        },

        /**
         * Move to previous step
         */
        previousStep: function(e) {
            e.preventDefault();
            
            if (this.state.currentStep === 2) {
                this.state.currentStep = 1;
                this.loadSourcePosts();
            } else if (this.state.currentStep === 3) {
                this.state.currentStep = 2;
                this.renderFieldsStep();
            }
        },

        /**
         * Load source fields via AJAX
         * 
         * Server Response Structure:
         * @typedef {Object} LoadSourceFieldsResponse
         * @property {boolean} success - Whether the request was successful
         * @property {Object} data - Response data object
         * @property {Array} data.fields - Array of field groups from source post
         * @property {string} data.fields[].key - Field group key (e.g., 'location_fields_group')
         * @property {string} data.fields[].title - Field group title (e.g., 'Location Information')
         * @property {Array} data.fields[].fields - Array of individual fields in this group
         * @property {string} data.fields[].fields[].key - Field key
         * @property {string} data.fields[].fields[].name - Field name
         * @property {string} data.fields[].fields[].label - Field label
         * @property {string} data.fields[].fields[].type - Field type (text, textarea, image, etc.)
         * @property {boolean} data.fields[].fields[].has_value - Whether field has a value in source
         * @property {boolean} data.fields[].fields[].will_overwrite - Whether field will overwrite existing data
         * @property {Object} data.source_post - Source post information
         * @property {number} data.source_post.id - Source post ID
         * @property {string} data.source_post.title - Source post title
         * @property {Object} data.source_post.stats - Source post field statistics
         * @property {Object} data.target_post - Target post information
         * @property {number} data.target_post.id - Target post ID
         * @property {string} data.target_post.title - Target post title
         * @property {Object} data.target_post.stats - Target post field statistics
         * @property {string} [data.message] - Error message if success is false
         */
        loadSourceFields: function() {
            this.log('Loading source fields for post:', this.state.selectedSource);
            this.showLoading('Loading custom fields...');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acf_clone_get_source_fields',
                    nonce: this.config.nonce,
                    target_post_id: this.config.postId,
                    source_post_id: this.state.selectedSource
                },
                success: this.onSourceFieldsLoaded.bind(this),
                error: this.onAjaxError.bind(this)
            });
        },

        /**
         * Handle source fields loaded
         * 
         * @param {LoadSourceFieldsResponse} response - Server response from loadSourceFields
         * @see loadSourceFields for complete response structure
         */
        onSourceFieldsLoaded: function(response) {
            this.log('Source fields loaded', response);

            if (!response.success) {
                this.showError(response.data.message || 'Failed to load source fields');
                return;
            }

            // Store fields array - each item has: {key, title, fields: [{key, name, label, type, has_value, will_overwrite}]}
            this.state.sourceFields = response.data.fields;
            this.state.currentStep = 2;
            this.renderFieldsStep();
        },

        /**
         * Render fields selection step
         */
        renderFieldsStep: function() {
            const fieldGroups = this.state.sourceFields;

            if (!fieldGroups || !Array.isArray(fieldGroups) || fieldGroups.length === 0) {
                this.showError('No custom fields found in the selected post.');
                return;
            }

            let html = `
                <div class="acf-clone-step" data-step="2">
                    <h3 class="acf-clone-step-title">Step 2: Select Fields to Clone</h3>
                    <p class="acf-clone-step-description">Choose which custom fields you want to copy:</p>
                    
                    <div class="acf-clone-field-groups">
            `;

            fieldGroups.forEach((group, index) => {
                html += this.renderFieldGroup(group.key || 'group_' + index, group);
            });

            html += `
                    </div>
                    
                    <div class="acf-clone-selection-summary" style="display: none;">
                        <h4>Selection Summary</h4>
                        <div class="acf-clone-summary-stats">
                            <div class="acf-clone-summary-stat">
                                Fields: <span class="acf-clone-summary-stat-value" id="selected-count">0</span>
                            </div>
                            <div class="acf-clone-summary-stat">
                                Groups: <span class="acf-clone-summary-stat-value" id="groups-count">0</span>
                            </div>
                            <div class="acf-clone-summary-stat">
                                Conflicts: <span class="acf-clone-summary-stat-value" id="conflicts-count">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            this.setModalBody(html);
            this.updateFooterButtons([
                { text: 'Back', class: 'button acf-clone-prev-step' },
                { text: 'Next', class: 'button button-primary acf-clone-next-step', disabled: true }
            ]);

            // Expand first group by default
            $('.acf-clone-group-header').first().click();
        },

        /**
         * Render individual field group
         */
        renderFieldGroup: function(groupKey, group) {
            let html = `
                <div class="acf-clone-field-group" data-group="${groupKey}">
                    <div class="acf-clone-group-header">
                        <h4 class="acf-clone-group-title">${this.escapeHtml(group.title)}</h4>
                        <span class="acf-clone-group-toggle">+</span>
                    </div>
                    <div class="acf-clone-group-fields">
            `;

            if (group.fields && group.fields.length > 0) {
                group.fields.forEach(field => {
                    html += this.renderField(field, groupKey);
                });
            } else {
                html += '<p class="acf-clone-text-muted">No fields found in this group.</p>';
            }

            html += `
                    </div>
                </div>
            `;

            return html;
        },

        /**
         * Render individual field
         */
        renderField: function(field, groupKey) {
            const hasValue = field.has_value ? 'acf-clone-field-has-value' : 'acf-clone-field-empty';
            const conflictClass = field.will_overwrite ? 'acf-clone-field-conflict' : '';
            
            let html = `
                <div class="acf-clone-field-item ${conflictClass}" data-field="${field.name}">
                    <div class="acf-clone-field-checkbox">
                        <input type="checkbox" 
                               id="field_${groupKey}_${field.name}" 
                               value="${field.name}" 
                               data-group="${groupKey}">
                    </div>
                    <div class="acf-clone-field-info">
                        <div class="acf-clone-field-label">${this.escapeHtml(field.label)}</div>
                        <div class="acf-clone-field-name">${field.name}</div>
                        <div class="acf-clone-field-type">${field.type}</div>
            `;

            if (field.preview) {
                html += `<div class="acf-clone-field-preview">${this.escapeHtml(field.preview)}</div>`;
            }

            if (field.will_overwrite) {
                html += `<div class="acf-clone-field-conflict-warning">⚠️ Will overwrite existing data</div>`;
            }

            html += `
                    </div>
                    <div class="acf-clone-field-status ${hasValue}">
                        ${field.has_value ? 'Has Value' : 'Empty'}
                    </div>
                </div>
            `;

            return html;
        },

        /**
         * Toggle field group expand/collapse
         */
        toggleFieldGroup: function(e) {
            const $header = $(e.currentTarget);
            const $fields = $header.next('.acf-clone-group-fields');
            const $toggle = $header.find('.acf-clone-group-toggle');

            $fields.toggleClass('expanded');
            $toggle.text($fields.hasClass('expanded') ? '−' : '+');
        },

        /**
         * Handle field selection
         */
        onFieldSelect: function(e) {
            const $checkbox = $(e.target);
            const fieldName = $checkbox.val();
            const groupKey = $checkbox.data('group');
            const isChecked = $checkbox.is(':checked');

            if (isChecked) {
                this.state.selectedFields.push({
                    name: fieldName,
                    group: groupKey
                });
            } else {
                this.state.selectedFields = this.state.selectedFields.filter(
                    field => !(field.name === fieldName && field.group === groupKey)
                );
            }

            this.updateSelectionSummary();
            this.log('Field selection updated', this.state.selectedFields);
        },

        /**
         * Update selection summary
         */
        updateSelectionSummary: function() {
            const selectedCount = this.state.selectedFields.length;
            const $summary = $('.acf-clone-selection-summary');
            
            if (selectedCount > 0) {
                $summary.show();
                
                // Count unique groups
                const uniqueGroups = new Set(this.state.selectedFields.map(f => f.group));
                
                // Count conflicts (fields that will overwrite)
                let conflictCount = 0;
                this.state.selectedFields.forEach(selected => {
                    const $fieldItem = $(`.acf-clone-field-item[data-field="${selected.name}"]`);
                    if ($fieldItem.hasClass('acf-clone-field-conflict')) {
                        conflictCount++;
                    }
                });

                $('#selected-count').text(selectedCount);
                $('#groups-count').text(uniqueGroups.size);
                $('#conflicts-count').text(conflictCount);

                $('.acf-clone-next-step').prop('disabled', false);
            } else {
                $summary.hide();
                $('.acf-clone-next-step').prop('disabled', true);
            }
        },

        /**
         * Show confirmation step
         */
        showConfirmationStep: function() {
            this.state.currentStep = 3;
            
            let html = `
                <div class="acf-clone-step" data-step="3">
                    <h3 class="acf-clone-step-title">Step 3: Confirm Clone Operation</h3>
                    <p class="acf-clone-step-description">Review your selections and execute the clone:</p>
                    
                    <div class="acf-clone-selection-summary">
                        <h4>Selected Fields (${this.state.selectedFields.length})</h4>
                        <div class="acf-clone-summary-list">
            `;

            // Group selections by field group
            const groupedSelections = {};
            this.state.selectedFields.forEach(field => {
                if (!groupedSelections[field.group]) {
                    groupedSelections[field.group] = [];
                }
                groupedSelections[field.group].push(field);
            });

            Object.entries(groupedSelections).forEach(([groupKey, fields]) => {
                const group = this.state.sourceFields.find(g => g.key === groupKey);
                html += `
                    <div class="acf-clone-confirmation-group">
                        <strong>${this.escapeHtml(group.title)}</strong>
                        <ul>
                `;
                
                fields.forEach(field => {
                    const fieldData = group.fields.find(f => f.name === field.name);
                    const conflictIcon = fieldData.will_overwrite ? ' ⚠️' : '';
                    html += `<li>${this.escapeHtml(fieldData.label)} (${fieldData.type})${conflictIcon}</li>`;
                });
                
                html += `
                        </ul>
                    </div>
                `;
            });

            html += `
                        </div>
                    </div>
                    
                    <div class="acf-clone-options">
                        <h4>Clone Options</h4>
                        <div class="acf-clone-option">
                            <label>
                                <input type="checkbox" id="create-backup" checked>
                                Create backup before cloning
                            </label>
                            <div class="acf-clone-option-description">
                                Creates a backup of existing field values that can be restored if needed.
                            </div>
                        </div>
                        <div class="acf-clone-option">
                            <label>
                                <input type="checkbox" id="preserve-empty">
                                Skip empty fields
                            </label>
                            <div class="acf-clone-option-description">
                                Don't copy fields that have no value in the source post.
                            </div>
                        </div>
                    </div>
                </div>
            `;

            this.setModalBody(html);
            this.updateFooterButtons([
                { text: 'Back', class: 'button acf-clone-prev-step' },
                { text: 'Clone Fields', class: 'button button-primary acf-clone-execute' }
            ]);
        },

        /**
         * Execute the clone operation
         * 
         * Sends the final clone request to server with selected fields and options.
         * 
         * Request Data Structure:
         * @typedef {Object} ExecuteCloneRequest
         * @property {string} action - 'acf_clone_execute_clone'
         * @property {string} nonce - Security nonce
         * @property {number} post_id - Target post ID (from this.config.postId)
         * @property {number} source_post_id - Source post ID (from this.state.selectedSource)
         * @property {Array<string>} field_keys - Selected field names array (extracted from this.state.selectedFields)
         * @property {Object} options - Clone operation options
         * @property {boolean} options.create_backup - Whether to create backup before cloning
         * @property {boolean} options.preserve_empty - Whether to preserve empty values
         */
        executeClone: function(e) {
            e.preventDefault();
            
            if (this.state.isLoading) {
                return;
            }

            const options = {
                create_backup: $('#create-backup').is(':checked'),
                preserve_empty: $('#preserve-empty').is(':checked'),
                overwrite_existing: true  // Allow overwriting existing values
            };

            this.log('Executing clone operation', {
                source: this.state.selectedSource,
                fields: this.state.selectedFields,
                options: options
            });

            this.showLoading('Cloning fields...');
            this.state.isLoading = true;

            // Extract field names from selected fields objects
            const fieldKeys = this.state.selectedFields.map(field => field.name);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acf_clone_execute_clone',
                    nonce: this.config.nonce,
                    target_post_id: this.config.postId,
                    source_post_id: this.state.selectedSource,
                    field_keys: fieldKeys,
                    options: options
                },
                success: this.onCloneComplete.bind(this),
                error: this.onAjaxError.bind(this)
            });
        },

        /**
         * Handle clone completion
         * 
         * Server Response Structure:
         * @typedef {Object} ExecuteCloneResponse
         * @property {boolean} success - Whether the clone operation was successful
         * @property {Object} data - Response data object
         * @property {number} data.cloned_count - Number of fields successfully cloned
         * @property {number} data.skipped_count - Number of fields skipped (if any)
         * @property {Array} data.cloned_fields - Array of cloned field names
         * @property {Array} data.skipped_fields - Array of skipped field names (if any)
         * @property {Object} data.source_post - Source post information
         * @property {number} data.source_post.id - Source post ID
         * @property {string} data.source_post.title - Source post title
         * @property {Object} data.target_post - Target post information
         * @property {number} data.target_post.id - Target post ID
         * @property {string} data.target_post.title - Target post title
         * @property {Object} [data.backup_info] - Backup information (if backup was created)
         * @property {string} [data.backup_info.backup_id] - Backup identifier
         * @property {string} [data.backup_info.created_at] - Backup creation timestamp
         * @property {Object} data.operation_summary - Summary of the operation
         * @property {number} data.operation_summary.total_requested - Total fields requested for cloning
         * @property {number} data.operation_summary.successful - Successfully cloned fields
         * @property {number} data.operation_summary.failed - Failed field operations
         * @property {string} [data.message] - Success or error message
         */
        onCloneComplete: function(response) {
            this.state.isLoading = false;
            this.log('Clone operation completed', response);

            if (response.success) {
                // Disable all buttons immediately to prevent double submissions
                this.state.modal.find('.button').prop('disabled', true);
                
                // Access cloned_count from response.data - also available: skipped_count, cloned_fields, operation_summary
                this.showSuccess(
                    `Successfully cloned ${response.data.cloned_count} field(s). ` +
                    `Reloading page to show updated fields...`
                );

                // Update footer to show completion state
                this.updateFooterButtons([
                    { text: 'Reloading...', class: 'button button-primary', disabled: true }
                ]);

                // Reload the page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                this.showError(response.data.message || 'Clone operation failed');
            }
        },

        /**
         * Handle AJAX errors
         * 
         * Standard jQuery AJAX Error Parameters:
         * @typedef {Object} AjaxErrorResponse
         * @property {Object} xhr - XMLHttpRequest object
         * @property {number} xhr.status - HTTP status code (404, 500, etc.)
         * @property {string} xhr.statusText - HTTP status text
         * @property {string} xhr.responseText - Raw response text
         * @property {Object} [xhr.responseJSON] - Parsed JSON response (if applicable)
         * @property {boolean} [xhr.responseJSON.success] - Success flag (usually false for errors)
         * @property {string} [xhr.responseJSON.data] - Error message from server
         * @property {string} status - Request status ('error', 'timeout', 'abort', etc.)
         * @property {string} error - Error message string
         */
        onAjaxError: function(xhr, status, error) {
            this.state.isLoading = false;
            this.log('AJAX error', { xhr, status, error });
            this.showError(`Request failed: ${error}`);
        },

        /**
         * Handle keyboard shortcuts
         */
        handleKeyboard: function(e) {
            if (!this.state.modal.is(':visible')) {
                return;
            }

            // ESC to close modal
            if (e.keyCode === 27) {
                this.closeModal();
            }

            // Enter to proceed
            if (e.keyCode === 13 && !$(e.target).is('textarea')) {
                e.preventDefault();
                const $nextButton = $('.acf-clone-next-step:visible, .acf-clone-execute:visible');
                if ($nextButton.length && !$nextButton.prop('disabled')) {
                    $nextButton.click();
                }
            }
        },

        /**
         * Show loading state
         */
        showLoading: function(message) {
            const html = `
                <div class="acf-clone-loading">
                    ${message || 'Loading...'}
                </div>
            `;
            this.setModalBody(html);
        },

        /**
         * Show error message
         */
        showError: function(message) {
            const html = `
                <div class="acf-clone-error-message">
                    <strong>Error:</strong> ${this.escapeHtml(message)}
                </div>
            `;
            this.setModalBody(html);
        },

        /**
         * Show success message
         */
        showSuccess: function(message) {
            const html = `
                <div class="acf-clone-success-message">
                    <strong>Success:</strong> ${this.escapeHtml(message)}
                </div>
            `;
            this.setModalBody(html);
        },

        /**
         * Show notice message
         */
        showNotice: function(message) {
            // Create temporary notice
            const $notice = $(`
                <div class="notice notice-warning is-dismissible" style="margin: 10px 0;">
                    <p>${this.escapeHtml(message)}</p>
                </div>
            `);
            
            $('.acf-clone-modal-body').prepend($notice);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        },

        /**
         * Set modal body content
         */
        setModalBody: function(html) {
            $('.acf-clone-modal-body').html(html);
        },

        /**
         * Update footer buttons
         */
        updateFooterButtons: function(buttons) {
            const $footer = $('.acf-clone-modal-footer');
            $footer.empty();

            buttons.forEach(button => {
                const disabled = button.disabled ? ' disabled' : '';
                $footer.append(`
                    <button type="button" class="${button.class}"${disabled}>
                        ${button.text}
                    </button>
                `);
            });
        },

        /**
         * Escape HTML for safe output
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * Debug logging
         */
        log: function(message, data) {
            if (this.config.debugMode && console && console.log) {
                console.log('[ACF Clone Fields]', message, data || '');
            }
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Check if we're on a post edit screen with the meta box
        if ($('.acf-clone-fields-metabox').length > 0) {
            ACFCloneFields.init();
        }
    });

    // Expose to global scope for debugging
    window.ACFCloneFields = ACFCloneFields;

})(jQuery);