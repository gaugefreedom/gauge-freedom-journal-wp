<?php
/**
 * Review Submission Form
 *
 * Displays the peer review form for reviewers
 */

if (!is_user_logged_in() || !current_user_can('submit_reviews')) {
    echo '<p>You must be logged in as a reviewer to access this form.</p>';
    return;
}

// Get review ID from URL
$review_id = isset($_GET['review_id']) ? intval($_GET['review_id']) : 0;

if (!$review_id) {
    echo '<p>Invalid review ID.</p>';
    return;
}

global $wpdb;

// Verify review assignment
$review = $wpdb->get_row($wpdb->prepare(
    "SELECT r.*, m.post_title, m.ID as manuscript_id
     FROM {$wpdb->prefix}gfj_reviews r
     JOIN {$wpdb->posts} m ON r.manuscript_id = m.ID
     WHERE r.id = %d AND r.reviewer_id = %d",
    $review_id,
    get_current_user_id()
));

if (!$review) {
    echo '<p>Review not found or not assigned to you.</p>';
    return;
}

if ($review->status === 'pending') {
    echo '<p>Please accept this review invitation from your dashboard before submitting a review.</p>';
    return;
}

if ($review->status === 'completed') {
    echo '<p>You have already submitted this review.</p>';
    return;
}

// Get manuscript data (blinded)
$abstract = get_post_meta($review->manuscript_id, '_gfj_abstract', true);
$keywords = get_post_meta($review->manuscript_id, '_gfj_keywords', true);
$blinded_file = get_post_meta($review->manuscript_id, '_gfj_blinded_file', true);
$code_repo = get_post_meta($review->manuscript_id, '_gfj_code_repo', true);
$data_repo = get_post_meta($review->manuscript_id, '_gfj_data_repo', true);
?>

