<?php
/*
Template Name: 博客模板
*/

get_header(); ?>

<div class="blog-page">
    <div class="rss-header" style="text-align:right; margin-bottom:20px; padding-right: 10px;">
        <a href="<?php echo esc_url(home_url('/feed/')); ?>" target="_blank" title="订阅博客 RSS" style="font-size:0.85em; opacity:0.6; text-decoration:none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><path d="M4 11a9 9 0 0 1 9 9"></path><path d="M4 4a16 16 0 0 1 16 16"></path><circle cx="5" cy="19" r="1"></circle></svg>
            RSS
        </a>
    </div>
    <?php
    $excluded_category_ids = array();

    $flash_category = get_flash_category_term();
    if ($flash_category) {
        $excluded_category_ids[] = (int) $flash_category->term_id;
    }

    $weekly_category = get_category_by_slug('weekly');
    if ($weekly_category) {
        $excluded_category_ids[] = (int) $weekly_category->term_id;
    }

    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $query_args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 30, // 博客每页 30 篇
        'paged' => $paged,
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
    );

    if (!empty($excluded_category_ids)) {
        $query_args['category__not_in'] = array_values(array_unique($excluded_category_ids));
    }

    $blog_query = new WP_Query($query_args);
    ?>

    <?php if ($blog_query->have_posts()) : ?>
        <div class="blog-list" id="blog-list">
            <?php
            $current_group = '';
            $is_open_list = false;

            while ($blog_query->have_posts()) : $blog_query->the_post();
                $group_label = get_the_date('Y年');

                if ($group_label !== $current_group) {
                    if ($is_open_list) {
                        echo '</ul></section>';
                    }

                    $current_group = $group_label;
                    $is_open_list = true;

                    echo '<section class="blog-group" data-group="' . esc_attr($group_label) . '">';
                    echo '<h2 class="blog-group-title">' . esc_html($group_label) . '</h2>';
                    echo '<ul class="blog-posts">';
                }

                echo '<li class="blog-item">';
                echo '<a class="blog-link" href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a>';
                echo '<time class="blog-time" datetime="' . esc_attr(get_the_date('c')) . '">' . esc_html(get_the_date('m-d')) . '</time>';
                echo '</li>';
            endwhile;

            if ($is_open_list) {
                echo '</ul></section>';
            }
            ?>
            
            <div class="pagination">
                <?php
                echo paginate_links(array(
                    'total' => $blog_query->max_num_pages,
                    'current' => max(1, get_query_var('paged')),
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'mid_size' => 2,
                    'end_size' => 1
                ));
                ?>
            </div>
        </div>
    <?php else : ?>
        <div class="blog-empty">
            <p>暂无博客文章。</p>
        </div>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>
</div>

<?php get_footer(); ?>
