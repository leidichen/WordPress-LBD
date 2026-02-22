<?php
/*
Template Name: 搜索结果页
*/
get_header(); ?>

<div class="blog-page search-page">
    <header class="page-header" style="margin-bottom: 40px;">

        
        <!-- 始终显示的搜索框 -->
        <div class="search-form-wrapper" style="margin-bottom: 20px; max-width: 500px;">
            <form role="search" method="get" id="searchform" class="searchform" action="<?php echo esc_url(home_url('/')); ?>">
                <div style="display: flex; gap: 10px;">
                    <input type="text" value="<?php echo get_search_query(); ?>" name="s" id="s" placeholder="输入关键词搜索..." style="flex: 1; padding: 10px; border: 1px solid var(--blockquote-border-color); border-radius: 4px; background: var(--background-color); color: var(--text-color); font-size: 16px;" />
                    <input type="submit" id="searchsubmit" value="搜索" style="padding: 10px 24px; background: var(--link-color); color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;" />
                </div>
            </form>
            
            <!-- 标签展示 -->
            <?php
            $tags = get_tags(array(
                'orderby' => 'count', 
                'order' => 'DESC', 
                'number' => 0 // 0 表示显示所有标签
            ));
            if ($tags) : ?>
                <div class="search-tags">
                    <?php foreach ($tags as $tag) : ?>
                        <a href="<?php echo get_tag_link($tag->term_id); ?>" class="search-tag-item">
                            #<?php echo $tag->name; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (get_search_query()) : ?>
            <p style="color: var(--gray-color); font-size: 0.9em;">
                找到 <?php echo $wp_query->found_posts; ?> 篇相关文章
            </p>
        <?php endif; ?>
    </header>

    <?php if (get_search_query()) : // 只有在有搜索词时才显示结果列表 ?>
        <?php if (have_posts()) : ?>
            <div class="blog-list">
                <ul class="blog-posts">
                    <?php while (have_posts()) : the_post(); ?>
                        <li class="blog-item">
                            <a class="blog-link" href="<?php the_permalink(); ?>">
                                <?php the_title(); ?>
                            </a>
                            <time class="blog-time" datetime="<?php echo get_the_date('c'); ?>">
                                <?php echo get_the_date('Y-m-d'); ?>
                            </time>
                        </li>
                    <?php endwhile; ?>
                </ul>

                <div class="pagination">
                    <?php
                    echo paginate_links(array(
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
                <p>没有找到相关文章，请尝试其他关键词。</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