<div class="gfj-review-form-container">

    <!-- Manuscript Information (Blinded) -->
    <div class="review-section">
        <h1>Submit Peer Review</h1>
        <p><strong>Manuscript Title:</strong> <?php echo esc_html($review->post_title); ?></p>
        <p><strong>Due Date:</strong> <?php echo date('F j, Y', strtotime($review->due_date)); ?></p>

        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; margin: 20px 0; border-radius: 4px;">
            <strong>⚠️ Double-Blind Review:</strong> Author information is hidden to ensure unbiased evaluation.
        </div>

        <?php
        // Check if this is a re-review
        $previous_review = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gfj_reviews
             WHERE manuscript_id = %d AND reviewer_id = %d AND status = 'completed' AND id != %d
             ORDER BY submitted_at DESC LIMIT 1",
            $review->manuscript_id, get_current_user_id(), $review_id
        ));

        if ($previous_review):
            $revision_notes = get_post_meta($review->manuscript_id, '_gfj_latest_revision_notes', true);
        ?>
        <div style="background: #dbeafe; border-left: 4px solid #2271b1; padding: 16px; margin: 20px 0; border-radius: 4px;">
            <h3 style="margin-top: 0;">📝 This is a Re-Review of a Revised Manuscript</h3>
            <p>You previously reviewed this manuscript on <?php echo date('F j, Y', strtotime($previous_review->submitted_at)); ?>. The author has submitted a revised version.</p>

            <details style="margin-top: 12px;">
                <summary style="cursor: pointer; font-weight: 600;">View Your Previous Review</summary>
                <div style="background: #fff; padding: 12px; margin-top: 8px; border-radius: 4px;">
                    <p><strong>Your Previous Recommendation:</strong> <?php echo ucwords(str_replace('_', ' ', $previous_review->recommendation)); ?></p>

                    <?php if ($previous_review->comments_to_author): ?>
                    <p><strong>Your Previous Comments to Author:</strong></p>
                    <div style="white-space: pre-wrap; background: #f9fafb; padding: 12px; border-radius: 4px;">
                        <?php echo esc_html($previous_review->comments_to_author); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </details>

            <?php if ($revision_notes): ?>
            <details style="margin-top: 12px;">
                <summary style="cursor: pointer; font-weight: 600;">View Author's Response to Your Review</summary>
                <div style="background: #fff; padding: 12px; margin-top: 8px; border-radius: 4px; white-space: pre-wrap;">
                    <?php echo esc_html($revision_notes); ?>
                </div>
            </details>
            <?php endif; ?>

            <p style="margin-bottom: 0; margin-top: 12px; font-size: 14px;">
                <strong>Your Task:</strong> Please assess whether the author has adequately addressed your previous comments in this revised version.
            </p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Manuscript Details -->
    <div class="review-section">
        <h2>Manuscript Details</h2>

        <h3>Abstract</h3>
        <div style="background: #f9fafb; padding: 16px; border-radius: 6px; line-height: 1.6;">
            <?php echo wp_kses_post($abstract); ?>
        </div>

        <?php if ($keywords): ?>
        <p style="margin-top: 16px;">
            <strong>Keywords:</strong> <?php echo esc_html($keywords); ?>
        </p>
        <?php endif; ?>

        <?php if ($blinded_file): ?>
        <p style="margin-top: 16px;">
            <a href="<?php echo wp_get_attachment_url($blinded_file); ?>" class="button" target="_blank">
                📄 Download Blinded Manuscript (PDF)
            </a>
        </p>
        <?php endif; ?>

        <?php if ($code_repo || $data_repo): ?>
        <p style="margin-top: 16px;">
            <?php if ($code_repo): ?>
                <a href="<?php echo esc_url($code_repo); ?>" class="button-secondary button" target="_blank">💻 Code Repository</a>
            <?php endif; ?>
            <?php if ($data_repo): ?>
                <a href="<?php echo esc_url($data_repo); ?>" class="button-secondary button" target="_blank">📊 Data Repository</a>
            <?php endif; ?>
        </p>
        <?php endif; ?>
    </div>

    <!-- Review Form -->
    <form id="gfj-review-form" method="post">
        <input type="hidden" name="review_id" value="<?php echo $review_id; ?>">

        <!-- Scoring Criteria -->
        <div class="review-section">
            <h2>1. Review Scores</h2>

            <div class="review-guidelines">
                <h4>Scoring Guide (1-5 scale)</h4>
                <ul>
                    <li><strong>1 = Poor:</strong> Major flaws, fundamental issues</li>
                    <li><strong>2 = Fair:</strong> Significant problems, substantial revisions needed</li>
                    <li><strong>3 = Good:</strong> Acceptable with minor revisions</li>
                    <li><strong>4 = Very Good:</strong> Strong work, minimal revisions</li>
                    <li><strong>5 = Excellent:</strong> Outstanding, publication-ready</li>
                </ul>
            </div>

            <div class="score-grid">
                <div class="score-item">
                    <label for="relevance_score">Relevance & Significance *</label>
                    <input type="number" name="relevance_score" id="relevance_score"
                           class="score-input" min="1" max="5" required>
                    <p class="score-hint">Importance to the field</p>
                </div>

                <div class="score-item">
                    <label for="soundness_score">Scientific Soundness *</label>
                    <input type="number" name="soundness_score" id="soundness_score"
                           class="score-input" min="1" max="5" required>
                    <p class="score-hint">Methods & analysis quality</p>
                </div>

                <div class="score-item">
                    <label for="clarity_score">Clarity & Presentation *</label>
                    <input type="number" name="clarity_score" id="clarity_score"
                           class="score-input" min="1" max="5" required>
                    <p class="score-hint">Writing & organization</p>
                </div>

                <div class="score-item">
                    <label for="openscience_score">Open Science Practices *</label>
                    <input type="number" name="openscience_score" id="openscience_score"
                           class="score-input" min="1" max="5" required>
                    <p class="score-hint">Code/data availability</p>
                </div>

                <div class="score-item">
                    <label for="impact_score">Potential Impact</label>
                    <input type="number" name="impact_score" id="impact_score"
                           class="score-input" min="1" max="5">
                    <p class="score-hint">Future influence (optional)</p>
                </div>

                <div class="score-item">
                    <label for="provenance_score">Computational Provenance</label>
                    <input type="number" name="provenance_score" id="provenance_score"
                           class="score-input" min="1" max="5">
                    <p class="score-hint">Reproducibility (optional)</p>
                </div>
            </div>
        </div>

        <!-- Comments to Author -->
        <div class="review-section">
            <h2>2. Comments for Author *</h2>

            <div class="review-guidelines">
                <h4>Guidelines for Constructive Feedback</h4>
                <ul>
                    <li>Be specific and provide concrete examples</li>
                    <li>Balance strengths and areas for improvement</li>
                    <li>Suggest actionable revisions where applicable</li>
                    <li>Maintain a professional and respectful tone</li>
                    <li>Focus on the science, not the authors</li>
                </ul>
            </div>

            <label for="comments_to_author" class="sr-only">Detailed comments for the author</label>
            <textarea name="comments_to_author" id="comments_to_author"
                      class="review-comments" required
                      placeholder="Provide detailed, constructive feedback for the authors. Include:
