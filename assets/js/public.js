/**
 * Gauge Freedom Journal - Public JavaScript
 * Handles frontend form submissions and interactions
 */

(function($) {
    'use strict';

    /**
     * Manuscript Submission Form
     */
    function initSubmissionForm() {
        const $form = $('#gfj-submission-form');

        if (!$form.length) return;

        $form.on('submit', function(e) {
            e.preventDefault();

            const $submitBtn = $(this).find('button[type="submit"]');
            const originalText = $submitBtn.text();

            // Validate required fields
            if (!validateSubmissionForm($form)) {
                return false;
            }

            // Prepare form data with files
            const formData = new FormData(this);
            formData.append('action', 'gfj_submit_manuscript');

            // Disable submit button and show loading
            $submitBtn.prop('disabled', true).text('Uploading files...');

            // Show progress indicator
            showProgress($form, 'Uploading manuscript files...');

            // Submit via AJAX
            $.ajax({
                url: gfjAjax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    // Upload progress
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percentComplete = (e.loaded / e.total) * 100;
                            updateProgress($form, percentComplete, 'Uploading: ' + Math.round(percentComplete) + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    if (response.success) {
                        // Show enhanced success modal
                        showSubmissionSuccess(response.data);

                        // Redirect to dashboard after countdown
                        let countdown = 5;
                        const countdownInterval = setInterval(function() {
                            countdown--;
                            $('.submission-countdown').text(countdown);
                            if (countdown <= 0) {
                                clearInterval(countdownInterval);
                                window.location.href = response.data.redirect_url;
                            }
                        }, 1000);
                    } else {
                        showError($form, response.data.message || 'Submission failed. Please try again.');
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    let errorMsg = 'Network error. Please check your connection and try again.';

                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    }

                    showError($form, errorMsg);
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });

            return false;
        });
    }

    /**
     * Validate submission form
     */
    function validateSubmissionForm($form) {
        let isValid = true;
        const errors = [];

        // Check required text fields
        $form.find('[required]').each(function() {
            const $field = $(this);
            if (!$field.val() || $field.val().trim() === '') {
                isValid = false;
                $field.addClass('error');
                errors.push($field.attr('name') + ' is required');
            } else {
                $field.removeClass('error');
            }
        });

        // Check required files
        const requiredFiles = ['blinded_file', 'full_file', 'latex_sources'];
        requiredFiles.forEach(function(fieldName) {
            const fileInput = $form.find('[name="' + fieldName + '"]')[0];
            if (fileInput && !fileInput.files.length) {
                isValid = false;
                $(fileInput).addClass('error');
                errors.push(fieldName.replace('_', ' ') + ' is required');
            } else if (fileInput) {
                $(fileInput).removeClass('error');
            }
        });

        // Validate file sizes
        const fileSizeLimits = {
            'blinded_file': 52428800,    // 50 MB
            'full_file': 52428800,       // 50 MB
            'latex_sources': 104857600,  // 100 MB
            'car_file': 10485760         // 10 MB
        };

        Object.keys(fileSizeLimits).forEach(function(fieldName) {
            const fileInput = $form.find('[name="' + fieldName + '"]')[0];
            if (fileInput && fileInput.files.length) {
                const file = fileInput.files[0];
                const limit = fileSizeLimits[fieldName];

                if (file.size > limit) {
                    isValid = false;
                    $(fileInput).addClass('error');
                    errors.push(fieldName + ' exceeds maximum size of ' + (limit / 1048576) + 'MB');
                }
            }
        });

        // Validate file types
        const fileTypes = {
            'blinded_file': ['.pdf'],
            'full_file': ['.pdf'],
            'latex_sources': ['.zip'],
            'car_file': ['.json', '.car']
        };

        Object.keys(fileTypes).forEach(function(fieldName) {
            const fileInput = $form.find('[name="' + fieldName + '"]')[0];
            if (fileInput && fileInput.files.length) {
                const file = fileInput.files[0];
                const allowedTypes = fileTypes[fieldName];
                const fileExt = '.' + file.name.split('.').pop().toLowerCase();

                if (!allowedTypes.includes(fileExt)) {
                    isValid = false;
                    $(fileInput).addClass('error');
                    errors.push(fieldName + ' must be ' + allowedTypes.join(' or '));
                }
            }
        });

        if (!isValid) {
            showError($form, 'Please fix the following errors:\n' + errors.join('\n'));
        }

        return isValid;
    }

    /**
     * Review Acceptance/Decline
     */
    function initReviewActions() {
        $(document).on('click', '.gfj-review-action', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const reviewId = $btn.data('review-id');
            const action = $btn.data('action'); // 'accept' or 'decline'

            if (!confirm('Are you sure you want to ' + action + ' this review invitation?')) {
                return;
            }

            $btn.prop('disabled', true).text(action === 'accept' ? 'Accepting...' : 'Declining...');

            $.ajax({
                url: gfjAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'gfj_review_action',
                    nonce: gfjAjax.nonce,
                    review_id: reviewId,
                    review_action: action
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || 'Action failed');
                        $btn.prop('disabled', false).text(action === 'accept' ? 'Accept' : 'Decline');
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                    $btn.prop('disabled', false).text(action === 'accept' ? 'Accept' : 'Decline');
                }
            });
        });
    }

    /**
     * Review Submission Form
     */
    function initReviewSubmissionForm() {
        const $form = $('#gfj-review-form');

        if (!$form.length) return;

        $form.on('submit', function(e) {
            e.preventDefault();

            const $submitBtn = $(this).find('button[type="submit"]');
            const originalText = $submitBtn.text();

            // Validate scores
            let isValid = true;
            const requiredScores = ['relevance_score', 'soundness_score', 'clarity_score', 'openscience_score'];

            requiredScores.forEach(function(scoreName) {
                const scoreValue = $form.find('[name="' + scoreName + '"]').val();
                if (!scoreValue || scoreValue < 1 || scoreValue > 5) {
                    isValid = false;
                    $form.find('[name="' + scoreName + '"]').addClass('error');
                }
            });

            // Validate comments
            if (!$form.find('[name="comments_to_author"]').val().trim()) {
                isValid = false;
                $form.find('[name="comments_to_author"]').addClass('error');
                alert('Comments to author are required');
                return false;
            }

            // Validate recommendation
            if (!$form.find('[name="recommendation"]').val()) {
                isValid = false;
                $form.find('[name="recommendation"]').addClass('error');
                alert('Please select a recommendation');
                return false;
            }

            if (!isValid) {
                alert('Please complete all required fields (scores 1-5)');
                return false;
            }

            if (!confirm('Are you sure you want to submit this review? You cannot edit it after submission.')) {
                return false;
            }

            $submitBtn.prop('disabled', true).text('Submitting review...');

            $.ajax({
                url: gfjAjax.ajaxurl,
                type: 'POST',
                data: $form.serialize() + '&action=gfj_submit_review&nonce=' + gfjAjax.nonce,
                success: function(response) {
                    if (response.success) {
                        showSuccess($form, response.data.message);

                        setTimeout(function() {
                            window.location.href = gfjAjax.dashboardUrl || '/dashboard/';
                        }, 2000);
                    } else {
                        showError($form, response.data.message || 'Submission failed');
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    showError($form, 'Network error. Please try again.');
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });

            return false;
        });

        // Real-time score validation
        $form.find('.score-input').on('change', function() {
            const $input = $(this);
            const value = parseInt($input.val());

            if (value >= 1 && value <= 5) {
                $input.removeClass('error');
            } else {
                $input.addClass('error');
            }
        });
    }

    /**
     * Utility Functions
     */
    function showProgress($context, message) {
        $context.find('.gfj-message').remove();
        $context.prepend('<div class="gfj-message gfj-progress"><div class="progress-bar"><div class="progress-fill" style="width: 0%"></div></div><p>' + message + '</p></div>');
    }

    function updateProgress($context, percent, message) {
        $context.find('.progress-fill').css('width', percent + '%');
        $context.find('.gfj-progress p').text(message);
    }

    function showSuccess($context, message) {
        $context.find('.gfj-message').remove();
        $context.prepend('<div class="gfj-message gfj-success">✅ ' + message + '</div>');

        setTimeout(function() {
            $context.find('.gfj-message').fadeOut();
        }, 5000);
    }

    function showError($context, message) {
        $context.find('.gfj-message').remove();
        $context.prepend('<div class="gfj-message gfj-error">❌ ' + message + '</div>');

        $('html, body').animate({
            scrollTop: $context.offset().top - 100
        }, 500);
    }

    /**
     * Show enhanced submission success modal
     */
    function showSubmissionSuccess(data) {
        const now = new Date();
        const formattedDate = now.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        const modalHtml = `
            <div id="gfj-submission-success-modal" class="gfj-modal gfj-success-modal">
                <div class="gfj-modal-content success-modal-content">
                    <div class="success-animation">
                        <div class="success-checkmark">
                            <div class="check-icon">
                                <span class="icon-line line-tip"></span>
                                <span class="icon-line line-long"></span>
                                <div class="icon-circle"></div>
                                <div class="icon-fix"></div>
                            </div>
                        </div>
                    </div>

                    <div class="success-content">
                        <h1>Manuscript Submitted Successfully!</h1>
                        <p class="success-subtitle">Your submission has been received and is now under review</p>

                        <div class="submission-details">
                            <div class="detail-row">
                                <span class="detail-label">Submission ID:</span>
                                <span class="detail-value">#${data.manuscript_id}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Submitted:</span>
                                <span class="detail-value">${formattedDate}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Status:</span>
                                <span class="detail-value status-badge">Awaiting Triage</span>
                            </div>
                        </div>

                        <div class="next-steps-box">
                            <h3>📋 What Happens Next?</h3>
                            <ol class="steps-list">
                                <li>
                                    <strong>Initial Review (1-7 days)</strong>
                                    <p>Our editorial team will perform an initial triage review of your manuscript.</p>
                                </li>
                                <li>
                                    <strong>Peer Review Decision</strong>
                                    <p>If approved for review, your manuscript will be sent to independent peer reviewers.</p>
                                </li>
                                <li>
                                    <strong>Email Notification</strong>
                                    <p>You'll receive email updates at each stage of the review process.</p>
                                </li>
                            </ol>
                        </div>

                        <div class="confirmation-note">
                            <p>📧 A confirmation email has been sent to your registered email address.</p>
                            <p>You can track your submission status anytime from your dashboard.</p>
                        </div>

                        <div class="redirect-message">
                            <p>Redirecting to your dashboard in <span class="submission-countdown">5</span> seconds...</p>
                            <a href="${data.redirect_url}" class="button button-primary button-large">
                                Go to Dashboard Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        $('#gfj-submission-success-modal').fadeIn(300);

        // Trigger animation
        setTimeout(function() {
            $('.success-checkmark').addClass('animate');
        }, 100);
    }

    function clearMessages($context) {
        $context.find('.gfj-message').remove();
    }

    /**
     * File upload preview
     */
    function initFilePreview() {
        $('input[type="file"]').on('change', function() {
            const $input = $(this);
            const $preview = $input.siblings('.file-preview');

            if (this.files && this.files[0]) {
                const file = this.files[0];
                const size = (file.size / 1048576).toFixed(2); // MB

                if ($preview.length) {
                    $preview.html('📄 ' + file.name + ' (' + size + ' MB)');
                } else {
                    $input.after('<div class="file-preview">📄 ' + file.name + ' (' + size + ' MB)</div>');
                }
            }
        });
    }

    /**
     * Initialize View Manuscript Details modal
     */
    function initViewManuscriptModal() {
        $(document).on('click', '.gfj-view-manuscript', function(e) {
            e.preventDefault();
            const manuscriptId = $(this).data('manuscript-id');

            // Show loading modal
            showManuscriptModal('<div class="gfj-loading">Loading manuscript details...</div>');

            // Fetch manuscript details via AJAX
            $.ajax({
                url: gfjAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'gfj_get_manuscript_details',
                    nonce: gfjAjax.nonce,
                    manuscript_id: manuscriptId
                },
                success: function(response) {
                    if (response.success) {
                        displayManuscriptDetails(response.data);
                    } else {
                        showManuscriptModal('<div class="gfj-error">' + (response.data.message || 'Failed to load manuscript details') + '</div>');
                    }
                },
                error: function() {
                    showManuscriptModal('<div class="gfj-error">Error loading manuscript details. Please try again.</div>');
                }
            });
        });
    }

    /**
     * Show manuscript modal
     */
    function showManuscriptModal(content) {
        // Remove existing modal if any
        $('#gfj-manuscript-modal').remove();

        const modal = $('<div id="gfj-manuscript-modal" class="gfj-modal">' +
            '<div class="gfj-modal-content">' +
            '<span class="gfj-modal-close">&times;</span>' +
            '<div class="gfj-modal-body">' + content + '</div>' +
            '</div></div>');

        $('body').append(modal);
        modal.fadeIn();

        // Close on X click
        modal.find('.gfj-modal-close').on('click', function() {
            modal.fadeOut(function() { $(this).remove(); });
        });

        // Close on outside click
        modal.on('click', function(e) {
            if ($(e.target).is('#gfj-manuscript-modal')) {
                modal.fadeOut(function() { $(this).remove(); });
            }
        });
    }

    /**
     * Display manuscript details in modal
     */
    function displayManuscriptDetails(data) {
        let html = '<div class="gfj-manuscript-details">';
        html += '<h2>' + escapeHtml(data.title) + '</h2>';

        // Status Banner
        html += '<div class="manuscript-status status-' + data.stage.slug + '">';
        html += '<span class="status-label">Status:</span> ';
        html += '<strong>' + escapeHtml(data.stage.name) + '</strong>';

        if (data.status_message) {
            html += '<p class="status-message">' + escapeHtml(data.status_message) + '</p>';
        }
        html += '</div>';

        // Manuscript Info
        html += '<div class="detail-section">';
        html += '<h3>Manuscript Information</h3>';
        html += '<table class="detail-table">';
        html += '<tr><td><strong>Type:</strong></td><td>' + escapeHtml(data.article_type || 'N/A') + '</td></tr>';
        html += '<tr><td><strong>Submitted:</strong></td><td>' + escapeHtml(data.submitted_date) + '</td></tr>';
        if (data.triage_deadline) {
            html += '<tr><td><strong>Triage Deadline:</strong></td><td>' + escapeHtml(data.triage_deadline) + '</td></tr>';
        }
        html += '</table>';
        html += '</div>';

        // Abstract
        if (data.abstract) {
            html += '<div class="detail-section">';
            html += '<h3>Abstract</h3>';
            html += '<div class="detail-content">' + escapeHtml(data.abstract) + '</div>';
            html += '</div>';
        }

        // Keywords
        if (data.keywords) {
            html += '<div class="detail-section">';
            html += '<h3>Keywords</h3>';
            html += '<div class="keyword-tags">';
            const keywords = data.keywords.split(',');
            keywords.forEach(function(keyword) {
                html += '<span class="keyword-tag">' + escapeHtml(keyword.trim()) + '</span>';
            });
            html += '</div>';
            html += '</div>';
        }

        // Files
        html += '<div class="detail-section">';
        html += '<h3>Uploaded Files</h3>';
        html += '<div class="file-list">';

        if (data.files.blinded) {
            html += '<div class="file-item">';
            html += '<span class="file-icon">📄</span> ';
            html += '<a href="' + data.files.blinded.url + '" target="_blank" class="file-link">Blinded Manuscript</a>';
            html += '<span class="file-meta">' + data.files.blinded.size + '</span>';
            html += '</div>';
        }

        if (data.files.full) {
            html += '<div class="file-item">';
            html += '<span class="file-icon">📄</span> ';
            html += '<a href="' + data.files.full.url + '" target="_blank" class="file-link">Full Manuscript</a>';
            html += '<span class="file-meta">' + data.files.full.size + '</span>';
            html += '</div>';
        }

        if (data.files.latex) {
            html += '<div class="file-item">';
            html += '<span class="file-icon">📦</span> ';
            html += '<a href="' + data.files.latex.url + '" target="_blank" class="file-link">LaTeX Sources</a>';
            html += '<span class="file-meta">' + data.files.latex.size + '</span>';
            html += '</div>';
        }

        if (data.files.car) {
            html += '<div class="file-item">';
            html += '<span class="file-icon">🔐</span> ';
            html += '<a href="' + data.files.car.url + '" target="_blank" class="file-link">CAR File</a>';
            html += '<span class="file-meta">' + data.files.car.size + '</span>';
            html += '</div>';
        }

        html += '</div>';
        html += '</div>';

        // Repositories
        if (data.code_repo || data.data_repo) {
            html += '<div class="detail-section">';
            html += '<h3>Code & Data</h3>';
            if (data.code_repo) {
                html += '<p><strong>Code Repository:</strong> <a href="' + escapeHtml(data.code_repo) + '" target="_blank">' + escapeHtml(data.code_repo) + '</a></p>';
            }
            if (data.data_repo) {
                html += '<p><strong>Data Repository:</strong> <a href="' + escapeHtml(data.data_repo) + '" target="_blank">' + escapeHtml(data.data_repo) + '</a></p>';
            }
            html += '</div>';
        }

        // Editor Feedback (if any)
        if (data.editor_feedback) {
            html += '<div class="detail-section feedback-section">';
            html += '<h3>Editor Feedback</h3>';
            html += '<div class="feedback-content">' + escapeHtml(data.editor_feedback) + '</div>';
            html += '</div>';
        }

        // Next Steps
        if (data.next_steps) {
            html += '<div class="detail-section next-steps-section">';
            html += '<h3>Next Steps</h3>';
            html += '<div class="next-steps-content">' + data.next_steps + '</div>';

            // Add Upload Revision button if stage is revision
            if (data.stage.slug === 'revision') {
                html += '<div style="margin-top: 20px; text-align: center;">';
                html += '<button type="button" class="button button-primary button-large gfj-upload-revision-from-modal" data-manuscript-id="' + data.manuscript_id + '">';
                html += '📤 Upload Revised Manuscript';
                html += '</button>';
                html += '</div>';
            }

            html += '</div>';
        }

        html += '</div>';

        showManuscriptModal(html);

        // Add event handler for upload revision button in modal
        $('.gfj-upload-revision-from-modal').on('click', function(e) {
            e.preventDefault();
            const manuscriptId = $(this).data('manuscript-id');
            $('#gfj-manuscript-modal').fadeOut(function() { $(this).remove(); });
            showRevisionUploadModal(manuscriptId);
        });
    }

    /**
     * Escape HTML for safe display
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML.replace(/\n/g, '<br>');
    }

    /**
     * Initialize Revision Upload functionality
     */
    function initRevisionUpload() {
        $(document).on('click', '.gfj-upload-revision', function(e) {
            e.preventDefault();
            const manuscriptId = $(this).data('manuscript-id');
            showRevisionUploadModal(manuscriptId);
        });
    }

    /**
     * Show revision upload modal
     */
    function showRevisionUploadModal(manuscriptId) {
        const modalHtml = `
            <div id="gfj-revision-modal" class="gfj-modal">
                <div class="gfj-modal-content">
                    <span class="gfj-modal-close">&times;</span>
                    <div class="gfj-modal-body">
                        <h2>Upload Revised Manuscript</h2>
                        <p class="revision-instructions">
                            Please upload the revised versions of your manuscript files.
                            All files marked with * are required.
                        </p>

                        <form id="gfj-revision-form" enctype="multipart/form-data">
                            <input type="hidden" name="manuscript_id" value="${manuscriptId}">

                            <div class="form-field">
                                <label for="revision_blinded_file">
                                    <strong>Blinded Manuscript (PDF) *</strong>
                                    <span class="field-description">Updated version without author information</span>
                                </label>
                                <input type="file" id="revision_blinded_file" name="blinded_file" accept=".pdf" required>
                            </div>

                            <div class="form-field">
                                <label for="revision_full_file">
                                    <strong>Full Manuscript (PDF) *</strong>
                                    <span class="field-description">Updated complete manuscript</span>
                                </label>
                                <input type="file" id="revision_full_file" name="full_file" accept=".pdf" required>
                            </div>

                            <div class="form-field">
                                <label for="revision_latex_file">
                                    <strong>LaTeX Sources (ZIP) *</strong>
                                    <span class="field-description">Updated source files</span>
                                </label>
                                <input type="file" id="revision_latex_file" name="latex_sources" accept=".zip" required>
                            </div>

                            <div class="form-field">
                                <label for="revision_car_file">
                                    <strong>CAR File (JSON)</strong>
                                    <span class="field-description">Optional: Updated CAR file</span>
                                </label>
                                <input type="file" id="revision_car_file" name="car_file" accept=".json,.car">
                            </div>

                            <div class="form-field">
                                <label for="revision_notes">
                                    <strong>Revision Notes *</strong>
                                    <span class="field-description">Describe the changes you made in response to the editor feedback</span>
                                </label>
                                <textarea id="revision_notes" name="revision_notes" rows="6" required placeholder="Summarize the revisions you made..."></textarea>
                            </div>

                            <div class="revision-progress" style="display: none;">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 0%"></div>
                                </div>
                                <p class="progress-text">Uploading...</p>
                            </div>

                            <div class="modal-actions">
                                <button type="submit" class="button button-primary button-large">
                                    Submit Revision
                                </button>
                                <button type="button" class="button button-large gfj-cancel-revision">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        const $modal = $('#gfj-revision-modal');
        $modal.fadeIn();

        // Close modal handlers
        $modal.find('.gfj-modal-close, .gfj-cancel-revision').on('click', function() {
            $modal.fadeOut(function() { $(this).remove(); });
        });

        $modal.on('click', function(e) {
            if ($(e.target).is('#gfj-revision-modal')) {
                $modal.fadeOut(function() { $(this).remove(); });
            }
        });

        // Handle form submission
        $('#gfj-revision-form').on('submit', function(e) {
            e.preventDefault();
            handleRevisionSubmit($(this));
        });
    }

    /**
     * Handle revision form submission
     */
    function handleRevisionSubmit($form) {
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.text();

        // Validate files
        const requiredFiles = ['blinded_file', 'full_file', 'latex_sources'];
        let hasAllFiles = true;

        requiredFiles.forEach(function(fieldName) {
            const fileInput = $form.find('[name="' + fieldName + '"]')[0];
            if (!fileInput || !fileInput.files.length) {
                hasAllFiles = false;
            }
        });

        if (!hasAllFiles) {
            alert('Please upload all required files.');
            return;
        }

        // Validate revision notes
        const notes = $form.find('[name="revision_notes"]').val().trim();
        if (!notes) {
            alert('Please provide revision notes describing your changes.');
            return;
        }

        // Prepare form data
        const formData = new FormData($form[0]);
        formData.append('action', 'gfj_upload_revision');
        formData.append('nonce', gfjAjax.nonce);

        // Show progress
        $submitBtn.prop('disabled', true).text('Uploading...');
        $form.find('.revision-progress').show();

        // Submit via AJAX
        $.ajax({
            url: gfjAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        $form.find('.progress-fill').css('width', percentComplete + '%');
                        $form.find('.progress-text').text('Uploading: ' + Math.round(percentComplete) + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $form.find('.gfj-modal-body').html(
                        '<div class="revision-success">' +
                        '<h2>✅ Revision Uploaded Successfully!</h2>' +
                        '<p>' + (response.data.message || 'Your revised manuscript has been submitted.') + '</p>' +
                        '<p>The editorial team has been notified and will review your revision.</p>' +
                        '</div>'
                    );

                    // Reload page after 2 seconds
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    alert('Error: ' + (response.data.message || 'Failed to upload revision. Please try again.'));
                    $submitBtn.prop('disabled', false).text(originalText);
                    $form.find('.revision-progress').hide();
                }
            },
            error: function(xhr, status, error) {
                let errorMsg = 'Network error. Please check your connection and try again.';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg = xhr.responseJSON.data.message;
                }
                alert('Error: ' + errorMsg);
                $submitBtn.prop('disabled', false).text(originalText);
                $form.find('.revision-progress').hide();
            }
        });
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initSubmissionForm();
        initReviewActions();
        initReviewSubmissionForm();
        initFilePreview();
        initViewManuscriptModal();
        initRevisionUpload();

        // Clear error styling on input
        $(document).on('input change', '.error', function() {
            $(this).removeClass('error');
        });
    });

})(jQuery);
