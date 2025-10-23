<?php

class GFJ_Manuscript_Post_Type {
    
    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_meta_boxes']);
    }
    
    /**
     * Register manuscript custom post type
     */
    public function register_post_type() {
        
        $labels = [
            'name' => 'Manuscripts',
            'singular_name' => 'Manuscript',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Manuscript',
            'edit_item' => 'Edit Manuscript',
            'view_item' => 'View Manuscript',
            'search_items' => 'Search Manuscripts',
        ];
        
        $args = [
            'labels' => $labels,
            'public' => false, // Not public to enforce access control
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-media-document',
            'capability_type' => 'post',
            'capabilities' => [
                'edit_post' => 'edit_manuscripts',
                'read_post' => 'view_manuscripts',
                'delete_post' => 'delete_manuscripts',
                'edit_posts' => 'edit_manuscripts',
                'edit_others_posts' => 'edit_manuscripts',
                'delete_posts' => 'delete_manuscripts',
                'publish_posts' => 'publish_manuscripts',
                'read_private_posts' => 'view_manuscripts',
            ],
            'supports' => ['title', 'author', 'custom-fields'],
            'has_archive' => false,
            'rewrite' => ['slug' => 'manuscripts'],
        ];
        
        register_post_type('gfj_manuscript', $args);
        
        // Register taxonomies
        register_taxonomy('manuscript_type', 'gfj_manuscript', [
            'labels' => [
                'name' => 'Article Types',
                'singular_name' => 'Article Type',
            ],
            'hierarchical' => true,
            'show_admin_column' => true,
        ]);
        
        register_taxonomy('manuscript_stage', 'gfj_manuscript', [
            'labels' => [
                'name' => 'Workflow Stages',
                'singular_name' => 'Stage',
            ],
            'hierarchical' => true,
            'show_admin_column' => true,
        ]);
        
        // Add default terms
        $this->create_default_terms();
    }
    
    /**
     * Create default taxonomy terms
     */
    private function create_default_terms() {
        // Article types
        $article_types = [
            'research' => 'Research Article',
            'short' => 'Short Communication',
            'protocol' => 'Registered Protocol',
            'perspective' => 'Perspective/Tutorial',
            'reproducibility' => 'Reproducibility Report',
            'dataset' => 'Dataset/Software Note',
        ];
        
        foreach ($article_types as $slug => $name) {
            if (!term_exists($name, 'manuscript_type')) {
                wp_insert_term($name, 'manuscript_type', ['slug' => $slug]);
            }
        }
        
        // Workflow stages
        $stages = [
            'triage' => 'Triage',
            'review' => 'In Review',
            'revision' => 'Revision Required',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'published' => 'Published',
        ];
        
        foreach ($stages as $slug => $name) {
            if (!term_exists($name, 'manuscript_stage')) {
                wp_insert_term($name, 'manuscript_stage', ['slug' => $slug]);
            }
        }
    }
    
    /**
     * Register meta boxes for manuscript data
     */
    public function register_meta_boxes() {
        add_action('add_meta_boxes', function() {
            
            // Manuscript Details
            add_meta_box(
                'gfj_manuscript_details',
                'Manuscript Details',
                [$this, 'render_details_metabox'],
                'gfj_manuscript',
                'normal',
                'high'
            );
            
            // Files (Blinded & Full)
            add_meta_box(
                'gfj_manuscript_files',
                'Files',
                [$this, 'render_files_metabox'],
                'gfj_manuscript',
                'normal',
                'high'
            );
            
            // Workflow Status (Editors only)
            if (current_user_can('triage_manuscripts')) {
                add_meta_box(
                    'gfj_workflow_triage',
                    'Triage Decision',
                    [$this, 'render_triage_metabox'],
                    'gfj_manuscript',
                    'side',
                    'high'
                );
            }
            
            // Reviews (Editors only)
            if (current_user_can('assign_reviewers')) {
                add_meta_box(
                    'gfj_manuscript_reviews',
                    'Reviews',
                    [$this, 'render_reviews_metabox'],
                    'gfj_manuscript',
                    'normal',
                    'default'
                );
            }
        });
    }
    
    /**
     * Render manuscript details metabox
     */
    public function render_details_metabox($post) {
        wp_nonce_field('gfj_manuscript_details', 'gfj_manuscript_details_nonce');

        $abstract = get_post_meta($post->ID, '_gfj_abstract', true);
        $keywords = get_post_meta($post->ID, '_gfj_keywords', true);
        $ai_statement = get_post_meta($post->ID, '_gfj_ai_statement', true);
        $conflicts = get_post_meta($post->ID, '_gfj_conflicts', true);
        $code_repo = get_post_meta($post->ID, '_gfj_code_repo', true);
        $data_repo = get_post_meta($post->ID, '_gfj_data_repo', true);
        $revision_notes = get_post_meta($post->ID, '_gfj_latest_revision_notes', true);
        $revision_count = get_post_meta($post->ID, '_gfj_revision_count', true);

        ?>

        <?php if ($revision_notes): ?>
        <div style="background: #f0f6ff; border: 2px solid #2271b1; border-radius: 6px; padding: 15px; margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: #2271b1;">📝 Author's Revision Notes <?php echo $revision_count ? '(Revision #' . $revision_count . ')' : ''; ?></h3>
            <div style="background: white; padding: 12px; border-radius: 4px; white-space: pre-wrap; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                <?php echo esc_html($revision_notes); ?>
            </div>
            <p style="margin-bottom: 0; margin-top: 10px; font-size: 13px; color: #646970;">
                <strong>Note:</strong> The author has submitted a revised version. Please review the changes and make a triage decision.
            </p>
        </div>
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th><label for="gfj_abstract">Abstract</label></th>
                <td>
                    <textarea name="gfj_abstract" id="gfj_abstract" rows="8" class="large-text"><?php echo esc_textarea($abstract); ?></textarea>
                    <p class="description">Visible during triage</p>
                </td>
            </tr>
            <tr>
                <th><label for="gfj_keywords">Keywords</label></th>
                <td>
                    <input type="text" name="gfj_keywords" id="gfj_keywords" value="<?php echo esc_attr($keywords); ?>" class="large-text">
                    <p class="description">Comma-separated</p>
                </td>
            </tr>
            <tr>
                <th><label for="gfj_code_repo">Code Repository</label></th>
                <td>
                    <input type="url" name="gfj_code_repo" id="gfj_code_repo" value="<?php echo esc_url($code_repo); ?>" class="large-text">
                </td>
            </tr>
            <tr>
                <th><label for="gfj_data_repo">Data Repository</label></th>
                <td>
                    <input type="url" name="gfj_data_repo" id="gfj_data_repo" value="<?php echo esc_url($data_repo); ?>" class="large-text">
                </td>
            </tr>
            <tr>
                <th><label for="gfj_ai_statement">AI Contributions Statement</label></th>
                <td>
                    <textarea name="gfj_ai_statement" id="gfj_ai_statement" rows="4" class="large-text"><?php echo esc_textarea($ai_statement); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="gfj_conflicts">Conflicts of Interest</label></th>
                <td>
                    <textarea name="gfj_conflicts" id="gfj_conflicts" rows="3" class="large-text"><?php echo esc_textarea($conflicts); ?></textarea>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render files metabox with access control
     */
    public function render_files_metabox($post) {
        $stage = wp_get_post_terms($post->ID, 'manuscript_stage', ['fields' => 'slugs']);
        $current_stage = !empty($stage) ? $stage[0] : 'triage';
        
        $blinded_file = get_post_meta($post->ID, '_gfj_blinded_file', true);
        $full_file = get_post_meta($post->ID, '_gfj_full_file', true);
        $latex_file = get_post_meta($post->ID, '_gfj_latex_file', true);
        $car_file = get_post_meta($post->ID, '_gfj_car_file', true);
        
        $can_view_full = current_user_can('view_full_manuscripts') && $current_stage !== 'triage';
        $can_view_blinded = current_user_can('view_blinded_manuscripts') || current_user_can('triage_manuscripts');
        $is_author = get_current_user_id() == $post->post_author;

        ?>
        <table class="form-table">
            <?php if ($is_author || $can_view_blinded): ?>
            <tr>
                <th>Blinded Manuscript (PDF)</th>
                <td>
                    <?php if ($blinded_file): ?>
                        <a href="<?php echo wp_get_attachment_url($blinded_file); ?>" class="button" target="_blank">
                            📄 View Blinded Version
                        </a>
                        <?php if ($current_stage === 'triage' && current_user_can('triage_manuscripts')): ?>
                            <p class="description" style="color: #2563eb; font-weight: 600;">
                                ℹ️ Review this blinded version for triage decision
                            </p>
                        <?php else: ?>
                            <p class="description">For reviewers - no author identifying information</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <input type="file" name="gfj_blinded_file" accept=".pdf">
                        <p class="description">For reviewers - no author identifying information</p>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            
            <?php if ($is_author || $can_view_full): ?>
            <tr>
                <th>Full Manuscript (PDF)</th>
                <td>
                    <?php if ($full_file): ?>
                        <a href="<?php echo wp_get_attachment_url($full_file); ?>" class="button" target="_blank">
                            📄 View Full Version
                        </a>
                        <?php if ($current_stage === 'triage'): ?>
                            <p class="description" style="color: #d63638;">🔒 Locked until triage approval</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <input type="file" name="gfj_full_file" accept=".pdf">
                    <?php endif; ?>
                    <p class="description">Complete manuscript with author information</p>
                </td>
            </tr>
            
            <tr>
                <th>LaTeX Sources (ZIP)</th>
                <td>
                    <?php if ($latex_file): ?>
                        <a href="<?php echo wp_get_attachment_url($latex_file); ?>" class="button" target="_blank">
                            📦 Download Sources
                        </a>
                    <?php else: ?>
                        <input type="file" name="gfj_latex_file" accept=".zip">
                    <?php endif; ?>
                </td>
            </tr>
            
            <tr>
                <th>CAR File (JSON)</th>
                <td>
                    <?php if ($car_file): ?>
                        <a href="<?php echo wp_get_attachment_url($car_file); ?>" class="button" target="_blank">
                            🔐 View CAR
                        </a>
                    <?php else: ?>
                        <input type="file" name="gfj_car_file" accept=".json,.car">
                    <?php endif; ?>
                    <p class="description">Content-Addressable Receipt</p>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }
    
    /**
     * Render triage metabox
     */
    public function render_triage_metabox($post) {
        $stage = wp_get_post_terms($post->ID, 'manuscript_stage', ['fields' => 'slugs']);
        $current_stage = !empty($stage) ? $stage[0] : 'triage';
        
        if ($current_stage !== 'triage') {
            echo '<p><strong>Status:</strong> ' . ucfirst($current_stage) . '</p>';
            return;
        }
        
        $submitted_date = get_the_date('Y-m-d H:i', $post);
        $deadline = get_post_meta($post->ID, '_gfj_triage_deadline', true);
        
        ?>
        <div class="gfj-triage-box">
            <p><strong>Submitted:</strong> <?php echo $submitted_date; ?></p>
            <p><strong>Deadline:</strong> <?php echo $deadline; ?></p>
            
            <p><strong>Triage Decision:</strong></p>
            <label>
                <input type="radio" name="gfj_triage_decision" value="approve"> 
                ✅ Send to Review
            </label><br>
            <label>
                <input type="radio" name="gfj_triage_decision" value="request_changes">
                ⚠️ Request Changes
            </label><br>
            <label>
                <input type="radio" name="gfj_triage_decision" value="desk_reject">
                ❌ Desk Reject
            </label>
            
            <p><label for="gfj_triage_notes">Decision Notes:</label></p>
            <textarea name="gfj_triage_notes" id="gfj_triage_notes" rows="4" class="widefat"></textarea>
            
            <p>
                <button type="button" class="button button-primary" onclick="gfjSubmitTriage()">
                    Submit Triage Decision
                </button>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render reviews metabox
     */
    public function render_reviews_metabox($post) {
        global $wpdb;
        
        // Get assigned reviews
        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gfj_reviews WHERE manuscript_id = %d",
            $post->ID
        ));
        
        ?>
        <div class="gfj-reviews-section">
            <h4>Assigned Reviewers</h4>
            
            <?php if (empty($reviews)): ?>
                <p>No reviewers assigned yet.</p>
            <?php else: ?>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th>Reviewer</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): 
                            $reviewer = get_userdata($review->reviewer_id);
                        ?>
                        <tr>
                            <td><?php echo esc_html($reviewer->display_name); ?></td>
                            <td><?php echo esc_html($review->status); ?></td>
                            <td><?php echo esc_html($review->due_date); ?></td>
                            <td>
                                <?php if ($review->status === 'completed'): ?>
                                    <button type="button" class="button button-small gfj-view-review-btn"
                                            data-review-id="<?php echo $review->id; ?>">
                                        View Review
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <hr>
            
            <h4>Invite New Reviewer</h4>
            <p>
                <label for="gfj_reviewer_select">Select Reviewer:</label>
                <select name="gfj_reviewer_select" id="gfj_reviewer_select" class="widefat">
                    <option value="">-- Select --</option>
                    <?php
                    $reviewers = get_users(['role' => 'gfj_reviewer']);
                    foreach ($reviewers as $reviewer):
                    ?>
                        <option value="<?php echo $reviewer->ID; ?>">
                            <?php echo esc_html($reviewer->display_name . ' (' . $reviewer->user_email . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <button type="button" class="button" onclick="gfjInviteReviewer(<?php echo $post->ID; ?>)">
                    Send Invitation
                </button>
            </p>
        </div>
        <?php
    }
}