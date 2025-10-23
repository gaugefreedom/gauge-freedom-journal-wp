<?php
/**
 * Login and Dashboard Redirect Handler
 *
 * Handles login redirects and dashboard access for GFJ users
 */

if (!defined('ABSPATH')) {
    exit;
}

class GFJ_Login {

    public function __construct() {
        // Enable WordPress registration
        add_action('init', [$this, 'enable_registration']);

        // Customize registration form
        add_action('register_form', [$this, 'add_registration_fields']);
        add_filter('registration_errors', [$this, 'validate_registration_fields'], 10, 3);
        add_action('user_register', [$this, 'save_registration_fields']);

        // Customize registration email
        add_filter('wp_new_user_notification_email', [$this, 'customize_new_user_email'], 10, 3);
        add_filter('wp_new_user_notification_email_admin', [$this, 'customize_admin_notification'], 10, 3);

        // Login redirect
        add_filter('login_redirect', [$this, 'custom_login_redirect'], 10, 3);

        // After login redirect (backup)
        add_action('wp_login', [$this, 'after_login_redirect'], 10, 2);

        // Redirect registration to WP default
        add_action('template_redirect', [$this, 'redirect_custom_register']);

        // Hide admin bar for non-admin users
        add_action('after_setup_theme', [$this, 'hide_admin_bar']);

        // Redirect from wp-admin for GFJ roles
        add_action('admin_init', [$this, 'redirect_from_admin']);

        // Custom admin menu for GFJ roles
        add_action('admin_menu', [$this, 'customize_admin_menu'], 999);

        // Hide unnecessary metaboxes
        add_action('admin_menu', [$this, 'remove_metaboxes']);

        // Hide author info in manuscript list for editors during triage
        add_filter('manage_gfj_manuscript_posts_columns', [$this, 'customize_manuscript_columns']);
        add_action('manage_gfj_manuscript_posts_custom_column', [$this, 'render_manuscript_column'], 10, 2);

        // Customize login page logo
        add_action('login_enqueue_scripts', [$this, 'customize_login_logo']);
        add_filter('login_headerurl', [$this, 'customize_login_logo_url']);
        add_filter('login_headertext', [$this, 'customize_login_logo_title']);
    }

    /**
     * Enable WordPress user registration
     */
    public function enable_registration() {
        // Enable user registration if not already enabled
        if (!get_option('users_can_register')) {
            update_option('users_can_register', 1);
        }

        // Set default role to subscriber (we'll change it based on selection)
        update_option('default_role', 'subscriber');
    }

    /**
     * Redirect custom register page to WordPress default
     */
    public function redirect_custom_register() {
        if (is_page('register') && !is_user_logged_in()) {
            wp_redirect(wp_registration_url());
            exit;
        }
    }

    /**
     * Add custom fields to WordPress registration form
     */
    public function add_registration_fields() {
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $role = isset($_POST['gfj_role']) ? sanitize_text_field($_POST['gfj_role']) : '';
        ?>
        <style>
            .gfj-registration-field {
                margin-bottom: 15px;
            }
            .gfj-registration-field label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
            }
            .gfj-registration-field input[type="text"] {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }
            .gfj-role-selection {
                margin: 20px 0;
                padding: 15px;
                background: #f0f6ff;
                border: 2px solid #2271b1;
                border-radius: 6px;
            }
            .gfj-role-option {
                display: block;
                padding: 12px;
                margin: 8px 0;
                border: 2px solid #ddd;
                border-radius: 4px;
                cursor: pointer;
                transition: all 0.2s;
            }
            .gfj-role-option:hover {
                border-color: #2271b1;
                background: #fff;
            }
            .gfj-role-option input[type="radio"] {
                margin-right: 8px;
            }
        </style>

        <p class="gfj-registration-field">
            <label for="first_name">First Name *</label>
            <input type="text" name="first_name" id="first_name" value="<?php echo esc_attr($first_name); ?>" required>
        </p>

        <p class="gfj-registration-field">
            <label for="last_name">Last Name *</label>
            <input type="text" name="last_name" id="last_name" value="<?php echo esc_attr($last_name); ?>" required>
        </p>

        <div class="gfj-role-selection">
            <label style="display: block; font-weight: 600; margin-bottom: 10px;">I want to register as: *</label>

            <label class="gfj-role-option">
                <input type="radio" name="gfj_role" value="gfj_author" <?php checked($role, 'gfj_author'); ?> required>
                <strong>Author</strong> - Submit manuscripts for review
            </label>

            <label class="gfj-role-option">
                <input type="radio" name="gfj_role" value="gfj_reviewer" <?php checked($role, 'gfj_reviewer'); ?> required>
                <strong>Reviewer</strong> - Peer review submitted manuscripts
            </label>

            <p style="margin-top: 10px; font-size: 13px; color: #666;">
                <strong>Note:</strong> Editor roles are by invitation only.
            </p>
        </div>

