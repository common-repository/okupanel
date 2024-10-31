<?php

if (!defined('ABSPATH'))
	exit();

function okupanel_ucfirst($str){
	return okupanel_strtoupper(okupanel_substr($str, 0, 1)).okupanel_substr($str, 1);
}

function okupanel_strtoupper($str){
	return function_exists('mb_strtoupper') ? mb_strtoupper($str) : strtoupper($str);
}

function okupanel_strtolower($str){
	return function_exists('mb_strtolower') ? mb_strtolower($str) : strtolower($str);
}

function okupanel_substr($str, $start, $length = null){
	if (!$length)
		$length = function_exists('mb_strlen') ? mb_strlen($str) : strlen($str);
	return function_exists('mb_substr') ? mb_substr($str, $start, $length) : substr($str, $start, $length);
}

function okupanel_human_time_diff($diff, $longNotation = false){
	$not_hour = $diff % HOUR_IN_SECONDS;
	$h = ($diff - $not_hour) / HOUR_IN_SECONDS;
	$m = ceil($not_hour / MINUTE_IN_SECONDS);

	$str = array();
	if (!$longNotation || $h)
		$str[] = sprintf($longNotation ? ($h > 1 ? __('%s hours', 'okupanel') : __('%s hour', 'okupanel')) : __('%sH', 'okupanel'), $h);
	if ($m)
		$str[] = $longNotation ? sprintf(__('%s minutes', 'okupanel'), $m) : str_pad($m, 2, '0');
	return $longNotation ? okupanel_plural($str) : implode('', $str);
}

function okupanel_plural($str, $sep = null){
	$last = array_pop($str);
	if (!$str) return $last;
	return implode(', ', $str).($sep ? $sep : ' '.__('and', 'okupanel').' ').$last;
}


function okupanel_is_page($only_panel = false){
	global $wp_query;
	if ($only_panel === false || $only_panel == 'panel'){
		if (is_singular()){
			$ids = explode(',', preg_replace('#(\s+)#', '', get_option('okupanel_page_ids', '')));
			if (in_array(get_the_ID(), $ids))
				return true;
		}
	}
	return apply_filters('okupanel_is_page', ($wp_query->is_main_query() && !empty($wp_query->query_vars['okupanel_action']) && (!$only_panel || $wp_query->query_vars['okupanel_action'] == $only_panel))
		|| ((!$only_panel || $only_panel == 'settings') && is_admin() && !empty($_GET['page']) && in_array($_GET['page'], array('okupanel-settings'))));
}


function okupanel_pretty_json($json){
	if (!is_string($json))
		$json = json_encode($json);

    $tc = 0;        //tab count
    $r = '';        //result
    $q = false;     //quotes
    $t = "\t";      //tab
    $nl = "\n";     //new line

    for($i=0;$i<strlen($json);$i++){
        $c = $json[$i];
        if($c=='"' && $json[$i-1]!='\\') $q = !$q;
        if($q){
            $r .= $c;
            continue;
        }
        switch($c){
            case '{':
            case '[':
                $r .= $c . $nl . str_repeat($t, ++$tc);
                break;
            case '}':
            case ']':
                $r .= $nl . str_repeat($t, --$tc) . $c;
                break;
            case ',':
                $r .= $c.' ';
                if($json[$i+1]!='{' && $json[$i+1]!='[') $r .= $nl . str_repeat($t, $tc);
                break;
            case ':':
                $r .= $c . ' ';
                break;
            default:
                $r .= $c;
        }
    }
    return stripslashes(str_replace("\\r\\n", "\\\\r\\\\n", str_replace("\t", '<span style="width: 50px; display: inline-block;"></span>', nl2br(htmlentities($r)))));
}


function okupanel_fetch($url, $return_json = false, $type = 'get', $data = array(), $headers = array(), $timeout = 15, $no_ssl_check = false){
//return false;
	if ($type == 'get' && $data)
		$url .= '?'.http_build_query($data);

	$process = curl_init();
	curl_setopt($process, CURLOPT_URL, $url);
	if ($headers)
		curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($process, CURLOPT_TIMEOUT, $timeout);
	if ($type == 'post' && $data)
		curl_setopt($process, CURLOPT_POSTFIELDS, $data);
	curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);

	if ($no_ssl_check){
		curl_setopt($process, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);
	}

	curl_setopt($process, CURLOPT_FOLLOWLOCATION, true);

	$ret = curl_exec($process);
	$error = curl_error($process);

	curl_close($process);

	if (current_user_can('manage_options')){
		if (okupanel_is_cron())
			echo '<b>[okupanel][cron] fetched '.$url.'</b><br>';
		if ($error && (okupanel_is_cron() || !empty($_GET['okupanel_print_errors']))){
			echo '<br><div style="color:red">'.$error.'</div><br>';
			die();
		}
	}

	if (!$return_json)
		return $ret;

	try {
		$json = json_decode($ret);
	} catch (Exception $e){
		return false;
	}
	return $json;
}

add_shortcode('okupanel', 'okupanel_shortcode');
function okupanel_shortcode(){
	ob_start();
	okupanel_print_popup();
	okupanel_print_panel();
	return ob_get_clean();
}

