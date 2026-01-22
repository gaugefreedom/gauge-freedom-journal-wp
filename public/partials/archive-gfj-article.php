<?php
/**
 * Archive Template for Articles
 *
 * @package Gauge_Freedom_Journal
 */

$use_block_template_parts = function_exists('wp_is_block_theme')
    && wp_is_block_theme()
    && function_exists('block_template_part');

$block_header = '';
$block_footer = '';

if ($use_block_template_parts) {
    ob_start();
    block_template_part('header');
    $block_header = ob_get_clean();

    ob_start();
    block_template_part('footer');
    $block_footer = ob_get_clean();

    ?>
    <!doctype html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php wp_head(); ?>
    </head>
    <body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <div class="wp-site-blocks">
    <?php
    echo $block_header;
} else {
    get_header();
}
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <div class="gfj-archive-container">
            <?php $card_template = GFJ_PLUGIN_DIR . 'public/partials/content-gfj-article-card.php'; ?>
            <header class="gfj-archive-header">
                <h1 class="page-title">Journal Archive</h1>
                <p class="gfj-archive-description">Browse all published articles by Volume and Issue.</p>
            </header>

            <?php
            // ==========================================
            // 1. ARTICLES IN PRESS / PREPRINTS (No Volume)
            // ==========================================
            $args_preprints = [
                'post_type'      => 'gfj_article',
                'posts_per_page' => -1,
                'tax_query'      => [
                    [
                        'taxonomy' => 'gfj_issue',
                        'operator' => 'NOT EXISTS', // Articles not assigned to a volume yet
                    ]
                ]
            ];
            $preprints_query = new WP_Query($args_preprints);

            if ($preprints_query->have_posts()) : ?>
                <section class="gfj-volume-section gfj-preprints-section">
                    <h2 class="gfj-volume-title">In Press / Latest Articles</h2>
                    
                    <?php while ($preprints_query->have_posts()) : $preprints_query->the_post(); ?>
                        <?php $gfj_card_type_override = 'Preprint'; ?>
                        <?php include $card_template; ?>
                    <?php endwhile; ?>
                    
                </section>
                <?php 
                wp_reset_postdata();
                unset($gfj_card_type_override);
            endif; 

            // ==========================================
            // 2. VOLUMES & ISSUES
            // ==========================================
            $volumes = get_terms([
                'taxonomy'   => 'gfj_issue',
                'parent'     => 0,      // Top level volumes
                'hide_empty' => true,
                'pad_counts' => true,
                'orderby'    => 'name', 
                'order'      => 'DESC', // Newest Volume first (Vol 2, Vol 1...)
            ]);

            if (!empty($volumes) && !is_wp_error($volumes)) :
                foreach ($volumes as $volume) : 
                    
                    $args_volume = [
                        'post_type'      => 'gfj_article',
                        'posts_per_page' => -1,
                        'tax_query'      => [
                            [
                                'taxonomy'         => 'gfj_issue',
                                'field'            => 'term_id',
                                'terms'            => $volume->term_id,
                                'include_children' => true,
                            ]
                        ],
                        'orderby' => 'date',
                        'order'   => 'ASC' // Issue 1 first
                    ];
                    $volume_query = new WP_Query($args_volume);
                    
                    if ($volume_query->have_posts()) : ?>
                        <section class="gfj-volume-section">
                            <h2 class="gfj-volume-title"><?php echo esc_html($volume->name); ?></h2>
                            
                            <?php while ($volume_query->have_posts()) : $volume_query->the_post(); ?>
                                <?php $gfj_card_type_override = 'Research'; ?>
                                <?php include $card_template; ?>
                            <?php endwhile; ?>
                            
                        </section>
                    <?php endif; 
                    wp_reset_postdata();
                endforeach;
            endif;
            ?>

        </div></main></div>

<?php
if ($use_block_template_parts) {
    echo $block_footer;
    ?>
    </div>
    <?php wp_footer(); ?>
    </body>
    </html>
    <?php
} else {
    get_footer();
}
?>
