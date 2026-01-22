<?php

class GFJ_Publisher_Handler {

    public function __construct() {
        // We initialize hooks manually or via a register method if prefered, 
        // but typically in this project structure, we register in class-gfj.php
    }

    public function register_handlers() {
        add_action('admin_post_gfj_publish_article', [$this, 'handle_publish_action']);
    }

    /**
     * Handle the publish action submission
     */
    public function handle_publish_action() {
        error_log('GFJ: handle_publish_action triggered');
        
        // 1. Verify Nonce & Permissions
        if (!isset($_POST['gfj_publish_nonce']) || !isset($_POST['manuscript_id'])) {
            error_log('GFJ: Missing nonce or ID');
            wp_die('Invalid request: Missing nonce or ID.');
        }

        $manuscript_id = intval($_POST['manuscript_id']);

        if (!wp_verify_nonce($_POST['gfj_publish_nonce'], 'gfj_publish_article_' . $manuscript_id)) {
            error_log('GFJ: Nonce verification failed');
            wp_die('Security check failed. Please refresh the page and try again.');
        }

        if (!current_user_can('publish_posts')) {
            error_log('GFJ: Permission denied');
            wp_die('You do not have permission to publish articles.');
        }

        error_log('GFJ: Permissions OK. Processing manuscript ' . $manuscript_id);

        // 2. Get Manuscript Data
        $manuscript = get_post($manuscript_id);
        if (!$manuscript || $manuscript->post_type !== 'gfj_manuscript') {
            error_log('GFJ: Invalid manuscript');
            wp_die('Invalid manuscript ID.');
        }

        if (!post_type_exists('gfj_article')) {
            error_log('GFJ: Article post type missing');
            wp_die('Configuration Error: "gfj_article" post type is not registered.');
        }

        // 3. Create new gfj_article post
        $abstract = get_post_meta($manuscript_id, '_gfj_abstract', true);
        
        $article_data = [
            'post_title'    => sanitize_text_field($manuscript->post_title),
            'post_content'  => '', 
            'post_excerpt'  => wp_kses_post($abstract),
            'post_status'   => 'draft', 
            'post_date'     => current_time('mysql'), // Ensure date is not in future
            'post_date_gmt' => current_time('mysql', 1),
            'post_type'     => 'gfj_article',
            'post_author'   => $manuscript->post_author,
        ];

        $article_id = wp_insert_post($article_data);

        if (is_wp_error($article_id)) {
            error_log('GFJ: Insert failed - ' . $article_id->get_error_message());
            wp_die('Error creating article: ' . $article_id->get_error_message());
        }

        if ($article_id === 0) {
            error_log('GFJ: Insert failed - ID 0');
            wp_die('Error creating article: Database insert failed (ID 0).');
        }

        error_log('GFJ: Article created with ID ' . $article_id);

        // 4. Copy Meta Data
        // - PDF File
        $full_file_id = get_post_meta($manuscript_id, '_gfj_full_file', true);
        if ($full_file_id) {
            $pdf_url = wp_get_attachment_url($full_file_id);
            update_post_meta($article_id, '_gfj_pdf_url', $pdf_url);
            update_post_meta($article_id, '_gfj_pdf_attachment_id', $full_file_id);
        }

        // - LaTeX Source
        $latex_file_id = get_post_meta($manuscript_id, '_gfj_latex_file', true);
        if ($latex_file_id) {
            $latex_url = wp_get_attachment_url($latex_file_id);
            update_post_meta($article_id, '_gfj_latex_url', $latex_url);
            update_post_meta($article_id, '_gfj_latex_attachment_id', $latex_file_id);
        }

        // - Artifacts Bundle
        $artifacts_file_id = get_post_meta($manuscript_id, '_gfj_artifacts_file', true);
        if ($artifacts_file_id) {
            $artifacts_url = wp_get_attachment_url($artifacts_file_id);
            update_post_meta($article_id, '_gfj_artifacts_url', $artifacts_url);
            update_post_meta($article_id, '_gfj_artifacts_attachment_id', $artifacts_file_id);
        }

        // - Other fields
        $significance = get_post_meta($manuscript_id, '_gfj_significance', true); // If this exists on manuscript? Spec didn't say it did, but if so copy it.
        // Actually manuscript doesn't seem to have significance/key findings in the previous steps. 
        // We will just initialize the article meta.
        
        update_post_meta($article_id, '_gfj_source_manuscript_id', $manuscript_id);
        update_post_meta($article_id, '_gfj_publication_date', current_time('Y-m-d'));
        
        // Generate a temporary DOI or placeholder if needed
        update_post_meta($article_id, '_gfj_doi', '10.XXXX/gfj.' . date('Y') . '.' . $article_id);

        // Author Display Name
        $author_data = get_userdata($manuscript->post_author);
        if ($author_data) {
            update_post_meta($article_id, '_gfj_author_display', $author_data->display_name);
        }

        // 5. Update Manuscript Status
        wp_set_object_terms($manuscript_id, 'published', 'manuscript_stage');

        // 6. Redirect to new Article Edit Screen
        wp_redirect(get_edit_post_link($article_id, 'raw'));
        exit;
    }
}
