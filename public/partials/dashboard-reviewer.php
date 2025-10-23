<?php
/**
 * Reviewer Dashboard Template
 */

global $wpdb;
$current_user = wp_get_current_user();

// Get assigned reviews
$reviews = $wpdb->get_results($wpdb->prepare(
    "SELECT r.*, m.post_title 
     FROM {$wpdb->prefix}gfj_reviews r
     JOIN {$wpdb->posts} m ON r.manuscript_id = m.ID
     WHERE r.reviewer_id = %d
     ORDER BY r.created_at DESC",
    $current_user->ID
));

$pending = 0;
$completed = 0;
foreach ($reviews as $review) {
    if ($review->status === 'pending' || $review->status === 'in_progress') {
        $pending++;
    } elseif ($review->status === 'completed') {
        $completed++;
    }
}
?>

<div class="gfj-dashboard reviewer-dashboard">
    <div class="dashboard-header">
        <h1>My Reviews</h1>
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="button">
            Logout
        </a>
    </div>
    
    <!-- Stats Cards -->
    <div class="gfj-stats-grid">
        <div class="stat-card orange">
            <div class="stat-value"><?php echo $pending; ?></div>
            <div class="stat-label">Pending Reviews</div>
        </div>
        <div class="stat-card green">
            <div class="stat-value"><?php echo $completed; ?></div>
            <div class="stat-label">Completed Reviews</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo count($reviews); ?></div>
            <div class="stat-label">Total Reviews</div>
        </div>
    </div>
    
    <!-- Pending Reviews -->
    <?php if ($pending > 0): ?>
    <div class="gfj-table-container">
        <h2>⚠️ Action Required</h2>
        <table class="gfj-manuscripts-table">
            <thead>
                <tr>
                    <th>Manuscript</th>
                    <th>Invited</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $review): 
                    if ($review->status !== 'pending' && $review->status !== 'in_progress') continue;
                    $days_left = ceil((strtotime($review->due_date) - time()) / (60 * 60 * 24));
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($review->post_title); ?></strong>
                        <div class="row-actions">
                            <?php
                            $abstract = get_post_meta($review->manuscript_id, '_gfj_abstract', true);
                            echo wp_trim_words($abstract, 15);
                            ?>
                        </div>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($review->created_at)); ?></td>
                    <td>
                        <?php echo date('M j, Y', strtotime($review->due_date)); ?>
                        <?php if ($days_left < 3): ?>
                            <span class="urgent">⚠️ <?php echo $days_left; ?> days left</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-pending"><?php echo ucwords(str_replace('_', ' ', $review->status)); ?></span>
                    </td>
                    <td>
                        <?php if ($review->status === 'pending'): ?>
                            <button type="button" class="button button-small gfj-review-action"
                                    data-review-id="<?php echo $review->id; ?>"
                                    data-action="accept">Accept</button>
                            <button type="button" class="button button-small gfj-review-action"
                                    data-review-id="<?php echo $review->id; ?>"
                                    data-action="decline">Decline</button>
                        <?php else: ?>
                            <a href="<?php echo home_url('/submit-review/?review_id=' . $review->id); ?>"
                               class="button button-primary button-small">Submit Review</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Completed Reviews -->
    <?php if ($completed > 0): ?>
    <div class="gfj-table-container">
        <h2>Completed Reviews</h2>
        <table class="gfj-manuscripts-table">
            <thead>
                <tr>
                    <th>Manuscript</th>
                    <th>Submitted On</th>
                    <th>Recommendation</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $review): 
                    if ($review->status !== 'completed') continue;
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($review->post_title); ?></strong>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($review->submitted_at)); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $review->recommendation; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $review->recommendation)); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if (count($reviews) === 0): ?>
    <div class="gfj-empty-state">
        <p>👥 No review assignments yet.</p>
        <p>You will receive email notifications when manuscripts are assigned to you.</p>
    </div>
    <?php endif; ?>
</div>