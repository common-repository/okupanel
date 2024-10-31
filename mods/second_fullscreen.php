<?php
// allow to use several different fullscreen modes thanks to /okupanel/?fullscreen=2&moving=1 (for screen 2)

if (!defined('ABSPATH'))
	exit();


add_filter('okupanel_line', 'okupanel_line_skip_fullscreen2'); 
function okupanel_line_skip_fullscreen2($atts){
	if (!empty($atts['fullscreen2']) && $atts['fullscreen2'] == 'no' && okupanel_is_second_screen())
		$atts['skip'] = true;
	return $atts;
}

add_filter('okupanel_js_extra_vars', function($extra){
	if (okupanel_is_second_screen()){
		$extra['fullscreen2'] = 1;
		$extra['fullscreen_number'] = okupanel_get_fullscreen_number();
	}
	return $extra;
});

function okupanel_get_fullscreen_number(){
	if (!empty($_REQUEST['okupanel_extra_vars'])
		&& !empty($_REQUEST['okupanel_extra_vars']['fullscreen2']))
		return 2;
	if (!empty($_REQUEST['okupanel_extra_vars'])
		&& !empty($_REQUEST['okupanel_extra_vars']['fullscreen_number']))
		return intval($_REQUEST['okupanel_extra_vars']['fullscreen_number']);
	if (!empty($_REQUEST['fullscreen2']))
		return 2;
	if (!empty($_REQUEST['fullscreen']))
		return intval($_REQUEST['fullscreen']);
	return 0;
}

function okupanel_is_second_screen(){
	return okupanel_get_fullscreen_number() == 2;
}
