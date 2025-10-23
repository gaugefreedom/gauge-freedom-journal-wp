<?php
/**
 * Plugin Uninstall Handler
 *
 * Fired when the plugin is uninstalled.
 * This file handles complete cleanup of all plugin data.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

/**
 * IMPORTANT: This will DELETE ALL plugin data permanently.
 * Only runs when user explicitly uninstalls the plugin (not on deactivation).
 */

// Delete all manuscripts (custom post type)
$manuscripts = get_posts([
    'post_type' => 'gfj_manuscript',
    'posts_per_page' => -1,
    'post_status' => 'any',
]);

foreach ($manuscripts as $manuscript) {
    // Delete associated attachments (files)
    $file_keys = ['_gfj_blinded_file', '_gfj_full_file', '_gfj_latex_file', '_gfj_car_file'];

    foreach ($file_keys as $key) {
        $attachment_id = get_post_meta($manuscript->ID, $key, true);
        if ($attachment_id) {
            wp_delete_attachment($attachment_id, true);
        }
    }

    // Delete the manuscript post
    wp_delete_post($manuscript->ID, true);
}

// Delete custom database tables
$table_reviews = $wpdb->prefix . 'gfj_reviews';
$table_decisions = $wpdb->prefix . 'gfj_decisions';
$table_ai_reviews = $wpdb->prefix . 'gfj_ai_reviews';

$wpdb->query("DROP TABLE IF EXISTS {$table_reviews}");
$wpdb->query("DROP TABLE IF EXISTS {$table_decisions}");
$wpdb->query("DROP TABLE IF EXISTS {$table_ai_reviews}");

// Delete all post meta for manuscripts
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_gfj_%'");

// Delete options
delete_option('gfj_version');
delete_option('gfj_settings');

// Remove custom taxonomies
unregister_taxonomy('manuscript_type');
unregister_taxonomy('manuscript_stage');

// Remove custom capabilities from all roles
$roles = ['administrator', 'editor', 'author'];
$gfj_caps = [
    'manage_gfj',
    'view_all_manuscripts',
    'edit_manuscripts',
    'submit_manuscripts',
    'edit_own_manuscripts',
    'view_own_manuscripts',
    'view_assigned_manuscripts',
    'submit_reviews',
    'view_blinded_manuscripts',
    'triage_manuscripts',
    'assign_reviewers',
    'make_decisions',
    'make_final_decisions',
    'override_decisions',
    'view_full_manuscripts',
    'manage_editors',
    'manage_workflow',
    'view_statistics',
    'export_data',
    'delete_manuscripts',
    'publish_manuscripts',
    'read_private_posts',
];

foreach ($roles as $role_name) {
    $role = get_role($role_name);
    if ($role) {
        foreach ($gfj_caps as $cap) {
            $role->remove_cap($cap);
        }
    }
}

// Remove custom roles
remove_role('gfj_author');
remove_role('gfj_reviewer');
remove_role('gfj_editor');
remove_role('gfj_eic');
remove_role('gfj_managing_editor');

// Flush rewrite rules
flush_rewrite_rules();

// Clear any cached data
wp_cache_flush();
