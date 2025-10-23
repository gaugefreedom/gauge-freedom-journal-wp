<?php

class GFJ_Activator {
    
    public static function activate() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Reviews table
        $table_reviews = $wpdb->prefix . 'gfj_reviews';
        $sql_reviews = "CREATE TABLE $table_reviews (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            manuscript_id bigint(20) NOT NULL,
            reviewer_id bigint(20) NOT NULL,
            editor_id bigint(20) NOT NULL,
            
            relevance_score tinyint(1) DEFAULT NULL,
            soundness_score tinyint(1) DEFAULT NULL,
            clarity_score tinyint(1) DEFAULT NULL,
            openscience_score tinyint(1) DEFAULT NULL,
            impact_score tinyint(1) DEFAULT NULL,
            provenance_score tinyint(1) DEFAULT NULL,
            
            comments_to_author longtext,
            comments_to_editor longtext,
            recommendation varchar(50) DEFAULT NULL,
            
            status varchar(50) DEFAULT 'pending',
            due_date datetime DEFAULT NULL,
            submitted_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            
            PRIMARY KEY (id),
            KEY manuscript_id (manuscript_id),
            KEY reviewer_id (reviewer_id)
        ) $charset_collate;";
        dbDelta($sql_reviews);
        
        // Decisions table
        $table_decisions = $wpdb->prefix . 'gfj_decisions';
        $sql_decisions = "CREATE TABLE $table_decisions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            manuscript_id bigint(20) NOT NULL,
            editor_id bigint(20) NOT NULL,
            decision_type varchar(50) NOT NULL,
            decision_letter longtext,
            internal_notes longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            
            PRIMARY KEY (id),
            KEY manuscript_id (manuscript_id)
        ) $charset_collate;";
        dbDelta($sql_decisions);
        
        // AI Reviews table
        $table_ai_reviews = $wpdb->prefix . 'gfj_ai_reviews';
        $sql_ai_reviews = "CREATE TABLE $table_ai_reviews (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            manuscript_id bigint(20) NOT NULL,
            math_check longtext,
            citation_check longtext,
            duplication_check longtext,
            car_verification longtext,
            overall_flags longtext,
            confidence_score decimal(3,2),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            
            PRIMARY KEY (id),
            KEY manuscript_id (manuscript_id)
        ) $charset_collate;";
        dbDelta($sql_ai_reviews);
        
        // Add custom roles and capabilities
        require_once GFJ_PLUGIN_DIR . 'includes/roles/class-gfj-roles.php';
        GFJ_Roles::add_roles();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}