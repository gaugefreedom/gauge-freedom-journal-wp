<?php
/**
 * Metabox Save Handler
 *
 * Handles saving manuscript metabox data with security validation
 */

if (!defined('ABSPATH')) {
    exit;
}

class GFJ_Metabox_Handler {

    public function __construct() {
        // Constructor
    }

    /**
     * Save manuscript metadata
     */
    public function save_manuscript_meta($post_id, $post) {
        // Security checks
        if (!isset($_POST['gfj_manuscript_details_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['gfj_manuscript_details_nonce'], 'gfj_manuscript_details')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check if this is a manuscript post type
        if ('gfj_manuscript' !== $post->post_type) {
            return;
        }

        // Save metadata fields
        $meta_fields = [
            'gfj_abstract' => 'wp_kses_post',
            'gfj_keywords' => 'sanitize_text_field',
            'gfj_code_repo' => 'esc_url_raw',
            'gfj_data_repo' => 'esc_url_raw',
            'gfj_ai_statement' => 'wp_kses_post',
            'gfj_conflicts' => 'wp_kses_post',
        ];

        foreach ($meta_fields as $field => $sanitize_func) {
            if (isset($_POST[$field])) {
                $meta_key = '_' . $field;
                $meta_value = $sanitize_func($_POST[$field]);
                update_post_meta($post_id, $meta_key, $meta_value);
            }
        }

        // Handle file uploads if present
        if (!empty($_FILES)) {
            $this->handle_metabox_file_uploads($post_id);
        }
    }

    /**
     * Handle file uploads from metabox
     */
    private function handle_metabox_file_uploads($post_id) {
        $file_handler = new GFJ_File_Handler();

        $_POST['gfj_upload'] = 1;
        $_POST['gfj_upload_nonce'] = wp_create_nonce('gfj_file_upload');

        $file_map = [
            'gfj_blinded_file' => '_gfj_blinded_file',
            'gfj_full_file' => '_gfj_full_file',
            'gfj_latex_file' => '_gfj_latex_file',
            'gfj_car_file' => '_gfj_car_file',
        ];

        foreach ($file_map as $field => $meta_key) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
                $attachment_id = $file_handler->handle_manuscript_upload($field, $post_id);

                if (!is_wp_error($attachment_id)) {
                    // Delete old attachment if exists
                    $old_attachment = get_post_meta($post_id, $meta_key, true);
                    if ($old_attachment) {
                        wp_delete_attachment($old_attachment, true);
                    }

                    update_post_meta($post_id, $meta_key, $attachment_id);
                }
            }
        }
    }
}
