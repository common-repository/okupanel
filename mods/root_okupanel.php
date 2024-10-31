<?php
// turn the homepage into okupanel (no /okupanel/ slug)

if (!defined('ABSPATH'))
	exit();


add_filter('frontpage_template', 'okupanel_root_page_template', 99);
function okupanel_root_page_template($template){
	return is_front_page() ? OKUPANEL_PATH.'/templates/panel.php' : $template;
}

add_filter('okupanel_is_page', function($is_page){
	return $is_page || is_front_page();
});

add_filter('okupanel_url', function($url, $okupanel_uri){
	return home_url($okupanel_uri
		? (
			strpos($okupanel_uri, '?') === false
			? rtrim($okupanel_uri, '/').'/'
			: '/'.ltrim($okupanel_uri, '/')
		) : '');
}, 0, 2);

add_action('init', 'okupanel_root_custom_rewrite');
function okupanel_root_custom_rewrite() {
	add_rewrite_rule('^(?:okupanel/)?(.*)/?$', 'index.php?okupanel_action=$matches[1]', 'top');

	// preload federation mod rules
	//add_rewrite_rule('^okunet/?$', 'index.php?okupanel_action=federation_panel', 'top');
}
