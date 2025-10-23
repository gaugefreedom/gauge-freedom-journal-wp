<?php
/**
 * Editor Dashboard Template
 */

$args = [
    'post_type' => 'gfj_manuscript',
    'posts_per_page' => -1,
];
$manuscripts = new WP_Query($args);

$stages = ['triage' => 0, 'review' => 0, 'revision' => 0, 'accepted' => 0];
$triage_queue = [];

if ($manuscripts->have_posts()) {
    while ($manuscripts->have_posts()) {
        $manuscripts->the_post();
        $stage_terms = wp_get_post_terms(get_the_ID(), 'manuscript_stage', ['fields' => 'slugs']);
        $stage = !empty($stage_terms) ? $stage_terms[0] : 'triage';
        
        if (isset($stages[$stage])) {
            $stages[$stage]++;
        }
        
        if ($stage === 'triage') {
            $triage_queue[] = get_post();
        }
    }
    wp_reset_postdata();
}

// Calculate average triage time (mock for now)
$avg_triage_time = '4.2 days';
?>

<div class="gfj-dashboard editor-dashboard">
    <div class="dashboard-header">
        <h1>Editorial Dashboard</h1>
        <div class="header-actions">
            <a href="<?php echo admin_url('edit.php?post_type=gfj_manuscript'); ?>" class="button">
                All Manuscripts
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
            <div class="stat-label">Awaiting Triage</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-value"><?php echo $stages['review']; ?></div>
            <div class="stat-label">In Review</div>
        </div>
        <div class="stat-card gray">
            <div class="stat-value"><?php echo $avg_triage_time; ?></div>
            <div class="stat-label">Avg. Triage Time</div>
        </div>
    </div>
    
    <!-- Triage Queue -->
    <?php if (!empty($triage_queue)): ?>
    <div class="gfj-table-container priority-section">
        <h2>⚡ Priority: Triage Queue</h2>
        <p class="section-description">Review these manuscripts within 7 days of submission</p>
        
        <table class="gfj-manuscripts-table">
            <thead>
                <tr>
                    <th>Manuscript</th>
                    <th>Type</th>
                    <th>Submitted</th>
                    <th>Deadline</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($triage_queue as $manuscript): 
                    setup_postdata($manuscript);
                    $type_terms = wp_get_post_terms($manuscript->ID, 'manuscript_type');
                    $type = !empty($type_terms) ? $type_terms[0]->name : '-';
                    $deadline = get_post_meta($manuscript->ID, '_gfj_triage_deadline', true);
                    $days_left = ceil((strtotime($deadline) - time()) / (60 * 60 * 24));
                ?>
                <tr>
                    <td>
                        <strong><?php echo get_the_title($manuscript); ?></strong>
                        <div class="row-actions">
                            🔒 Author: [Anonymous during triage]
                        </div>
                    </td>
                    <td><?php echo esc_html($type); ?></td>
                    <td><?php echo get_the_date('M j, Y', $manuscript); ?></td>
                    <td>
                        <?php echo date('M j, Y', strtotime($deadline)); ?>
                        <?php if ($days_left <= 2): ?>
                            <span class="urgent">⚠️ <?php echo $days_left; ?> days</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo admin_url('post.php?post=' . $manuscript->ID . '&action=edit'); ?>" 
                           class="button button-primary button-small">Review for Triage</a>
                    </td>
                </tr>
                <?php endforeach; wp_reset_postdata(); ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- All Manuscripts by Stage -->
    <div class="gfj-table-container">
        <h2>All Manuscripts</h2>
        
        <div class="stage-filters">
            <button class="filter-btn active" data-stage="all">All</button>
            <button class="filter-btn" data-stage="triage">Triage (<?php echo $stages['triage']; ?>)</button>
            <button class="filter-btn" data-stage="review">In Review (<?php echo $stages['review']; ?>)</button>
            <button class="filter-btn" data-stage="revision">Revision (<?php echo $stages['revision']; ?>)</button>
            <button class="filter-btn" data-stage="accepted">Accepted (<?php echo $stages['accepted']; ?>)</button>
        </div>
        
        <table class="gfj-manuscripts-table">
            <thead>
                <tr>
                    <th>Manuscript</th>
                    <th>Author</th>
                    <th>Stage</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $manuscripts->rewind_posts();
                while ($manuscripts->have_posts()): $manuscripts->the_post(); 
                    $stage_terms = wp_get_post_terms(get_the_ID(), 'manuscript_stage');
                    $stage_slug = !empty($stage_terms) ? $stage_terms[0]->slug : 'triage';
                    $stage_name = !empty($stage_terms) ? $stage_terms[0]->name : 'Triage';
                    $author = get_userdata(get_the_author_meta('ID'));
                    $can_see_author = $stage_slug !== 'triage';
                ?>
                <tr data-stage="<?php echo $stage_slug; ?>">
                    <td>
                        <strong><?php the_title(); ?></strong>
                        <div class="row-actions">
                            <?php echo wp_trim_words(get_post_meta(get_the_ID(), '_gfj_abstract', true), 15); ?>
                        </div>
                    </td>
                    <td>
                        <?php if ($can_see_author): ?>
                            <?php echo esc_html($author->display_name); ?>
                        <?php else: ?>
                            <span class="anonymous">🔒 Anonymous</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $stage_slug; ?>">
                            <?php echo esc_html($stage_name); ?>
                        </span>
                    </td>
                    <td><?php echo get_the_date(); ?></td>
                    <td>
                        <a href="<?php echo admin_url('post.php?post=' . get_the_ID() . '&action=edit'); ?>" 
                           class="button button-small">
                            <?php echo $stage_slug === 'triage' ? 'Review' : 'View Details'; ?>
                        </a>
                    </td>
                </tr>
                <?php endwhile; wp_reset_postdata(); ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.filter-btn').on('click', function() {
        var stage = $(this).data('stage');
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        
        if (stage === 'all') {
            $('tbody tr').show();
        } else {
            $('tbody tr').hide();
            $('tbody tr[data-stage="' + stage + '"]').show();
        }
    });
});
</script>