<?php
// 主题设置
define('LBD_VERSION', '1.2.2');

/**
 * 自动更新设置 (基于 GitHub)
 */
require 'inc/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/leidichen/WordPress-LBD/',
	__FILE__,
	'WordPress-LBD'
);

// 开启分支切换功能（可选，方便测试 beta 版本）
// $myUpdateChecker->getVcsApi()->enableReleaseAssets();

if (!function_exists('dear_setup')) :
	function dear_setup()
	{
      add_theme_support('automatic-feed-links');
      add_theme_support('title-tag');
	  add_theme_support('custom-background');
      register_nav_menu('primary-menu', 'Primary Menu');
      register_nav_menu('footer-menu', 'Footer Menu');
    }
    add_action('after_setup_theme', 'dear_setup');
endif;

// 主题样式
if ( ! function_exists( 'yayu_load_style' ) ) :
	function yayu_load_style() {
		// 主题元信息文件（style.css）+ 模块化样式文件
		wp_enqueue_style( 'dear-style', get_stylesheet_uri(), array(), LBD_VERSION );
		wp_enqueue_style(
			'dear-base-style',
			get_template_directory_uri() . '/assets/css/base.css',
			array('dear-style'),
			LBD_VERSION
		);
		if (is_page_template('weekly.php') || is_category('weekly')) {
			wp_enqueue_style(
				'dear-weekly-style',
				get_template_directory_uri() . '/assets/css/weekly.css',
				array('dear-base-style'),
				LBD_VERSION
			);
		}
		if (is_page_template('blog.php')) {
			wp_enqueue_style(
				'dear-blog-style',
				get_template_directory_uri() . '/assets/css/blog.css',
				array('dear-base-style'),
				LBD_VERSION
			);
		}
		if (is_page_template('idea.php')) {
			wp_enqueue_style(
				'dear-idea-style',
				get_template_directory_uri() . '/assets/css/idea.css',
				array('dear-base-style'),
				LBD_VERSION
			);
		}
		// 注册并内联一个小脚本用于主题亮/暗切换（基于 body.light-theme）
		wp_register_script('dear-theme-toggle', false, array(), null, true);
		wp_enqueue_script('dear-theme-toggle');
		$script = "(function(){var t=document.getElementById('theme-toggle');if(!t)return;var b=document.body,s=localStorage.getItem('dear-theme')||'dark';if(s==='light')b.classList.add('light-theme');var sunIcon=t.querySelector('.sun'),moonIcon=t.querySelector('.moon');function sync(){var isLight=b.classList.contains('light-theme');t.setAttribute('aria-pressed',isLight?'true':'false');if(sunIcon&&moonIcon){sunIcon.style.display=isLight?'none':'inline';moonIcon.style.display=isLight?'inline':'none';}}t.addEventListener('click',function(){var isLight=b.classList.contains('light-theme');if(isLight){b.classList.remove('light-theme');localStorage.setItem('dear-theme','dark');}else{b.classList.add('light-theme');localStorage.setItem('dear-theme','light');}sync();});sync();})();";
		wp_add_inline_script('dear-theme-toggle', $script);

		// 闪念页面交互脚本
		if (is_page_template('idea.php')) {
			wp_enqueue_script(
				'dear-idea-page',
				get_template_directory_uri() . '/assets/js/idea-page.js',
				array(),
				'20260222',
				true
			);
		}
	}
	add_action( 'wp_enqueue_scripts', 'yayu_load_style' );