function okupanel_current_url(){
	return isset($_REQUEST['okupanel_extra_vars'], $_REQUEST['okupanel_extra_vars']['current_url']) ? $_REQUEST['okupanel_extra_vars']['current_url'] : add_query_arg(false, false);
}

add_action('wp_head', 'okupanel_print_js_vars', -99999);
function okupanel_print_js_vars(){
	if (!okupanel_is_page())
		return;
	?>
	<script type="text/javascript">

		// pass variables to JS
		var OKUPANEL = <?= json_encode(apply_filters('okupanel_main_js_var', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'autorefresh_frequency' => ((!empty($_GET['fullscreen']) ? OKUPANEL_FULLSCREEN_REFRESH_FREQUENCY : OKUPANEL_CLIENT_REFRESH_FREQUENCY) * MINUTE_IN_SECONDS) * 1000, // MS
			'desynced_error_delay' => (2 * (OKUPANEL_FULLSCREEN_REFRESH_FREQUENCY * MINUTE_IN_SECONDS) + 10) * 1000, // MS
			'simulate_desynced' => !empty($_GET['simulate_desynced']),
			'now' => strtotime(date_i18n('Y-m-d H:i:s')), // pass server time to JS
			'fullscreen' => !empty($_GET['fullscreen']),
			'version' => OKUPANEL_VERSION,
			'loading' => __('Loading', 'okupanel'),
			'extra_vars' => apply_filters('okupanel_js_extra_vars', array(
				'current_url' => okupanel_current_url(),
			)),
		))) ?>;

	</script>
	<style>

		@font-face {
			font-family: 'led_board';
			src: url('<?= OKUPANEL_URL ?>/assets/font/led_board/led_board-7.ttf') format('truetype'); /* Chrome 4+, Firefox 3.5, Opera 10+, Safari 3—5 */
		}
		@font-face {
			font-family: 'soljik_dambaek';
			src: url('<?= OKUPANEL_URL ?>/assets/font/soljik_dambaek/Soljik-Dambaek.ttf') format('truetype'); /* Chrome 4+, Firefox 3.5, Opera 10+, Safari 3—5 */
		}

		@font-face {
			font-family: 'F25_bank_printer';
			src: url('<?= OKUPANEL_URL ?>/assets/font/F25_bank_printer/F25_Bank_Printer.ttf') format('truetype'); /* Chrome 4+, Firefox 3.5, Opera 10+, Safari 3—5 */
		}

		@font-face {
			font-family: 'nasalization';
			src: url('<?= OKUPANEL_URL ?>/assets/font/nasalization/nasalization-rg.ttf') format('truetype'); /* Chrome 4+, Firefox 3.5, Opera 10+, Safari 3—5 */
		}

		@font-face {
			font-family: 'roboto';
			src: url('<?= OKUPANEL_URL ?>/assets/font/roboto/Roboto-Light.ttf') format('truetype'); /* Chrome 4+, Firefox 3.5, Opera 10+, Safari 3—5 */
		}

	</style>
	<?php
}

function okupanel_print_popup(){
	?>
	<div id="okupanel-popup">
		<div class="okupanel-popup-bg"></div>
		<div class="okupanel-popup-inner">
			<div class="okupanel-popup-close"><i class="fa fa-times"></i></div>
			<div class="okupanel-popup-title">
				<div class="okupanel-popup-title-label"></div>
				<div class="okupanel-popup-link"></div>
			</div>
			<div class="okupanel-popup-content">
			</div>
		</div>
	</div>
	<?php
	do_action('okupanel_print_popup_after');
}



function okupanel_parse_time($str, $return_gmt = false){
	static $offset = null;
	if ($offset === null)
		$offset = intval(get_option('gmt_offset', 0) * HOUR_IN_SECONDS);
	$time = strtotime($str);

	if (preg_match('#.*Z$#i', $str)) // string is gmt
		return $return_gmt ? $time : $time + $offset;

	return $return_gmt ? $time - $offset : $time;
}


function okupanel_stripslashes($str){
	return preg_replace('#\\\\,#', ',', $str);
}

function okupanel_get_network_accounts(){
	return apply_filters('okupanel_network_accounts', array(
		'feed' => get_bloginfo('rss2_url'),
	));
}

function okupanel_add_to_summary(&$tr, $after_key_in_order, $new_key, $html){
	if (!$after_key_in_order)
		$tr['summary'][$new_key] = $html;
	else
		foreach ($after_key_in_order as $after_key)
			if (($i = array_search($after_key, array_keys($tr['summary']))) !== false){
				$arr = array();
				$arr['collective'] = $html;
				$tr['summary'] = array_merge(array_slice($tr['summary'], 0, $i+1), $arr, array_slice($tr['summary'], $i+1));
				break;
			}
}

function okupanel_add_tr_cell(&$tr, $after_key_in_order, $new_key, $html){
	$new = array();
	$new[$new_key] = $html;
	foreach ($after_key_in_order as $after_key){
		if (($i = array_search($after_key, array_keys($tr['tds']))) !== false){
			$tr['tds'] = array_merge(array_slice($tr['tds'], 0, $i+1), $new, array_slice($tr['tds'], $i+1));
			break;
		}
	}
}

function okupanel_url($okupanel_uri = null){
	return apply_filters('okupanel_url', home_url('okupanel/'.($okupanel_uri !== '/' ? $okupanel_uri : '')), $okupanel_uri);
}
