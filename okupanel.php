<?php
/*
Plugin Name: OkuPanel
Plugin URI: https://code.cdbcommunications.org/okulabs/okupanel/
Description: Live screen and online system to display federated events and rooms in self-organized spaces.
Version: 10.4
Author: OkuLabs, via cDb Communications
Author URI: https://cdbcommunications.org/
License: GPLv2 or later
Text Domain: okupanel
*/


if (!defined('ABSPATH'))
	exit();

define('OKUPANEL_MAX_LOCATION_LENGTH', 12); // CHARACTERS
define('OKUPANEL_EVENTS_CACHE_DURATION', 8); // MINUTES
define('OKUPANEL_EVENTS_CACHE_DURATION_VIA_CRON', 5); // MINUTES
define('OKUPANEL_CLIENT_REFRESH_FREQUENCY', !empty($_GET['okupanel_refresh_rate']) ? intval($_GET['okupanel_refresh_rate']) : 15); // MINUTES (for visitors)
define('OKUPANEL_FULLSCREEN_REFRESH_FREQUENCY', 1); // MINUTES (for fullscreen mode)

define('OKUPANEL_CLIENT_STARTING_DELAY', 5); // MINUTES BEFORE EVENT
define('OKUPANEL_CLIENT_STARTING_UPTO', 5); // MINUTES AFTER EVENT
define('OKUPANEL_TIME_OFFSET', 0); // TIME OFFSET IN MINUTES (SHOULD BE 0). MOSTLY FOR TESTING PURPOSE
define('OKUPANEL_CLIENT_EVENT_NEW', 2); // DAYS TO SHOW THE "NEW" EVENT LABEL

define('OKUPANEL_PRECISE_DAY_BEFORE', '08:00');

// stop editing from now

define('OKUPANEL_VERSION', '10.4'); // increment to force assets recaching
define('OKUPANEL_PATH', __DIR__);
define('OKUPANEL_URL', plugins_url('', __FILE__));
define('OKUPANEL_CALENDAR_CHECK_SSL', false); // DO NOT CHECK SSL CERTIFICATES

require __DIR__.'/inc/helpers.php';
require __DIR__.'/inc/ajax.php';
require __DIR__.'/inc/settings.php';
require __DIR__.'/inc/gcal_auth.php';
require __DIR__.'/inc/assets.php';
require __DIR__.'/inc/cron.php';

// rewrite "/okupanel"

add_action('init', 'okupanel_custom_rewrite');
function okupanel_custom_rewrite() {
	add_rewrite_rule('^okupanel/?$', 'index.php?okupanel_action=panel', 'top');
	add_rewrite_rule('^okupanel(/(.*))?/?$', 'index.php?okupanel_action=$matches[2]', 'top');

	// preload federation mod rules
	//add_rewrite_rule('^okunet/?$', 'index.php?okupanel_action=federation_panel', 'top');
}

add_filter('query_vars', 'okupanel_query_vars');
function okupanel_query_vars($qvars){
  $qvars[] = 'okupanel_action';
  return $qvars;
}

add_filter('template_include', 'okupanel_page_template', 99);
function okupanel_page_template($template){
	global $wp_query, $wpdb;
	if ($wp_query->is_main_query() && !empty($wp_query->query_vars['okupanel_action']) && preg_match('#^([a-z_]+)(?:/([a-zA-Z0-9_-]+))?$#i', $wp_query->query_vars['okupanel_action'], $m)){
		switch ($m[1]){
			case 'panel':
				return OKUPANEL_PATH.'/templates/panel.php';
			case 'ics':
				return OKUPANEL_PATH.'/templates/ics.php';
		}
		do_action('okupanel_action_'.$m[1], !empty($m[2]) ? $m[2] : false);
	}
	return $template;
}

register_activation_hook( __FILE__, 'okupanel_install' );
function okupanel_install(){
	okupanel_flush_rewrites();
}

register_deactivation_hook( __FILE__, 'okupanel_flush_rewrites' );
function okupanel_flush_rewrites() {
	okupanel_custom_rewrite();
	flush_rewrite_rules();
}


// translate

add_filter('plugin_locale', 'okupanel_change_locale', 0, 2);
function okupanel_change_locale($locale, $domain = null){

	// translate ajax
	return $domain == 'okupanel' && !empty($_POST['okupanel_locale']) ? $_POST['okupanel_locale'] : $locale;
}
load_plugin_textdomain('okupanel', false, basename(OKUPANEL_PATH).'/languages');
