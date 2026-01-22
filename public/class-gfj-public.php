<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Gauge_Freedom_Journal
 * @subpackage Gauge_Freedom_Journal/public
 */

class GFJ_Public {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // MANUALLY ADD THE NEW HOOK HERE to ensure it runs
        add_filter('the_content', [$this, 'inject_article_content']);
        add_shortcode('gfj_latest_articles', [$this, 'render_latest_articles_shortcode']);
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . '../assets/css/public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . '../assets/js/public.js',
            array('jquery'),
            $this->version,
            false
        );

        wp_enqueue_script(
            'gfj-mathjax',
            'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.7/MathJax.js?config=TeX-AMS_CHTML',
            array(),
            null,
            false
        );
        wp_script_add_data('gfj-mathjax', 'defer', true);
    }

    /**
     * [DISABLED] We return $template immediately to let the THEME handle the page.
     * This fixes the missing Header/Footer issue.
     */
    public function filter_single_template($template) {
        // We do NOT want to load a custom file anymore. 
        // We let the theme load its default page.php or single.php.
        return $template;
    }

    /**
     * Filter the archive template for gfj_article post type.
     */
    public function filter_archive_template($template) {
        if (is_post_type_archive('gfj_article') || is_tax('gfj_issue') || is_tax('gfj_topic')) {
            $plugin_template = plugin_dir_path(dirname(__FILE__)) . 'public/partials/archive-gfj-article.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }

    /**
     * Output head-level CSS overrides for single articles.
     */
    public function render_article_head_styles() {
        if (!is_singular('gfj_article')) {
            return;
        }
        ?>
        <style>
            /* Hide Theme Title */
            .single-gfj_article .entry-header,
            .single-gfj_article .page-header,
            .single-gfj_article h1.entry-title,
            .single-gfj_article .wp-block-post-title,
            .single-gfj_article h1.wp-block-post-title {
                display: none !important;
            }

            /* Hide Theme Author/Bio Footer */
            .single-gfj_article .entry-author,
            .single-gfj_article .author-bio,
            .single-gfj_article .post-author,
            .single-gfj_article .about-author,
            .single-gfj_article .entry-footer .author,
            .single-gfj_article .wp-block-post-author,
            .single-gfj_article .wp-block-post-author-name,
            .single-gfj_article .wp-block-post-author-biography,
            .single-gfj_article .wp-block-post-author__content,
            .single-gfj_article .wp-block-post-author__name,
            .single-gfj_article .wp-block-post-author__bio,
            .single-gfj_article .wp-block-post-author__avatar {
                display: none !important;
            }

            /* Hide Theme Date Blocks */
            .single-gfj_article .entry-date,
            .single-gfj_article .posted-on,
            .single-gfj_article .wp-block-post-date,
            .single-gfj_article .wp-block-post-date time {
                display: none !important;
            }
        </style>
        <?php
    }

    /**
     * Shortcode to display latest articles. Usage: [gfj_latest_articles count="3"]
     */
    public function render_latest_articles_shortcode($atts) {
        $atts = shortcode_atts([
            'count' => 5,
            'title' => 'Latest Articles',
        ], $atts);

        $query = new WP_Query([
            'post_type'      => 'gfj_article',
            'posts_per_page' => intval($atts['count']),
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        if (!$query->have_posts()) {
            return '';
        }

        $card_template = GFJ_PLUGIN_DIR . 'public/partials/content-gfj-article-card.php';

        ob_start();
        ?>
        <div class="gfj-latest-articles-container">
            <?php if (!empty($atts['title'])): ?>
                <h2 class="wp-block-heading has-large-font-size"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>

            <div class="gfj-article-list">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <?php $gfj_card_type_override = 'Research'; ?>
                    <?php $gfj_card_hide_actions = true; ?>
                    <?php include $card_template; ?>
                <?php endwhile; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
        unset($gfj_card_type_override, $gfj_card_hide_actions);
        return ob_get_clean();
    }

    /**
     * THE NEW ENGINE: Inject the Article Layout matching 'public.css' exactly.
     */
    public function inject_article_content($content) {
        // 1. Safety Checks
        if (!is_singular('gfj_article') || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        // 2. Get Data
        $post_id = get_the_ID();
        global $post;
        $raw_title = $post->post_title;
        $doi = get_post_meta($post_id, '_gfj_doi', true);
        $pdf_url = get_post_meta($post_id, '_gfj_pdf_url', true);
        $artifacts_url = get_post_meta($post_id, '_gfj_artifacts_url', true);
        $latex_url = get_post_meta($post_id, '_gfj_latex_url', true);
        $significance = get_post_meta($post_id, '_gfj_significance', true);
        $key_findings = get_post_meta($post_id, '_gfj_key_findings', true);
        $ai_disclosure = get_post_meta($post_id, '_gfj_ai_disclosure', true);
        
        // Prepare Author String (or use specific meta if you have it)
        $authors = get_post_meta($post_id, '_gfj_author_display', true);
        if (!$authors) {
            $authors = get_the_author(); 
        }

        // 3. Start Output Buffering
        ob_start();
        ?>

        <div class="gfj-article-wrapper alignwide">
            
            <header class="gfj-article-header">
                <div class="gfj-article-meta-top" style="display:flex; align-items:center; flex-wrap:wrap; gap:10px;">
                    <span class="gfj-article-type">Research Article</span>

                    <?php if ($doi): ?>
                        <span class="gfj-meta-separator">•</span>
                        <a href="https://doi.org/<?php echo esc_attr($doi); ?>" class="gfj-article-doi" style="text-decoration:none; color:#666;">DOI: <?php echo esc_html($doi); ?></a>
                    <?php endif; ?>

                    <span class="gfj-meta-separator">•</span>
                    <span class="gfj-article-date" style="color:#666;"><?php echo get_the_date('F j, Y'); ?></span>

                    <span class="gfj-meta-separator" style="color:#ccc;">|</span>
                    <a href="<?php echo esc_url(get_post_type_archive_link('gfj_article')); ?>" class="gfj-back-link" style="text-decoration:none; font-weight:600; font-size:0.9em; color:var(--gfj-primary, #0073aa);">
                        &larr; Back to Articles
                    </a>
                </div>

                <h1 class="gfj-article-title"><?php echo wp_kses_post($raw_title); ?></h1>

                <div class="gfj-article-authors">
                    <?php echo esc_html($authors); ?>
                </div>
            </header>

            <div class="gfj-article-layout">
                
                <div class="gfj-article-main">
                    
                    <?php if ($significance): ?>
                        <section>
                            <h3>Significance</h3>
                            <div class="gfj-box-highlight">
                                <?php echo wpautop(wp_kses_post($significance)); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <section>
                        <h3>Abstract</h3>
                        <div class="gfj-abstract-content">
                            <?php echo get_the_excerpt(); ?>
                        </div>
                    </section>

                    <?php if ($key_findings): ?>
                        <section>
                            <h3>Key Findings</h3>
                            <div class="gfj-findings-list">
                                <?php echo wpautop(wp_kses_post($key_findings)); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <section class="gfj-article-content">
                        <h3>Full Text</h3>
                        <hr style="margin: 0 0 20px 0; border-color: #e5e7eb;">
                        <?php echo $content; ?>
                    </section>

                    <?php if ($ai_disclosure): ?>
                        <div class="gfj-article-ai-disclosure">
                            <h4>Transparency Statement</h4>
                            <strong>AI Contribution:</strong> <?php echo esc_html($ai_disclosure); ?>
                        </div>
                    <?php endif; ?>

                </div>

                <aside class="gfj-sticky-sidebar">
                    
                    <div class="gfj-action-buttons">
                        <?php if ($pdf_url): ?>
                            <a href="<?php echo esc_url($pdf_url); ?>" class="button button-primary gfj-btn-download gfj-btn-pdf" target="_blank">
                                <span class="dashicons dashicons-pdf"></span> Download PDF
                            </a>
                        <?php endif; ?>

                        <?php if ($artifacts_url): ?>
                            <a href="<?php echo esc_url($artifacts_url); ?>" class="button gfj-btn-download gfj-btn-artifacts" style="background-color: #1f2937; color: #fff; border: none;">
                                <span class="dashicons dashicons-download"></span> Artifacts Bundle
                            </a>
                        <?php endif; ?>

                        <?php if ($latex_url): ?>
                            <a href="<?php echo esc_url($latex_url); ?>" class="button gfj-btn-download gfj-btn-latex" style="background-color: #4b5563; color: #fff; border: none;">
                                <span class="dashicons dashicons-code-standards"></span> Source (LaTeX)
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="gfj-sidebar-section">
                        <h3>Cite this Article</h3>
                        <div class="gfj-citation-content">
                            <?php 
                            $citation = $authors . " (" . get_the_date('Y') . "). " . get_the_title() . ". Gauge Freedom Journal.";
                            echo esc_html($citation); 
                            ?>
                        </div>
                        <div class="gfj-toggle-bibtex">
                            <a href="#" onclick="jQuery('.gfj-bibtex-code').slideToggle(); return false;">Show BibTeX</a>
                        </div>
                        <div class="gfj-bibtex-code" style="display:none;">
                            <pre>@article{gfj<?php echo get_the_ID(); ?>,
                            title={<?php echo get_the_title(); ?>},
                            author={<?php echo $authors; ?>},
                            journal={Gauge Freedom Journal},
                            year={<?php echo get_the_date('Y'); ?>},
                            doi={<?php echo esc_html($doi); ?>}
                            }</pre>
                        </div>
                    </div>

                    <div class="gfj-sidebar-section">
                        <h3>License</h3>
                        <div style="font-size: 13px; color: #666;">
                            <p>CC-BY 4.0 International.</p>
                            <p>Open Access & Double-Blind Reviewed.</p>
                        </div>
                    </div>

                </aside>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }

    /**
     * Inject Highwire Press meta tags into the head for single articles.
     */
    public function render_highwire_tags() {
        if (!is_singular('gfj_article')) {
            return;
        }

        $post_id = get_the_ID();
        $doi = get_post_meta($post_id, '_gfj_doi', true);
        $pdf_url = get_post_meta($post_id, '_gfj_pdf_url', true);
        $pub_date = get_post_meta($post_id, '_gfj_publication_date', true);
        $date_w3c = $pub_date ? date('Y-m-d', strtotime($pub_date)) : get_the_date('Y-m-d');

        // Parse Author String (Split by " and " or ",")
        $author_display = get_post_meta($post_id, '_gfj_author_display', true);
        $authors = $author_display
            ? preg_split('/(\s+and\s+|\s+&\s+|,\s+)/i', $author_display)
            : [get_the_author()];

        echo "\n\n";

        // 1. Highwire Press (Google Scholar)
        printf('<meta name="citation_title" content="%s">' . "\n", esc_attr(get_the_title()));
        printf('<meta name="citation_journal_title" content="Gauge Freedom Journal">' . "\n");
        printf(
            '<meta name="citation_publication_date" content="%s">' . "\n",
            esc_attr(date('Y/m/d', strtotime($date_w3c)))
        );

        foreach ($authors as $author) {
            $author = trim($author);
            if ($author) {
                printf('<meta name="citation_author" content="%s">' . "\n", esc_attr($author));
            }
        }

        if ($pdf_url) {
            printf('<meta name="citation_pdf_url" content="%s">' . "\n", esc_url($pdf_url));
        }
        if ($doi) {
            printf('<meta name="citation_doi" content="%s">' . "\n", esc_attr($doi));
        }
        printf('<meta name="citation_fulltext_html_url" content="%s">' . "\n", esc_url(get_permalink()));

        // Volume/Issue detection from Taxonomy
        $terms = get_the_terms($post_id, 'gfj_issue');
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                if (preg_match('/Volume\s+(\d+)/i', $term->name, $matches)) {
                    printf('<meta name="citation_volume" content="%s">' . "\n", esc_attr($matches[1]));
                }
                if (preg_match('/Issue\s+(\d+)/i', $term->name, $matches)) {
                    printf('<meta name="citation_issue" content="%s">' . "\n", esc_attr($matches[1]));
                }
            }
        }

        // 2. Dublin Core (Libraries/Zotero)
        printf('<meta name="dc.title" content="%s">' . "\n", esc_attr(get_the_title()));
        printf('<meta name="dc.date" content="%s">' . "\n", esc_attr($date_w3c));
        printf('<meta name="dc.publisher" content="Gauge Freedom Journal">' . "\n");
        if ($doi) {
            printf('<meta name="dc.identifier" content="%s">' . "\n", esc_attr($doi));
            printf('<meta name="dc.identifier.doi" content="%s">' . "\n", esc_attr($doi));
        }

        foreach ($authors as $author) {
            $author = trim($author);
            if ($author) {
                printf('<meta name="dc.creator" content="%s">' . "\n", esc_attr($author));
            }
        }

        echo "\n\n";
    }
}
