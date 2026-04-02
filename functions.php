<?php
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// 主题设置
define('LBD_VERSION', '1.3.1');

/**
 * 自动更新设置 (基于 GitHub)
 */
require 'inc/plugin-update-checker/plugin-update-checker.php';

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
	  add_theme_support('custom-background', array(
        'default-color' => '022430',
        'wp-head-callback' => 'dear_custom_background_cb', // 使用自定义回调，防止 WP 输出不必要的 CSS
      ));
      register_nav_menu('primary-menu', 'Primary Menu');
      register_nav_menu('footer-menu', 'Footer Menu');
    }
    add_action('after_setup_theme', 'dear_setup');
endif;

// 自定义背景回调：仅输出 CSS 变量，不输出 body.custom-background 样式
if ( ! function_exists( 'dear_custom_background_cb' ) ) :
    function dear_custom_background_cb() {
        $background_color = get_background_color();
        // 只有当颜色存在且不为空时才输出变量
        if ( $background_color ) {
            echo "<style>:root { --background-color: #" . esc_attr($background_color) . "; }</style>\n";
        }
    }
endif;

if ( ! function_exists( 'dear_get_theme_preference' ) ) :
	function dear_get_theme_preference() {
		if ( isset( $_COOKIE['dear-theme'] ) ) {
			$theme = sanitize_key( wp_unslash( $_COOKIE['dear-theme'] ) );
			if ( in_array( $theme, array( 'light', 'dark' ), true ) ) {
				return $theme;
			}
		}
		// 移除强制 'dark'，允许默认回退或由 CSS 决定
		// return 'dark';
        return 'dark'; // 暂时保持 dark 作为默认，但确保 CSS 能覆盖
	}
endif;

if ( ! function_exists( 'dear_filter_language_attributes' ) ) :
	function dear_filter_language_attributes( $output ) {
		if ( dear_get_theme_preference() !== 'light' ) {
			return $output;
		}

		if ( strpos( $output, 'class=' ) !== false ) {
			return preg_replace( '/class=("|\')(.*?)\\1/', 'class=$1$2 light-theme$1', $output, 1 );
		}

		return trim( $output ) . ' class="light-theme"';
	}
	add_filter( 'language_attributes', 'dear_filter_language_attributes' );
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
				'qweather-icons',
				'https://cdn.jsdelivr.net/npm/qweather-icons@1.3.2/font/qweather-icons.css',
				array(),
				'1.3.2'
			);
			wp_enqueue_style(
				'dear-idea-style',
				get_template_directory_uri() . '/assets/css/idea.css',
				array('dear-base-style'),
				LBD_VERSION
			);
		}
		// 注册并内联一个小脚本用于主题亮/暗切换
		if (is_page_template('podcast.php')) {
			wp_enqueue_style(
				'dear-podcast-style',
				get_template_directory_uri() . '/assets/css/podcast.css',
				array('dear-base-style'),
				LBD_VERSION
			);
		}
		wp_register_script('dear-theme-toggle', false, array(), null, true);
		wp_enqueue_script('dear-theme-toggle');
		$script = "(function(){var t=document.getElementById('theme-toggle');if(!t)return;var b=document.documentElement;var sunIcon=t.querySelector('.sun'),moonIcon=t.querySelector('.moon');var m=document.getElementById('theme-color-meta');function persist(theme){try{localStorage.setItem('dear-theme',theme);document.cookie='dear-theme='+theme+'; path=/; max-age=31536000; SameSite=Lax';}catch(e){}}function sync(){var isLight=b.classList.contains('light-theme');t.setAttribute('aria-pressed',isLight?'true':'false');if(sunIcon&&moonIcon){sunIcon.style.display=isLight?'none':'inline';moonIcon.style.display=isLight?'inline':'none';}if(m)m.setAttribute('content',isLight?'#ffffff':'#022430');}t.addEventListener('click',function(){var isLight=b.classList.contains('light-theme');if(isLight){b.classList.remove('light-theme');persist('dark');}else{b.classList.add('light-theme');persist('light');}sync();});sync();})();";
		wp_add_inline_script('dear-theme-toggle', $script);

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

