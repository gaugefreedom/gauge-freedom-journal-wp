<?php
/**
 * Author Dashboard Template
 */

$current_user = wp_get_current_user();
$args = [
    'post_type' => 'gfj_manuscript',
    'author' => $current_user->ID,
    'posts_per_page' => -1,
];
$manuscripts = new WP_Query($args);

// Count by stage
$stages = ['triage' => 0, 'review' => 0, 'revision' => 0, 'accepted' => 0, 'rejected' => 0];
if ($manuscripts->have_posts()) {
    while ($manuscripts->have_posts()) {
        $manuscripts->the_post();
        $stage_terms = wp_get_post_terms(get_the_ID(), 'manuscript_stage', ['fields' => 'slugs']);
        $stage = !empty($stage_terms) ? $stage_terms[0] : 'triage';
        if (isset($stages[$stage])) {
            $stages[$stage]++;
        }
    }
    wp_reset_postdata();
}
?>

<div class="gfj-dashboard author-dashboard">
    <div class="dashboard-header">
        <h1>My Manuscripts</h1>
        <div class="header-actions">
            <a href="<?php echo home_url('/submit-manuscript/'); ?>" class="button button-primary">
                + Submit New Manuscript
            </a>
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="button">
                Logout
            </a>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="gfj-stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo array_sum($stages); ?></div>
            <div class="stat-label">Total Submissions</div>
        </div>
        <div class="stat-card yellow">
            <div class="stat-value"><?php echo $stages['triage']; ?></div>
            <div class="stat-label">In Triage</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-value"><?php echo $stages['review']; ?></div>
            <div class="stat-label">In Review</div>
        </div>
        <div class="stat-card green">
            <div class="stat-value"><?php echo $stages['accepted']; ?></div>
            <div class="stat-label">Accepted</div>
        </div>
    </div>
    
    <!-- Manuscripts Table -->
    <div class="gfj-table-container">
        <h2>All Manuscripts</h2>
        
        <?php if ($manuscripts->have_posts()): ?>
        <table class="gfj-manuscripts-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Stage</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($manuscripts->have_posts()): $manuscripts->the_post();
                    $stage_terms = wp_get_post_terms(get_the_ID(), 'manuscript_stage');
                    $stage = !empty($stage_terms) ? $stage_terms[0]->name : 'Triage';
                    $stage_slug = !empty($stage_terms) ? $stage_terms[0]->slug : 'triage';
                    $type_terms = wp_get_post_terms(get_the_ID(), 'manuscript_type');
                    $type = !empty($type_terms) ? $type_terms[0]->name : '-';
                    $deadline = get_post_meta(get_the_ID(), '_gfj_triage_deadline', true);
                ?>
                <tr>
                    <td>
                        <strong><?php the_title(); ?></strong>
                        <div class="row-actions">
                            <?php echo wp_trim_words(get_post_meta(get_the_ID(), '_gfj_abstract', true), 20); ?>
                        </div>
                    </td>
                    <td><?php echo esc_html($type); ?></td>
                    <td>
                        <span class="badge badge-<?php echo strtolower($stage); ?>">
                            <?php echo esc_html($stage); ?>
                        </span>
                    </td>
                    <td><?php echo get_the_date(); ?></td>
                    <td>
                        <?php if ($stage === 'Triage' && $deadline): ?>
                            <span class="deadline">⏱️ Decision due: <?php echo date('M j', strtotime($deadline)); ?></span>
                        <?php elseif ($stage === 'In Review'): ?>
                            <span class="status-review">👥 Under review</span>
                        <?php elseif ($stage_slug === 'revision'): ?>
                            <span class="status-revision" style="color: #f59e0b; font-weight: 600;">⚠️ Revision Required</span>
                        <?php elseif ($stage === 'Accepted'): ?>
                            <span class="status-accepted">✅ Accepted</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($stage_slug === 'revision'): ?>
                            <button type="button" class="button button-primary button-small gfj-upload-revision" data-manuscript-id="<?php echo get_the_ID(); ?>">
                                📤 Upload Revision
                            </button>
                        <?php endif; ?>

                        <?php if (current_user_can('edit_manuscripts')): ?>
                            <a href="<?php echo admin_url('post.php?post=' . get_the_ID() . '&action=edit'); ?>" class="button button-small">View Details</a>
                        <?php else: ?>
                            <button type="button" class="button button-small gfj-view-manuscript" data-manuscript-id="<?php echo get_the_ID(); ?>">View Details</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; wp_reset_postdata(); ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="gfj-empty-state">
            <p>📄 You haven't submitted any manuscripts yet.</p>
            <a href="<?php echo home_url('/submit-manuscript/'); ?>" class="button button-primary">
                Submit Your First Manuscript
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>