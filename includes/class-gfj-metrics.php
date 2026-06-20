<?php
/**
 * Article metrics handler (views and downloads).
 */

if (!defined('ABSPATH')) {
    exit;
}

class GFJ_Metrics {

    public function __construct() {
        add_action('wp_ajax_gfj_track_metric', [$this, 'handle_metric_tracking']);
        add_action('wp_ajax_nopriv_gfj_track_metric', [$this, 'handle_metric_tracking']);
    }

    /**
     * AJAX handler: increment the requested counter.
     */
    public function handle_metric_tracking() {
        check_ajax_referer('gfj_metrics_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';

        if (!$post_id || get_post_type($post_id) !== 'gfj_article') {
            wp_send_json_error('Invalid Article');
        }

        $valid_types = ['views', 'pdf', 'bundle', 'latex'];

        if (in_array($type, $valid_types, true)) {
            $key = '_gfj_metric_' . $type;
            $current = (int) get_post_meta($post_id, $key, true);
            update_post_meta($post_id, $key, $current + 1);

            if ($type !== 'views') {
                $total = (int) get_post_meta($post_id, '_gfj_metric_total_downloads', true);
                update_post_meta($post_id, '_gfj_metric_total_downloads', $total + 1);
            }

            wp_send_json_success(['new_count' => $current + 1]);
        }

        wp_send_json_error('Invalid Metric Type');
    }
}
