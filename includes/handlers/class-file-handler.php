<?php
/**
 * Secure File Upload Handler
 *
 * Handles manuscript file uploads with security validation
 */

if (!defined('ABSPATH')) {
    exit;
}

class GFJ_File_Handler {

    /**
     * Allowed MIME types for uploads
     */
    private $allowed_types = [
        'pdf' => 'application/pdf',
        'zip' => 'application/zip',
        'json' => 'application/json',
        'car' => 'application/octet-stream', // CAR files
    ];

    /**
     * Maximum file sizes (in bytes)
     */
    private $max_sizes = [
        'pdf' => 52428800,    // 50 MB
        'zip' => 104857600,   // 100 MB
        'json' => 10485760,   // 10 MB
        'car' => 10485760,    // 10 MB
    ];

    public function __construct() {
        // Constructor
    }

    /**
     * Register handlers
     *
     * Note: MIME type registration is now handled in the main plugin file
     * (gauge-freedom-journal.php) for JSON and CAR files.
     */
    public function register_handlers() {
        add_filter('wp_handle_upload_prefilter', [$this, 'validate_upload']);
    }

    /**
     * Validate uploaded file before processing
     */
    public function validate_upload($file) {
        // Check if this is a GFJ upload
        if (!isset($_POST['gfj_upload']) || !wp_verify_nonce($_POST['gfj_upload_nonce'], 'gfj_file_upload')) {
            return $file;
        }

        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_ext = strtolower($file_ext);

        // Validate extension
        if (!array_key_exists($file_ext, $this->allowed_types)) {
            $file['error'] = 'File type not allowed. Only PDF, ZIP, JSON, and CAR files are permitted.';
            return $file;
        }

        // Validate size
        if (isset($this->max_sizes[$file_ext]) && $file['size'] > $this->max_sizes[$file_ext]) {
            $max_mb = $this->max_sizes[$file_ext] / 1048576;
            $file['error'] = sprintf('File too large. Maximum size for %s files is %d MB.', strtoupper($file_ext), $max_mb);
            return $file;
        }

        // Validate MIME type (real check, not just extension)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $real_mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        // For PDF and ZIP, strict MIME validation
        if (in_array($file_ext, ['pdf', 'zip']) && $real_mime !== $this->allowed_types[$file_ext]) {
            $file['error'] = 'File MIME type does not match extension. Possible file spoofing detected.';
            return $file;
        }

        // Sanitize filename
        $file['name'] = $this->sanitize_filename($file['name']);

        return $file;
    }

    /**
     * Sanitize filename
     */
    private function sanitize_filename($filename) {
        // Remove special characters and spaces
        $filename = sanitize_file_name($filename);

        // Add timestamp prefix to prevent collisions
        $info = pathinfo($filename);
        $name = $info['filename'];
        $ext = isset($info['extension']) ? '.' . $info['extension'] : '';

        return sanitize_title($name) . '-' . time() . $ext;
    }

    /**
     * Handle manuscript file uploads
     */
    public function handle_manuscript_upload($file_field, $manuscript_id) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        // Verify permissions
        if (!current_user_can('submit_manuscripts') && !current_user_can('edit_manuscripts')) {
            return new WP_Error('permission_denied', 'You do not have permission to upload files.');
        }

        // Check if file exists
        if (!isset($_FILES[$file_field]) || $_FILES[$file_field]['error'] === UPLOAD_ERR_NO_FILE) {
            return new WP_Error('no_file', 'No file uploaded.');
        }

        // Check for upload errors
        if ($_FILES[$file_field]['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', 'File upload failed with error code: ' . $_FILES[$file_field]['error']);
        }

        // Set upload overrides
        $upload_overrides = [
            'test_form' => false,
            'mimes' => $this->allowed_types,
        ];

        // Handle upload
        $uploaded_file = wp_handle_upload($_FILES[$file_field], $upload_overrides);

        if (isset($uploaded_file['error'])) {
            return new WP_Error('upload_failed', $uploaded_file['error']);
        }

