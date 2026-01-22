<?php
/**
 * Template part for displaying article card in archive
 */
$post_id = get_the_ID();
$doi = get_post_meta($post_id, '_gfj_doi', true);
$author_display = get_post_meta($post_id, '_gfj_author_display', true);
$pdf_url = get_post_meta($post_id, '_gfj_pdf_url', true);
$pdf_attachment_id = get_post_meta($post_id, '_gfj_pdf_attachment_id', true);
$pub_date = get_post_meta($post_id, '_gfj_publication_date', true);

// Use secure download link ONLY if the manual URL is empty
if (empty($pdf_url) && $pdf_attachment_id) {
    $pdf_url = GFJ_File_Handler::get_download_url($pdf_attachment_id);
}

$topics = get_the_terms($post_id, 'gfj_topic');
$topic_name = !empty($topics) && !is_wp_error($topics) ? $topics[0]->name : 'Article';
$card_type_override = isset($gfj_card_type_override) ? $gfj_card_type_override : '';
$card_type = $card_type_override ? $card_type_override : $topic_name;
$hide_actions = !empty($gfj_card_hide_actions);

$display_date = $pub_date ? date_i18n('M Y', strtotime($pub_date)) : get_the_date('M Y');
$authors = !empty($author_display) ? $author_display : get_the_author();
?>
<article class="gfj-article-card">
    <div class="gfj-card-left">
        <span class="gfj-card-date"><?php echo esc_html($display_date); ?></span>
        <span class="gfj-card-type"><?php echo esc_html($card_type); ?></span>
    </div>
    
    <div class="gfj-card-middle">
        <h3 class="gfj-card-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>
        <div class="gfj-card-authors">
            <?php echo esc_html($authors); ?>
        </div>
        <?php if ($doi): ?>
        <div class="gfj-card-doi">
            DOI: <a href="https://doi.org/<?php echo esc_attr($doi); ?>" target="_blank"><?php echo esc_html($doi); ?></a>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (!$hide_actions): ?>
    <div class="gfj-card-right">
        <?php if ($pdf_url): ?>
        <a href="<?php echo esc_url($pdf_url); ?>" class="button button-small button-secondary" target="_blank" aria-label="Download PDF">
            <span class="dashicons dashicons-pdf"></span> PDF
        </a>
        <?php else: ?>
        <a href="<?php the_permalink(); ?>" class="button button-small button-primary">View</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</article>