if ( ! function_exists( 'dear_output_theme_init_script' ) ) :
	function dear_output_theme_init_script() {
        // 获取用户自定义背景色（如果用户未设置，WP 会返回默认值或空）
        $custom_bg = get_background_color();
        
        // 确保默认颜色和用户设置的颜色都能正确传递给 JS
        // 如果 $custom_bg 为空，使用 CSS 变量中的默认值（这里不硬编码，交给 CSS 处理）
        // 但为了 JS 逻辑的完整性，我们可以传递一个标识
        
        $bg_color_js = $custom_bg ? '#' . $custom_bg : '';

		echo "<script>(function(){try{var d=document.documentElement;var s=localStorage.getItem('dear-theme')||'dark';document.cookie='dear-theme='+s+'; path=/; max-age=31536000; SameSite=Lax';if(s==='light'){d.classList.add('light-theme');}else{d.classList.remove('light-theme');}var m=document.getElementById('theme-color-meta');if(m){m.setAttribute('content',s==='light'?'#ffffff':'#022430');}}catch(e){}})();</script>\n";
	}
	add_action( 'wp_head', 'dear_output_theme_init_script', 0 );
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
        if (is_category('weekly')) {
            $query->set('posts_per_page', 24); // 周刊分类每页24篇
        }
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
    
    // 文本折叠开关
    $wp_customize->add_setting('flash_clamp_enabled', array(
        'default' => 1,
        'sanitize_callback' => 'absint',
    ));
    $wp_customize->add_control('flash_clamp_enabled', array(
        'label' => '启用文本折叠',
        'section' => 'dear_flash_settings',
        'type' => 'checkbox',
        'description' => '关闭后，卡片文本默认全展开，不显示“显示更多”'
    ));
    // 折叠行数
    $wp_customize->add_setting('flash_clamp_lines', array(
        'default' => 20,
        'sanitize_callback' => 'absint',
    ));
    $wp_customize->add_control('flash_clamp_lines', array(
        'label' => '折叠行数',
        'section' => 'dear_flash_settings',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 1,
            'max' => 50,
            'step' => 1,
        ),
        'description' => '当启用折叠时，超过该行数则折叠（例如 20）'
    ));

    $wp_customize->add_setting('flash_posts_per_page', array(
        'default' => 15,
        'sanitize_callback' => 'absint',
    ));
    $wp_customize->add_control('flash_posts_per_page', array(
        'label' => '每页卡片数',
        'section' => 'dear_flash_settings',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 5,
            'max' => 100,
            'step' => 5,
        ),
        'description' => '设置分页每页显示的卡片数量'
    ));

    // 修改默认菜单名称
    $section_colors = $wp_customize->get_section('colors');
    if ($section_colors) {
        $section_colors->title = '颜色设置';
    }

    $panel_nav_menus = $wp_customize->get_panel('nav_menus');
    if ($panel_nav_menus) {
        $panel_nav_menus->title = '菜单设置';
    }
}
add_action('customize_register', 'dear_flash_customizer', 20); // 提高优先级

// 方案2：通过翻译过滤强制修改“菜单”字样（备用且强力）
function dear_rename_customizer_menu_text($translated_text, $text, $domain) {
    if (is_customize_preview() && $text === 'Menus' && ($domain === 'default' || empty($domain))) {
        return '菜单设置';
    }
    if (is_customize_preview() && $text === '菜单' && ($domain === 'default' || empty($domain))) {
        return '菜单设置';
    }
    return $translated_text;
}
add_filter('gettext', 'dear_rename_customizer_menu_text', 20, 3);

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