        <!-- Honeypot for spam protection -->
        <div style="position: absolute; left: -5000px;" aria-hidden="true">
            <label for="website">Website (leave blank)</label>
            <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
        </div>
        <?php
    }

    /**
     * Validate custom registration fields
     */
    public function validate_registration_fields($errors, $sanitized_user_login, $user_email) {
        // Check honeypot
        if (!empty($_POST['website'])) {
            $errors->add('spam_detected', 'Registration failed.');
            return $errors;
        }

        if (empty($_POST['first_name'])) {
            $errors->add('first_name_error', '<strong>Error</strong>: First name is required.');
        }

        if (empty($_POST['last_name'])) {
            $errors->add('last_name_error', '<strong>Error</strong>: Last name is required.');
        }

        if (empty($_POST['gfj_role']) || !in_array($_POST['gfj_role'], ['gfj_author', 'gfj_reviewer'])) {
            $errors->add('role_error', '<strong>Error</strong>: Please select a role (Author or Reviewer).');
        }

        return $errors;
    }

    /**
     * Save custom registration fields
     */
    public function save_registration_fields($user_id) {
        if (!empty($_POST['first_name'])) {
            update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['first_name']));
        }

        if (!empty($_POST['last_name'])) {
            update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['last_name']));
        }

        // Set the selected role
        if (!empty($_POST['gfj_role'])) {
            $user = new WP_User($user_id);
            $user->set_role($_POST['gfj_role']);
        }

        // Update display name
        $first_name = get_user_meta($user_id, 'first_name', true);
        $last_name = get_user_meta($user_id, 'last_name', true);
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $first_name . ' ' . $last_name,
        ]);
    }

    /**
     * Customize new user notification email
     */
    public function customize_new_user_email($wp_new_user_notification_email, $user, $blogname) {
        // Generate password reset key
        $key = get_password_reset_key($user);

        if (is_wp_error($key)) {
            // If key generation fails, return default email
            return $wp_new_user_notification_email;
        }

        $message = sprintf(__('Welcome to Gauge Freedom Journal!'), $user->user_login) . "\r\n\r\n";
        $message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n";
        $message .= sprintf(__('Email: %s'), $user->user_email) . "\r\n\r\n";
        $message .= __('To set your password, visit the following address:') . "\r\n\r\n";
        $message .= network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login') . "\r\n\r\n";
        $message .= __('After setting your password, you can log in and access your dashboard at:') . "\r\n";
        $message .= home_url('/dashboard/') . "\r\n";

        $wp_new_user_notification_email['subject'] = '[Gauge Freedom Journal] Account Activation';
        $wp_new_user_notification_email['message'] = $message;

        return $wp_new_user_notification_email;
    }

    /**
     * Customize admin notification email
     */
    public function customize_admin_notification($wp_new_user_notification_email_admin, $user, $blogname) {
        $role = '';
        $user_obj = new WP_User($user->ID);
        if (!empty($user_obj->roles)) {
            $role = str_replace('gfj_', '', $user_obj->roles[0]);
        }

        $message = sprintf(__('New user registration on %s:'), $blogname) . "\r\n\r\n";
        $message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n";
        $message .= sprintf(__('Email: %s'), $user->user_email) . "\r\n";
        $message .= sprintf(__('Name: %s %s'), get_user_meta($user->ID, 'first_name', true), get_user_meta($user->ID, 'last_name', true)) . "\r\n";
        $message .= sprintf(__('Role: %s'), ucfirst($role)) . "\r\n\r\n";
        $message .= sprintf(__('View user: %s'), admin_url('user-edit.php?user_id=' . $user->ID)) . "\r\n";

        $wp_new_user_notification_email_admin['subject'] = '[GFJ] New User Registration: ' . $user->user_login;
        $wp_new_user_notification_email_admin['message'] = $message;

        return $wp_new_user_notification_email_admin;
    }

    /**
     * Redirect users to custom dashboard after login
     */
    public function custom_login_redirect($redirect_to, $request, $user) {
        if (!isset($user->roles) || !is_array($user->roles)) {
            return $redirect_to;
        }

        // GFJ roles should go to custom dashboard
        $gfj_roles = ['gfj_author', 'gfj_reviewer', 'gfj_editor', 'gfj_eic', 'gfj_managing_editor'];

        foreach ($gfj_roles as $role) {
            if (in_array($role, $user->roles)) {
                return home_url('/dashboard/');
            }
        }

        return $redirect_to;
    }

    /**
     * After login hook (backup redirect)
     */
    public function after_login_redirect($user_login, $user) {
        if (!isset($user->roles) || !is_array($user->roles)) {
            return;
        }

        $gfj_roles = ['gfj_author', 'gfj_reviewer', 'gfj_editor', 'gfj_eic', 'gfj_managing_editor'];

        foreach ($gfj_roles as $role) {
            if (in_array($role, $user->roles)) {
                wp_safe_redirect(home_url('/dashboard/'));
                exit;
            }
        }
    }

    /**
     * Hide admin bar for GFJ users (they use custom dashboard)
     */
    public function hide_admin_bar() {
        if (!current_user_can('manage_options')) {
            $user = wp_get_current_user();
            $gfj_roles = ['gfj_author', 'gfj_reviewer', 'gfj_editor', 'gfj_eic', 'gfj_managing_editor'];

            foreach ($gfj_roles as $role) {
                if (in_array($role, $user->roles)) {
                    show_admin_bar(false);
                    break;
                }
            }
        }
    }

    /**
     * Redirect GFJ users away from wp-admin
     */
    public function redirect_from_admin() {
        // Allow AJAX requests
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        // Administrators can access wp-admin
        if (current_user_can('manage_options')) {
            return;
        }

        $user = wp_get_current_user();
        $gfj_roles = ['gfj_author', 'gfj_reviewer'];

        // Authors and reviewers should NOT access wp-admin
        foreach ($gfj_roles as $role) {
            if (in_array($role, $user->roles)) {
                wp_safe_redirect(home_url('/dashboard/'));
                exit;
            }
        }
    }

    /**
     * Customize admin menu for editors
     */
    public function customize_admin_menu() {
        // For editors, simplify the menu
        if (!current_user_can('manage_options')) {
            $user = wp_get_current_user();

            if (in_array('gfj_editor', $user->roles) || in_array('gfj_eic', $user->roles)) {
                // Remove default Dashboard
                remove_menu_page('index.php');

                // Add custom Dashboard link
                add_menu_page(
                    'Dashboard',
                    'Dashboard',
                    'triage_manuscripts',
                    'gfj-dashboard',
                    [$this, 'redirect_to_dashboard'],
                    'dashicons-dashboard',
                    2
                );

                // Rename Manuscripts menu
                global $menu;
                foreach ($menu as $key => $item) {
                    if ($item[2] === 'edit.php?post_type=gfj_manuscript') {
                        $menu[$key][0] = 'Manage Manuscripts';
                        break;
                    }
                }
            }
        }
    }

    /**
     * Redirect function for dashboard menu item
     */
    public function redirect_to_dashboard() {
        wp_redirect(home_url('/dashboard/'));
        exit;
    }

    /**
     * Customize manuscript list columns to hide author during triage
     */
    public function customize_manuscript_columns($columns) {
        // Remove author column - we'll add custom one
        if (isset($columns['author'])) {
            unset($columns['author']);
        }

        // Add custom author column with access control
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            // Add after title
            if ($key === 'title') {
                $new_columns['gfj_author'] = 'Author';
            }
        }

        return $new_columns;
    }

    /**
     * Render custom author column with double-blind protection
     */
    public function render_manuscript_column($column, $post_id) {
        if ($column === 'gfj_author') {
            $stage = wp_get_post_terms($post_id, 'manuscript_stage', ['fields' => 'slugs']);
            $current_stage = !empty($stage) ? $stage[0] : 'triage';

            if ($current_stage === 'triage') {
                // During triage, hide author info
                echo '<span style="color: #646970;">🔒 Anonymous (Triage)</span>';
            } else {
                // After triage, show author
                $author_id = get_post_field('post_author', $post_id);
                $author = get_userdata($author_id);
                if ($author) {
                    echo esc_html($author->display_name);
                }
            }
        }
    }

    /**
     * Remove unnecessary metaboxes from manuscript edit screen
     */
    public function remove_metaboxes() {
        // Remove Custom Fields metabox (confusing for editors)
        remove_meta_box('postcustom', 'gfj_manuscript', 'normal');

        // Remove Comments metabox
        remove_meta_box('commentstatusdiv', 'gfj_manuscript', 'normal');

        // Remove Author metabox (we control this)
        remove_meta_box('authordiv', 'gfj_manuscript', 'normal');

        // Remove Slug metabox
        remove_meta_box('slugdiv', 'gfj_manuscript', 'normal');
    }

    /**
     * Customize login page logo
     */
    public function customize_login_logo() {
        // Check if custom logo is set (can be configured in WordPress settings)
        $custom_logo_id = get_theme_mod('custom_logo');
        $logo_url = '';

        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
        }

        // If no logo set, use a default or allow admin to configure
        if (!$logo_url) {
            // Check for custom option
            $logo_url = get_option('gfj_login_logo_url', '');
        }

        // If still no logo, show default WordPress logo
        if (!$logo_url) {
            return;
        }

        ?>
        <style type="text/css">
            #login h1 a, .login h1 a {
                background-image: url('<?php echo esc_url($logo_url); ?>');
                background-size: contain;
                background-position: center;
                width: 320px;
                height: 120px;
                padding: 0;
            }
            .login form {
                margin-top: 20px;
            }
            .login #backtoblog, .login #nav {
                text-align: center;
            }
        </style>
        <?php
    }

    /**
     * Customize login logo URL
     */
    public function customize_login_logo_url() {
        return home_url();
    }

    /**
     * Customize login logo title
     */
    public function customize_login_logo_title() {
        return get_bloginfo('name') . ' - ' . get_bloginfo('description');
    }
}
