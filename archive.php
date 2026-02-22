<?php get_header(); ?>

<div class="container">
    <div class="posts">
        <?php if (have_posts()) : ?>
            <?php if (is_category('weekly')) : ?>
                <!-- 周刊分类特殊处理 -->
                <?php
                echo '<div class="weekly-grid columns-' . get_weekly_grid_columns() . '" style="gap: ' . get_weekly_card_spacing() . 'px;">';
                while ( have_posts() ) : the_post(); 
                    echo '<div class="weekly-card columns-' . get_weekly_grid_columns() . '">';
                    echo '<a href="' . get_permalink() . '" class="weekly-card-link">';
                    echo '<div class="weekly-card-image">';
                    
                    $cover_image = get_weekly_cover_image();
                    if ($cover_image) {
                        echo '<img src="' . esc_url($cover_image) . '" alt="' . get_the_title_attribute() . '" loading="lazy">';
                    } else {
                        echo '<div class="weekly-placeholder"><span>周刊</span></div>';
                    }
                    
                    echo '</div>';
                    echo '<div class="weekly-card-content">';
                    echo '<h3 class="weekly-card-title">' . get_the_title() . '</h3>';
                    echo '</div>';
                    echo '</a>';
                    echo '</div>';
                endwhile;
                echo '</div>';
                ?>
            <?php else : ?>
                <!-- 普通分类显示 - 不再处理闪念分类 -->
                <ul class="posts">
                <?php while (have_posts()) : the_post(); ?>
                    <li>
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        <time><?php echo get_the_date('Y-m-d'); ?></time>
                    </li>
                <?php endwhile; ?>
                </ul>
            <?php endif; ?>
            
            <div class="pagination">
                <?php echo paginate_links(); ?>
            </div>
            
        <?php else : ?>
            <article class="post no-results">
                <header class="post-header">
                    <h2 class="post-title">暂无文章</h2>
                </header>
                <div class="post-content">
                    <p>抱歉，暂时没有找到相关文章。</p>
                </div>
            </article>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
