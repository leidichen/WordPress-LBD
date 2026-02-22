<?php
/*
Template Name: 订阅页面
*/
get_header(); ?>

<div class="subscribe-page">
    <h1>订阅本站</h1>
    
    <div class="subscribe-content">
        <p>你可以通过 RSS 订阅感兴趣的内容：</p>
        
        <div class="rss-list">
            <div class="rss-item">
                <h3><a href="<?php echo esc_url(home_url('/feed/')); ?>">博客 RSS</a></h3>
                <p>仅包含深度思考与技术文章，剔除周刊与闪念。</p>
                <div class="rss-link-box">
                    <code><?php echo esc_url(home_url('/feed/')); ?></code>
                </div>
            </div>
            
            <div class="rss-item">
                <h3><a href="<?php echo esc_url(home_url('/category/weekly/feed/')); ?>">周刊 RSS</a></h3>
                <p>每周精选分享，独立更新。</p>
                <div class="rss-link-box">
                    <code><?php echo esc_url(home_url('/category/weekly/feed/')); ?></code>
                </div>
            </div>

            <div class="rss-item">
                <?php 
                $flash_slug = 'idea';
                if (function_exists('get_flash_category_name')) {
                    $flash_slug = get_flash_category_name();
                    // 如果拿到的是分类名而不是 slug，尝试转换一下或者直接用 category/slug 形式
                    // 这里为了保险，还是动态获取一下 term
                    $flash_term = get_flash_category_term();
                    if ($flash_term) {
                        $flash_slug = $flash_term->slug;
                    }
                }
                ?>
                <h3><a href="<?php echo esc_url(home_url('/category/' . $flash_slug . '/feed/')); ?>">闪念 RSS</a></h3>
                <p>类似朋友圈的碎碎念，生活灵感。</p>
                <div class="rss-link-box">
                    <code><?php echo esc_url(home_url('/category/' . $flash_slug . '/feed/')); ?></code>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.subscribe-page {
    max-width: 800px;
    margin: 40px auto;
    padding: 0 20px;
}
.subscribe-page h1 {
    margin-bottom: 30px;
    font-size: 2em;
    color: var(--heading-color);
}
.rss-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 40px;
}
.rss-item {
    background: rgba(255, 255, 255, 0.05);
    padding: 25px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.rss-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: var(--link-color);
}
.rss-item h3 {
    margin-top: 0;
    margin-bottom: 10px;
}
.rss-item h3 a {
    color: var(--link-color);
    text-decoration: none;
}
.rss-item p {
    color: var(--text-color);
    opacity: 0.8;
    margin-bottom: 15px;
    font-size: 0.95em;
    line-height: 1.5;
}
.rss-link-box {
    background: rgba(0, 0, 0, 0.2);
    padding: 8px 12px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.85em;
    word-break: break-all;
    color: var(--code-color);
    border: 1px solid rgba(255,255,255,0.05);
}
</style>

<?php get_footer(); ?>