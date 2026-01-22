<?php
/**
 * Single Article Template
 *
 * @package Gauge_Freedom_Journal
 */

get_header();

while ( have_posts() ) : the_post();

    $article_id = get_the_ID();
    $doi = get_post_meta($article_id, '_gfj_doi', true);
    $author_display = get_post_meta($article_id, '_gfj_author_display', true);
    $pdf_url = get_post_meta($article_id, '_gfj_pdf_url', true);
    $pdf_attachment_id = get_post_meta($article_id, '_gfj_pdf_attachment_id', true);
    $latex_url = get_post_meta($article_id, '_gfj_latex_url', true);
    $latex_attachment_id = get_post_meta($article_id, '_gfj_latex_attachment_id', true);
    $artifacts_url = get_post_meta($article_id, '_gfj_artifacts_url', true);
    $artifacts_attachment_id = get_post_meta($article_id, '_gfj_artifacts_attachment_id', true);
    $significance = get_post_meta($article_id, '_gfj_significance', true);

    // Use secure download link ONLY if the manual URL is empty
    if (empty($pdf_url) && $pdf_attachment_id) {
        $pdf_url = GFJ_File_Handler::get_download_url($pdf_attachment_id);
    }

    if (empty($latex_url) && $latex_attachment_id) {
        $latex_url = GFJ_File_Handler::get_download_url($latex_attachment_id);
    }

    if (empty($artifacts_url) && $artifacts_attachment_id) {
        $artifacts_url = GFJ_File_Handler::get_download_url($artifacts_attachment_id);
    }

    $key_findings = get_post_meta($article_id, '_gfj_key_findings', true);
    $ai_disclosure = get_post_meta($article_id, '_gfj_ai_disclosure', true);
    $pub_date = get_post_meta($article_id, '_gfj_publication_date', true);

    // Format date
    $pub_date_display = $pub_date ? date_i18n(get_option('date_format'), strtotime($pub_date)) : '';

    // Authors
    $authors = !empty($author_display) ? $author_display : get_the_author();

    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="gfj-article-container">
            <div class="gfj-article-wrapper">
                
                <!-- Header -->
                <header class="gfj-article-header">
                    <div class="gfj-article-meta-top">
                        <span class="gfj-article-type"><?php echo get_the_term_list($article_id, 'gfj_topic', '', ', '); ?></span>
                        <?php if ($pub_date_display): ?>
                            <span class="gfj-pub-date">Published: <?php echo esc_html($pub_date_display); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="gfj-article-title"><?php the_title(); ?></h1>
                    
                    <div class="gfj-article-authors">
                        <?php echo esc_html($authors); ?>
                    </div>
                    
                    <?php if ($doi): ?>
                    <div class="gfj-article-doi">
                        <a href="https://doi.org/<?php echo esc_attr($doi); ?>" target="_blank">
                            https://doi.org/<?php echo esc_html($doi); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </header>

                <div class="gfj-article-layout">
                    
                    <!-- Main Content -->
                    <div class="gfj-article-main">
                        
                        <!-- Abstract -->
                        <section class="gfj-article-abstract">
                            <h2>Abstract</h2>
                            <div class="gfj-abstract-content">
                                <?php the_excerpt(); ?> 
                            </div>
                        </section>
                        
                        <!-- Significance Statement -->
                        <?php if ($significance): ?>
                        <section class="gfj-article-significance">
                            <h3>Significance Statement</h3>
                            <div class="gfj-box-highlight">
                                <?php echo wp_kses_post(wpautop($significance)); ?>
                            </div>
                        </section>
                        <?php endif; ?>

                        <!-- Key Findings -->
                        <?php if ($key_findings): ?>
                        <section class="gfj-article-findings">
                            <h3>Key Findings</h3>
                            <div class="gfj-findings-list">
                                <?php echo wp_kses_post(wpautop($key_findings)); ?>
                            </div>
                        </section>
                        <?php endif; ?>

                        <!-- Full Content -->
                        <section class="gfj-article-content">
                            <?php the_content(); ?>
                        </section>

                        <!-- AI Disclosure -->
                        <?php if ($ai_disclosure): ?>
                        <section class="gfj-article-ai-disclosure">
                            <h4>AI Contribution Disclosure</h4>
                            <p><?php echo esc_html($ai_disclosure); ?></p>
                        </section>
                        <?php endif; ?>

                    </div>

                    <!-- Sidebar -->
                    <aside class="gfj-article-sidebar">
                        <div class="gfj-sticky-sidebar">
                            
                            <div class="gfj-action-buttons">
                                <?php if ($pdf_url): ?>
                                <a href="<?php echo esc_url($pdf_url); ?>" class="button gfj-btn-download gfj-btn-pdf" target="_blank">
                                    <span class="dashicons dashicons-pdf"></span> Download PDF
                                </a>
                                <?php endif; ?>

                                <?php if ($latex_url): ?>
                                <a href="<?php echo esc_url($latex_url); ?>" class="button gfj-btn-download gfj-btn-latex" target="_blank" style="background-color: #0f172a;">
                                    <span class="dashicons dashicons-archive"></span> Download Sources (ZIP)
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($artifacts_url): ?>
                                <a href="<?php echo esc_url($artifacts_url); ?>" class="button gfj-btn-download gfj-btn-artifacts" target="_blank">
                                    <span class="dashicons dashicons-database"></span> Download Artifacts
                                </a>
                                <?php endif; ?>
                            </div>

                            <div class="gfj-sidebar-section gfj-citation-box">
                                <h3>How to Cite</h3>
                                <div class="gfj-citation-content">
                                    <?php 
                                    $citation_override = get_post_meta($article_id, '_gfj_citation_override', true);
                                    
                                    if ($citation_override) {
                                        echo wp_kses_post($citation_override);
                                    } else {
                                        // Dynamic Construction
                                        $cite_authors = !empty($author_display) ? $author_display : get_the_author();
                                        
                                        // Get Volume/Issue
                                        $issues = get_the_terms($article_id, 'gfj_issue');
                                        $vol_issue = '';
                                        if ($issues && !is_wp_error($issues)) {
                                            foreach ($issues as $term) {
                                                if ($term->parent == 0) { // Volume
                                                    $vol_num = str_replace('Volume ', '', $term->name); 
                                                    $vol_issue = $vol_num;
                                                } else { // Issue
                                                    $issue_num = str_replace('Issue ', '', $term->name);
                                                    $vol_issue .= '(' . $issue_num . ')';
                                                }
                                            }
                                        }

                                        echo esc_html($cite_authors) . '. ';
                                        echo '"' . get_the_title() . '". ';
                                        echo '<em>Gauge Freedom Journal</em> (' . date('Y') . ')';
                                        if ($vol_issue) echo ', ' . esc_html($vol_issue);
                                        echo '. ';
                                        if ($doi) echo 'DOI: ' . esc_html($doi);
                                    }
                                    ?>
                                    
                                    <div style="margin-top: 15px;">
                                        <button class="button-link gfj-toggle-bibtex">Show BibTeX</button>
                                    </div>
                                    
                                    <div class="gfj-bibtex-code" style="display:none;">
        <pre><?php 
        $bibtex_override = get_post_meta($article_id, '_gfj_bibtex_override', true);

        if ($bibtex_override) {
            echo esc_html($bibtex_override);
        } else {
        ?>@article{gfj_<?php echo $article_id; ?>,
          author = {<?php echo !empty($author_display) ? esc_html($author_display) : get_the_author(); ?>},
          title = {<?php the_title(); ?>},
          journal = {Gauge Freedom Journal},
          year = {<?php echo date('Y'); ?>},
        <?php if ($vol_issue) echo "  volume = {{$vol_issue}},\n"; ?>
          doi = {<?php echo esc_html($doi); ?>}
        }<?php 
        } 
        ?></pre>
                                    </div>
                                </div>
                            </div>

                            <div class="gfj-sidebar-section">
                                <h3>License</h3>
                                <p>
                                    <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" rel="noopener">
                                        <img src="https://mirrors.creativecommons.org/presskit/buttons/88x31/png/by.png" alt="CC BY 4.0" style="width:88px;">
                                    </a>
                                    <br>
                                    This work is licensed under a Creative Commons Attribution 4.0 International License.
                                </p>
                            </div>

                        </div>
                    </aside>

                </div>
            </div>
        </div>
    </article>

    <?php
endwhile; // End of the loop.

?>

<script>
jQuery(document).ready(function($) {
    $('.gfj-toggle-bibtex').on('click', function() {
        $('.gfj-bibtex-code').slideToggle();
    });
});
</script>

<?php get_footer(); ?>
