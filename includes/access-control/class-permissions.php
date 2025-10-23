<?php

class GFJ_Permissions {
    
    /**
     * Check if user can view manuscript based on stage and role
     */
    public static function can_view_manuscript($user_id, $manuscript_id) {
        $manuscript = get_post($manuscript_id);
        if (!$manuscript) return false;
        
        $user = get_userdata($user_id);
        if (!$user) return false;
        
        $stage = wp_get_post_terms($manuscript_id, 'manuscript_stage', ['fields' => 'slugs']);
        $current_stage = !empty($stage) ? $stage[0] : 'triage';
        
        // Author can always view their own manuscript
        if ($manuscript->post_author == $user_id) {
            return true;
        }
        
        // Editors can view metadata during triage
        if (in_array('gfj_editor', $user->roles) || in_array('gfj_eic', $user->roles)) {
            return true;
        }
        
        // Reviewers can view if assigned
        if (in_array('gfj_reviewer', $user->roles)) {
            return self::is_reviewer_assigned($user_id, $manuscript_id);
        }
        
        return false;
    }
    
    /**
     * Check if user can view full manuscript (not blinded)
     */
    public static function can_view_full_manuscript($user_id, $manuscript_id) {
        $manuscript = get_post($manuscript_id);
        if (!$manuscript) return false;
        
        $stage = wp_get_post_terms($manuscript_id, 'manuscript_stage', ['fields' => 'slugs']);
        $current_stage = !empty($stage) ? $stage[0] : 'triage';
        
        // Author always sees full version
        if ($manuscript->post_author == $user_id) {
            return true;
        }
        
        $user = get_userdata($user_id);
        
        // Editors can see full version ONLY after triage
        if (in_array('gfj_editor', $user->roles) || in_array('gfj_eic', $user->roles)) {
            if ($current_stage !== 'triage') {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user can view author information
     */
    public static function can_view_author_info($user_id, $manuscript_id) {
        $manuscript = get_post($manuscript_id);
        if (!$manuscript) return false;
        
        $stage = wp_get_post_terms($manuscript_id, 'manuscript_stage', ['fields' => 'slugs']);
        $current_stage = !empty($stage) ? $stage[0] : 'triage';
        
        // Author sees their own info
        if ($manuscript->post_author == $user_id) {
            return true;
        }
        
        $user = get_userdata($user_id);
        
        // Editors see author info after triage
        if (in_array('gfj_editor', $user->roles) || in_array('gfj_eic', $user->roles)) {
            if ($current_stage !== 'triage') {
                return true;
            }
        }
        
        // Reviewers NEVER see author info (double-blind)
        return false;
    }
    
    /**
     * Check if reviewer is assigned to manuscript
     */
    private static function is_reviewer_assigned($user_id, $manuscript_id) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gfj_reviews 
             WHERE manuscript_id = %d AND reviewer_id = %d",
            $manuscript_id, $user_id
        ));
        return $count > 0;
    }
}