<?php

class GFJ_Roles {
    
    /**
     * Add custom roles on plugin activation
     */
    public static function add_roles() {
        
        // Journal Author (extends WP Author)
        add_role('gfj_author', 'Journal Author', [
            'read' => true,
            'submit_manuscripts' => true,
            'edit_own_manuscripts' => true,
            'view_own_manuscripts' => true,
        ]);
        
        // Journal Reviewer
        add_role('gfj_reviewer', 'Journal Reviewer', [
            'read' => true,
            'view_assigned_manuscripts' => true,
            'submit_reviews' => true,
            'view_blinded_manuscripts' => true,
        ]);
        
        // Journal Editor
        add_role('gfj_editor', 'Journal Editor', [
            'read' => true,
            'view_all_manuscripts' => true,
            'triage_manuscripts' => true,
            'assign_reviewers' => true,
            'make_decisions' => true,
            'view_full_manuscripts' => true, // After triage
            'edit_manuscripts' => true,
        ]);
        
        // Editor-in-Chief
        add_role('gfj_eic', 'Editor in Chief', [
            'read' => true,
            'view_all_manuscripts' => true,
            'triage_manuscripts' => true,
            'assign_reviewers' => true,
            'make_decisions' => true,
            'make_final_decisions' => true,
            'override_decisions' => true,
            'view_full_manuscripts' => true,
            'edit_manuscripts' => true,
            'manage_editors' => true,
        ]);
        
        // Managing Editor
        add_role('gfj_managing_editor', 'Managing Editor', [
            'read' => true,
            'view_all_manuscripts' => true,
            'manage_workflow' => true,
            'view_statistics' => true,
            'export_data' => true,
        ]);
        
        // Add capabilities to Administrator
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('manage_gfj');
            $admin->add_cap('view_all_manuscripts');
            $admin->add_cap('edit_manuscripts');
        }
    }
    
    /**
     * Remove custom roles on plugin deactivation
     */
    public static function remove_roles() {
        remove_role('gfj_author');
        remove_role('gfj_reviewer');
        remove_role('gfj_editor');
        remove_role('gfj_eic');
        remove_role('gfj_managing_editor');
    }
    
    /**
     * Check if user has role
     */
    public static function user_has_role($user_id, $role) {
        $user = get_userdata($user_id);
        if (!$user) return false;
        return in_array($role, (array) $user->roles);
    }
    
    /**
     * Get user's primary GFJ role
     */
    public static function get_user_gfj_role($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return false;
        
        $gfj_roles = ['gfj_eic', 'gfj_editor', 'gfj_managing_editor', 'gfj_reviewer', 'gfj_author'];
        
        foreach ($gfj_roles as $role) {
            if (in_array($role, (array) $user->roles)) {
                return $role;
            }
        }
        
        return false;
    }
}