<?php get_header();?>
<?php if ( is_home() ) {
$article_id = get_home_intro_page_id() ?: 2; // 获取设置的页面ID，若未设置则默认为2（兼容旧版）
$intro_post = get_post($article_id);
if ($intro_post) {
    echo apply_filters('the_content', $intro_post->post_content);
} elseif ($article_id == 2) {
    // 仅在默认为2且找不到页面时提示，避免未设置时也报错
    if (current_user_can('edit_theme_options')) {
        echo '<p style="color:var(--gray-color);font-size:0.9em;">[提示：未找到首页介绍内容。请在后台“外观-自定义-首页设置”中选择一个页面]</p>';
    }
}
?>
<h3>近期文章</h3>
<p>
<ul class="posts">
<?php
$excluded_categories = array();
// 获取闪念分类
if (function_exists('get_flash_category_term')) {
    $flash_category = get_flash_category_term();
    if ($flash_category) {
        $excluded_categories[] = $flash_category->term_id;
    }
}
// 获取周刊分类
$weekly_category = get_category_by_slug('weekly');
if ($weekly_category) {
    $excluded_categories[] = $weekly_category->term_id;
}

$args = array(
    'category__not_in' => $excluded_categories,
    'numberposts' => 10 // 保持默认或指定数量
);

$posts = get_posts($args);
foreach ($posts as $post) :
    setup_postdata($post);
?>
<li>
<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
</li>
<?php endforeach; ?>
<?php wp_reset_postdata(); ?>
</ul>
</p>
<?php } else { if (have_posts()) { while (have_posts()) { the_post(); ?>
<h1><?php the_title(); ?></h1>
<?php if ( is_single() ) : ?><p><?php the_category( ', ' );?>&nbsp · &nbsp<time datetime="<?php the_time('Y-m-d'); ?>"><?php echo get_the_date('Y-m-d'); ?></time></p><?php endif; ?>
<div class="content"><?php the_content(); ?></div>
<p><?php wp_link_pages(); ?></p>
<?php if ( is_single() && get_the_tags() ) : ?><p class="tags"><?php the_tags( ' #', ' #', '' ); ?></p><?php endif; ?>
<?php // 注释掉评论功能，因极简主题不需要评论
// if ( comments_open() ) { ?><div class="comlist"><?php // comments_template();?></div><?php // } ?>
<?php }}} ?>
<?php get_footer();?>
