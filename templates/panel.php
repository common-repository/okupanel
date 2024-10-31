<?php 

if (!defined('ABSPATH'))
	exit();


if (!empty($_GET['okupanel_debug']) && current_user_can('manage_options')){
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
}
	
show_admin_bar(false);

add_filter('pre_get_document_title', 'okupanel_panel_title', 999);
add_filter('wp_title', 'okupanel_panel_title', 999);
//add_filter('the_title', 'okupanel_panel_title', 999);
add_filter('document_title', 'okupanel_panel_title', 999);
add_filter('wpseo_opengraph_title', 'okupanel_panel_title', 999);
add_filter('wpseo_twitter_title', 'okupanel_panel_title', 999);

function okupanel_panel_title($title){

	$title = get_bloginfo('name');
	$title = apply_filters('okupanel_panel_title', $title);
	return ($title == '' ? '' : $title.' - ').'OkuPanel';
}

add_filter('wpseo_canonical', 'okupanel_panel_canonical', 99);
add_filter('get_canonical_url', 'okupanel_panel_canonical', 99);
add_filter('wpseo_opengraph_url', 'okupanel_panel_canonical', 99);
add_filter('wpseo_twitter_url', 'okupanel_panel_canonical', 99);

function okupanel_panel_canonical($url){
	 return okupanel_url();
}

add_filter('wpseo_opengraph_desc', 'okupanel_panel_desc', 99);
add_filter('wpseo_twitter_description', 'okupanel_panel_desc', 99);

function okupanel_panel_desc($desc){
	 return get_bloginfo('description');
}

add_filter('wpseo_prev_rel_link', '__return_false');
add_filter('wpseo_next_rel_link', '__return_false');

?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js okupanel">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	
	<?php wp_head(); ?>

	<script type="text/javascript"> 
		<?= get_option('okupanel_panel_js', '') ?> 
	</script>

	<style>
		<?php echo get_option('okupanel_panel_css', ''); ?>

		html, body {
			margin: 0 !important; 
			padding: 0 !important;
		}
	</style>
</head>
<body <?php body_class((!empty($_GET['fullscreen']) ? 'okupanel-fullscreen' : '').(!empty($_GET['moving']) ? ' okupanel-moving' : '')); ?>>
	<?php 

	okupanel_print_popup();
	
	if (!empty($_GET['fullscreen']) && ($bottombar_html = okupanel_bottombar())){ ?>
		<div class="okupanel-bottom-bar">
			<div class="okupanel-bottom-bar-inner" data-okupanel-bottombar="<?= esc_attr(okupanel_bottombar()) ?>">
				<div class="okupanel-bottom-bar-lines">
				<?php echo $bottombar_html; ?>
				</div>
			</div>
		</div>
	<?php } ?>
	<div class="okupanel-bg-wrap">
		<div class="okupanel-header-wrap">
			<?php do_action('okupanel_header_before'); ?>
			<div class="okupanel-header"><?= get_option('okupanel_intro', 'OkuPanel').okupanel_clock() ?></span></div>
			<?php do_action('okupanel_header_after'); ?>
		</div>
		<div class="okupanel-panel">
			<div class="okupanel-panel-mobile-menu">
				<a href="#" onclick="jQuery('body').toggleClass('okupanel-panel-mobile-menu-open'); return false;"><?= get_option('okupanel_links_label', __('Links', 'okupanel')) ?></a>
			</div>
			<div class="okupanel-panel-inner">
				<?php do_action('okupanel_panel_before'); ?>
				<div class="okupanel-panel-right">
					<div class="okupanel-panel-right-inner"><?php
						do_action('okupanel_right_panel_before');
						echo do_shortcode(get_option('okupanel_right_panel', ''));
						do_action('okupanel_right_panel_after');
					?></div>
				</div>
				<div class="okupanel-panel-left">
					<div class="okupanel-table-top">
						<?php do_action('okupanel_left_panel_before'); ?>
						<?php okupanel_print_panel(); ?>
						<?php do_action('okupanel_left_panel_after'); ?>
					</div>
					<?php if (!empty($_GET['moving'])){ ?>
						<div class="okupanel-table-bottom"><div class="okupanel-table-bottom-inner"><div class="okupanel-table-moving"></div></div></div>
					<?php } ?>
				</div>
				<?php do_action('okupanel_panel_after'); ?>
			</div>
			<div class="okupanel-footer"><a href="https://code.cdbcommunications.org/okulabs/okupanel" target="_blank">OkuPanel</a> <i class="fa fa-copyright fa-rotate-180"></i> <a href="https://code.cdbcommunications.org/okulabs" target="_blank">OkuLabs</a></div>
		</div>
		<?php wp_footer(); ?>
	</div>
</body>
</html><?php
