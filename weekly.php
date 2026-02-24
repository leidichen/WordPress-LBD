<?php
/*
Template Name: 网格模板
*/
get_header(); ?>

<div class="weekly-page">
    
    <div class="rss-header" style="text-align:right; margin-bottom:20px; padding-right: 10px;">
        <a href="<?php echo esc_url(home_url('/category/weekly/feed/')); ?>" target="_blank" title="订阅周刊 RSS" style="font-size:0.85em; opacity:0.6; text-decoration:none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><path d="M4 11a9 9 0 0 1 9 9"></path><path d="M4 4a16 16 0 0 1 16 16"></path><circle cx="5" cy="19" r="1"></circle></svg>
            RSS
        </a>
    </div>

    <?php
    $weekly_category = get_category_by_slug('weekly');
    
    if ($weekly_category) {
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $args = array(
            'cat' => $weekly_category->term_id,
            'posts_per_page' => 12, // 网格每页 12 篇
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $weekly_query = new WP_Query($args);
        
        if ($weekly_query->have_posts()) : ?>
            <div class="weekly-grid columns-<?php echo get_weekly_grid_columns(); ?>">
                <?php while ($weekly_query->have_posts()) : $weekly_query->the_post(); ?>
                    <div class="weekly-card columns-<?php echo get_weekly_grid_columns(); ?>">
                        <a href="<?php the_permalink(); ?>" class="weekly-card-link">
                            <div class="weekly-card-image">
                                <?php 
                                $cover_image = get_weekly_cover_image();
                                if ($cover_image) : ?>
                                    <img src="<?php echo esc_url($cover_image); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
                                <?php else : ?>
                                    <div class="weekly-placeholder">
                                        <span>周刊</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="weekly-card-content">
                                <h3 class="weekly-card-title"><?php the_title(); ?></h3>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>

            <?php if ($weekly_query->max_num_pages > 1) : ?>
                <div class="pagination">
                    <?php
                    echo paginate_links(array(
                        'total' => $weekly_query->max_num_pages,
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
            <p>暂无周刊文章。</p>
        <?php endif;
        
        wp_reset_postdata();
    } else {
        echo '<p>未找到weekly分类，请检查分类设置。</p>';
    }
    ?>
    
</div>

<?php get_footer(); ?>
