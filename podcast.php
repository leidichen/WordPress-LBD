<?php
/**
 * Template Name: 播客模板
 *
 * 这是一套全新的播客模板，用于展示来自指定 RSS 的音频节目。支持单列卡片展示和分页。
 */

get_header(); 

// 获取主题设置中的 RSS 地址和分页数量
$rss_url = get_podcast_rss_url();
$posts_per_page = get_podcast_posts_per_page();

// 获取当前页码
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

// 使用 Transient 缓存 RSS 获取结果，避免频繁请求，缓存 1 小时 (3600 秒)
$transient_key = 'dear_podcast_rss_' . md5($rss_url);
$rss_items = get_transient($transient_key);

$is_error = false;
$error_msg = '';

if (false === $rss_items) {
    include_once(ABSPATH . WPINC . '/feed.php');
    $rss = fetch_feed($rss_url);
    
    if (!is_wp_error($rss)) {
        $maxitems = $rss->get_item_quantity(500); // 默认最多获取500个，避免过大
        $feed_items = $rss->get_items(0, $maxitems);
        
        $rss_items = array();
        
        // 尝试从 Feed 频道获取全局封面图
        $channel_image = '';
        if ($image = $rss->get_image_url()) {
            $channel_image = $image;
        }

        foreach ($feed_items as $item) {
            $title = $item->get_title();
            $link = $item->get_permalink();
            $date = $item->get_date('Y-m-d');
            $description = $item->get_description();
            
            // 提取 enclosure 音频链接
            $audio_url = '';
            if ($enclosures = $item->get_enclosures()) {
                foreach ($enclosures as $enclosure) {
                    if ($enclosure->get_type() && strpos($enclosure->get_type(), 'audio/') === 0) {
                        $audio_url = $enclosure->get_link();
                        break;
                    }
                }
            }

            // 提取图片（如果单集有封面）
            $item_image = $channel_image; // 默认使用频道图片
            // 尝试从小宇宙格式或 itunes:image 中提取封面
            if ($enclosures = $item->get_enclosures()) {
                foreach ($enclosures as $enclosure) {
                    if ($enclosure->get_type() && strpos($enclosure->get_type(), 'image/') === 0) {
                        $item_image = $enclosure->get_link();
                        break;
                    }
                }
            }

            $rss_items[] = array(
                'title' => $title,
                'link' => $link,
                'date' => $date,
                'description' => $description,
                'audio_url' => $audio_url,
                'image' => $item_image
            );
        }
        
        set_transient($transient_key, $rss_items, HOUR_IN_SECONDS);
    } else {
        $is_error = true;
        $error_msg = $rss->get_error_message();
    }
}

// 分页逻辑计算
$total_items = $rss_items ? count($rss_items) : 0;
$total_pages = ceil($total_items / $posts_per_page);
$offset = ($paged - 1) * $posts_per_page;
$current_page_items = $rss_items ? array_slice($rss_items, $offset, $posts_per_page) : array();
$podcast_intro_html = '';

if (have_posts()) {
    while (have_posts()) : the_post();
        $podcast_intro_html = trim(apply_filters('the_content', get_the_content()));
    endwhile;
    rewind_posts();
}
?>

<!-- 页面主要内容区 -->
<main id="primary" class="site-main">
    <div class="podcast-container">
        <div class="rss-header" style="text-align:right; margin-bottom:20px; padding-right: 10px;">
            <a href="<?php echo esc_url($rss_url); ?>" target="_blank" rel="noopener noreferrer" title="订阅播客 RSS" style="font-size:0.85em; opacity:0.6; text-decoration:none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><path d="M4 11a9 9 0 0 1 9 9"></path><path d="M4 4a16 16 0 0 1 16 16"></path><circle cx="5" cy="19" r="1"></circle></svg>
                RSS
            </a>
        </div>

        <?php if ($podcast_intro_html !== ''): ?>
        <header class="page-header">
            <div class="podcast-intro">
                <?php echo $podcast_intro_html; ?>
            </div>
        </header>
        <?php endif; ?>

        <!-- 播客列表区 -->
        <div class="podcast-list">
            <?php if ($is_error): ?>
                <div class="podcast-error">
                    <p>无法加载播客源。错误信息：<?php echo esc_html($error_msg); ?></p>
                </div>
            <?php elseif (empty($current_page_items)): ?>
                <div class="podcast-empty">
                    <p>暂无播客内容。</p>
                </div>
            <?php else: ?>
                <?php foreach ($current_page_items as $item): ?>
                    <article class="podcast-card">
                        <div class="podcast-content">
                            <div class="podcast-meta-row">
                                <span class="podcast-badge">Podcast</span>
                                <span class="podcast-date"><?php echo esc_html($item['date']); ?></span>
                                <a class="podcast-episode-link" href="<?php echo esc_url($item['link']); ?>" target="_blank" rel="noopener noreferrer">
                                    查看单集
                                </a>
                            </div>
                            <h2 class="podcast-title">
                                <?php echo esc_html($item['title']); ?>
                            </h2>
                            
                            <?php if ($item['description']): ?>
                                <div class="podcast-desc">
                                    <?php 
                                    // 截断过长的简介
                                    $desc = wp_strip_all_tags($item['description']);
                                    echo esc_html(mb_strimwidth($desc, 0, 180, '...')); 
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($item['audio_url']): ?>
                                <div class="podcast-player-wrap">
                                    <div class="podcast-player">
                                        <audio controls preload="none">
                                            <source src="<?php echo esc_url($item['audio_url']); ?>" type="audio/mpeg">
                                            您的浏览器不支持 <audio> 标签。
                                        </audio>
                                    </div>
                                </div>
                            <?php endif; ?>


                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 分页区 -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination podcast-pagination">
            <?php
            $current_url = get_permalink();
            // 自定义分页输出
            if ($paged > 1) {
                echo '<a href="' . esc_url(add_query_arg('paged', $paged - 1, $current_url)) . '">&laquo; 上一页</a>';
            }
            
            echo '<span class="page-numbers current">第 ' . $paged . ' / ' . $total_pages . ' 页</span>';
            
            if ($paged < $total_pages) {
                echo '<a href="' . esc_url(add_query_arg('paged', $paged + 1, $current_url)) . '">下一页 &raquo;</a>';
            }
            ?>
        </div>
        <?php endif; ?>

    </div>
</main>

<?php 
get_footer(); 
?>
