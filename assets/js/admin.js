/**
 * Gauge Freedom Journal - Admin JavaScript
 * Handles admin metabox actions and editorial functions
 */

(function($) {
    'use strict';

    /**
     * Triage Decision Submission
     */
    window.gfjSubmitTriage = function() {
        const $metabox = $('#gfj_workflow_triage');
        const manuscriptId = $('#post_ID').val();
        const decision = $('input[name="gfj_triage_decision"]:checked').val();
        const notes = $('#gfj_triage_notes').val();

        // Validation
        if (!decision) {
            alert('Please select a triage decision');
            return;
        }

        if (!notes || notes.trim() === '') {
            alert('Please provide decision notes for the author');
            return;
        }

        // Confirmation
        const decisionText = {
            'approve': 'approve this manuscript for peer review',
            'request_changes': 'request changes to this manuscript',
            'desk_reject': 'reject this manuscript'
        };

        if (!confirm('Are you sure you want to ' + decisionText[decision] + '?\n\nThis decision will be sent to the author.')) {
            return;
        }

        // Disable form
        $metabox.find('input, textarea, button').prop('disabled', true);
        $metabox.find('button').text('Submitting decision...');

        // Submit via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gfj_submit_triage',
                nonce: gfjAdmin.nonce,
                manuscript_id: manuscriptId,
                decision: decision,
                notes: notes
            },
            success: function(response) {
                if (response.success) {
                    showAdminNotice('success', response.data.message || 'Triage decision submitted successfully!');

                    // Reload page to show updated stage
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAdminNotice('error', response.data.message || 'Failed to submit triage decision');
                    $metabox.find('input, textarea, button').prop('disabled', false);
                    $metabox.find('button').text('Submit Triage Decision');
                }
            },
            error: function(xhr, status, error) {
                let errorMsg = 'Network error. Please try again.';

                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg = xhr.responseJSON.data.message;
                }

                showAdminNotice('error', errorMsg);
                $metabox.find('input, textarea, button').prop('disabled', false);
                $metabox.find('button').text('Submit Triage Decision');
            }
        });
    };

    /**
     * Reviewer Invitation
     */
    window.gfjInviteReviewer = function(manuscriptId) {
        const reviewerId = $('#gfj_reviewer_select').val();

        if (!reviewerId) {
            alert('Please select a reviewer');
            return;
        }

        const reviewerName = $('#gfj_reviewer_select option:selected').text();

        if (!confirm('Send review invitation to ' + reviewerName + '?')) {
            return;
        }

        const $btn = $('#gfj_manuscript_reviews').find('button');
        const originalText = $btn.text();
        $btn.prop('disabled', true).text('Sending invitation...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gfj_invite_reviewer',
                nonce: gfjAdmin.nonce,
                manuscript_id: manuscriptId,
                reviewer_id: reviewerId
            },
            success: function(response) {
                if (response.success) {
                    showAdminNotice('success', response.data.message || 'Reviewer invitation sent successfully!');

                    // Reload to show updated reviewer list
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAdminNotice('error', response.data.message || 'Failed to send invitation');
                    $btn.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr, status, error) {
                let errorMsg = 'Network error. Please try again.';

                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg = xhr.responseJSON.data.message;
                }

                showAdminNotice('error', errorMsg);
                $btn.prop('disabled', false).text(originalText);
            }
        });
    };

    /**
     * View Review Details (Modal or Inline)
     */
    function initReviewViewer() {
        $(document).on('click', '.gfj-view-review', function(e) {
            e.preventDefault();

            const reviewId = $(this).data('review-id');

            // Placeholder - implement review detail view
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gfj_get_review',
                    nonce: gfjAdmin.nonce,
                    review_id: reviewId
                },
                success: function(response) {
                    if (response.success) {
                        displayReviewModal(response.data.review);
                    } else {
                        alert('Failed to load review');
                    }
                },
                error: function() {
                    alert('Network error');
                }
            });
        });
    }

    /**
     * Display review in modal
     */
    function displayReviewModal(review) {
        const modalHtml = `
            <div class="gfj-review-modal-overlay">
                <div class="gfj-review-modal">
                    <div class="modal-header">
                        <h2>Review Details</h2>
                        <button class="modal-close" onclick="closeReviewModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <h3>Scores</h3>
                        <table class="review-scores">
                            <tr><td>Relevance:</td><td>${review.relevance_score}/5</td></tr>
                            <tr><td>Soundness:</td><td>${review.soundness_score}/5</td></tr>
                            <tr><td>Clarity:</td><td>${review.clarity_score}/5</td></tr>
                            <tr><td>Open Science:</td><td>${review.openscience_score}/5</td></tr>
                            ${review.impact_score ? '<tr><td>Impact:</td><td>' + review.impact_score + '/5</td></tr>' : ''}
                            ${review.provenance_score ? '<tr><td>Provenance:</td><td>' + review.provenance_score + '/5</td></tr>' : ''}
                        </table>

                        <h3>Recommendation</h3>
                        <p><strong>${review.recommendation}</strong></p>

                        <h3>Comments to Author</h3>
                        <div class="review-comments">${review.comments_to_author}</div>

                        <h3>Comments to Editor (Confidential)</h3>
                        <div class="review-comments">${review.comments_to_editor}</div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
    }

    /**
     * Close review modal
     */
    window.closeReviewModal = function() {
        $('.gfj-review-modal-overlay').fadeOut(300, function() {
            $(this).remove();
        });
    };

    /**
     * Stage transition helper
     */
    function initStageTransition() {
        $('.gfj-change-stage').on('click', function(e) {
            e.preventDefault();

            const manuscriptId = $(this).data('manuscript-id');
            const newStage = $(this).data('stage');
            const stageName = $(this).text();

            if (!confirm('Change manuscript stage to: ' + stageName + '?')) {
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gfj_change_stage',
                    nonce: gfjAdmin.nonce,
                    manuscript_id: manuscriptId,
                    stage: newStage
                },
                success: function(response) {
                    if (response.success) {
                        showAdminNotice('success', 'Stage updated successfully');
                        location.reload();
                    } else {
                        showAdminNotice('error', response.data.message || 'Failed to update stage');
                    }
                },
                error: function() {
                    showAdminNotice('error', 'Network error');
                }
            });
        });
    }

    /**
     * Auto-save draft notes
     */
    function initAutoSave() {
        let saveTimer;

        $('.gfj-autosave').on('input', function() {
            const $field = $(this);
            const fieldName = $field.attr('name');
            const manuscriptId = $('#post_ID').val();

            clearTimeout(saveTimer);

            saveTimer = setTimeout(function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'gfj_autosave',
                        nonce: gfjAdmin.nonce,
                        manuscript_id: manuscriptId,
                        field_name: fieldName,
                        field_value: $field.val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $field.addClass('saved');
                            setTimeout(function() {
                                $field.removeClass('saved');
                            }, 2000);
                        }
                    }
                });
            }, 1000);
        });
    }

    /**
     * Bulk assign reviewers
     */
    function initBulkReviewerAssign() {
        $('#gfj-bulk-assign-reviewers').on('click', function(e) {
            e.preventDefault();

            const selectedManuscripts = [];
            $('.manuscript-checkbox:checked').each(function() {
                selectedManuscripts.push($(this).val());
            });

            if (selectedManuscripts.length === 0) {
                alert('Please select at least one manuscript');
                return;
            }

            const reviewerId = $('#bulk-reviewer-select').val();

            if (!reviewerId) {
                alert('Please select a reviewer');
                return;
            }

            if (!confirm('Assign selected manuscripts to this reviewer?')) {
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gfj_bulk_assign_reviewer',
                    nonce: gfjAdmin.nonce,
                    manuscript_ids: selectedManuscripts,
                    reviewer_id: reviewerId
                },
                success: function(response) {
                    if (response.success) {
                        showAdminNotice('success', 'Reviewers assigned successfully');
                        location.reload();
                    } else {
                        showAdminNotice('error', response.data.message);
                    }
                },
                error: function() {
                    showAdminNotice('error', 'Network error');
                }
            });
        });
    }

    /**
     * File upload validation in metabox
     */
    function initMetaboxFileValidation() {
        $('input[type="file"]').on('change', function() {
            const $input = $(this);
            const file = this.files[0];

            if (!file) return;

            // Check file size
            const maxSizes = {
                'gfj_blinded_file': 52428800,    // 50 MB
                'gfj_full_file': 52428800,       // 50 MB
                'gfj_latex_file': 104857600,     // 100 MB
                'gfj_car_file': 10485760         // 10 MB
            };

            const fieldName = $input.attr('name');
            const maxSize = maxSizes[fieldName];

            if (maxSize && file.size > maxSize) {
                alert('File too large. Maximum size: ' + (maxSize / 1048576) + 'MB');
                $input.val('');
                return;
            }

            // Show file preview
            const fileName = file.name;
            const fileSize = (file.size / 1048576).toFixed(2);

            $input.siblings('.file-info').remove();
            $input.after('<div class="file-info">📄 ' + fileName + ' (' + fileSize + ' MB)</div>');
        });
    }

    /**
     * Show admin notice
     */
    function showAdminNotice(type, message) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';

        const $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');

        $('.wrap h1').after($notice);

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);

        // Scroll to notice
        $('html, body').animate({
            scrollTop: $notice.offset().top - 100
        }, 500);
    }

    /**
     * Confirm before leaving page with unsaved changes
     */
    function initUnsavedChangesWarning() {
        let hasUnsavedChanges = false;

        $('form').on('change', 'input, textarea, select', function() {
            hasUnsavedChanges = true;
        });

        $('form').on('submit', function() {
            hasUnsavedChanges = false;
        });

        $(window).on('beforeunload', function() {
            if (hasUnsavedChanges) {
                return 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
    }

    /**
     * Initialize View Review button functionality
     */
    function initViewReviewButton() {
        $(document).on('click', '.gfj-view-review-btn', function(e) {
            e.preventDefault();
            console.log('View Review button clicked');

            const reviewId = $(this).data('review-id');
            console.log('Review ID:', reviewId);

            if (!reviewId) {
                alert('Error: Review ID is missing');
                return;
            }

            // Show loading modal
            showReviewModal('<div class="gfj-loading" style="text-align: center; padding: 40px;">Loading review details...</div>');

            // Fetch review details via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gfj_get_review_details',
                    nonce: gfjAdmin.nonce,
                    review_id: reviewId
                },
                success: function(response) {
                    console.log('AJAX response:', response);
                    if (response.success) {
                        displayReviewDetails(response.data);
                    } else {
                        showReviewModal('<div class="error" style="padding: 20px; color: #d63638;">' + (response.data.message || 'Failed to load review') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', xhr, status, error);
                    showReviewModal('<div class="error" style="padding: 20px; color: #d63638;">Network error loading review details. Check console for details.</div>');
                }
            });
        });
    }

    /**
     * Show review modal
     */
    function showReviewModal(content) {
        // Remove existing modal if any
        $('#gfj-review-modal').remove();

        const modal = $('<div id="gfj-review-modal" class="gfj-modal"><div class="gfj-modal-content">' +
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
            if ($(e.target).is('#gfj-review-modal')) {
                modal.fadeOut(function() { $(this).remove(); });
            }
        });
    }

    /**
     * Display review details in modal
     */
    function displayReviewDetails(review) {
        console.log('displayReviewDetails called with:', review);

        if (!review) {
            showReviewModal('<div class="error" style="padding: 20px; color: #d63638;">Error: No review data received</div>');
            return;
        }

        let html = '<div class="gfj-review-details">';
        html += '<h2>Peer Review Details</h2>';

        // Reviewer info
        html += '<div class="review-section">';
        html += '<h3>Reviewer</h3>';
        html += '<p><strong>' + (review.reviewer_name ? escapeHtml(review.reviewer_name) : 'Unknown') + '</strong></p>';
        html += '<p>Submitted: ' + (review.submitted_at ? escapeHtml(review.submitted_at) : 'N/A') + '</p>';
        html += '</div>';

        // Recommendation
        if (review.recommendation) {
            html += '<div class="review-section">';
            html += '<h3>Recommendation</h3>';
            html += '<p class="review-recommendation recommendation-' + review.recommendation + '" style="font-weight: bold; padding: 10px; background: #f0f6ff; border-left: 4px solid #2271b1;">';
            html += escapeHtml(review.recommendation).replace(/_/g, ' ').toUpperCase();
            html += '</p>';
            html += '</div>';
        }

        // Scores
        const scoreMap = [
            {key: 'relevance_score', label: 'Relevance & Significance'},
            {key: 'soundness_score', label: 'Scientific Soundness'},
            {key: 'clarity_score', label: 'Clarity & Presentation'},
            {key: 'openscience_score', label: 'Open Science Practices'},
            {key: 'impact_score', label: 'Expected Impact'},
            {key: 'provenance_score', label: 'Data & Code Provenance'}
        ];

        html += '<div class="review-section">';
        html += '<h3>Evaluation Scores</h3>';
        html += '<table class="review-scores-table" style="width: 100%; border-collapse: collapse;">';

        scoreMap.forEach(function(score) {
            if (review[score.key]) {
                html += '<tr style="border-bottom: 1px solid #ddd;">';
                html += '<td style="padding: 8px;">' + score.label + '</td>';
                html += '<td style="padding: 8px; text-align: right;"><span class="score-badge" style="background: #2271b1; color: white; padding: 4px 12px; border-radius: 3px; font-weight: bold;">' + review[score.key] + '/5</span></td>';
                html += '</tr>';
            }
        });

        html += '</table>';
        html += '</div>';

        // Comments to Editor
        if (review.comments_to_editor) {
            html += '<div class="review-section">';
            html += '<h3>Confidential Comments (Editor Only)</h3>';
            html += '<div class="review-comments" style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px; white-space: pre-wrap;">' + escapeHtml(review.comments_to_editor) + '</div>';
            html += '</div>';
        }

        // Comments to Author
        if (review.comments_to_author) {
            html += '<div class="review-section">';
            html += '<h3>Comments for Author</h3>';
            html += '<div class="review-comments" style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px; white-space: pre-wrap;">' + escapeHtml(review.comments_to_author) + '</div>';
            html += '</div>';
        }

        // AI Usage
        if (review.ai_usage) {
            html += '<div class="review-section">';
            html += '<h3>AI Assistance</h3>';
            html += '<p>' + escapeHtml(review.ai_usage) + '</p>';
            html += '</div>';
        }

        // Editor Decision Section
        html += '<div class="review-section editor-decision-section" style="background: #f0f6ff; border: 2px solid #2271b1; padding: 20px; margin-top: 20px;">';
        html += '<h3 style="margin-top: 0;">Editorial Decision</h3>';
        html += '<p>Based on this review, what would you like to do?</p>';

        html += '<div class="decision-buttons" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">';
        html += '<button type="button" class="button button-primary gfj-editor-decision-btn" data-decision="accept" data-manuscript-id="' + review.manuscript_id + '">';
        html += '✅ Accept Manuscript';
        html += '</button>';

        html += '<button type="button" class="button gfj-editor-decision-btn" data-decision="minor_revision" data-manuscript-id="' + review.manuscript_id + '">';
        html += '📝 Request Minor Revisions';
        html += '</button>';

        html += '<button type="button" class="button gfj-editor-decision-btn" data-decision="major_revision" data-manuscript-id="' + review.manuscript_id + '">';
        html += '📝 Request Major Revisions';
        html += '</button>';

        html += '<button type="button" class="button gfj-editor-decision-btn" data-decision="reject" data-manuscript-id="' + review.manuscript_id + '" style="background: #d63638; color: white; border-color: #d63638;">';
        html += '❌ Reject Manuscript';
        html += '</button>';
        html += '</div>';

        html += '<div class="decision-notes" style="margin-top: 15px;">';
        html += '<label for="editor-decision-notes"><strong>Comments for Author:</strong></label>';
        html += '<textarea id="editor-decision-notes" rows="6" style="width: 100%; margin-top: 5px;" placeholder="Provide detailed feedback for the author based on the review..."></textarea>';
        html += '</div>';
        html += '</div>';

        html += '</div>';

        showReviewModal(html);
    }

    /**
     * Escape HTML for safe display
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML.replace(/\n/g, '<br>');
    }

    /**
     * Initialize editor decision buttons
     */
    function initEditorDecision() {
        $(document).on('click', '.gfj-editor-decision-btn', function() {
            const $btn = $(this);
            const decision = $btn.data('decision');
            const manuscriptId = $btn.data('manuscript-id');
            const notes = $('#editor-decision-notes').val().trim();

            if (!notes) {
                alert('Please provide comments for the author before making a decision.');
                $('#editor-decision-notes').focus();
                return;
            }

            const decisionLabels = {
                'accept': 'Accept this manuscript',
                'minor_revision': 'Request minor revisions',
                'major_revision': 'Request major revisions',
                'reject': 'Reject this manuscript'
            };

            if (!confirm('Are you sure you want to ' + decisionLabels[decision] + '?')) {
                return;
            }

            // Disable all buttons
            $('.gfj-editor-decision-btn').prop('disabled', true);
            $btn.text('Processing...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gfj_editor_decision',
                    nonce: gfjAdmin.nonce,
                    manuscript_id: manuscriptId,
                    decision: decision,
                    notes: notes
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message || 'Decision recorded successfully!');
                        $('#gfj-review-modal').fadeOut(function() { $(this).remove(); });
                        location.reload(); // Reload to show updated status
                    } else {
                        alert('Error: ' + (response.data.message || 'Failed to record decision'));
                        $('.gfj-editor-decision-btn').prop('disabled', false);
                        $btn.text(decisionLabels[decision]);
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                    $('.gfj-editor-decision-btn').prop('disabled', false);
                    $btn.text(decisionLabels[decision]);
                }
            });
        });
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initReviewViewer();
        initStageTransition();
        initAutoSave();
        initBulkReviewerAssign();
        initMetaboxFileValidation();
        initUnsavedChangesWarning();
        initViewReviewButton();
        initEditorDecision();

        // Enable WordPress dismissible notices
        $(document).on('click', '.notice-dismiss', function() {
            $(this).parent('.notice').fadeOut();
        });

        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                $('#publish, #save-post').click();
            }
        });
    });

})(jQuery);
