<?php
/**
 * AJAX Handler
 *
 * Handles all AJAX requests with security validation
 */

if (!defined('ABSPATH')) {
    exit;
}

class GFJ_Ajax_Handler {

    public function __construct() {
        // Constructor
    }

    /**
     * Handle manuscript submission
     */
    public function submit_manuscript() {
        // Verify nonce
        if (!check_ajax_referer('gfj_submit_manuscript', 'gfj_submit_nonce', false)) {
            wp_send_json_error(['message' => 'Security verification failed.'], 403);
        }

        // Check permissions
        if (!current_user_can('submit_manuscripts')) {
            wp_send_json_error(['message' => 'You do not have permission to submit manuscripts.'], 403);
        }

        // Validate required fields
        $required_fields = ['title', 'article_type', 'abstract', 'keywords', 'ai_statement', 'conflicts'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(['message' => "Required field missing: {$field}"], 400);
            }
        }

        // Validate files
        $required_files = ['blinded_file', 'full_file', 'latex_sources'];
        foreach ($required_files as $file) {
            if (!isset($_FILES[$file]) || $_FILES[$file]['error'] === UPLOAD_ERR_NO_FILE) {
                wp_send_json_error(['message' => "Required file missing: {$file}"], 400);
            }
        }

        // Create manuscript post
        $post_data = [
            'post_type' => 'gfj_manuscript',
            'post_title' => sanitize_text_field($_POST['title']),
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ];

        $manuscript_id = wp_insert_post($post_data);

        if (is_wp_error($manuscript_id)) {
            wp_send_json_error(['message' => 'Failed to create manuscript: ' . $manuscript_id->get_error_message()], 500);
        }

        // Set article type taxonomy
        $article_type = sanitize_text_field($_POST['article_type']);
        wp_set_object_terms($manuscript_id, $article_type, 'manuscript_type');

        // Set initial stage to triage
        wp_set_object_terms($manuscript_id, 'triage', 'manuscript_stage');

        // Save metadata
        $meta_fields = [
            '_gfj_abstract' => 'wp_kses_post',
            '_gfj_keywords' => 'sanitize_text_field',
            '_gfj_code_repo' => 'esc_url_raw',
            '_gfj_data_repo' => 'esc_url_raw',
            '_gfj_ai_statement' => 'wp_kses_post',
            '_gfj_conflicts' => 'wp_kses_post',
            '_gfj_cover_letter' => 'wp_kses_post',
        ];

        foreach ($meta_fields as $meta_key => $sanitize_func) {
            $post_key = str_replace('_gfj_', '', $meta_key);
            if (isset($_POST[$post_key])) {
                update_post_meta($manuscript_id, $meta_key, $sanitize_func($_POST[$post_key]));
            }
        }

        // Set triage deadline (7 days from submission)
        $deadline = date('Y-m-d H:i:s', strtotime('+7 days'));
        update_post_meta($manuscript_id, '_gfj_triage_deadline', $deadline);

        // Handle file uploads
        $file_handler = new GFJ_File_Handler();

        $_POST['gfj_upload'] = 1;
        $_POST['gfj_upload_nonce'] = wp_create_nonce('gfj_file_upload');

        $file_map = [
            'blinded_file' => '_gfj_blinded_file',
            'full_file' => '_gfj_full_file',
            'latex_sources' => '_gfj_latex_file',
            'car_file' => '_gfj_car_file',
        ];

        $uploaded_files = [];

        foreach ($file_map as $field => $meta_key) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
                $attachment_id = $file_handler->handle_manuscript_upload($field, $manuscript_id);

                if (is_wp_error($attachment_id)) {
                    // Rollback: delete the manuscript
                    wp_delete_post($manuscript_id, true);
                    wp_send_json_error([
                        'message' => "File upload failed for {$field}: " . $attachment_id->get_error_message()
                    ], 500);
                }

