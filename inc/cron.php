<?php

if (!defined('ABSPATH'))
	die();

function okupanel_cron_schedules($schedules){
    if (!isset($schedules['everyminute'])){
        $schedules['everyminute'] = array(
            'interval' => 60,
            'display' => __('Once every minute'));
    }
    return $schedules;
}
add_filter('cron_schedules', 'okupanel_cron_schedules');

// schedule once
if (is_admin() && !wp_get_schedule('okupanel_cron_trigger'))
    add_action('init', 'okupanel_schedule', 10);

function okupanel_schedule(){
	wp_schedule_event(time(), 'everyminute', 'okupanel_cron_trigger');
}

add_action('okupanel_cron_trigger', 'okupanel_cron_trigger');
function okupanel_cron_trigger(){
	echo '[okupanel][cron] start<br>';
	define('OKUPANEL_CRON', true);
	do_action('okupanel_cron');
	do_shortcode(get_option('okupanel_right_panel', ''));
	echo '[okupanel][cron] end';
}

function okupanel_is_cron(){
	if (current_user_can('manage_options') && !empty($_GET['okupanel_force_cron']))
		return true;
	return defined('OKUPANEL_CRON');
}

register_deactivation_hook(__FILE__, 'okupanel_cron_deactivation');
function okupanel_cron_deactivation() {
	wp_clear_scheduled_hook('okupanel_cron_trigger');
}

add_action('init', function(){
	if (current_user_can('manage_options') && !empty($_GET['okupanel_force_cron'])){
		okupanel_cron_trigger();
		exit();
	}
}, 9999);
