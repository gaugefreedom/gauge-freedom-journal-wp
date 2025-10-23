<?php
/**
 * Plugin Deactivation Handler
 *
 * Handles cleanup when plugin is deactivated
 */

if (!defined('ABSPATH')) {
    exit;
}

class GFJ_Deactivator {

    /**
     * Deactivation routine
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Note: We do NOT remove roles or delete data on deactivation
        // This preserves data if user temporarily deactivates the plugin
        // Data cleanup should only happen on uninstall
    }
}
