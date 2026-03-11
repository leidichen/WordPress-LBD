<?php
/*
Template Name: 卡片模板
*/

get_header(); ?>

<div class="idea-container" style="--idea-clamp-lines: <?php echo (int) get_flash_clamp_lines(); ?>;" data-clamp-enabled="<?php echo get_flash_clamp_enabled() ? '1' : '0'; ?>">
    
    <div class="rss-header" style="text-align:right; margin-bottom:20px; padding-right: 10px;">
        <?php 
        $rss_flash_slug = 'idea';
        if (function_exists('get_flash_category_term')) {
            $rss_flash_term = get_flash_category_term();
            if ($rss_flash_term) {
                $rss_flash_slug = $rss_flash_term->slug;
            }
        }
        ?>
        <a href="<?php echo esc_url(home_url('/category/' . $rss_flash_slug . '/feed/')); ?>" target="_blank" title="订阅闪念 RSS" style="font-size:0.85em; opacity:0.6; text-decoration:none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><path d="M4 11a9 9 0 0 1 9 9"></path><path d="M4 4a16 16 0 0 1 16 16"></path><circle cx="5" cy="19" r="1"></circle></svg>
            RSS
        </a>
    </div>

    <?php
    // 优先按 slug，再按名称回退
    $flash_category_name = get_flash_category_name();
    $flash_category = get_flash_category_term();

    if ($flash_category) {
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $args = array(
            'cat' => $flash_category->term_id,
            'posts_per_page' => get_flash_posts_per_page(),
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC'
        );

        $idea_query = new WP_Query($args);

        if ($idea_query->have_posts()) : ?>
            <div class="idea-grid">
                <?php while ($idea_query->have_posts()) : $idea_query->the_post(); ?>
                    <li class="idea-card">
                        <div class="idea-card-wrapper">
                            <?php
                            // 使用较大的长度限制，让 CSS 处理视觉截断
                            $content_data = get_twitter_style_content(10000);
                            $has_image = ($content_data['image_count'] ?? 0) > 0;
                            $card_extra_class = $has_image ? 'has-image' : 'no-image';
                            ?>
                            <div class="idea-card-container <?php echo $card_extra_class; ?>">
                                <div class="idea-card-header">
                                    <span class="idea-timestamp"><?php echo get_the_time('n月j日 H:i'); ?></span>
                                    <div class="idea-meta">
                                        <?php if (function_exists('get_weather_enabled') && get_weather_enabled() && get_the_date('Ymd') === date_i18n('Ymd')): ?>
                                            <span class="idea-weather" aria-label="weather"></span>
                                        <?php endif; ?>
                                        <span class="idea-word-count"><?php echo get_flash_word_count(); ?></span>
                                    </div>
                                </div>
                                <div class="idea-card-content" data-gallery='<?php echo esc_attr($content_data['gallery_data'] ?? ''); ?>' data-image-count='<?php echo $content_data['image_count'] ?? 0; ?>'>
                                    <?php if (isset($content_data['images_html'])): ?>
                                        <div class="idea-content-clamp">
                                            <?php echo $content_data['text_html']; ?>
                                        </div>
                                        <div class="idea-expand-toggle">
                                            <span class="idea-expand-text">显示更多</span>
                                        </div>
                                        <?php echo $content_data['images_html']; ?>
                                    <?php else: ?>
                                        <div class="idea-content-clamp">
                                            <?php echo $content_data['full']; ?>
                                        </div>
                                        <div class="idea-expand-toggle">
                                            <span class="idea-expand-text">显示更多</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="idea-card-footer">
                                    <button class="idea-action-btn copy-rich" type="button" aria-label="复制文本">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="9" y="9" width="13" height="13" rx="2"></rect>
                                            <rect x="3" y="3" width="13" height="13" rx="2"></rect>
                                        </svg>
                                    </button>
                                    <button class="idea-action-btn copy-md" type="button" aria-label="复制Markdown">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="14" rx="2"></rect>
                                            <path d="M6 12V9l3 3V9h2v6H6zM16 9h-3v6h3l3-3-3-3z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endwhile; ?>
            </div>
            
            <?php if ($idea_query->max_num_pages > 1) : ?>
                <div class="pagination">
                    <?php
                    echo paginate_links(array(
                        'total' => $idea_query->max_num_pages,
                        'current' => max(1, get_query_var('paged')),
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'mid_size' => 2,
                        'end_size' => 1
                    ));
                    ?>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <div class="idea-empty">
                <p>暂无闪念内容</p>
            </div>
        <?php endif;

        wp_reset_postdata();
    } else {
        echo '<div class="idea-empty"><p>请先创建"' . esc_html($flash_category_name) . '"分类</p></div>';
    }
    ?>
</div>

<?php get_footer(); ?>