endif;

	// 固定默认配色方案 - 使用用户手调的Bear博客风格
	function dear_weekly_dynamic_styles() {
	    $columns = get_weekly_grid_columns();
	    $card_spacing = get_weekly_card_spacing();
	    
	    $css = '<style id="dear-weekly-styles">';
	    
	    // 网格列数样式 - 仅在非移动端生效
	    $css .= '@media (min-width: 641px) {';
	    if ($columns == '2') {
	        $css .= '.weekly-grid.columns-2 { grid-template-columns: repeat(2, 1fr) !important; }';
	    } else {
	        $css .= '.weekly-grid.columns-3 { grid-template-columns: repeat(3, 1fr) !important; }';
	    }
	    $css .= '}';
	    
	    // 卡片间距样式 - 仅在非移动端生效，移动端统一为 10px
	    $css .= '@media (min-width: 641px) {';
	    $css .= '.weekly-grid { gap: ' . $card_spacing . 'px !important; }';
	    $css .= '}';
	    
	    // 三栏布局时优化卡片尺寸比例 - 仅在非移动端生效
	    $css .= '@media (min-width: 641px) {';
	    if ($columns == '3') {
	        $css .= '.weekly-card-image { height: 120px !important; }'; // 更扁平的高度
	        $css .= '.weekly-card-content { padding: 6px !important; }'; // 更小的内边距
	        $css .= '.weekly-card-title { font-size: 0.85em !important; }'; // 更小的标题
	    } else {
	        $css .= '.weekly-card-image { height: 140px !important; }'; // 两栏时稍高一些
	        $css .= '.weekly-card-content { padding: 8px !important; }';
	        $css .= '.weekly-card-title { font-size: 0.9em !important; }';
	    }
	    $css .= '}';
	    
	    $css .= '</style>';
	    
	    echo $css;
	}
	add_action('wp_head', 'dear_weekly_dynamic_styles', 25);
	
function get_dear_excluded_category_ids($include_weekly = true, $include_flash = true) {
    $excluded_categories = array();

    if ($include_flash && function_exists('get_flash_category_term')) {
        $flash_category = get_flash_category_term();
        if ($flash_category) {
            $excluded_categories[] = $flash_category->term_id;
        }
    }

    if ($include_weekly) {
        $weekly_category = get_category_by_slug('weekly');
        if ($weekly_category) {
            $excluded_categories[] = $weekly_category->term_id;
        }
    }

    return array_values(array_unique($excluded_categories));
}

// 文章数设置
function custom_posts_per_page($query){
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    if($query->is_home()){
        $query->set('posts_per_page',8); //首页近期更新文章数
        $excluded_categories = get_dear_excluded_category_ids(true, true);
        if (!empty($excluded_categories)) {
            $existing = $query->get('category__not_in');
            if (!is_array($existing)) {
                $existing = empty($existing) ? array() : (array) $existing;
            }
            $query->set('category__not_in', array_values(array_unique(array_merge($existing, $excluded_categories))));
        }
    }
    if($query->is_search()){
        $query->set('posts_per_page', 20); // 搜索页每页显示20篇

        $excluded_categories = get_dear_excluded_category_ids(true, true);
        if (!empty($excluded_categories)) {
            $existing = $query->get('category__not_in');
            if (!is_array($existing)) {
                $existing = empty($existing) ? array() : (array) $existing;
            }
            $query->set('category__not_in', array_values(array_unique(array_merge($existing, $excluded_categories))));
        }
    }

    // RSS Feed 过滤：主 Feed 排除闪念和周刊
    if ($query->is_feed() && !$query->is_category() && !$query->is_tag() && !$query->is_author()) {
        $excluded_categories = get_dear_excluded_category_ids(true, true);
        if (!empty($excluded_categories)) {
            $existing = $query->get('category__not_in');
            if (!is_array($existing)) {
                $existing = empty($existing) ? array() : (array) $existing;
            }
            $query->set('category__not_in', array_values(array_unique(array_merge($existing, $excluded_categories))));
        }
    }

    if($query->is_archive()){
        if (function_exists('get_flash_category_term')) {
            $flash_category = get_flash_category_term();
            if ($flash_category && !is_category($flash_category->term_id) && !is_flash_idea_page()) {
                $excluded_categories = get_dear_excluded_category_ids(false, true);
                if (!empty($excluded_categories)) {
                    $existing = $query->get('category__not_in');
                    if (!is_array($existing)) {
                        $existing = empty($existing) ? array() : (array) $existing;
                    }
                    $query->set('category__not_in', array_values(array_unique(array_merge($existing, $excluded_categories))));
                }
            }
        }
    }
}
add_action('pre_get_posts','custom_posts_per_page');

