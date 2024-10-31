<?php
// detect images from event descriptions and convert them to pictures + adds the [okupanel_featured_flyer] shortcode to show the last featured event in fullscreen=2 mode's sidebar

if (!defined('ABSPATH'))
	exit();


add_filter('okupanel_tr_vars', function($vars){
	if (!empty($vars['e']['description']) && preg_match_all('#https?://\S+?/[\S]+\.(jpe?g|png)#ius', $vars['e']['description'], $matches, PREG_SET_ORDER)){
		foreach ($matches as $m){
			$vars['after'] .= '<br><a href="'.$m[0].'" target="_blank"><img class="okupanel-featured-flyer" style="width: 100%; max-width: 400px;" src="'.$m[0].'" /></a><br>';
			$vars['e']['description'] = str_replace($m[0], '', $vars['e']['description']);
			$vars['e']['description'] = preg_replace('#(<br\s*\?>)+#ius', '<br>', $vars['e']['description']);
			$vars['e']['description'] = preg_replace('#<a\s+[^>]*>\s*</a>#ius', '', $vars['e']['description']);
		}
	}
	return $vars;
});

add_filter('okupanel_most_important_item', function($str, $e){
        if (!empty($_REQUEST['fullscreen']) && empty($_REQUEST['fullscreen2']))
	   return $str;
	if (!empty($e['description']) && preg_match_all('#https?://\S+?/[\S]+\.(jpe?g|png)#ius', $e['description'], $matches))
		$str .= '<div style="margin: 12px -5px -10px"><a href="'.$matches[0][0].'" target="_blank"><img src="'.$matches[0][0].'" class="okupanel-next-flyer" /></a></div>';
	return $str;
}, 0, 2);

add_shortcode('okupanel_next_flyer', function($atts = array(), $content = ''){
	return ''; // cancelled, useless as now included in most important items
});

// [okupanel_the_next_flyer style="float: right; margin: 0 10px 10px; max-width: 370px;"]

add_shortcode('okupanel_the_next_flyer', function($atts = array(), $content = ''){
		
	if (!($events = okupanel_get_events()))
		return '';

	foreach ($events as $e){
		
		if (okupanel_is_party($e) && !empty($e['description']) && preg_match_all('#https?://\S+?/[\S]+\.(jpe?g|png)#ius', $e['description'], $matches))
			return '<span style="'.(!empty($atts['style']) ? $atts['style'] : '').'"><a href="'.$matches[0][0].'" target="_blank"><img src="'.$matches[0][0].'" class="okupanel-next-flyer" /></a></span>';
	}
	return '';
});
