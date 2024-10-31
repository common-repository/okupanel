<?php
// federate several okupanels altogether or/and add federated virtual nodes on one okupanel

if (!defined('ABSPATH'))
	exit();


// enable fed mode with ?test_federation_switch=1
define('OKUPANEL_FED_ACTIVE', defined('DOING_AJAX') && DOING_AJAX
	? (!empty($_REQUEST['okupanel_extra_vars']['fed_enabled']) && $_REQUEST['okupanel_extra_vars']['fed_enabled'] !== 'false')
	: true); //: !empty($_REQUEST['test_federation_switch']));

define('OKUPANEL_FED_TEST_URL', false);

add_action('okupanel_action_graph', function(){
	$ret = apply_filters('okupanel_action_graph_output', array(
		'federated_name' => get_option('okupanel_fed_name', ''),
		'federated_short_name' => get_option('okupanel_fed_name_vshort', ''),
		'name' => get_bloginfo('name'),
		'description' => get_bloginfo('description'),
		'public_email' => get_option('okupanel_fed_public_email'),
		'language' => get_bloginfo('language'),
		'networks' => okupanel_get_network_accounts(),
		'address' => get_option('okupanel_address', ''),
		'address_url' => get_option('okupanel_address_url', ''),
		'events' => okupanel_fed_get_public_events(),
	));
	header('Content-Type: application/json');
	echo json_encode($ret, JSON_PRETTY_PRINT);
	die();
});

/*
add_action('okupanel_action_nodes', function(){
	$ret = okupanel_fed_get_nodes();
	header('Content-Type: application/json');
	echo json_encode($ret, JSON_PRETTY_PRINT);
	die();
});
*/

function okupanel_fed_get_public_events(){

	$events = array();
	foreach (okupanel_get_events() as $e)
		if ($e['status'] == 'confirmed')
			$events[] = apply_filters('okulec_fed_public_event', array(
				'summary' => $e['summary'],
				'description' => $e['description'],
				'location' => $e['location'],
				'status' => $e['status'],
				//'origin' => $e['origin'],
				'created' => $e['created'],
				'created_gmt' => $e['created_gmt'],
				'start' => $e['start'],
				'start_gmt' => $e['start_gmt'],
				'end' => $e['end'],
				'end_gmt' => $e['end_gmt'],
				'updated' => $e['updated'],
				'updated_gmt' => $e['updated_gmt'],
				'htmlLink' => $e['htmlLink'],
				'recurrence' => $e['recurrence'],
				'exceptions' => $e['exceptions'],
			), $e);

	return $events;
}


add_filter('okupanel_textline_fields', 'okupanel_fed_fields_save');
function okupanel_fed_fields_save($fields){
	$fields[] = 'fed_nodes';
	return $fields;
}

add_filter('okupanel_textline_fields', 'okupanel_fed_fields_textline_save');
function okupanel_fed_fields_textline_save($fields){
	$fields[] = 'fed_name';
	$fields[] = 'fed_name_vshort';
	$fields[] = 'fed_public_email';
	return $fields;
}