                update_post_meta($manuscript_id, $meta_key, $attachment_id);
                $uploaded_files[$field] = $attachment_id;
            }
        }

        // Send notification to editors
        $this->notify_editors_new_submission($manuscript_id);

        // Log submission
        do_action('gfj_manuscript_submitted', $manuscript_id, get_current_user_id());

        wp_send_json_success([
            'message' => 'Manuscript submitted successfully!',
            'manuscript_id' => $manuscript_id,
            'redirect_url' => home_url('/dashboard/'),
        ]);
    }

    /**
     * Handle triage decision
     */
    public function submit_triage() {
        // Verify nonce
        if (!check_ajax_referer('gfj_admin', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security verification failed.'], 403);
        }

        // Check permissions
        if (!current_user_can('triage_manuscripts')) {
            wp_send_json_error(['message' => 'You do not have permission to triage manuscripts.'], 403);
        }

        // Validate input
        $manuscript_id = isset($_POST['manuscript_id']) ? intval($_POST['manuscript_id']) : 0;
        $decision = isset($_POST['decision']) ? sanitize_text_field($_POST['decision']) : '';
        $notes = isset($_POST['notes']) ? wp_kses_post($_POST['notes']) : '';

        if (!$manuscript_id || !in_array($decision, ['approve', 'request_changes', 'desk_reject'])) {
            wp_send_json_error(['message' => 'Invalid request parameters.'], 400);
        }

        // Verify manuscript exists and is in triage
        $manuscript = get_post($manuscript_id);
        if (!$manuscript || $manuscript->post_type !== 'gfj_manuscript') {
            wp_send_json_error(['message' => 'Manuscript not found.'], 404);
        }

        $stage = wp_get_post_terms($manuscript_id, 'manuscript_stage', ['fields' => 'slugs']);
        $current_stage = !empty($stage) ? $stage[0] : '';

        if ($current_stage !== 'triage') {
            wp_send_json_error(['message' => 'Manuscript is not in triage stage.'], 400);
        }

        global $wpdb;

        // Record decision
        $wpdb->insert(
            $wpdb->prefix . 'gfj_decisions',
            [
                'manuscript_id' => $manuscript_id,
                'editor_id' => get_current_user_id(),
                'decision_type' => 'triage_' . $decision,
                'decision_letter' => $notes,
                'internal_notes' => '',
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );

        // Update stage based on decision
        $new_stage_map = [
            'approve' => 'review',
            'request_changes' => 'revision',
            'desk_reject' => 'rejected',
        ];

        $new_stage = $new_stage_map[$decision];
        wp_set_object_terms($manuscript_id, $new_stage, 'manuscript_stage');

        // Record transition metadata
        update_post_meta($manuscript_id, '_gfj_triage_decision', $decision);
        update_post_meta($manuscript_id, '_gfj_triage_date', current_time('mysql'));
        update_post_meta($manuscript_id, '_gfj_triage_editor', get_current_user_id());
        update_post_meta($manuscript_id, '_gfj_triage_notes', $notes); // Store notes for author to see

        // Mark revision type if requesting changes during triage
        if ($decision === 'request_changes') {
            update_post_meta($manuscript_id, '_gfj_revision_type', 'triage'); // Triage-stage revision
        }

        // Notify author
        $this->notify_author_triage_decision($manuscript_id, $decision, $notes);

        // Log action
        do_action('gfj_triage_completed', $manuscript_id, $decision);

        wp_send_json_success([
            'message' => 'Triage decision recorded successfully.',
            'new_stage' => $new_stage,
        ]);
    }

    /**
     * Handle reviewer invitation
     */
    public function invite_reviewer() {
        // Verify nonce
        if (!check_ajax_referer('gfj_admin', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security verification failed.'], 403);
        }

        // Check permissions
        if (!current_user_can('assign_reviewers')) {
            wp_send_json_error(['message' => 'You do not have permission to assign reviewers.'], 403);
        }

        // Validate input
        $manuscript_id = isset($_POST['manuscript_id']) ? intval($_POST['manuscript_id']) : 0;
        $reviewer_id = isset($_POST['reviewer_id']) ? intval($_POST['reviewer_id']) : 0;

        if (!$manuscript_id || !$reviewer_id) {
            wp_send_json_error(['message' => 'Invalid request parameters.'], 400);
        }

        // Verify reviewer role
        $reviewer = get_userdata($reviewer_id);
        if (!$reviewer || !in_array('gfj_reviewer', $reviewer->roles)) {
            wp_send_json_error(['message' => 'Invalid reviewer.'], 400);
        }

        // Check if already assigned with pending/in_progress review
        global $wpdb;
        $existing_pending = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gfj_reviews
             WHERE manuscript_id = %d AND reviewer_id = %d
             AND status IN ('pending', 'in_progress')",
            $manuscript_id, $reviewer_id
        ));

        if ($existing_pending > 0) {
            wp_send_json_error(['message' => 'This reviewer already has a pending review for this manuscript.'], 400);
        }

        // Get revision round for this manuscript (for re-reviews)
        $revision_count = get_post_meta($manuscript_id, '_gfj_revision_count', true);
        $review_round = $revision_count ? intval($revision_count) + 1 : 1;

        // Create review invitation
        $due_date = date('Y-m-d H:i:s', strtotime('+21 days'));

        $wpdb->insert(
            $wpdb->prefix . 'gfj_reviews',
            [
                'manuscript_id' => $manuscript_id,
                'reviewer_id' => $reviewer_id,
                'editor_id' => get_current_user_id(),
                'status' => 'pending',
                'due_date' => $due_date,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%s', '%s', '%s']
        );

        $review_id = $wpdb->insert_id;

        // Send invitation email
        $this->send_review_invitation($review_id, $manuscript_id, $reviewer_id);

        // Log action
        do_action('gfj_reviewer_invited', $manuscript_id, $reviewer_id);

        wp_send_json_success([
            'message' => 'Reviewer invitation sent successfully.',
            'review_id' => $review_id,
        ]);
    }

    /**
     * Handle review submission
     */
    public function submit_review() {
        // Verify nonce
        if (!check_ajax_referer('gfj_public', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security verification failed.'], 403);
        }

        // Check permissions
        if (!current_user_can('submit_reviews')) {
            wp_send_json_error(['message' => 'You do not have permission to submit reviews.'], 403);
        }

        // Validate input
        $review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;

        if (!$review_id) {
            wp_send_json_error(['message' => 'Invalid review ID.'], 400);
        }

        global $wpdb;

        // Verify review assignment
        $review = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gfj_reviews WHERE id = %d AND reviewer_id = %d",
            $review_id, get_current_user_id()
        ));

        if (!$review) {
            wp_send_json_error(['message' => 'Review not found or not assigned to you.'], 404);
        }

        if ($review->status === 'completed') {
            wp_send_json_error(['message' => 'This review has already been submitted.'], 400);
        }

        // Validate scores
        $required_scores = ['relevance_score', 'soundness_score', 'clarity_score', 'openscience_score'];
        foreach ($required_scores as $score) {
            if (!isset($_POST[$score]) || !is_numeric($_POST[$score]) || $_POST[$score] < 1 || $_POST[$score] > 5) {
                wp_send_json_error(['message' => "Invalid or missing score: {$score}"], 400);
            }
        }

        // Update review
        $wpdb->update(
            $wpdb->prefix . 'gfj_reviews',
            [
                'relevance_score' => intval($_POST['relevance_score']),
                'soundness_score' => intval($_POST['soundness_score']),
                'clarity_score' => intval($_POST['clarity_score']),
                'openscience_score' => intval($_POST['openscience_score']),
                'impact_score' => isset($_POST['impact_score']) ? intval($_POST['impact_score']) : null,
                'provenance_score' => isset($_POST['provenance_score']) ? intval($_POST['provenance_score']) : null,
                'comments_to_author' => wp_kses_post($_POST['comments_to_author']),
                'comments_to_editor' => wp_kses_post($_POST['comments_to_editor']),
                'recommendation' => sanitize_text_field($_POST['recommendation']),
                'status' => 'completed',
                'submitted_at' => current_time('mysql'),
            ],
            ['id' => $review_id],
            ['%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );

        // Notify editor
        $this->notify_editor_review_completed($review->manuscript_id, $review_id);

        // Log action
        do_action('gfj_review_submitted', $review->manuscript_id, $review_id);

        wp_send_json_success([
            'message' => 'Review submitted successfully. Thank you for your contribution!',
        ]);
    }

    /**
     * Send email notification to editors about new submission
     */
    private function notify_editors_new_submission($manuscript_id) {
        $manuscript = get_post($manuscript_id);
        $editors = get_users(['role__in' => ['gfj_editor', 'gfj_eic']]);

        $subject = '[Gauge Freedom Journal] New Submission: ' . $manuscript->post_title;
        $message = "A new manuscript has been submitted and requires triage:\n\n";
        $message .= "Title: {$manuscript->post_title}\n";
        $message .= "Submitted: " . get_the_date('', $manuscript_id) . "\n";
        $message .= "View: " . admin_url('post.php?post=' . $manuscript_id . '&action=edit') . "\n";

        foreach ($editors as $editor) {
            wp_mail($editor->user_email, $subject, $message);
        }
    }

    /**
     * Notify author of triage decision
     */
    private function notify_author_triage_decision($manuscript_id, $decision, $notes) {
        $manuscript = get_post($manuscript_id);
        if (!$manuscript) {
            error_log("GFJ: Cannot send notification - manuscript $manuscript_id not found");
            return;
        }

        $author = get_userdata($manuscript->post_author);
        if (!$author || !$author->user_email) {
            error_log("GFJ: Cannot send notification - author not found for manuscript $manuscript_id");
            return;
        }

        $decision_text = [
            'approve' => 'approved for peer review',
            'request_changes' => 'requires revisions',
            'desk_reject' => 'rejected',
        ];

        $subject = '[Gauge Freedom Journal] Triage Decision: ' . $manuscript->post_title;
        $message = "Your manuscript has been {$decision_text[$decision]}.\n\n";
        $message .= "Manuscript: {$manuscript->post_title}\n\n";
        $message .= "Editor's Comments:\n{$notes}\n\n";
        $message .= "View details: " . home_url('/dashboard/');

        wp_mail($author->user_email, $subject, $message);
    }

    /**
     * Send review invitation
     */
    private function send_review_invitation($review_id, $manuscript_id, $reviewer_id) {
        $manuscript = get_post($manuscript_id);
        if (!$manuscript) {
            error_log("GFJ: Cannot send review invitation - manuscript $manuscript_id not found");
            return;
        }

        $reviewer = get_userdata($reviewer_id);
        if (!$reviewer || !$reviewer->user_email) {
            error_log("GFJ: Cannot send review invitation - reviewer $reviewer_id not found");
            return;
        }

        $abstract = get_post_meta($manuscript_id, '_gfj_abstract', true);

        // Check if this is a re-review (reviewer has completed a previous review)
        global $wpdb;
        $previous_review_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gfj_reviews
             WHERE manuscript_id = %d AND reviewer_id = %d AND status = 'completed'",
            $manuscript_id, $reviewer_id
        ));

        $is_rereview = $previous_review_count > 0;

        $subject = $is_rereview
            ? '[Gauge Freedom Journal] Re-Review Invitation (Revised Manuscript)'
            : '[Gauge Freedom Journal] Review Invitation';

        $message = $is_rereview
            ? "You are invited to review a REVISED version of a manuscript you previously reviewed:\n\n"
            : "You have been invited to review a manuscript:\n\n";

        $message .= "Title: {$manuscript->post_title}\n\n";

        if ($is_rereview) {
            $revision_notes = get_post_meta($manuscript_id, '_gfj_latest_revision_notes', true);
            if ($revision_notes) {
                $message .= "Author's Response to Previous Review:\n";
                $message .= wp_strip_all_tags($revision_notes) . "\n\n";
            }
            $message .= "Please review the revised manuscript and assess whether the author has adequately addressed your previous comments.\n\n";
        }

        $message .= "Abstract:\n" . wp_strip_all_tags($abstract) . "\n\n";
        $message .= "Please accept or decline this invitation in your dashboard:\n";
        $message .= home_url('/dashboard/');

        wp_mail($reviewer->user_email, $subject, $message);
    }

    /**
     * Notify editor when review is completed
     */
    private function notify_editor_review_completed($manuscript_id, $review_id) {
        global $wpdb;

        $review = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gfj_reviews WHERE id = %d",
            $review_id
        ));

        $manuscript = get_post($manuscript_id);
        $editor = get_userdata($review->editor_id);

        $subject = '[Gauge Freedom Journal] Review Completed: ' . $manuscript->post_title;
        $message = "A review has been completed for manuscript: {$manuscript->post_title}\n\n";
        $message .= "View manuscript: " . admin_url('post.php?post=' . $manuscript_id . '&action=edit');

        wp_mail($editor->user_email, $subject, $message);
    }

    /**
     * Handle review acceptance/decline
     */
    public function review_action() {
        // Verify nonce
        if (!check_ajax_referer('gfj_public', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security verification failed.'], 403);
        }

        // Check permissions
        if (!current_user_can('submit_reviews')) {
            wp_send_json_error(['message' => 'You do not have permission to manage reviews.'], 403);
        }

        $review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
        $action = isset($_POST['review_action']) ? sanitize_text_field($_POST['review_action']) : '';

        if (!$review_id || !in_array($action, ['accept', 'decline'])) {
            wp_send_json_error(['message' => 'Invalid parameters.'], 400);
        }

        global $wpdb;

        // Verify review belongs to current user
        $review = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gfj_reviews WHERE id = %d AND reviewer_id = %d",
            $review_id, get_current_user_id()
        ));

        if (!$review) {
            wp_send_json_error(['message' => 'Review not found.'], 404);
        }

        if ($review->status !== 'pending') {
            wp_send_json_error(['message' => 'This invitation has already been responded to.'], 400);
        }

        // Update status
        $new_status = $action === 'accept' ? 'in_progress' : 'declined';

        $wpdb->update(
            $wpdb->prefix . 'gfj_reviews',
            ['status' => $new_status],
            ['id' => $review_id],
            ['%s'],
            ['%d']
        );

        // Notify editor
        $editor = get_userdata($review->editor_id);
        $manuscript = get_post($review->manuscript_id);
        $reviewer = get_userdata(get_current_user_id());

        $subject = '[Gauge Freedom Journal] Review Invitation ' . ucfirst($action) . 'ed';
        $message = "{$reviewer->display_name} has {$action}ed the review invitation for:\n\n";
        $message .= "Manuscript: {$manuscript->post_title}\n\n";

        if ($action === 'decline') {
            $message .= "You may need to invite another reviewer.";
        }

        wp_mail($editor->user_email, $subject, $message);

        wp_send_json_success([
            'message' => 'Review invitation ' . $action . 'ed successfully.'
        ]);
    }

    /**
     * Get review details for display
     */
    public function get_review() {
        // Verify nonce
        if (!check_ajax_referer('gfj_admin', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security verification failed.'], 403);
        }

        // Check permissions (editors only)
        if (!current_user_can('view_full_manuscripts')) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }

        $review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;

        if (!$review_id) {
            wp_send_json_error(['message' => 'Invalid review ID.'], 400);
        }

        global $wpdb;

        $review = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gfj_reviews WHERE id = %d",
            $review_id
        ), ARRAY_A);

        if (!$review) {
            wp_send_json_error(['message' => 'Review not found.'], 404);
        }

        wp_send_json_success(['review' => $review]);
    }

    /**
     * Get detailed review information for display
     */
    public function get_review_details() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gfj_admin')) {
            wp_send_json_error(['message' => 'Security verification failed.'], 403);
        }

        // Check permissions (editors only)
        if (!current_user_can('view_full_manuscripts') && !current_user_can('triage_manuscripts')) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }

        $review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;

        if (!$review_id) {
            wp_send_json_error(['message' => 'Invalid review ID.'], 400);
        }

        global $wpdb;

        // Get review with reviewer information
        $review = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, u.display_name as reviewer_name
             FROM {$wpdb->prefix}gfj_reviews r
             LEFT JOIN {$wpdb->users} u ON r.reviewer_id = u.ID
             WHERE r.id = %d",
            $review_id
        ), ARRAY_A);

        if (!$review) {
            wp_send_json_error(['message' => 'Review not found.'], 404);
        }

        // Format submitted_at date
        if ($review['submitted_at']) {
            $review['submitted_at'] = date('F j, Y', strtotime($review['submitted_at']));
        }

        wp_send_json_success($review);
    }

    /**
     * Handle editor decision after review
     */
    public function editor_decision() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gfj_admin')) {
            wp_send_json_error(['message' => 'Security verification failed.'], 403);
        }

        // Check permissions (editors only)
        if (!current_user_can('triage_manuscripts')) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }

        $manuscript_id = isset($_POST['manuscript_id']) ? intval($_POST['manuscript_id']) : 0;
        $decision = isset($_POST['decision']) ? sanitize_text_field($_POST['decision']) : '';
        $notes = isset($_POST['notes']) ? wp_kses_post($_POST['notes']) : '';

        if (!$manuscript_id || !$decision || !$notes) {
            wp_send_json_error(['message' => 'Missing required fields.'], 400);
        }

        $valid_decisions = ['accept', 'minor_revision', 'major_revision', 'reject'];
        if (!in_array($decision, $valid_decisions)) {
            wp_send_json_error(['message' => 'Invalid decision.'], 400);
        }

        $manuscript = get_post($manuscript_id);

        if (!$manuscript || $manuscript->post_type !== 'gfj_manuscript') {
            wp_send_json_error(['message' => 'Manuscript not found.'], 404);
        }

        // Map decision to stage
        $stage_map = [
            'accept' => 'accepted',
            'minor_revision' => 'revision',
            'major_revision' => 'revision',
            'reject' => 'rejected'
        ];

        $new_stage = $stage_map[$decision];

        // Update manuscript stage
        wp_set_object_terms($manuscript_id, $new_stage, 'manuscript_stage');

        // Store editor decision notes
        update_post_meta($manuscript_id, '_gfj_editor_decision', $decision);
        update_post_meta($manuscript_id, '_gfj_editor_notes', $notes);
        update_post_meta($manuscript_id, '_gfj_decision_date', current_time('mysql'));

        // Mark revision type if requesting revisions
        if (in_array($decision, ['minor_revision', 'major_revision'])) {
            update_post_meta($manuscript_id, '_gfj_revision_type', 'review'); // Review-stage revision
        }

        // Notify author
        $this->notify_author_decision($manuscript_id, $decision, $notes);

        // Log action
        do_action('gfj_editor_decision', $manuscript_id, $decision, get_current_user_id());

        $decision_messages = [
            'accept' => 'Manuscript accepted! The author has been notified.',
            'minor_revision' => 'Minor revisions requested. The author has been notified.',
            'major_revision' => 'Major revisions requested. The author has been notified.',
            'reject' => 'Manuscript rejected. The author has been notified.'
        ];

        wp_send_json_success([
            'message' => $decision_messages[$decision],
        ]);
    }

    /**
     * Notify author of editor's decision
     */
    private function notify_author_decision($manuscript_id, $decision, $notes) {
        $manuscript = get_post($manuscript_id);
        if (!$manuscript) {
            error_log("GFJ: Cannot send decision notification - manuscript $manuscript_id not found");
            return;
        }

        $author = get_userdata($manuscript->post_author);
        if (!$author || !$author->user_email) {
            error_log("GFJ: Cannot send decision notification - author not found for manuscript $manuscript_id");
            return;
        }

        $decision_text = [
            'accept' => 'Accepted for Publication',
            'minor_revision' => 'Minor Revisions Requested',
            'major_revision' => 'Major Revisions Requested',
            'reject' => 'Not Accepted for Publication',
        ];

        $subject = '[Gauge Freedom Journal] Decision: ' . $manuscript->post_title;
        $message = "Your manuscript has received an editorial decision: {$decision_text[$decision]}\n\n";
        $message .= "Manuscript: {$manuscript->post_title}\n\n";
        $message .= "Editor's Comments:\n{$notes}\n\n";

        if (in_array($decision, ['minor_revision', 'major_revision'])) {
            $message .= "Please log in to your dashboard to upload your revised manuscript:\n";
        } else {
            $message .= "View details in your dashboard:\n";
        }

        $message .= home_url('/dashboard/');

        wp_mail($author->user_email, $subject, $message);
    }

    /**
     * Get manuscript details for authors
     */
    public function get_manuscript_details() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gfj_public')) {
            wp_send_json_error(['message' => 'Security verification failed.'], 403);
        }

        $manuscript_id = isset($_POST['manuscript_id']) ? intval($_POST['manuscript_id']) : 0;

        if (!$manuscript_id) {
            wp_send_json_error(['message' => 'Invalid manuscript ID.'], 400);
        }

        $manuscript = get_post($manuscript_id);

        if (!$manuscript || $manuscript->post_type !== 'gfj_manuscript') {
            wp_send_json_error(['message' => 'Manuscript not found.'], 404);
        }

        // Check if user is the author or has permission to view
        if ($manuscript->post_author != get_current_user_id() && !current_user_can('view_full_manuscripts')) {
            wp_send_json_error(['message' => 'You do not have permission to view this manuscript.'], 403);
        }

        // Get stage
        $stage_terms = wp_get_post_terms($manuscript_id, 'manuscript_stage');
        $stage = !empty($stage_terms) ? [
            'slug' => $stage_terms[0]->slug,
            'name' => $stage_terms[0]->name
        ] : ['slug' => 'triage', 'name' => 'Triage'];

        // Get article type
        $type_terms = wp_get_post_terms($manuscript_id, 'manuscript_type');
        $article_type = !empty($type_terms) ? $type_terms[0]->name : 'N/A';

        // Get metadata
        $abstract = get_post_meta($manuscript_id, '_gfj_abstract', true);
        $keywords = get_post_meta($manuscript_id, '_gfj_keywords', true);
        $code_repo = get_post_meta($manuscript_id, '_gfj_code_repo', true);
        $data_repo = get_post_meta($manuscript_id, '_gfj_data_repo', true);
        $triage_deadline = get_post_meta($manuscript_id, '_gfj_triage_deadline', true);

        // Get files
        $files = [];

        $file_map = [
            'blinded' => '_gfj_blinded_file',
            'full' => '_gfj_full_file',
            'latex' => '_gfj_latex_file',
            'car' => '_gfj_car_file'
        ];

        foreach ($file_map as $key => $meta_key) {
            $attachment_id = get_post_meta($manuscript_id, $meta_key, true);
            if ($attachment_id) {
                $url = wp_get_attachment_url($attachment_id);
                $file_size = size_format(filesize(get_attached_file($attachment_id)));
                $files[$key] = [
                    'url' => $url,
                    'size' => $file_size
                ];
            }
        }

        // Generate status message and next steps based on stage
        $status_message = '';
        $next_steps = '';
        $editor_feedback = '';

        switch ($stage['slug']) {
            case 'triage':
                $status_message = 'Your manuscript is under initial review by our editors.';
                $next_steps = '<p>Our editorial team will review your manuscript within 7 days and make a decision on whether to send it for peer review.</p>';
                if ($triage_deadline) {
                    $next_steps .= '<p><strong>Expected decision by:</strong> ' . date('F j, Y', strtotime($triage_deadline)) . '</p>';
                }
                break;

            case 'review':
                $status_message = 'Your manuscript has been sent for peer review.';
                $next_steps = '<p>Your manuscript is currently being reviewed by independent experts. You will be notified once all reviews are complete.</p>';

                // Get review status
                global $wpdb;
                $review_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}gfj_reviews WHERE manuscript_id = %d",
                    $manuscript_id
                ));
                $completed_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}gfj_reviews WHERE manuscript_id = %d AND status = 'completed'",
                    $manuscript_id
                ));

                if ($review_count > 0) {
                    $next_steps .= '<p><strong>Review Progress:</strong> ' . $completed_count . ' of ' . $review_count . ' reviews completed.</p>';
                }
                break;

            case 'revision':
                $status_message = 'Revisions have been requested for your manuscript.';
                $next_steps = '<p><strong>Action Required:</strong> Please review the editor feedback below and upload a revised version of your manuscript.</p>';
                $next_steps .= '<p>Contact the editorial team if you have any questions about the requested revisions.</p>';

                // Get editor feedback (could be from triage or post-review decision)
                $triage_notes = get_post_meta($manuscript_id, '_gfj_triage_notes', true);
                $editor_notes = get_post_meta($manuscript_id, '_gfj_editor_notes', true);

                if ($editor_notes) {
                    $editor_feedback = $editor_notes; // Post-review decision takes priority
                } elseif ($triage_notes) {
                    $editor_feedback = $triage_notes; // Triage decision
                }
                break;

            case 'accepted':
                $status_message = 'Congratulations! Your manuscript has been accepted for publication.';
                $next_steps = '<p>Your manuscript will proceed to the production stage. You will be contacted by the editorial team for any final details.</p>';
                break;

            case 'rejected':
                $status_message = 'We regret to inform you that your manuscript was not accepted for publication.';
                $next_steps = '<p>Thank you for considering Gauge Freedom Journal. We encourage you to address the feedback and consider submitting to other venues.</p>';

                $triage_notes = get_post_meta($manuscript_id, '_gfj_triage_notes', true);
                if ($triage_notes) {
                    $editor_feedback = $triage_notes;
                }
                break;

            case 'published':
                $status_message = 'Your manuscript has been published!';
                $next_steps = '<p>Your work is now publicly available. Thank you for publishing with Gauge Freedom Journal.</p>';
                break;
        }

        // Prepare response
        $response = [
            'manuscript_id' => $manuscript_id,
            'title' => $manuscript->post_title,
            'stage' => $stage,
            'article_type' => $article_type,
            'submitted_date' => get_the_date('F j, Y', $manuscript),
            'abstract' => $abstract,
            'keywords' => $keywords,
            'code_repo' => $code_repo,
            'data_repo' => $data_repo,
            'triage_deadline' => $triage_deadline ? date('F j, Y', strtotime($triage_deadline)) : null,
            'files' => $files,
            'status_message' => $status_message,
            'next_steps' => $next_steps,
            'editor_feedback' => $editor_feedback
        ];

        wp_send_json_success($response);
    }

    /**
     * Handle revision upload from authors
     */
    public function upload_revision() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gfj_public')) {
            wp_send_json_error(['message' => 'Security verification failed.'], 403);
        }

        // Check permissions
        if (!current_user_can('submit_manuscripts')) {
            wp_send_json_error(['message' => 'You do not have permission to upload revisions.'], 403);
        }

        $manuscript_id = isset($_POST['manuscript_id']) ? intval($_POST['manuscript_id']) : 0;
        $revision_notes = isset($_POST['revision_notes']) ? wp_kses_post($_POST['revision_notes']) : '';

        if (!$manuscript_id || !$revision_notes) {
            wp_send_json_error(['message' => 'Missing required fields.'], 400);
        }

        $manuscript = get_post($manuscript_id);

        if (!$manuscript || $manuscript->post_type !== 'gfj_manuscript') {
            wp_send_json_error(['message' => 'Invalid manuscript.'], 404);
        }

        // Verify user is the author
        if ($manuscript->post_author != get_current_user_id()) {
            wp_send_json_error(['message' => 'You can only upload revisions for your own manuscripts.'], 403);
        }

        // Check manuscript is in revision stage
        $stage_terms = wp_get_post_terms($manuscript_id, 'manuscript_stage', ['fields' => 'slugs']);
        $current_stage = !empty($stage_terms) ? $stage_terms[0] : '';

        if ($current_stage !== 'revision') {
            wp_send_json_error(['message' => 'This manuscript is not in revision stage.'], 400);
        }

        // Validate files
        $required_files = ['blinded_file', 'full_file', 'latex_sources'];
        foreach ($required_files as $file) {
            if (!isset($_FILES[$file]) || $_FILES[$file]['error'] === UPLOAD_ERR_NO_FILE) {
                wp_send_json_error(['message' => "Required file missing: {$file}"], 400);
            }
        }

        // Get current revision number
        $revision_history = get_post_meta($manuscript_id, '_gfj_revision_history', true);
        if (!is_array($revision_history)) {
            $revision_history = [];
        }
        $revision_number = count($revision_history) + 1;

        // Backup old file IDs before uploading new ones
        $old_files = [
            'blinded' => get_post_meta($manuscript_id, '_gfj_blinded_file', true),
            'full' => get_post_meta($manuscript_id, '_gfj_full_file', true),
            'latex' => get_post_meta($manuscript_id, '_gfj_latex_file', true),
            'car' => get_post_meta($manuscript_id, '_gfj_car_file', true),
        ];

        // Handle file uploads
        $file_handler = new GFJ_File_Handler();

        $_POST['gfj_upload'] = 1;
        $_POST['gfj_upload_nonce'] = wp_create_nonce('gfj_file_upload');

        $file_map = [
            'blinded_file' => '_gfj_blinded_file',
            'full_file' => '_gfj_full_file',
            'latex_sources' => '_gfj_latex_file',
            'car_file' => '_gfj_car_file',
        ];

        $new_files = [];

        foreach ($file_map as $field => $meta_key) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
                $attachment_id = $file_handler->handle_manuscript_upload($field, $manuscript_id);

                if (is_wp_error($attachment_id)) {
                    wp_send_json_error([
                        'message' => "File upload failed for {$field}: " . $attachment_id->get_error_message()
                    ], 500);
                }

                // Update to new file
                update_post_meta($manuscript_id, $meta_key, $attachment_id);
                $new_files[str_replace('_gfj_', '', $meta_key)] = $attachment_id;
            }
        }

        // Record revision in history
        $revision_entry = [
            'revision_number' => $revision_number,
            'uploaded_at' => current_time('mysql'),
            'notes' => $revision_notes,
            'old_files' => $old_files,
            'new_files' => $new_files,
        ];

        $revision_history[] = $revision_entry;
        update_post_meta($manuscript_id, '_gfj_revision_history', $revision_history);

        // Update manuscript status based on revision type
        $revision_type = get_post_meta($manuscript_id, '_gfj_revision_type', true);

        if ($revision_type === 'review') {
            // Review-stage revision: goes back to review (not anonymous)
            wp_set_object_terms($manuscript_id, 'review', 'manuscript_stage');
        } else {
            // Triage-stage revision: goes back to triage (anonymous)
            wp_set_object_terms($manuscript_id, 'triage', 'manuscript_stage');
        }

        // Add revision notes to post meta
        update_post_meta($manuscript_id, '_gfj_latest_revision_notes', $revision_notes);
        update_post_meta($manuscript_id, '_gfj_revision_count', $revision_number);

        // Notify editors about the revision
        $this->notify_editors_revision_uploaded($manuscript_id, $revision_number, $revision_notes);

        // Log action
        do_action('gfj_revision_uploaded', $manuscript_id, $revision_number, get_current_user_id());

        wp_send_json_success([
            'message' => 'Revision uploaded successfully! (Revision #' . $revision_number . ')',
            'revision_number' => $revision_number,
        ]);
    }

    /**
     * Notify editors about uploaded revision
     */
    private function notify_editors_revision_uploaded($manuscript_id, $revision_number, $notes) {
        $manuscript = get_post($manuscript_id);
        $author = get_userdata($manuscript->post_author);

        // Get editors
        $editors = get_users(['role__in' => ['gfj_editor', 'gfj_eic', 'gfj_managing_editor']]);

        $subject = '[GFJ] Revision Uploaded: ' . $manuscript->post_title;

        $message = "A revised manuscript has been uploaded.\n\n";
        $message .= "Manuscript: {$manuscript->post_title}\n";
        $message .= "Author: {$author->display_name}\n";
        $message .= "Revision #: {$revision_number}\n\n";
        $message .= "Revision Notes:\n{$notes}\n\n";
        $message .= "View manuscript: " . admin_url('post.php?post=' . $manuscript_id . '&action=edit') . "\n";

        foreach ($editors as $editor) {
            wp_mail($editor->user_email, $subject, $message);
        }
    }

    /**
     * Change manuscript stage
     */
    public function change_stage() {
        // Verify nonce
        if (!check_ajax_referer('gfj_admin', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security verification failed.'], 403);
        }

        // Check permissions
        if (!current_user_can('make_decisions')) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }

        $manuscript_id = isset($_POST['manuscript_id']) ? intval($_POST['manuscript_id']) : 0;
        $stage = isset($_POST['stage']) ? sanitize_text_field($_POST['stage']) : '';

        $allowed_stages = ['triage', 'review', 'revision', 'accepted', 'rejected', 'published'];

        if (!$manuscript_id || !in_array($stage, $allowed_stages)) {
            wp_send_json_error(['message' => 'Invalid parameters.'], 400);
        }

        // Update stage
        wp_set_object_terms($manuscript_id, $stage, 'manuscript_stage');

        // Log action
        do_action('gfj_stage_changed', $manuscript_id, $stage, get_current_user_id());

        wp_send_json_success([
            'message' => 'Stage updated successfully.',
            'stage' => $stage
        ]);
    }

    /**
     * Auto-save field value
     */
    public function autosave() {
        // Verify nonce
        if (!check_ajax_referer('gfj_admin', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security verification failed.'], 403);
        }

        $manuscript_id = isset($_POST['manuscript_id']) ? intval($_POST['manuscript_id']) : 0;
        $field_name = isset($_POST['field_name']) ? sanitize_text_field($_POST['field_name']) : '';
        $field_value = isset($_POST['field_value']) ? wp_kses_post($_POST['field_value']) : '';

        if (!$manuscript_id || !$field_name) {
            wp_send_json_error(['message' => 'Invalid parameters.'], 400);
        }

        // Check permission
        if (!current_user_can('edit_post', $manuscript_id)) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }

        // Allowed fields for autosave
        $allowed_fields = ['gfj_triage_notes', 'gfj_decision_notes', 'gfj_internal_notes'];

        if (!in_array($field_name, $allowed_fields)) {
            wp_send_json_error(['message' => 'Field not allowed for autosave.'], 400);
        }

        update_post_meta($manuscript_id, '_' . $field_name, $field_value);

        wp_send_json_success(['message' => 'Saved']);
    }

    /**
     * Bulk assign reviewer to manuscripts
     */
    public function bulk_assign_reviewer() {
        // Verify nonce
        if (!check_ajax_referer('gfj_admin', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security verification failed.'], 403);
        }

        // Check permissions
        if (!current_user_can('assign_reviewers')) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }

        $manuscript_ids = isset($_POST['manuscript_ids']) ? array_map('intval', $_POST['manuscript_ids']) : [];
        $reviewer_id = isset($_POST['reviewer_id']) ? intval($_POST['reviewer_id']) : 0;

        if (empty($manuscript_ids) || !$reviewer_id) {
            wp_send_json_error(['message' => 'Invalid parameters.'], 400);
        }

        global $wpdb;
        $success_count = 0;

        foreach ($manuscript_ids as $manuscript_id) {
            // Check if already assigned
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}gfj_reviews
                 WHERE manuscript_id = %d AND reviewer_id = %d",
                $manuscript_id, $reviewer_id
            ));

            if ($exists) {
                continue;
            }

            // Create review invitation
            $wpdb->insert(
                $wpdb->prefix . 'gfj_reviews',
                [
                    'manuscript_id' => $manuscript_id,
                    'reviewer_id' => $reviewer_id,
                    'editor_id' => get_current_user_id(),
                    'status' => 'pending',
                    'due_date' => date('Y-m-d H:i:s', strtotime('+21 days')),
                    'created_at' => current_time('mysql'),
                ],
                ['%d', '%d', '%d', '%s', '%s', '%s']
            );

            $success_count++;
        }

        wp_send_json_success([
            'message' => "{$success_count} reviewer invitations sent successfully."
        ]);
    }
}
