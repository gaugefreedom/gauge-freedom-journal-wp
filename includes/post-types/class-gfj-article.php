<?php

class GFJ_Article_Post_Type {

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_meta_boxes']);
        add_action('save_post_gfj_article', [$this, 'save_article_meta'], 10, 2);
    }

    /**
     * Register Article custom post type and taxonomies
     */
    public function register_post_type() {

        $labels = [
            'name'                  => 'Articles',
            'singular_name'         => 'Article',
            'menu_name'             => 'Articles',
            'name_admin_bar'        => 'Article',
            'add_new'               => 'Add New',
            'add_new_item'          => 'Add New Article',
            'new_item'              => 'New Article',
            'edit_item'             => 'Edit Article',
            'view_item'             => 'View Article',
            'all_items'             => 'All Articles',
            'search_items'          => 'Search Articles',
            'parent_item_colon'     => 'Parent Articles:',
            'not_found'             => 'No articles found.',
            'not_found_in_trash'    => 'No articles found in Trash.',
            'featured_image'        => 'Article Cover Image',
            'set_featured_image'    => 'Set cover image',
            'remove_featured_image' => 'Remove cover image',
            'use_featured_image'    => 'Use as cover image',
            'archives'              => 'Article Archives',
            'insert_into_item'      => 'Insert into article',
            'uploaded_to_this_item' => 'Uploaded to this article',
            'filter_items_list'     => 'Filter articles list',
            'items_list_navigation' => 'Articles list navigation',
            'items_list'            => 'Articles list',
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => ['slug' => 'article'],
            'capability_type'    => 'post',
            'has_archive'        => 'articles',
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-book',
            'supports'           => ['title', 'editor', 'excerpt', 'author', 'thumbnail', 'custom-fields'],
        ];

        register_post_type('gfj_article', $args);

        // Register custom taxonomy: gfj_topic
        $topic_labels = [
            'name'              => 'Topics',
            'singular_name'     => 'Topic',
            'search_items'      => 'Search Topics',
            'all_items'         => 'All Topics',
            'parent_item'       => 'Parent Topic',
            'parent_item_colon' => 'Parent Topic:',
            'edit_item'         => 'Edit Topic',
            'update_item'       => 'Update Topic',
            'add_new_item'      => 'Add New Topic',
            'new_item_name'     => 'New Topic Name',
            'menu_name'         => 'Topics',
        ];

        $topic_args = [
            'hierarchical'      => true,
            'labels'            => $topic_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'topic'],
        ];

        register_taxonomy('gfj_topic', ['gfj_article'], $topic_args);

        // Register custom taxonomy: gfj_issue (Volume/Issue)
        $issue_labels = [
            'name'              => 'Volumes & Issues',
            'singular_name'     => 'Volume/Issue',
            'search_items'      => 'Search Volumes/Issues',
            'all_items'         => 'All Volumes & Issues',
            'parent_item'       => 'Parent Volume',
            'parent_item_colon' => 'Parent Volume:',
            'edit_item'         => 'Edit Issue',
            'update_item'       => 'Update Issue',
            'add_new_item'      => 'Add New Issue',
            'new_item_name'     => 'New Issue Name',
            'menu_name'         => 'Volumes/Issues',
        ];

        $issue_args = [
            'hierarchical'      => true,
            'labels'            => $issue_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'issue'],
        ];

        register_taxonomy('gfj_issue', ['gfj_article'], $issue_args);
    }

    /**
     * Register meta boxes for article data
     */
    public function register_meta_boxes() {
        add_action('add_meta_boxes', function() {
            add_meta_box(
                'gfj_article_data',
                'Article Metadata',
                [$this, 'render_article_data_metabox'],
                'gfj_article',
                'normal',
                'high'
            );
        });
    }

    /**
     * Render the article metadata metabox
     *
     * @param WP_Post $post The post object.
     */
    public function render_article_data_metabox($post) {
        wp_nonce_field('gfj_article_data', 'gfj_article_data_nonce');

        $doi = get_post_meta($post->ID, '_gfj_doi', true);
        $author_display = get_post_meta($post->ID, '_gfj_author_display', true);
        $pdf_url = get_post_meta($post->ID, '_gfj_pdf_url', true);
        $latex_url = get_post_meta($post->ID, '_gfj_latex_url', true);
        $artifacts_url = get_post_meta($post->ID, '_gfj_artifacts_url', true);
        $significance = get_post_meta($post->ID, '_gfj_significance', true);
        $key_findings = get_post_meta($post->ID, '_gfj_key_findings', true);
        $ai_disclosure = get_post_meta($post->ID, '_gfj_ai_disclosure', true);
        $publication_date = get_post_meta($post->ID, '_gfj_publication_date', true);
        $source_manuscript_id = get_post_meta($post->ID, '_gfj_source_manuscript_id', true);

        // If key_findings is an array, we might want to display it as a list or handle it.
        // For simplicity in the admin text area, we'll treat it as text (maybe new line separated or HTML)
        // or check if it's serialized. If it's serialized, we might need a better UI.
        // The spec says "Serialized array or HTML list". Let's assume for now the user might input HTML or text.
        // If it's already an array, we'll implode it for the textarea.
        if (is_array($key_findings)) {
            $key_findings = implode("\n", $key_findings);
        }

        ?>
        <table class="form-table">
            <tr>
                <th><label for="gfj_doi">DOI</label></th>
                <td>
                    <input type="text" name="gfj_doi" id="gfj_doi" value="<?php echo esc_attr($doi); ?>" class="large-text">
                </td>
            </tr>
            <tr>
                <th><label for="gfj_author_display">Author(s) Display String</label></th>
                <td>
                    <input type="text" name="gfj_author_display" id="gfj_author_display" value="<?php echo esc_attr($author_display); ?>" class="large-text">
                    <p class="description">How authors appear on the paper (e.g. "Jane Doe, John Smith et al.")</p>
                </td>
            </tr>
            <tr>
                <th><label for="gfj_pdf_url">PDF URL</label></th>
                <td>
                    <input type="url" name="gfj_pdf_url" id="gfj_pdf_url" value="<?php echo esc_url($pdf_url); ?>" class="large-text">
                </td>
            </tr>
            <?php 
            $latex_url = get_post_meta($post->ID, '_gfj_latex_url', true);
            ?>
            <tr>
                <th><label for="gfj_latex_url">LaTeX Source URL</label></th>
                <td>
                    <input type="url" name="gfj_latex_url" id="gfj_latex_url" value="<?php echo esc_url($latex_url); ?>" class="large-text">
                </td>
            </tr>
            <tr>
                <th><label for="gfj_artifacts_url">Artifacts Bundle URL</label></th>
                <td>
                    <input type="url" name="gfj_artifacts_url" id="gfj_artifacts_url" value="<?php echo esc_url($artifacts_url); ?>" class="large-text">
                    <p class="description">URL to the ZIP bundle: CARs, Logs, etc.</p>
                </td>
            </tr>
            <tr>
                <th><label for="gfj_publication_date">Publication Date</label></th>
                <td>
                    <input type="date" name="gfj_publication_date" id="gfj_publication_date" value="<?php echo esc_attr($publication_date); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="gfj_significance">Significance Statement</label></th>
                <td>
                    <textarea name="gfj_significance" id="gfj_significance" rows="4" class="large-text"><?php echo esc_textarea($significance); ?></textarea>
                </td>
            </tr>
            <?php 
            $citation_override = get_post_meta($post->ID, '_gfj_citation_override', true);
            ?>
            <tr>
                <th><label for="gfj_citation_override">Citation Override</label></th>
                <td>
                    <textarea name="gfj_citation_override" id="gfj_citation_override" rows="3" class="large-text"><?php echo esc_textarea($citation_override); ?></textarea>
                    <p class="description">Fully override the "How to Cite" text. (e.g. "Smith, J. (2024). Title. Journal, 1(1).")</p>
                </td>
            </tr>
            <?php 
            $bibtex_override = get_post_meta($post->ID, '_gfj_bibtex_override', true);
            ?>
            <tr>
                <th><label for="gfj_bibtex_override">BibTeX Override</label></th>
                <td>
                    <textarea name="gfj_bibtex_override" id="gfj_bibtex_override" rows="5" class="large-text" style="font-family:monospace;"><?php echo esc_textarea($bibtex_override); ?></textarea>
                    <p class="description">Fully override the BibTeX code block.</p>
                </td>
            </tr>
            <tr>
                <th><label for="gfj_key_findings">Key Findings</label></th>
                <td>
                    <textarea name="gfj_key_findings" id="gfj_key_findings" rows="5" class="large-text"><?php echo esc_textarea($key_findings); ?></textarea>
                    <p class="description">Enter key findings. HTML list or text.</p>
                </td>
            </tr>
            <tr>
                <th><label for="gfj_ai_disclosure">AI Disclosure</label></th>
                <td>
                    <textarea name="gfj_ai_disclosure" id="gfj_ai_disclosure" rows="3" class="large-text"><?php echo esc_textarea($ai_disclosure); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="gfj_source_manuscript_id">Source Manuscript ID</label></th>
                <td>
                    <input type="number" name="gfj_source_manuscript_id" id="gfj_source_manuscript_id" value="<?php echo esc_attr($source_manuscript_id); ?>" class="small-text">
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save article metadata
     *
     * @param int $post_id The ID of the post being saved.
     * @param WP_Post $post The post object.
     */
    public function save_article_meta($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['gfj_article_data_nonce']) || !wp_verify_nonce($_POST['gfj_article_data_nonce'], 'gfj_article_data')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Sanitize and save fields
        $fields = [
            'gfj_doi'                  => 'sanitize_text_field',
            'gfj_author_display'       => 'sanitize_text_field',
            'gfj_pdf_url'              => 'esc_url_raw',
            'gfj_latex_url'            => 'esc_url_raw',
            'gfj_artifacts_url'        => 'esc_url_raw',
            'gfj_publication_date'     => 'sanitize_text_field',
            'gfj_significance'         => 'sanitize_textarea_field',
            'gfj_citation_override'    => 'wp_kses_post', // Allow HTML (italics etc)
            'gfj_bibtex_override'      => 'wp_kses_post', // Allow formatting
            'gfj_key_findings'         => 'wp_kses_post', // Allow HTML in key findings
            'gfj_ai_disclosure'        => 'sanitize_textarea_field',
            'gfj_source_manuscript_id' => 'absint',
        ];

        foreach ($fields as $field => $sanitizer) {
            if (isset($_POST[$field])) {
                $meta_key = '_' . $field; // Prepend underscore to key name as defined in render function (e.g., _gfj_doi)
                $value = $_POST[$field];
                
                if ($sanitizer === 'wp_kses_post') {
                    $value = wp_kses_post($value);
                } elseif (function_exists($sanitizer)) {
                    $value = $sanitizer($value);
                }

                update_post_meta($post_id, $meta_key, $value);
            }
        }
    }
}