add_action('okupanel_print_extra_textarea_fields_2', 'okupanel_fed_fields');
function okupanel_fed_fields(){
	?>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Federated nodes', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><textarea name="okupanel_fed_nodes"><?= esc_textarea(get_option('okupanel_fed_nodes', '')) ?></textarea></div>
		</div>
	</div>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Federated short name', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><input name="okupanel_fed_name" value="<?= esc_attr(get_option('okupanel_fed_name', '')) ?>" maxlength="20" /></div>
			<div><?= __('(20 characters max, for the federated widget in the sidebar)', 'okupanel') ?></div>
		</div>
	</div>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Federated very short name', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><input name="okupanel_fed_name_vshort" value="<?= esc_attr(get_option('okupanel_fed_name_vshort', '')) ?>" maxlength="7" /></div>
			<div><?= __('(7 characters max, for inside the events table)', 'okupanel') ?></div>
		</div>
	</div>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('PUBLIC Email', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><input name="okupanel_fed_public_email" value="<?= esc_attr(get_option('okupanel_fed_public_email', '')) ?>" /></div>
		</div>
	</div>
	<?php
}

function okupanel_fed_get_nodes(){
	static $results = null;
	if ($results === null)
		$results = array();
	else
		return $results;

	$nodes = array();
	$results = array();

	// virtual nodes
	$vnodes = get_option('okupanel_vnodes', array());
	foreach ($vnodes as $n)
		if ($sname = trim($n['short_name']))
			$results[$n['short_name']] = array(
				'virtual' => true,
				'id' => $sname,
				'base_url' => $n['url'],
				'label' => null,//$n['name'],
				'api_url' => null,
				'result' => array(
					'federated_name' => trim($n['name']),
					'federated_short_name' => $sname,
					'name' => trim($n['name']),
					'description' => '',
					'public_email' => '',
					'language' => get_locale(),
					'networks' => array(
						'feed' => '',
						'facebook' => '',
						'twitter' => '',
						'youtube' => '',
					),
					'address' => $n['address'],
					'address_url' => $n['address_url'],
					'events' => array(),
				),
			);

	foreach (explode("\n", get_option('okupanel_fed_nodes', '')) as $l){
		$l = trim($l);
		$label = null;
		if (preg_match('#^(https?://\S+)(\s+(\S+))?$#iu', $l, $m)){
			$l = $m[1];
			$label = count($m) > 2 ? $m[3] : null;
		}

		if (!$l || !($l = esc_url($l)))
			continue;
		if (preg_match('#^(https?://.+?)(/okupanel)?/?([\?\#].*)?$#i', $l, $m)){
			$l = $m[1];
			if (!($l = esc_url($l)))
				continue;
		} else
			$l = rtrim($l, '/');
		if (rtrim(home_url(), '/') != $l){
			$nodes[] = array(
				'base_url' => $l,
				'label' => $label
			);
		}
	}

	foreach ($nodes as $n){
		$base_url = trim($n['base_url']);
		$key = 'okupanel_fed_events_for_'.base64_encode($n['base_url']);
		$api_url = OKUPANEL_FED_TEST_URL ? OKUPANEL_FED_TEST_URL : $n['base_url'].'/okupanel/graph/';
		$ret = array();
		if (($cache = get_option($key, false)) && $cache['time'] > strtotime('-'.(okupanel_is_cron() ? 10 : 15).' minutes') && (!current_user_can('manage_options') || empty($_GET['update'])))
			$ret = $cache['ret'];
		else {

			if (current_user_can('manage_options') && !empty($_GET['okupanel_fed_print_nodes']))
			   echo "FETCHING ".$n['base_url'].'<br>';

			$ret = okupanel_fetch($api_url, true);

			if ($ret || !$cache)
				update_option($key, array(
					'time' => time(),
					'ret' => $ret,
				));
			else if ($cache && $cache['ret'])
				$ret = $cache['ret'];
		}
		$results[$base_url] = $n + array(
			'api_url' => $api_url,
			'result' => (array) $ret
		);
	}
	//echo '</script></style>'; print_r($results); die();

	return $results;
}

function okupanel_fed_get_node_url($n){
	if (empty($n['virtual']))
		return rtrim($n['base_url'], '/').'/okupanel/';

	return add_query_arg('bookmarkme', base64_encode($n['id']), remove_query_arg('update', okupanel_current_url()));
}

function okupanel_fed_get_node_public_url($n){
	return $n['base_url'];
}

function okupanel_fed_get_node_switch_url($n){
	$current_url = remove_query_arg('update', okupanel_url());
	$current_bookmarks = okupanel_fed_get_active(null);

	// general button's url
	$ccurrent_bookmarks = $current_bookmarks;
	if ($active = (in_array($n['base_url'], $ccurrent_bookmarks) || (!empty($n['id']) && in_array($n['id'], $ccurrent_bookmarks))))
		array_splice($ccurrent_bookmarks, array_search(in_array($n['base_url'], $ccurrent_bookmarks) ? $n['base_url'] : $n['id'], $ccurrent_bookmarks), 1);
	else
		$ccurrent_bookmarks[] = !empty($n['virtual']) ? $n['id'] : $n['base_url'];
	$ccurrent_bookmarks = array_unique($ccurrent_bookmarks);
	sort($ccurrent_bookmarks);

	$fed_url = $ccurrent_bookmarks ? add_query_arg('bookmarkme', base64_encode(implode(',', $ccurrent_bookmarks)), $current_url) : remove_query_arg('bookmarkme', $current_url);

	return $fed_url;
}

function okupanel_fed_get_active($nodes = null, $return_nodes = false){
	static $current_bookmarks = null;
	if ($current_bookmarks === null){
		if (!$nodes)
			$nodes = okupanel_fed_get_nodes();
		$current_bookmarks = array();
		$bkme = array();
		$vnodes = get_option('okupanel_vnodes', array());

		if (okupanel_is_cron())
			foreach ($nodes as $n)
				$current_bookmarks[$n['base_url']] = $n;
		else {

			if (defined('DOING_AJAX') && DOING_AJAX
				? (!empty($_REQUEST['okupanel_extra_vars']['fed_enabled']) && $_REQUEST['okupanel_extra_vars']['fed_enabled'] !== 'true' && $_REQUEST['okupanel_extra_vars']['fed_enabled'] !== true)
				: !empty($_REQUEST['bookmarkme'])){

				$bkme = explode(',', base64_decode(defined('DOING_AJAX') && DOING_AJAX
					? $_REQUEST['okupanel_extra_vars']['fed_enabled']
					: $_REQUEST['bookmarkme']));
				foreach ($bkme as $id){
					if ($n = okupanel_fed_get_event_node($id))
						$current_bookmarks[$id] = $n;
				}
			}
		}
		if (okupanel_is_cron() || (!$bkme && get_option('okupanel_fed_only', false)))
			foreach ($vnodes as $n)
				$current_bookmarks[$n['short_name']] = $n;

		//echo '</script></style>'; print_r($nodes); die();
	}
	//print_r(array_keys($current_bookmarks));
	return $return_nodes ? array_values($current_bookmarks) : array_keys($current_bookmarks);
}

add_shortcode('okupanel_federation_switch', function($atts = array(), $content = ''){
	if ((!empty($_REQUEST['fullscreen']) && $_REQUEST['fullscreen'] !== 'false') || !OKUPANEL_FED_ACTIVE || !($nodes = okupanel_fed_get_nodes()))
		return '';
	return '<div class="okupanel-fed-switch-wrap">'.($content ? '<div class="okupanel-fed-switch-content">'.$content.'</div>' : '').'<div class="okupanel-fed-switch-inner">'.okupanel_federation_switch().'</div></div>';
});

add_filter('okupanel_all_events_returned', function($events){
	$nevents = array();
	$active = okupanel_fed_get_active(null, false);
	foreach ($events as $e)
		if (empty($e['node']) || in_array($e['node'], $active))
			$nevents[] = $e;
	$events = $nevents;

	if (OKUPANEL_FED_ACTIVE){

		foreach (okupanel_fed_get_active(null, true) as $n){
			$nevents = array();
			/*$key = 'okupanel_fed_events_cache_'.base64_encode($n['base_url']);

			if (($cache = get_option($key)) && $cache['time'] > strtotime('-10 minutes'))
				$nevents = $cache['events'];

			else {
			* */
			if (!empty($n['result']) && $n['result']['events']){
				foreach ($n['result']['events'] as $e){
					$nevents[] = array(
						'node' => $n['base_url'],
					) + (array) $e + array(
						'status' => 'confirmed',
					);
				}
			}
			if ($nevents){
				okupanel_factorize_event_locations($nevents);
				$events = array_merge($events, $nevents);
			}
		}

	}
	okupanel_sort_events($events);
	return $events;
});

add_filter('okupanel_factorize_events', function($do, $a, $b){
	if (!$do)
		return false;
	if (empty($a['node']))
		$a['node'] = null;
	if (empty($b['node']))
		$b['node'] = null;
	return $a['node'] === $b['node'];
}, 0, 3);

add_filter('okupanel_tr', function($tr, $e){
	if (okupanel_fed_get_active()){
		okupanel_add_tr_cell($tr, array('duration'), 'node', okupanel_fed_get_federated_name($e, true));

		if (!get_option('okupanel_fed_force_show_rooms', false))
			unset($tr['tds']['location']);
	}
	return $tr;
}, 0, 2);


add_filter('okupanel_force_event_location', function($show){
        return $show || okupanel_fed_get_active();
});


function okupanel_fed_get_federated_name($e, $in_table = false){
	$nodes = okupanel_fed_get_nodes();
	$node = 'N/D';
	if (empty($e['node']))
		$node = '<a href="'.home_url('okupanel/').'">'.get_option($in_table ? 'okupanel_fed_name_vshort' : 'okupanel_fed_name', '').'</a>';
	else {
		foreach ($nodes as $n)
			if ($n['result'] && ($n['base_url'] == $e['node'] || (!empty($n['id']) && $n['id'] == $e['node']))){

				$node = '<a href="'.okupanel_fed_get_node_url($n).'">'.htmlentities(
					$n['label']
					? $n['label']
					: (
						$in_table && !empty($n['result']['federated_short_name'])
						? $n['result']['federated_short_name']
						: (
							!empty($n['result']['federated_name'])
							? $n['result']['federated_name']
							: mb_substr($n['result']['name'], 0, 10)
						)
					)
				).'</a>';

				//$tr['tds']['node'] = $n['base_url'];
				break;
			}
	}
	return $node;
}

add_filter('okupanel_ths', function($ths){
	if (OKUPANEL_FED_ACTIVE && okupanel_fed_get_active()){
		if (($i = array_search('location', array_keys($ths))) !== false){

			if (!get_option('okupanel_fed_force_show_rooms', false)){

				$ths = array_merge(array_slice($ths, 0, $i), array('fed_nodes' => '<th>'.__('Centro', 'okupanel').'</th>'), array_slice($ths, $i));
				unset($ths['location']);

			} else {

				$ths = array_merge(array_slice($ths, 0, $i), array('fed_nodes' => '<th>'.__('Centro', 'okupanel').'</th>'), array_slice($ths, $i));
			}
		}
	}
	return $ths;
});

add_filter('okupanel_event_address', function($address, $e){
	if (OKUPANEL_FED_ACTIVE && okupanel_fed_get_active()){

		if (empty($e['node'])){
			$address = '<a href="'.home_url().'" target="_blank">'.get_option('okupanel_fed_name', get_bloginfo('name')).'</a>';
			if ($caddress = get_option('okupanel_address', ''))
				$address .= ' ('.(
					($address_url = get_option('okupanel_address_url', ''))
					? '<a href="'.$address_url.'" target="_blank">'.$caddress.'</a>'
					: $caddress
				).')';

		} else {

			$address = 'N/D';
			if ($n = okupanel_fed_get_event_node($e['node'])){
				$address = '<a href="'.$n['base_url'].'" target="_blank">'.htmlentities(
					$n['result'] && $n['result']['name']
					? $n['result']['name']
					: $n['label']
				).'</a>';

				if ($n['result'] && !empty($n['result']['address'])){
					if ($n['result']['address_url'])
						$address .= ' (<a href="'.$n['result']['address_url'].'" target="_blank">'.htmlentities($n['result']['address']).'</a>)';
					else
						$address .= ' ('.htmlentities($n['result']['address']).')';
				}
			}
		}
	}
	return $address;
}, 0, 2);

function okupanel_fed_get_event_node($node_id){
	static $nodes = null;
	if ($nodes === null)
		$nodes = okupanel_fed_get_nodes();
	foreach ($nodes as $n)
		if ($n['base_url'] == $node_id || (!empty($n['id']) && $n['id'] == $node_id))
			return $n;
	return null;
}

function okupanel_fed_is_node_active($n){
	$current_bookmarks = okupanel_fed_get_active(null);
	return in_array($n['base_url'], $current_bookmarks) || (!empty($n['id']) && in_array($n['id'], $current_bookmarks));
}

function okupanel_federation_switch(){
	if (!OKUPANEL_FED_ACTIVE)
		return '';

	$nodes = okupanel_fed_get_nodes();
	if (!$nodes)
		return '';

	$buttons = array();

	if (!get_option('okupanel_fed_only', false)){

		ob_start();
		$active = true;
		$button_label = ($fed_name = get_option('okupanel_fed_name', '')) ? $fed_name : get_bloginfo('name');
		$url = rtrim(home_url(), '/')
		?>
		<div>
			<label class="okupanel-fedswitch-btn-own <?= ($active ? 'okupanel-fedswitch-active' : 'okupanel-fedswitch-inactive') ?>"><span href="<?= esc_attr($url) ?>" class="okupanel-fedswitch-btn"><i class="fa fa-check"></i></span><span class="okupanel-fedswitch-label"><a href="<?= esc_attr(okupanel_url()) ?>"><?= htmlentities($button_label) ?></span></span><a href="<?= home_url() ?>" title="<?= esc_attr(__('See website', 'okupanel')) ?>"><i class="fa fa-angle-right"></i></a></label>
		</div>
		<?php

		$buttons[] = ob_get_clean();
	}
//		$buttons[] = '<div><label class="okupanel-fedswitch-btn-own"><span class="okupanel-fedswitch-btn"><i class="fa fa-check"></i></span><span class="okupanel-fedswitch-label"><input type="checkbox" checked disabled="true" onclick="return false" name="okupanel_fed_switch_'..'" /> '..'</span></label></div>';

	global $wp;

	foreach ($nodes as $n){

		$button_label = (
			$n['label']
			? $n['label']
			: (
				$n['result'] && !empty($n['result']['federated_name'])
				? $n['result']['federated_name']
				: (
				  $n['result'] && !empty($n['result']['name'])
				  ? $n['result']['name']
				  : (
					preg_match('#^https?://(.*)$#i', $n['base_url'], $m)
					? $m[1]
					: $n['base_url']
				  )
				)
			)
		);
		if (mb_strlen($button_label) > 20)
			$button_label = okupanel_substr($button_label, 0, 18).'..';

		$active = okupanel_fed_is_node_active($n);
		ob_start();

		?>
		<div>
			<label class="<?= ($active ? 'okupanel-fedswitch-active' : 'okupanel-fedswitch-inactive') ?>"><span class="okupanel-fedswitch-btn-wrap"><a href="<?= esc_attr(okupanel_fed_get_node_switch_url($n)) ?>" class="okupanel-fedswitch-btn"><i class="fa fa-check"></i></a><span class="okupanel-fedswitch-label"><a href="<?= esc_attr(okupanel_fed_get_node_url($n)) ?>"><?= htmlentities($button_label) ?></span></span><a href="<?= okupanel_fed_get_node_public_url($n) ?>" title="<?= esc_attr(__('See website', 'okupanel')) ?>"><i class="fa fa-angle-right"></i></a></label>
		</div>
		<?php

		$buttons[] = ob_get_clean();
	}
	ob_start();
	?>
	<style>
	.okupanel_fed_switch {
	    color: #ddd;
		padding: 9px 0 15px 41px;
		font-size: 14.4px;
		font-family: Roboto;
	}
	.okupanel-fedswitch-inactive .okupanel-fedswitch-btn i {
		display: none;
	}
	.okupanel-fedswitch-inactive .okupanel-fedswitch-btn:hover i {
		display: inline-block;
	}
	.okupanel-fedswitch-active .okupanel-fedswitch-btn:hover i {
		display: none;
	}
	.okupanel-fedswitch-btn-own .okupanel-fedswitch-btn i {
		display: inline-block !important;
	}
	.okupanel-fedswitch-btn {
		padding: 7px 0 0 6px;
		display: inline-block;
		vertical-align: top;
		background: #333;
		height: 32px;
		width: 27px;
		box-sizing: border-box;
	}
	.okupanel-fedswitch-btn,
	a.okupanel-fedswitch-btn:hover {
		color: white;
	}
	.okupanel_fed_switch label {
		padding: 0;
		display: inline-block;
		border: 1px solid #aaa;
		vertical-align: top;
		margin-bottom: 6px;
		cursor: pointer;
		position: relative;
	}
	.okupanel-fed-switch-content i {
		font-size: 18px;
		margin: 0 4px 0 3px;
		display: inline-block;
		vertical-align: top;
		float: left;
	}
	.okupanel-fed-switch-content span {
		margin-left: 36px;
		display: block;
		font-size: 14.4px;
	}
	.okupanel_fed_switch label.okupanel-fedswitch-btn-own {
		cursor: default;
	}
	.okupanel_fed_switch label > a,
	.okupanel_fed_switch label .okupanel-fedswitch-label a {
		color: #ccc;
	}
	.okupanel_fed_switch label > a i {
		font-size: 18px;
		vertical-align: top;
		margin: 0;
		background: #333;
		height: 32px;
		padding: 7px 6px 0;
		box-sizing: border-box;
	}
	.okupanel_fed_switch label > a:hover i {
		background: #555;
	}
	.okupanel-fedswitch-label {
		padding: 8px 8px 0 8px;
		display: inline-block;
		vertical-align: top;
	}
	.okupanel_fed_switch input {
		position: fixed;
		top: -99999px;
		left: -99999px;
	}
	</style>
	<?php
	$style = ob_get_clean();
	return '<div class="okupanel_fed_switch">'.$style.implode('', $buttons).'</div>';
}


add_filter('okulec_show_event_collective', function($show, $e, $context){
	// @TODO: pass collective name, logo, URL and networks through okugraph

	if (!empty($e['node']) || (OKUPANEL_FED_ACTIVE && okupanel_fed_get_active()))
		return false;
	return $show;
}, 0, 3);


add_filter('okupanel_get_floor_from_location', function($false, $location, $for_sorting){
	if ($false === false && OKUPANEL_FED_ACTIVE && okupanel_fed_get_active())
		return null;
	return $false;
}, 0, 3);

add_filter('okupanel_tr_mobile_metas', function($metas, $e){
	if (OKUPANEL_FED_ACTIVE && okupanel_fed_get_active())
		$metas = okupanel_fed_get_federated_name($e);//.($metas ? ' – '.$metas : '');
	return $metas;
}, 0, 2);

add_filter('okupanel_js_extra_vars', function($arr){
	$arr['fed_enabled'] = OKUPANEL_FED_ACTIVE ? (!empty($_REQUEST['bookmarkme']) ? $_REQUEST['bookmarkme'] : true) : false;
	return $arr;
});

add_filter('okupanel_skip_event', function($skip, $e, $is_featured, $opts){
	if ($is_featured && OKUPANEL_FED_ACTIVE && okupanel_fed_get_active())
		return true;
	return $skip;
}, 0, 4);


add_filter('okupanel_show_most_important', function($true){
	if (OKUPANEL_FED_ACTIVE && okupanel_fed_get_active())
		return false;
	return $true;
});


add_filter('okupanel_new_fetched_event', function($nevent, $spl_args, $url, $format){
	if (!empty($spl_args['NODE']))
		$nevent['node'] = $spl_args['NODE'];
	return $nevent;
}, 0, 4);

add_action('okupanel_interface_ics_after_fields', function(){
	?>
	<div class="okupanel-field">
		<label><?= __('Virtual nodes', 'okupanel') ?>:</label>
		<div class="okupanel-field-inner">
			<?php
			$nodes = get_option('okupanel_vnodes', array());

			$empty = !$nodes;
			for ($i=0; $i < ($empty ? 2 : 1); $i++)
				$nodes[] = array(
					'blank' => !$i,
					'short_name' => '',
					'name' => '',
					'url' => '',
					'address' => '',
					'address_url' => '',
				);
			?>
			<?php
			static $js = false;
			if (!$js){
				$js = true;
				?>
				<script>
					function okupanel_multi_hook_item(item){

					}
					jQuery(document).ready(function(){
						jQuery('.okupanel-multi').each(function(){
							var m = jQuery(this);
							m.find('.okupanel-multi-item').each(function(){
								okupanel_multi_hook_item(jQuery(this));
							});
						});
					});
					function okupanel_multi_add(btn){
						var m = jQuery(btn).closest('.okupanel-multi');;
						var bl = m.find('.okupanel-multi-item-blank');
						var item = bl.clone(false).removeClass('okupanel-multi-item-blank').insertBefore(bl);
						okupanel_multi_hook_item(item);
						return false;
					}
					function okupanel_multi_delete(btn){
						jQuery(btn).closest('.okupanel-multi-item').remove();
						return false;
					}
				</script>
				<style>
				.okupanel-multi-item-blank {
					display: none;
				}
				</style>
				<?php
			}
			?>
			<div class="okupanel-multi okupanel-nodes">
				<table border="1">
					<tr>
						<th>Short name (7 char max, ID)</th>
						<th>Name (20 char max)</th>
						<th>Agenda URL or website</th>
						<th>Address</th>
						<th>Address url</th>
						<th>&nbsp;</th>
					</tr>
				<?php
				foreach ($nodes as $n){
					?>
					<tr class="okupanel-multi-item<?php if (!empty($n['blank'])) echo ' okupanel-multi-item-blank'; ?>">
						<td><input type="text" name="okupanel_vnode_short_name[]" value="<?= esc_attr($n['short_name']) ?>" /></td>
						<td><input type="text" name="okupanel_vnode_name[]" value="<?= esc_attr($n['name']) ?>" /></td>
						<td><input type="url" name="okupanel_vnode_url[]" value="<?= esc_attr($n['url']) ?>" /></td>
						<td><input type="text" name="okupanel_vnode_address[]" value="<?= esc_attr($n['address']) ?>" /></td>
						<td><input type="url" name="okupanel_vnode_address_url[]" value="<?= esc_attr($n['address_url']) ?>" /></td>
						<td class="okupanel-multi-tr-actions"><a onclick="return okupanel_multi_delete(this);" class="okupanel-multi-delete" href="#"><i class="fa fa-trash"></i></a></td>
					</tr>
					<?php
				}
				?>
				</table>
				<div class="okupanel-multi-actions"><button class="okupanel-multi-add" onclick="return okupanel_multi_add(this);"><i class="fa fa-plus"></i> Add virtual node</button></div>
			</div>
			<div><?= __('Virtual nodes are nodes without own okupanel installation. That is, only an iCalendar URL. These virtual nodes can be linked appending NODE=XXX to a URL of the previous field, XXX being the short name in this table.', 'okupanel') ?></div>
		</div>
	</div>
	<div class="okupanel-field">
		<label><?= __('Do not consider this WP a node', 'okupanel') ?>:</label>
		<div class="okupanel-field-inner">
			<input type="checkbox" name="okupanel_fed_only" <?php if (get_option('okupanel_fed_only', false)) echo 'checked'; ?>/>
		</div>
	</div>
	<div class="okupanel-field">
		<label><?= __('Always show rooms on main view', 'okupanel') ?>:</label>
		<div class="okupanel-field-inner">
			<input type="checkbox" name="okupanel_fed_force_show_rooms" <?php if (get_option('okupanel_fed_force_show_rooms', false)) echo 'checked'; ?>/>
		</div>
	</div>
	<?php
});

add_action('okupanel_save_settings', function(){

	$length = count($_POST['okupanel_vnode_short_name']) - 1;
	$nodes = array();
	for ($i=0; $i<$length; $i++)
		$nodes[] = array(
			'short_name' => sanitize_text_field($_POST['okupanel_vnode_short_name'][$i]),
			'name' => sanitize_text_field($_POST['okupanel_vnode_name'][$i]),
			'url' => esc_url($_POST['okupanel_vnode_url'][$i]),
			'address' => sanitize_text_field($_POST['okupanel_vnode_address'][$i]),
			'address_url' => sanitize_text_field($_POST['okupanel_vnode_address_url'][$i]),
		);
	update_option('okupanel_vnodes', $nodes);

	foreach (array('fed_only', 'fed_force_show_rooms') as $k)
		update_option('okupanel_'.$k, !empty($_POST['okupanel_'.$k]) && $_POST['okupanel_'.$k] !== 'false');
});


