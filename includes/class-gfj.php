<?php
/**
 * Main Plugin Class
 *
 * Coordinates all plugin functionality and initializes components
 */

if (!defined('ABSPATH')) {
    exit;
}

class GFJ {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = GFJ_VERSION;
        $this->plugin_name = 'gauge-freedom-journal';

        $this->load_dependencies();
        $this->define_hooks();
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {

        // Core classes
        require_once GFJ_PLUGIN_DIR . 'includes/roles/class-gfj-roles.php';
        require_once GFJ_PLUGIN_DIR . 'includes/access-control/class-permissions.php';
        require_once GFJ_PLUGIN_DIR . 'includes/post-types/class-manuscript.php';
        require_once GFJ_PLUGIN_DIR . 'includes/class-gfj-login.php';

        // Handlers
        require_once GFJ_PLUGIN_DIR . 'includes/handlers/class-file-handler.php';
        require_once GFJ_PLUGIN_DIR . 'includes/handlers/class-ajax-handler.php';
        require_once GFJ_PLUGIN_DIR . 'includes/handlers/class-metabox-handler.php';

        // Access control
        require_once GFJ_PLUGIN_DIR . 'includes/access-control/class-query-filters.php';

        // Initialize post type
        new GFJ_Manuscript_Post_Type();

        // Initialize login handler
        new GFJ_Login();
    }

    /**
     * Register hooks
     */
    private function define_hooks() {

        // File uploads
        $file_handler = new GFJ_File_Handler();
        add_action('init', [$file_handler, 'register_handlers']);

        // AJAX endpoints
        $ajax_handler = new GFJ_Ajax_Handler();
        add_action('wp_ajax_gfj_submit_manuscript', [$ajax_handler, 'submit_manuscript']);
        add_action('wp_ajax_gfj_submit_triage', [$ajax_handler, 'submit_triage']);
        add_action('wp_ajax_gfj_invite_reviewer', [$ajax_handler, 'invite_reviewer']);
        add_action('wp_ajax_gfj_submit_review', [$ajax_handler, 'submit_review']);
        add_action('wp_ajax_gfj_review_action', [$ajax_handler, 'review_action']);
        add_action('wp_ajax_gfj_get_review', [$ajax_handler, 'get_review']);
        add_action('wp_ajax_gfj_get_review_details', [$ajax_handler, 'get_review_details']);
        add_action('wp_ajax_gfj_get_manuscript_details', [$ajax_handler, 'get_manuscript_details']);
        add_action('wp_ajax_gfj_upload_revision', [$ajax_handler, 'upload_revision']);
        add_action('wp_ajax_gfj_change_stage', [$ajax_handler, 'change_stage']);
        add_action('wp_ajax_gfj_editor_decision', [$ajax_handler, 'editor_decision']);
        add_action('wp_ajax_gfj_autosave', [$ajax_handler, 'autosave']);
        add_action('wp_ajax_gfj_bulk_assign_reviewer', [$ajax_handler, 'bulk_assign_reviewer']);

        // Metabox saving
        $metabox_handler = new GFJ_Metabox_Handler();
        add_action('save_post_gfj_manuscript', [$metabox_handler, 'save_manuscript_meta'], 10, 2);

        // Query filters for access control
        $query_filters = new GFJ_Query_Filters();
        add_action('pre_get_posts', [$query_filters, 'filter_manuscripts']);
        add_filter('posts_where', [$query_filters, 'filter_by_permissions'], 10, 2);

        // File download protection
        add_action('template_redirect', [$file_handler, 'protect_file_access']);

        // Frontend shortcodes
        add_shortcode('gfj_submit_form', [$this, 'render_submission_form']);
        add_shortcode('gfj_dashboard', [$this, 'render_dashboard']);
        add_shortcode('gfj_review_form', [$this, 'render_review_form']);
        add_shortcode('gfj_register', [$this, 'render_register_form']);

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Render submission form shortcode
     */
    public function render_submission_form() {
        ob_start();
        include GFJ_PLUGIN_DIR . 'public/partials/submission-form.php';
        return ob_get_clean();
    }

    /**
     * Render dashboard shortcode
     */
    public function render_dashboard() {
        if (!is_user_logged_in()) {
            return '<p>Please log in to access your dashboard.</p>';
        }

        $user_role = GFJ_Roles::get_user_gfj_role(get_current_user_id());

        ob_start();

        switch ($user_role) {
            case 'gfj_author':
                include GFJ_PLUGIN_DIR . 'public/partials/dashboard-author.php';
                break;
            case 'gfj_reviewer':
                include GFJ_PLUGIN_DIR . 'public/partials/dashboard-reviewer.php';
                break;
            case 'gfj_editor':
            case 'gfj_eic':
            case 'gfj_managing_editor':
                include GFJ_PLUGIN_DIR . 'public/partials/dashboard-editor.php';
                break;
            default:
                echo '<p>You do not have access to this dashboard.</p>';
        }

        return ob_get_clean();
    }

    /**
     * Render review form shortcode
     */
    public function render_review_form() {
        ob_start();
        include GFJ_PLUGIN_DIR . 'public/partials/review-form.php';
        return ob_get_clean();
    }

    /**
     * Render register form shortcode
     */
    public function render_register_form() {
        ob_start();
        include GFJ_PLUGIN_DIR . 'public/partials/register-form.php';
        return ob_get_clean();
    }

    /**
     * Enqueue public assets
     */
    public function enqueue_public_assets() {
        wp_enqueue_style(
            'gfj-public',
            GFJ_PLUGIN_URL . 'assets/css/public.css',
            [],
            $this->version
        );

        wp_enqueue_script(
            'gfj-public',
            GFJ_PLUGIN_URL . 'assets/js/public.js',
            ['jquery'],
            $this->version,
            true
        );

        wp_localize_script('gfj-public', 'gfjAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gfj_public'),
            'dashboardUrl' => home_url('/dashboard/'),
        ]);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Get current screen
        $screen = get_current_screen();

        // Only load on manuscript edit/new pages
        if (!$screen || $screen->post_type !== 'gfj_manuscript') {
            return;
        }

        wp_enqueue_style(
            'gfj-admin',
            GFJ_PLUGIN_URL . 'assets/css/admin.css',
            [],
            $this->version
        );

        wp_enqueue_script(
            'gfj-admin',
            GFJ_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            $this->version,
            true
        );

        wp_localize_script('gfj-admin', 'gfjAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gfj_admin'),
        ]);
    }

    /**
     * Run the plugin
     */
    public function run() {
        // Plugin is loaded via hooks defined in define_hooks()
    }

    /**
     * Get plugin version
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Get plugin name
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }
}