function get_flash_clamp_enabled() { return (bool) get_theme_mod('flash_clamp_enabled', 1); }
function get_flash_clamp_lines() { return (int) get_theme_mod('flash_clamp_lines', 20); }
function get_flash_posts_per_page() { return (int) get_theme_mod('flash_posts_per_page', 15); }

// 检查是否为闪念独立页面
function is_flash_idea_page() {
    return is_page_template('idea.php');
}

// 首页设置面板
function dear_home_customizer($wp_customize) {
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

    // 每页卡片数（分页）
    $wp_customize->add_setting('weekly_posts_per_page', array(
        'default' => 24,
        'sanitize_callback' => 'absint',
    ));
    $wp_customize->add_control('weekly_posts_per_page', array(
        'label' => '每页卡片数',
        'section' => 'dear_weekly_settings',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 6,
            'max' => 96,
            'step' => 6,
        ),
        'description' => '设置网格页每页显示的卡片数量'
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

// 播客（Podcast）设置面板
function dear_podcast_customizer($wp_customize) {
    // 播客设置面板
    $wp_customize->add_section('dear_podcast_settings', array(
        'title' => '播客设置',
        'priority' => 37,
        'description' => '设置播客模板的显示样式和参数'
    ));

    // 播客 RSS 源地址
    $wp_customize->add_setting('podcast_rss_url', array(
        'default' => 'https://feed.xyzfm.space/bf7xdm8hrfg6',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control('podcast_rss_url', array(
        'label' => '播客 RSS 源地址',
        'section' => 'dear_podcast_settings',
        'type' => 'url',
        'description' => '输入播客 RSS 源的 URL（例如小宇宙的播客源）。程序会自动解析。'
    ));

    // 播客每页显示卡片数
    $wp_customize->add_setting('podcast_posts_per_page', array(
        'default' => 20,
        'sanitize_callback' => 'absint',
    ));
    $wp_customize->add_control('podcast_posts_per_page', array(
        'label' => '每页播客数',
        'section' => 'dear_podcast_settings',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 5,
            'max' => 50,
            'step' => 1,
        ),
        'description' => '设置播客页面每页显示的播客数量，默认 20 篇'
    ));
}
add_action('customize_register', 'dear_podcast_customizer');

function get_podcast_rss_url() {
    return esc_url_raw(get_theme_mod('podcast_rss_url', 'https://feed.xyzfm.space/bf7xdm8hrfg6'));
}

function get_podcast_posts_per_page() {
    return (int) get_theme_mod('podcast_posts_per_page', 20);
}

// 获取网格每页卡片数
function get_weekly_posts_per_page() {
    return get_theme_mod('weekly_posts_per_page', 24);
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
    
    // 将任务复选框替换为安全的标记元素
    $content = preg_replace('/<input[^>]*type=["\']checkbox["\'][^>]*checked[^>]*>/i', '<span class="todo-checkbox checked"></span>', $content);
    $content = preg_replace('/<input[^>]*type=["\']checkbox["\'][^>]*>/i', '<span class="todo-checkbox"></span>', $content);
    
    // 保留安全的HTML标签
    $allowed_tags = array(
        'img' => array(
            'src' => true,
            'alt' => true,
            'title' => true,
            'width' => true,
            'height' => true,
            'class' => true,
            'style' => true,
            'srcset' => true,
            'sizes' => true,
            'loading' => true,
            'decoding' => true,
            'fetchpriority' => true,
            'data-src' => true,
            'data-original' => true,
            'data-lazy-src' => true
        ),
        'p' => array('class' => true, 'style' => true),
        'br' => array(),
        'strong' => array(),
        'em' => array(),
        'a' => array('href' => true, 'title' => true, 'target' => true),
        'ul' => array('class' => true, 'style' => true),
        'ol' => array('class' => true, 'style' => true),
        'li' => array('class' => true, 'style' => true),
        'blockquote' => array('class' => true, 'style' => true),
        'h1' => array('class' => true, 'style' => true),
        'h2' => array('class' => true, 'style' => true),
        'h3' => array('class' => true, 'style' => true),
        'h4' => array('class' => true, 'style' => true),
        'h5' => array('class' => true, 'style' => true),
        'h6' => array('class' => true, 'style' => true),
        'code' => array('class' => true, 'style' => true),
        'pre' => array('class' => true, 'style' => true),
        'span' => array('class' => true, 'style' => true),
        'video' => array('src' => true, 'controls' => true, 'width' => true, 'height' => true, 'class' => true, 'style' => true, 'muted' => true, 'autoplay' => true, 'loop' => true, 'preload' => true, 'poster' => true, 'playsinline' => true),
        'audio' => array('src' => true, 'controls' => true, 'class' => true, 'style' => true, 'muted' => true, 'autoplay' => true, 'loop' => true, 'preload' => true),
        'source' => array('src' => true, 'type' => true),
        'iframe' => array('src' => true, 'width' => true, 'height' => true, 'frameborder' => true, 'allowfullscreen' => true, 'scrolling' => true, 'class' => true, 'style' => true, 'allow' => true)
    );
    
    $content_filtered = wp_kses($content, $allowed_tags);
    
    // 提取所有图片标签和信息
    preg_match_all('/<img[^>]+>/i', $content, $images);
    $image_tags = isset($images[0]) ? $images[0] : array();
    if (empty($image_tags) && has_post_thumbnail()) {
        $thumbnail_html = get_the_post_thumbnail(get_the_ID(), 'large');
        if (!empty($thumbnail_html)) {
            $image_tags[] = $thumbnail_html;
        }
    }
    
    // 解析图片信息
    $image_data = array();
    foreach ($image_tags as $img_tag) {
        $img_src = ''; $img_alt = '';
        if (preg_match('/src=["\']([^"\']*)["\']/', $img_tag, $matches)) {
            $img_src = $matches[1];
        }
        if (!$img_src && preg_match('/data-src=["\']([^"\']*)["\']/', $img_tag, $matches)) {
            $img_src = $matches[1];
            $img_tag = preg_replace('/<img/i', '<img src="' . esc_url($img_src) . '"', $img_tag, 1);
        } elseif (!$img_src && preg_match('/data-original=["\']([^"\']*)["\']/', $img_tag, $matches)) {
            $img_src = $matches[1];
            $img_tag = preg_replace('/<img/i', '<img src="' . esc_url($img_src) . '"', $img_tag, 1);
        } elseif (!$img_src && preg_match('/data-lazy-src=["\']([^"\']*)["\']/', $img_tag, $matches)) {
            $img_src = $matches[1];
            $img_tag = preg_replace('/<img/i', '<img src="' . esc_url($img_src) . '"', $img_tag, 1);
        }
        if (!$img_src && preg_match('/srcset=["\']([^"\']*)["\']/', $img_tag, $matches)) {
            $srcset = trim($matches[1]);
            if ($srcset !== '') {
                $first = trim(explode(',', $srcset)[0]);
                $parts = preg_split('/\s+/', $first);
                $img_src = $parts[0] ?? '';
            }
        }
        if (!$img_src && preg_match('/data-srcset=["\']([^"\']*)["\']/', $img_tag, $matches)) {
            $srcset = trim($matches[1]);
            if ($srcset !== '') {
                $first = trim(explode(',', $srcset)[0]);
                $parts = preg_split('/\s+/', $first);
                $img_src = $parts[0] ?? '';
            }
        }
        if (preg_match('/alt=["\']([^"\']*)["\']/', $img_tag, $matches)) {
            $img_alt = $matches[1];
        }
        if (!$img_src) {
            continue;
        }
        $clean_img_tag = '<img src="' . esc_url($img_src) . '" alt="' . esc_attr($img_alt) . '" style="max-width: 100%; height: auto;" loading="lazy" decoding="async">';
        $image_data[] = array('src' => $img_src, 'alt' => $img_alt, 'tag' => $clean_img_tag);
    }
    
    // 移除图片标签，保留其余富文本结构用于展示
    $content_without_images = preg_replace('/<img[^>]+>/i', '', $content_filtered);
    $rich_html = $content_without_images;

    // 清理开头由编辑器或同步插件插入的空段落/空白，避免正文被整体下推
    $rich_html = preg_replace('/^(?:\s|&nbsp;|<br\s*\/?>|<p>(?:\s|&nbsp;|<br\s*\/?>)*<\/p>)+/i', '', $rich_html);
    
    // 统一文本末尾与图片间距：移除结尾多余空段落/换行/空白，确保图片前固定由 CSS 控制的单行间距
    $rich_html = preg_replace('/(?:\s|<br\s*\/?>|<p>(?:\s|&nbsp;|<br\s*\/?>)*<\/p>)+$/i', '', $rich_html);
    
    // 另外生成纯文本用于长度统计与截断
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
                'full' => $images_display['full'] . $rich_html,
                'images_html' => $images_display['full'],
                'text_html' => $rich_html,
                'gallery_data' => $images_display['gallery_data'],
                'image_count' => count($image_data),
                'is_truncated' => true
            );
        } else {
            // 文本未超长
            return array(
                'preview' => $images_display['preview'] . $rich_html,
                'full' => $images_display['full'] . $rich_html,
                'images_html' => $images_display['full'],
                'text_html' => $rich_html,
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
                'full' => $rich_html,
                'images_html' => '',
                'text_html' => $rich_html,
                'image_count' => 0,
                'is_truncated' => true
            );
        } else {
            return array(
                'preview' => $rich_html,
                'full' => $rich_html,
                'images_html' => '',
                'text_html' => $rich_html,
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
        $single_html = '<div class="image-gallery single-image">';
        $single_html .= '<div class="image-item">' . $image_data[0]['tag'] . '</div>';
        $single_html .= '</div>';
        return array(
            'preview' => $single_html,
            'full' => $single_html,
            'gallery_data' => json_encode(array($image_data[0]))
        );
    } else {
        if ($image_count == 2) {
            $preview_html = '<div class="image-gallery two-images">';
            $preview_html .= '<div class="image-item">' . $image_data[0]['tag'] . '</div>';
            $preview_html .= '<div class="image-item">' . $image_data[1]['tag'] . '</div>';
            $preview_html .= '</div>';
            
            $full_html = '<div class="image-gallery two-images">';
            $full_html .= '<div class="image-item">' . $image_data[0]['tag'] . '</div>';
            $full_html .= '<div class="image-item">' . $image_data[1]['tag'] . '</div>';
            $full_html .= '</div>';
        } else {
            $preview_html = '<div class="image-gallery grid-images">';
            foreach ($image_data as $img) {
                $preview_html .= '<div class="image-item">' . $img['tag'] . '</div>';
            }
            $preview_html .= '</div>';
            
            $full_html = $preview_html;
        }
        
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

// 天气：REST 路由与自定义项
add_action('rest_api_init', 'lbd_register_weather_route');
function lbd_register_weather_route() {
    register_rest_route('lbd/v1', '/weather', array(
        'methods'  => 'GET',
        'callback' => 'lbd_get_today_weather',
        'permission_callback' => '__return_true'
    ));
}

function get_weather_enabled() { return (bool) get_theme_mod('weather_enabled', 0); }
function get_qweather_location() { return trim(get_theme_mod('qweather_location', '')); }
function get_qweather_api_key() { return trim(get_theme_mod('qweather_api_key', '')); }

function lbd_fetch_weather_snapshot($location, $api_key, $cache_ttl = 1800) {
    if (empty($location) || empty($api_key)) {
        return false;
    }

    $cache_key = 'lbd_weather_3d_' . md5($location . '|' . date_i18n('Ymd'));
    if ($cache_ttl > 0) {
        $cached = get_transient($cache_key);
        if ($cached) {
            return $cached;
        }
    }

    $url = add_query_arg(array(
        'location' => $location,
        'key' => $api_key
    ), 'https://devapi.qweather.com/v7/weather/3d');

    $resp = wp_remote_get($url, array('timeout' => 8));
    if (is_wp_error($resp)) {
        return false;
    }

    $status = wp_remote_retrieve_response_code($resp);
    $body = json_decode(wp_remote_retrieve_body($resp), true);
    if ($status !== 200 || empty($body['daily'])) {
        return false;
    }

    $today = date_i18n('Y-m-d');
    $todayItem = null;
    foreach ($body['daily'] as $item) {
        if (isset($item['fxDate']) && $item['fxDate'] === $today) {
            $todayItem = $item;
            break;
        }
    }

    if (!$todayItem) {
        $todayItem = $body['daily'][0];
    }

    $out = array(
        'iconDay' => isset($todayItem['iconDay']) ? $todayItem['iconDay'] : '',
        'textDay' => isset($todayItem['textDay']) ? $todayItem['textDay'] : '',
        'iconNight' => isset($todayItem['iconNight']) ? $todayItem['iconNight'] : '',
        'textNight' => isset($todayItem['textNight']) ? $todayItem['textNight'] : '',
        'tempMax' => isset($todayItem['tempMax']) ? $todayItem['tempMax'] : '',
        'tempMin' => isset($todayItem['tempMin']) ? $todayItem['tempMin'] : ''
    );

    if ($cache_ttl > 0) {
        set_transient($cache_key, $out, $cache_ttl);
    }

    return $out;
}

function lbd_get_post_weather_snapshot($post_id = 0) {
    $post_id = $post_id ? (int) $post_id : get_the_ID();
    if ($post_id <= 0) {
        return false;
    }

    $snapshot = get_post_meta($post_id, '_lbd_weather_snapshot', true);
    return is_array($snapshot) ? $snapshot : false;
}

function lbd_capture_weather_snapshot_on_first_publish($new_status, $old_status, $post) {
    if (!$post instanceof WP_Post || $post->post_type !== 'post') {
        return;
    }

    if ($new_status !== 'publish' || $old_status === 'publish') {
        return;
    }

    if (!get_weather_enabled() || lbd_get_post_weather_snapshot($post->ID)) {
        return;
    }

    $flash_category = get_flash_category_term();
    if (!$flash_category || !has_category((int) $flash_category->term_id, $post)) {
        return;
    }

    $weather = lbd_fetch_weather_snapshot(get_qweather_location(), get_qweather_api_key());
    if (!$weather) {
        return;
    }

    $weather['capturedDate'] = current_time('Y-m-d');
    $weather['capturedAt'] = current_time('mysql');
    $weather['location'] = get_qweather_location();

    update_post_meta($post->ID, '_lbd_weather_snapshot', $weather);
}
add_action('transition_post_status', 'lbd_capture_weather_snapshot_on_first_publish', 10, 3);

function lbd_get_today_weather(WP_REST_Request $req) {
    if (!get_weather_enabled()) return new WP_REST_Response(null, 204);
    $key = get_qweather_api_key();
    $loc = get_qweather_location();
    if (empty($key) || empty($loc)) return new WP_REST_Response(array('error' => 'missing'), 400);

    $out = lbd_fetch_weather_snapshot($loc, $key, 30 * MINUTE_IN_SECONDS);
    if (!$out) return new WP_REST_Response(null, 204);
    return rest_ensure_response($out);
}

function dear_weather_customizer($wp_customize) {
    $wp_customize->add_section('dear_weather_settings', array(
        'title' => '天气设置',
        'priority' => 40,
        'description' => '用于闪念页面当天卡片天气图标'
    ));

    $wp_customize->add_setting('weather_enabled', array(
        'default' => 0,
        'sanitize_callback' => 'absint',
    ));
    $wp_customize->add_control('weather_enabled', array(
        'label' => '启用天气图标',
        'section' => 'dear_weather_settings',
        'type' => 'checkbox'
    ));

    $wp_customize->add_setting('qweather_location', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('qweather_location', array(
        'label' => '位置（城市ID或经纬度）',
        'section' => 'dear_weather_settings',
        'type' => 'text',
        'description' => '示例：101010100 或 39.9,116.3'
    ));

    $wp_customize->add_setting('qweather_api_key', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('qweather_api_key', array(
        'label' => '和风天气 API Key',
        'section' => 'dear_weather_settings',
        'type' => 'text'
    ));

    // 自定义器验证按钮
    if (class_exists('WP_Customize_Control')) {
        if (!class_exists('LBD_Verify_Button_Control')) {
            class LBD_Verify_Button_Control extends WP_Customize_Control {
                public $type = 'lbd_verify_button';
                public function render_content() {
                    ?>
                    <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
                    <?php if (!empty($this->description)) : ?>
                        <span class="description customize-control-description"><?php echo wp_kses_post($this->description); ?></span>
                    <?php endif; ?>
                    <div class="lbd-weather-verify-row" style="display:flex;align-items:center;gap:10px;">
                        <button type="button" class="button button-primary" id="lbd-weather-verify-btn">验证天气</button>
                        <a id="lbd-weather-help-link" href="https://github.com/leidichen/WordPress-LBD/blob/main/docs/qweather-setup.md" target="_blank" rel="noopener" aria-label="帮助" title="如何获取和风天气API与位置ID" style="display:inline-flex;align-items:center;text-decoration:none;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#646970" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </a>
                        <span id="lbd-weather-verify-result" style="line-height:28px;"></span>
                    </div>
                    <?php
                }
            }
        }
        $wp_customize->add_setting('qweather_verify_dummy', array(
            'default' => '',
            'sanitize_callback' => '__return_empty_string',
        ));
        $wp_customize->add_control(new LBD_Verify_Button_Control($wp_customize, 'qweather_verify_dummy', array(
            'label' => '验证设置',
            'section' => 'dear_weather_settings',
            'settings' => 'qweather_verify_dummy',
            'description' => '点击按钮验证当前位置与API Key是否有效（将实时请求和风3日预报）'
        )));
    }
}
add_action('customize_register', 'dear_weather_customizer');

// 移除“背景图片”设置，仅保留背景色相关逻辑
add_action('customize_register', function($wp_customize){
    if ($wp_customize && method_exists($wp_customize, 'remove_section')) {
        $wp_customize->remove_section('background_image');
    }
}, 999);

// 自定义器控制面板脚本：验证按钮逻辑
add_action('customize_controls_enqueue_scripts', function () {
    wp_enqueue_script('customize-controls');
    $script = <<<JS
    (function() {
      if (!window.wp || !wp.customize) return;
      document.addEventListener('click', function(e){
        var t = e.target;
        if (!t || t.id !== 'lbd-weather-verify-btn') return;
        var out = document.getElementById('lbd-weather-verify-result');
        if (!out) {
          out = document.createElement('span');
          out.id = 'lbd-weather-verify-result';
          out.style.marginLeft = '10px';
          t.insertAdjacentElement('afterend', out);
        }
        var loc = wp.customize('qweather_location') && wp.customize('qweather_location').get();
        var key = wp.customize('qweather_api_key') && wp.customize('qweather_api_key').get();
        out.textContent = '';
        if(!loc || !key){
          out.textContent = '请先填写位置与API Key';
          out.style.color = '#d63638';
          return;
        }
        t.disabled = true;
        out.textContent = '正在验证...';
        out.style.color = '';
        var url = '/wp-json/lbd/v1/weather/verify?location=' + encodeURIComponent(loc) + '&key=' + encodeURIComponent(key);
        fetch(url, { credentials: 'same-origin' })
          .then(function(r){
            if (r.status === 204) return null;
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
          })
          .then(function(data){
            if(!data || !data.ok){
              out.textContent = '验证失败：' + (data && data.msg ? data.msg : '接口无返回');
              out.style.color = '#d63638';
              return;
            }
            var detail = [];
            if(data.iconDay) detail.push('iconDay ' + data.iconDay);
            if(data.textDay) detail.push(data.textDay);
            if(data.tempMin!=null && data.tempMax!=null) detail.push(data.tempMin + '–' + data.tempMax + '°C');
            out.textContent = '验证成功' + (detail.length? '（' + detail.join(' / ') + '）' : '');
            out.style.color = '#00a32a';
          })
          .catch(function(err){
            out.textContent = '验证失败：' + (err && err.message ? err.message : '网络错误');
            out.style.color = '#d63638';
          })
          .finally(function(){ t.disabled = false; });
      });
    })();
    JS;
    wp_add_inline_script('customize-controls', $script);
});

// 验证路由：使用传入的location与key进行即时检测
add_action('rest_api_init', function () {
    register_rest_route('lbd/v1', '/weather/verify', array(
        'methods'  => 'GET',
        'callback' => function (WP_REST_Request $req) {
            $loc = trim($req->get_param('location'));
            $key = trim($req->get_param('key'));
            if (empty($loc) || empty($key)) {
                return rest_ensure_response(array('ok' => false, 'msg' => '缺少参数'));
            }
            $cache_key = 'lbd_weather_verify_' . md5($loc . '|' . $key);
            $cached = get_transient($cache_key);
            if ($cached) return rest_ensure_response($cached);
            $weather = lbd_fetch_weather_snapshot($loc, $key, 0);
            if (!$weather) {
                $url = add_query_arg(array('location'=>$loc,'key'=>$key), 'https://devapi.qweather.com/v7/weather/3d');
                $resp = wp_remote_get($url, array('timeout'=>8));
                $body = is_wp_error($resp) ? array() : json_decode(wp_remote_retrieve_body($resp), true);
                $msg = isset($body['code']) ? ('接口返回 code ' . $body['code']) : '响应异常';
                return rest_ensure_response(array('ok'=>false,'msg'=>$msg));
            }
            $out = array(
                'ok' => true,
                'iconDay' => isset($weather['iconDay']) ? $weather['iconDay'] : '',
                'textDay' => isset($weather['textDay']) ? $weather['textDay'] : '',
                'tempMax' => isset($weather['tempMax']) ? $weather['tempMax'] : null,
                'tempMin' => isset($weather['tempMin']) ? $weather['tempMin'] : null
            );
            set_transient($cache_key, $out, 2 * MINUTE_IN_SECONDS);
            return rest_ensure_response($out);
        },
        'permission_callback' => '__return_true'
    ));
});


/**
 * 还原 Obsidian 等客户端同步由于转义造成的 <video> / <audio> / <iframe> 标签
 */
function restore_escaped_media_tags($content) {
    if (empty($content)) return $content;
    
    $tags = array('video', 'audio', 'iframe');
    foreach ( $tags as $tag ) {
        // 解码开始标签，例如 &lt;video src="..." controls...&gt;
        $pattern = '/&lt;(' . $tag . '\b.*?)&gt;/is';
        if ( preg_match($pattern, $content) ) {
            $content = preg_replace_callback($pattern, function($matches) {
                // 将被转义的引号等也一并还原
                return '<' . html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8') . '>';
            }, $content);
            // 替换闭合标签
            $content = str_ireplace('&lt;/' . $tag . '&gt;', '</' . $tag . '>', $content);
        }
    }
    return $content;
}
add_filter('the_content', 'restore_escaped_media_tags', 20);
add_filter('the_excerpt', 'restore_escaped_media_tags', 20);