// 程序优化
remove_action('wp_head', 'wp_generator'); // 移除WordPress版本
// remove_filter('comment_text', 'make_clickable', 9); // 移除wordpress留言中自动链接功能 - 已注释，因不使用评论功能
remove_action('wp_head', 'rsd_link'); // 移除离线编辑器开放接口
remove_action('wp_head', 'index_rel_link'); // 去除本页唯一链接信息
remove_action('wp_head', 'wlwmanifest_link'); // 移除离线编辑器开放接口
remove_filter('the_content', 'wptexturize'); // 禁止代码标点符合转义

// 禁用REST API、移除wp-json链接
// add_filter('rest_enabled', '__return_false');
// add_filter('rest_jsonp_enabled', '__return_false');
// remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
// remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );

// 禁用l10n.js
wp_deregister_script('l10n');

// 禁止头部加载s.w.org
function remove_dns_prefetch($hints, $relation_type)
{
	if ('dns-prefetch' === $relation_type) {
		return array_diff(wp_dependencies_unique_hosts(), $hints);
	}
	return $hints;
}
add_filter('wp_resource_hints', 'remove_dns_prefetch', 10, 2);

// 移除原生 gallery style
add_filter('use_default_gallery_style', '__return_false');

// 彻底移除管理员工具条
add_filter('show_admin_bar','__return_false');

// 禁用Open Sans
function remove_open_sans()
{
	wp_deregister_style('open-sans');
	wp_register_style('open-sans', false);
	wp_enqueue_style('open-sans', '');
}
add_action('init', 'remove_open_sans');