- Summary of the work
- Major strengths
- Areas needing improvement
- Specific suggestions for revision
- Questions or clarifications needed"></textarea>
        </div>

        <!-- Comments to Editor -->
        <div class="review-section">
            <h2>3. Confidential Comments for Editor</h2>

            <p style="color: #6b7280; font-size: 14px; margin-bottom: 16px;">
                These comments will NOT be shared with the authors. Use this space for concerns about methodology,
                ethical issues, or other matters requiring editorial attention.
            </p>

            <label for="comments_to_editor" class="sr-only">Confidential comments for the editor</label>
            <textarea name="comments_to_editor" id="comments_to_editor"
                      class="review-comments"
                      placeholder="Optional: Confidential comments for the editor only (e.g., concerns about plagiarism, ethical issues, comparison to similar work)"></textarea>
        </div>

        <!-- Recommendation -->
        <div class="review-section">
            <h2>4. Final Recommendation *</h2>

            <label for="recommendation" style="font-weight: 600; margin-bottom: 10px; display: block;">
                Your recommendation to the editor:
            </label>

            <select name="recommendation" id="recommendation" class="recommendation-select" required>
                <option value="">-- Select Recommendation --</option>
                <option value="accept">✅ Accept - Publication ready</option>
                <option value="minor_revision">📝 Minor Revision - Accept after small changes</option>
                <option value="major_revision">🔄 Major Revision - Requires substantial changes</option>
                <option value="reject_resubmit">↩️ Reject & Resubmit - Major overhaul needed</option>
                <option value="reject">❌ Reject - Does not meet publication standards</option>
            </select>

            <div style="background: #f0f9ff; border-left: 4px solid #2563eb; padding: 16px; margin-top: 20px; border-radius: 6px;">
                <strong>Important:</strong> Your recommendation is advisory. The editor makes the final decision
                based on all reviews and editorial assessment.
            </div>
        </div>

        <!-- AI Assistance Disclosure -->
        <div class="review-section">
            <h2>5. AI Assistance Disclosure</h2>

            <p style="color: #6b7280; font-size: 14px; margin-bottom: 16px;">
                In accordance with journal policy, please disclose any AI tools used in preparing this review.
            </p>

            <label>
                <input type="checkbox" name="ai_used" value="1" id="ai_checkbox">
                I used AI tools (e.g., ChatGPT, Grammarly, translation tools) to assist with this review
            </label>

            <div id="ai_details" style="display: none; margin-top: 16px;">
                <label for="ai_disclosure" style="font-weight: 500; display: block; margin-bottom: 8px;">
                    Please describe how AI was used:
                </label>
                <textarea name="ai_disclosure" id="ai_disclosure" rows="4"
                          style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                          placeholder="Example: Used ChatGPT to improve grammar and clarity of feedback, used DeepL for translation assistance"></textarea>
            </div>
        </div>

        <!-- Submit Section -->
        <div class="review-section">
            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; margin-bottom: 20px; border-radius: 6px;">
                <strong>Before submitting:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <li>Review your scores and comments for accuracy</li>
                    <li>Ensure feedback is constructive and specific</li>
                    <li>Verify all required fields are completed</li>
                    <li><strong>You cannot edit your review after submission</strong></li>
                </ul>
            </div>

            <button type="submit" class="button-primary button-large">
                Submit Review
            </button>
            <a href="<?php echo home_url('/dashboard/'); ?>" class="button button-large">Cancel</a>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Show/hide AI disclosure
    $('#ai_checkbox').on('change', function() {
        if ($(this).is(':checked')) {
            $('#ai_details').slideDown();
            $('#ai_disclosure').attr('required', true);
        } else {
            $('#ai_details').slideUp();
            $('#ai_disclosure').attr('required', false);
        }
    });

    // Score input styling
    $('.score-input').on('input', function() {
        var value = parseInt($(this).val());
        var $item = $(this).closest('.score-item');

        $item.removeClass('score-1 score-2 score-3 score-4 score-5');

        if (value >= 1 && value <= 5) {
            $item.addClass('score-' + value);
        }
    });

    // Character counter for comments
    function updateCharCount($textarea, minChars) {
        var count = $textarea.val().length;
        var $counter = $textarea.siblings('.char-count');

        if (!$counter.length) {
            $counter = $('<div class="char-count" style="margin-top: 6px; font-size: 12px; color: #6b7280;"></div>');
            $textarea.after($counter);
        }

        if (count < minChars) {
            $counter.html(count + ' / ' + minChars + ' characters (minimum)').css('color', '#d97706');
        } else {
            $counter.html(count + ' characters').css('color', '#059669');
        }
    }

    $('#comments_to_author').on('input', function() {
        updateCharCount($(this), 200);
    });

    // Initialize
    updateCharCount($('#comments_to_author'), 200);
});
</script>

<style>
/* Score color coding */
.score-item.score-1 { background: #fee2e2; }
.score-item.score-2 { background: #fed7aa; }
.score-item.score-3 { background: #fef3c7; }
.score-item.score-4 { background: #d1fae5; }
.score-item.score-5 { background: #dbeafe; }

.score-item.score-1 label,
.score-item.score-2 label,
.score-item.score-3 label,
.score-item.score-4 label,
.score-item.score-5 label {
    color: #1f2937;
}
</style>
