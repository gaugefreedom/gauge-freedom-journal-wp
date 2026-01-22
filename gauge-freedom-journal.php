<?php
/**
 * Plugin Name: Gauge Freedom Journal
 * Plugin URI: https://gaugefreedom.org
 * Description: Academic journal management system with double-blind peer review, workflow management, and human+AI collaboration
 * Version: 0.2.0
 * Author: Gauge Freedom, Inc.
 * Author URI: https://gaugefreedom.org
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: gauge-freedom-journal
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Minimum requirements
define('GFJ_MIN_PHP_VERSION', '7.4');
define('GFJ_MIN_WP_VERSION', '5.8');

// Check PHP version
if (version_compare(PHP_VERSION, GFJ_MIN_PHP_VERSION, '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>';
        echo sprintf(
            __('Gauge Freedom Journal requires PHP %s or higher. You are running PHP %s.', 'gauge-freedom-journal'),
            GFJ_MIN_PHP_VERSION,
            PHP_VERSION
        );
        echo '</p></div>';
    });
    return;
}

// Check WordPress version
global $wp_version;
if (version_compare($wp_version, GFJ_MIN_WP_VERSION, '<')) {
    add_action('admin_notices', function() {
        global $wp_version;
        echo '<div class="error"><p>';
        echo sprintf(
            __('Gauge Freedom Journal requires WordPress %s or higher. You are running WordPress %s.', 'gauge-freedom-journal'),
            GFJ_MIN_WP_VERSION,
            $wp_version
        );
        echo '</p></div>';
    });
    return;
}

define('GFJ_VERSION', '0.1.0');
define('GFJ_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GFJ_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * ===================================================================
 * Allow custom file uploads for manuscript submissions.
 * ===================================================================
 *
 * Adds JSON and CAR to the list of allowed MIME types in WordPress.
 * This is necessary for supporting CAR (Content-Addressable Receipt) files
 * and other data-related uploads for reproducible science.
 *
 * Hooked into the 'upload_mimes' filter.
 */
add_filter('upload_mimes', 'gfj_add_custom_mime_types');

if (!function_exists('gfj_add_custom_mime_types')) {
    /**
     * Adds custom MIME types to the allowed list.
     *
     * @param array $mimes Array of allowed MIME types.
     * @return array Modified array of MIME types.
     */
    function gfj_add_custom_mime_types($mimes) {
        // Add .json for data files and CAR receipts
        $mimes['json'] = 'application/json';

        // Add .car for Content-Addressable Receipts (treated as binary)
        $mimes['car'] = 'application/octet-stream';

        return $mimes;
    }
}

/**
 * ===================================================================
 * Additional MIME type check bypass for JSON/CAR files.
 * ===================================================================
 *
 * Some hosting environments have strict MIME checking. This filter
 * allows JSON and CAR files to bypass the strict check.
 */
add_filter('wp_check_filetype_and_ext', 'gfj_check_filetype', 10, 4);

if (!function_exists('gfj_check_filetype')) {
    /**
     * Override file type check for JSON and CAR files.
     *
     * @param array  $data      File data.
     * @param string $file      Full path to the file.
     * @param string $filename  The name of the file.
     * @param array  $mimes     Array of allowed MIME types.
     * @return array Modified file data.
     */
    function gfj_check_filetype($data, $file, $filename, $mimes) {
        $filetype = wp_check_filetype($filename, $mimes);

        if ($filetype['ext'] === 'json' || $filetype['ext'] === 'car') {
            $data['ext'] = $filetype['ext'];
            $data['type'] = $filetype['type'];
        }

        return $data;
    }
}

/**
 * Activation hook
 */
function activate_gfj() {
    require_once GFJ_PLUGIN_DIR . 'includes/class-gfj-activator.php';
    GFJ_Activator::activate();
}
register_activation_hook(__FILE__, 'activate_gfj');

/**
 * Deactivation hook
 */
function deactivate_gfj() {
    require_once GFJ_PLUGIN_DIR . 'includes/class-gfj-deactivator.php';
    GFJ_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'deactivate_gfj');

/**
 * Core plugin class
 */
require GFJ_PLUGIN_DIR . 'includes/class-gfj.php';

/**
 * Begin execution
 */
function run_gfj() {
    $plugin = new GFJ();
    $plugin->run();
}
run_gfj();