        // Create attachment
        $attachment_id = $this->create_attachment($uploaded_file, $manuscript_id);

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        return $attachment_id;
    }

    /**
     * Create WordPress attachment
     */
    private function create_attachment($file, $post_id = 0) {
        $file_path = $file['file'];
        $file_name = basename($file_path);
        $file_type = $file['type'];

        // Prepare attachment data
        $attachment = [
            'guid' => $file['url'],
            'post_mime_type' => $file_type,
            'post_title' => sanitize_file_name(pathinfo($file_name, PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        // Insert attachment
        $attachment_id = wp_insert_attachment($attachment, $file_path, $post_id);

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        return $attachment_id;
    }

    /**
     * Protect file access based on stage and role
     */
    public function protect_file_access() {
        // Only process attachment URLs
        if (!is_attachment()) {
            return;
        }

        $attachment_id = get_queried_object_id();
        $post_parent = wp_get_post_parent_id($attachment_id);

        // Check if this is a manuscript attachment
        if (!$post_parent || get_post_type($post_parent) !== 'gfj_manuscript') {
            return;
        }

        $user_id = get_current_user_id();

        // Not logged in
        if (!$user_id) {
            wp_die('You must be logged in to access this file.', 'Access Denied', ['response' => 403]);
        }

        // Check file type and apply access rules
        $attachment_meta_key = $this->get_attachment_meta_key($attachment_id, $post_parent);

        if (!$attachment_meta_key) {
            // Not a GFJ managed file
            return;
        }

        // Apply access control based on file type
        $can_access = $this->check_file_access_permission($user_id, $post_parent, $attachment_meta_key);

        if (!$can_access) {
            wp_die(
                'You do not have permission to access this file. Access to manuscript files is restricted based on your role and the manuscript stage.',
                'Access Denied',
                ['response' => 403]
            );
        }
    }

    /**
     * Get attachment meta key for a file
     */
    private function get_attachment_meta_key($attachment_id, $manuscript_id) {
        $meta_keys = [
            '_gfj_blinded_file',
            '_gfj_full_file',
            '_gfj_latex_file',
            '_gfj_car_file'
        ];

        foreach ($meta_keys as $key) {
            $stored_id = get_post_meta($manuscript_id, $key, true);
            if ($stored_id == $attachment_id) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Check file access permission
     */
    private function check_file_access_permission($user_id, $manuscript_id, $meta_key) {
        $manuscript = get_post($manuscript_id);
        if (!$manuscript) {
            return false;
        }

        // Author always has access to their files
        if ($manuscript->post_author == $user_id) {
            return true;
        }

        $stage = wp_get_post_terms($manuscript_id, 'manuscript_stage', ['fields' => 'slugs']);
        $current_stage = !empty($stage) ? $stage[0] : 'triage';

        // Blinded file access
        if ($meta_key === '_gfj_blinded_file') {
            // Reviewers and editors can access blinded files
            return current_user_can('view_blinded_manuscripts') || current_user_can('view_full_manuscripts');
        }

        // Full file access (locked during triage)
        if ($meta_key === '_gfj_full_file') {
            if ($current_stage === 'triage') {
                // Only EiC can access full manuscript during triage
                return current_user_can('override_decisions');
            }
            return GFJ_Permissions::can_view_full_manuscript($user_id, $manuscript_id);
        }

        // LaTeX and CAR files (same rules as full manuscript)
        if (in_array($meta_key, ['_gfj_latex_file', '_gfj_car_file'])) {
            return GFJ_Permissions::can_view_full_manuscript($user_id, $manuscript_id);
        }

        return false;
    }

    /**
     * Delete attachment files when manuscript is deleted
     */
    public function delete_manuscript_files($post_id) {
        if (get_post_type($post_id) !== 'gfj_manuscript') {
            return;
        }

        $file_keys = [
            '_gfj_blinded_file',
            '_gfj_full_file',
            '_gfj_latex_file',
            '_gfj_car_file'
        ];

        foreach ($file_keys as $key) {
            $attachment_id = get_post_meta($post_id, $key, true);
            if ($attachment_id) {
                wp_delete_attachment($attachment_id, true);
            }
        }
    }
}