// 禁用 auto-embeds
remove_filter( 'the_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );

// 阻止站内文章 Pingback
add_action('pre_ping', 'no_self_ping');
function no_self_ping(&$links)
{
	$home = home_url();
	foreach ($links as $l => $link)
		if (0 === strpos($link, $home))
			unset($links[$l]);
}

// WordPress 关闭 XML-RPC 的 pingback 端口
// add_filter( 'xmlrpc_methods', 'remove_xmlrpc_pingback_ping' );
// function remove_xmlrpc_pingback_ping( $methods ) {
// 	unset( $methods['pingback.ping'] );
// 	return $methods;
// }

// 禁用XML-RPC
// add_filter('xmlrpc_enabled', '__return_false');

// 禁用 emoji's
function disable_emojis()
{
	remove_action('wp_head', 'print_emoji_detection_script', 7);
	remove_action('admin_print_scripts', 'print_emoji_detection_script');
	remove_action('wp_print_styles', 'print_emoji_styles');
	remove_action('admin_print_styles', 'print_emoji_styles');
	remove_filter('the_content_feed', 'wp_staticize_emoji');
	remove_filter('comment_text_rss', 'wp_staticize_emoji');
	remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
	add_filter('tiny_mce_plugins', 'disable_emojis_tinymce');
}
add_action('init', 'disable_emojis');

// 用于删除tinymce插件的emoji
function disable_emojis_tinymce($plugins)
{
	if (is_array($plugins)) {
		return array_diff($plugins, array('wpemoji'));
	} else {
		return array();
	}
}

// 禁用 wp-embed.min.js
function my_deregister_scripts(){
    wp_dequeue_script( 'wp-embed' );
}
add_action( 'wp_footer', 'my_deregister_scripts' );

// 禁用古滕堡编辑器
add_filter('use_block_editor_for_post', '__return_false', 10);  
add_filter('use_widgets_block_editor', '__return_false', 10);
remove_action( 'wp_enqueue_scripts', 'wp_common_block_scripts_and_styles' );

// 移除头部 Gutenberg global-styles-inline-css
add_action( 'wp_print_styles', function()
{
  wp_deregister_style('global-styles');
} );

//移除经典主题样式 classic-theme-styles-inline-css
add_action( 'wp_enqueue_scripts', function() {
	wp_dequeue_style( 'classic-theme-styles' );
}, 20 );

//回复评论框跟随。如需启用，去掉 add_action 前面的注释符 //
if (!function_exists('yayu_enqueue_scripts')) :
	function yayu_enqueue_scripts()
	{
		// 注释掉评论回复功能代码，因不使用评论功能
		/*
		if ((!is_admin()) && is_singular()) {
			wp_enqueue_script('jquery', '', 'jquery', '', true);
			if (comments_open() && get_option('thread_comments')) {
				wp_enqueue_script('comment-reply');
			};
		};
		*/
	}
	//add_action('wp_enqueue_scripts', 'yayu_enqueue_scripts');
endif;

// 动态流布局（原闪念）卡片展示设置
function dear_flash_customizer($wp_customize) {
    // 动态流布局设置面板
    $wp_customize->add_section('dear_flash_settings', array(
        'title' => '卡片设置',
        'priority' => 35,
        'description' => '设置流式卡片布局（如闪念、朋友圈、状态更新）的样式和参数'
    ));
    
    // 分类标识（slug）设置
    $wp_customize->add_setting('flash_category_name', array(
        'default' => '闪念',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('flash_category_name', array(
        'label' => '关联分类标识（slug）',
        'section' => 'dear_flash_settings',
        'type' => 'text',
        'description' => '输入要使用流式布局的分类 slug。例如 "flash"、"status" 或 "daily"。'
    ));
    
    // 显示字数设置
    $wp_customize->add_setting('flash_content_length', array(
        'default' => 280,
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('flash_content_length', array(
        'label' => '内容显示字数',
        'section' => 'dear_flash_settings',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 100,
            'max' => 500,
            'step' => 50,
        ),
        'description' => '设置卡片中显示的内容字数（中文字符）'
    ));
    
    // 卡片圆角大小设置
    $wp_customize->add_setting('flash_card_radius', array(
        'default' => 12,
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('flash_card_radius', array(
        'label' => '卡片圆角大小',
        'section' => 'dear_flash_settings',
        'type' => 'range',
        'input_attrs' => array(
            'min' => 0,
            'max' => 20,
            'step' => 2,
        ),
        'description' => '设置卡片的圆角大小（像素）'
    ));
}
add_action('customize_register', 'dear_flash_customizer');

// 获取闪念分类标识（slug）
function get_flash_category_name() {
    return get_theme_mod('flash_category_name', '闪念');
}

// 获取闪念分类对象：优先按 slug，其次按名称回退
function get_flash_category_term() {
    $flash_key = get_flash_category_name();
    $flash_category = get_category_by_slug($flash_key);

    if ($flash_category) {
        return $flash_category;
    }

    $categories = get_categories(array('hide_empty' => false));
    foreach ($categories as $cat) {
        if ($cat->name === $flash_key) {
            return $cat;
        }
    }

    return false;
}

// 获取闪念内容长度
function get_flash_content_length() {
    return get_theme_mod('flash_content_length', 280);
}

// 获取闪念卡片圆角大小
function get_flash_card_radius() {
    return get_theme_mod('flash_card_radius', 12);
}

// 检查是否为闪念独立页面
function is_flash_idea_page() {
    return is_page_template('idea.php');
}

// 首页设置面板
function dear_home_customizer($wp_customize) {
    // 首页设置面板
    $wp_customize->add_section('dear_home_settings', array(
        'title' => '首页设置',
        'priority' => 30,
        'description' => '设置首页显示内容'
    ));
    
    // 首页介绍内容来源页面设置
    $wp_customize->add_setting('home_intro_page_id', array(
        'default' => '',
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('home_intro_page_id', array(
        'label' => '首页介绍内容来源页面',
        'section' => 'dear_home_settings',
        'type' => 'dropdown-pages',
        'description' => '选择一个页面，其内容将显示在首页文章列表上方（替代默认的硬编码 ID 2）'
    ));
}
add_action('customize_register', 'dear_home_customizer');

// 获取首页介绍页面ID
function get_home_intro_page_id() {
    return get_theme_mod('home_intro_page_id', '');
}

// 网格布局（原周刊）设置面板
function dear_weekly_customizer($wp_customize) {
    // 网格布局设置面板
    $wp_customize->add_section('dear_weekly_settings', array(
        'title' => '网格设置',
        'priority' => 36,
        'description' => '设置网格布局（如周刊、作品集、相册）的显示样式和参数'
    ));
    
    // 网格列数设置
    $wp_customize->add_setting('weekly_grid_columns', array(
        'default' => '3',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    
    $wp_customize->add_control('weekly_grid_columns', array(
        'label' => '网格列数',
        'section' => 'dear_weekly_settings',
        'type' => 'select',
        'choices' => array(
            '2' => '两栏布局',
            '3' => '三栏布局',
        ),
        'description' => '选择网格页面的列数布局'
    ));
    
    // 卡片间距设置
    $wp_customize->add_setting('weekly_card_spacing', array(
        'default' => '20',
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('weekly_card_spacing', array(
        'label' => '卡片间距',
        'section' => 'dear_weekly_settings',
        'type' => 'range',
        'input_attrs' => array(
            'min' => 10,
            'max' => 50,
            'step' => 5,
        ),
        'description' => '调整卡片之间的间距（10-50px）'
    ));
}
add_action('customize_register', 'dear_weekly_customizer');

// 获取周刊网格列数设置
function get_weekly_grid_columns() {
    return get_theme_mod('weekly_grid_columns', '3');
}

// 获取周刊卡片间距设置
function get_weekly_card_spacing() {
    return get_theme_mod('weekly_card_spacing', '20');
}

// 闪念文章字数统计
function get_flash_word_count() {
    $content = get_the_content();
    $content = strip_shortcodes($content);
    $content = apply_filters('the_content', $content);
    $content = str_replace(']]>', ']]>', $content);
    
    // 获取纯文本内容
    $content_plain = wp_strip_all_tags($content);
    
    // 计算字符数（包含标点符号）
    $char_count = mb_strlen($content_plain, 'UTF-8');
    
    // 计算词数（中文字符数，不包含标点符号）
    $chinese_chars = preg_match_all('/[\x{4e00}-\x{9fff}]/u', $content_plain, $matches);
    $word_count = $chinese_chars;
    
    return $word_count . '个词 ' . $char_count . '个字符';
}

// 智能时间显示函数
function get_smart_time_display() {
    $post_time = get_the_time('U');
    $current_time = current_time('timestamp');
    $time_diff = $current_time - $post_time;
    $hours_diff = floor($time_diff / 3600);
    $days_diff = floor($time_diff / 86400);
    
    if ($hours_diff < 24) {
        return $hours_diff . '小时前';
    } elseif ($days_diff < 30) {
        return $days_diff . '天前';
    } else {
        return get_the_time('m-d');
    }
}

// Twitter式内容处理函数 - 支持多图片轮播显示
function get_twitter_style_content($length = 300) {
    $content = get_the_content();
    $content = strip_shortcodes($content);
    $content = apply_filters('the_content', $content);
    $content = str_replace(']]>', ']]>', $content);
    
    // 保留安全的HTML标签
    $allowed_tags = array(
        'img' => array(
            'src' => true,
            'alt' => true,
            'title' => true,
            'width' => true,
            'height' => true,
            'class' => true,
            'style' => true
        ),
        'p' => array('class' => true, 'style' => true),
        'br' => array(),
        'strong' => array(),
        'em' => array(),
        'a' => array('href' => true, 'title' => true, 'target' => true)
    );
    
    $content_filtered = wp_kses($content, $allowed_tags);
    
    // 提取所有图片标签和信息
    preg_match_all('/<img[^>]+>/i', $content_filtered, $images);
    $image_tags = isset($images[0]) ? $images[0] : array();
    
    // 解析图片信息
    $image_data = array();
    foreach ($image_tags as $img_tag) {
        $img_src = ''; $img_alt = '';
        if (preg_match('/src=["\']([^"\']*)["\']/', $img_tag, $matches)) {
            $img_src = $matches[1];
        }
        if (preg_match('/alt=["\']([^"\']*)["\']/', $img_tag, $matches)) {
            $img_alt = $matches[1];
        }
        // 确保图片标签包含必要的属性
        $clean_img_tag = $img_tag;
        if (!strpos($img_tag, 'style=')) {
            $clean_img_tag = str_replace('>', ' style="max-width: 100%; height: auto;">', $clean_img_tag);
        }
        $image_data[] = array('src' => $img_src, 'alt' => $img_alt, 'tag' => $clean_img_tag);
    }
    
    // 移除图片标签后获取纯文本内容
    $content_without_images = preg_replace('/<img[^>]+>/i', '', $content_filtered);
    
    // 在去除标签前，先将段落和换行标签转换为换行符，确保段落结构不丢失
    // </p> 转换为双换行以保持段落分隔，<br> 转换为单换行
    $content_with_breaks = str_replace(array('</p>', '<br />', '<br>'), array("\n\n", "\n", "\n"), $content_without_images);
    $text_content = wp_strip_all_tags($content_with_breaks);
    
    // 规范化换行符：将连续3个及以上换行符合并为2个，避免空段落
    $text_content = preg_replace("/\n{3,}/", "\n\n", $text_content);
    
    // 根据图片数量生成不同展示
    if (count($image_data) > 0) {
        $images_display = generate_image_gallery_display($image_data);
        
        if (mb_strlen($text_content, 'UTF-8') > $length) {
            // 文本超长，需要折叠
            $truncated_text = mb_substr($text_content, 0, $length, 'UTF-8');
            return array(
                'preview' => $images_display['preview'] . wpautop($truncated_text . '...'),
                'full' => $images_display['full'] . wpautop($text_content),
                'images_html' => $images_display['full'], // 新增：仅图片HTML
                'text_html' => wpautop($text_content),     // 新增：仅文本HTML
                'gallery_data' => $images_display['gallery_data'],
                'image_count' => count($image_data),
                'is_truncated' => true
            );
        } else {
            // 文本未超长
            return array(
                'preview' => $images_display['preview'] . wpautop($text_content),
                'full' => $images_display['full'] . wpautop($text_content),
                'images_html' => $images_display['full'], // 新增
                'text_html' => wpautop($text_content),     // 新增
                'gallery_data' => $images_display['gallery_data'],
                'image_count' => count($image_data),
                'is_truncated' => false
            );
        }
    } else {
        // 无图片内容
        if (mb_strlen($text_content, 'UTF-8') > $length) {
            $truncated_text = mb_substr($text_content, 0, $length, 'UTF-8');
            return array(
                'preview' => wpautop($truncated_text . '...'),
                'full' => wpautop($text_content),
                'images_html' => '', // 新增
                'text_html' => wpautop($text_content), // 新增
                'image_count' => 0,
                'is_truncated' => true
            );
        } else {
            return array(
                'preview' => wpautop($text_content),
                'full' => wpautop($text_content),
                'images_html' => '', // 新增
                'text_html' => wpautop($text_content), // 新增
                'image_count' => 0,
                'is_truncated' => false
            );
        }
    }
}

// 生成图片画廊展示
function generate_image_gallery_display($image_data) {
    $image_count = count($image_data);
    
    if ($image_count == 1) {
        // 单张图片
        return array(
            'preview' => $image_data[0]['tag'],
            'full' => $image_data[0]['tag'],
            'gallery_data' => json_encode(array($image_data[0]))
        );
    } else {
        // 两张或更多图片 - 始终显示前两张并排
        $preview_html = '<div class="image-gallery two-images">';
        $preview_html .= '<div class="image-item">' . $image_data[0]['tag'] . '</div>';
        $preview_html .= '<div class="image-item">' . $image_data[1]['tag'] . '</div>';
        $preview_html .= '</div>';
        
        // 展开后也保持并排显示前两张
        $full_html = '<div class="image-gallery two-images">';
        $full_html .= '<div class="image-item">' . $image_data[0]['tag'] . '</div>';
        $full_html .= '<div class="image-item">' . $image_data[1]['tag'] . '</div>';
        $full_html .= '</div>';
        
        return array(
            'preview' => $preview_html,
            'full' => $full_html,
            'gallery_data' => json_encode($image_data)
        );
    }
}

// 周刊功能相关函数

// 获取周刊封面图片
function get_weekly_cover_image() {
    $content = get_the_content();
    
    // 使用正则表达式提取第一张图片
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
        return $matches[1];
    }
    
    // 如果内容中没有图片，尝试获取特色图片
    if (has_post_thumbnail()) {
        $thumbnail_id = get_post_thumbnail_id();
        $thumbnail_url = wp_get_attachment_image_src($thumbnail_id, 'medium');
        if ($thumbnail_url) {
            return $thumbnail_url[0];
        }
    }
    
    return false;
}

?>
