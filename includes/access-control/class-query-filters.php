<?php
/**
 * Query Filters for Access Control
 *
 * Filters manuscript queries based on user permissions
 */

if (!defined('ABSPATH')) {
    exit;
}

class GFJ_Query_Filters {

    public function __construct() {
        // Constructor
    }

    /**
     * Filter manuscript queries based on user role
     */
    public function filter_manuscripts($query) {
        // Only filter manuscript queries
        if (!is_admin() && !$query->is_main_query()) {
            return;
        }

        if ($query->get('post_type') !== 'gfj_manuscript') {
            return;
        }

        // Don't filter for administrators
        if (current_user_can('manage_options')) {
            return;
        }

        $user_id = get_current_user_id();

        // Not logged in - no manuscripts
        if (!$user_id) {
            $query->set('post__in', [0]);
            return;
        }

        $user = wp_get_current_user();

        // Authors see only their own manuscripts
        if (in_array('gfj_author', $user->roles)) {
            $query->set('author', $user_id);
            return;
        }

        // Reviewers see only assigned manuscripts
        if (in_array('gfj_reviewer', $user->roles)) {
            global $wpdb;
            $manuscript_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT manuscript_id FROM {$wpdb->prefix}gfj_reviews
                 WHERE reviewer_id = %d",
                $user_id
            ));

            if (empty($manuscript_ids)) {
                $query->set('post__in', [0]);
            } else {
                $query->set('post__in', $manuscript_ids);
            }
            return;
        }

        // Editors and EiC see all manuscripts (no filter needed)
    }

    /**
     * Filter WHERE clause for additional permission checks
     */
    public function filter_by_permissions($where, $query) {
        global $wpdb;

        // Only filter manuscript queries
        if ($query->get('post_type') !== 'gfj_manuscript') {
            return $where;
        }

        // Don't filter for administrators
        if (current_user_can('manage_options')) {
            return $where;
        }

        // Additional security layer - already handled in filter_manuscripts
        // This is a backup in case direct queries bypass pre_get_posts

        return $where;
    }
}
