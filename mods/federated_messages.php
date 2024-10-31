<?php
// gives the [okupanel_federated_message_urgent] and [okupanel_federated_message_regular] shortcodes to put in the sidebar, showing all messages (respectively urgent and regular ones) shared between federated nodes, and those locally set

if (!defined('ABSPATH'))
	exit();

// TODO: add frases to shortcode atts ("se necesitan..") or options
// add custom link to message and link on frontend
// limit messages to 30(?) characters (both on textarea and while printing)
// allow regenerating the secret url
// filtrar la URL secreta para root_okupanels
// update and translate .po
// correct moving=1 when there is not much content
// message to setup/confirm the cron job?
// check federation switch after autorefresh
// check timeline in the past


function okupanel_fedmsg_random_int($min, $max) {
	if (function_exists('random_int'))
		return random_int($min, $max);

	$range = $counter = $max - $min;
	$bits = 1;

	while ($counter >>= 1) {
		++$bits;
	}

	$bytes = (int)max(ceil($bits/8), 1);
	$bitmask = pow(2, $bits) - 1;

	if ($bitmask >= PHP_INT_MAX) {
		$bitmask = PHP_INT_MAX;
	}

	do {
		$result = hexdec(
			bin2hex(
				function_exists('mcrypt_create_iv')
				? mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM)
				: (
					function_exists('random_bytes')
					? random_bytes($bytes)
					: okupanel_fedmsg_random_string($bytes)
				)
			)
		) & $bitmask;
	} while ($result > $range);

	return $result + $min;
}


function okupanel_fedmsg_random_string($length){
	$bytes = '';
	while (strlen($bytes) < $length)
	  $bytes .= chr(mt_rand(0, 255));
	return $bytes;
}

if (!function_exists('fedmsg_random_str')){
	function okupanel_fedmsg_random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'){
		$str = '';
		$max = mb_strlen($keyspace, '8bit') - 1;
		for ($i = 0; $i < $length; ++$i) {
			$str .= $keyspace[okupanel_fedmsg_random_int(0, $max)];
		}
		return $str;
	}
}

add_action('okupanel_print_extra_textarea_fields_2', 'okupanel_fedmsg_fields');
function okupanel_fedmsg_fields_admin(){
	return okupanel_fedmsg_fields();
}
function okupanel_fedmsg_fields($stand_page = false){
	$token = get_option('okupanel_fedmsg_urgent_token', false);
	if (!$token){
		$token = okupanel_fedmsg_random_str(30);
		update_option('okupanel_fedmsg_urgent_token', $token);
	}
	$mode = get_option('okupanel_fedmsg_mode', 'local');
	?>
	<style>
		.okupanel-fedmsg-secret-url,
		.okupanel-fedmsg-secret-url a {
			color: #A85C29;
			font-weight: bold
		}
		.okupanel-field-inner-radios > div {
			margin: 10px 0;
			clear: both;
			position: relative;
			padding-left: 0;
		}
	</style>
	<div class="okupanel-field okupanel-settings-field okupanel-settings-field-fedmsg">
		<label><?= __('(Federated) message:', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><textarea maxlength="70" name="okupanel_fedmsg_msg"><?= esc_textarea(get_option('okupanel_fedmsg_msg', '')) ?></textarea></div>
			<div><?= sprintf(__('Up to %d characters', 'okupanel'), 70) ?></div>
		</div>
	</div>
	<div class="okupanel-field">
		<label>&nbsp;</label>
		<div class="okupanel-field-inner okupanel-field-inner-radios">
			<div><input type="radio" name="okupanel_fedmsg_mode" value="local"<?php if ($mode == 'local') echo ' checked'; ?> /> <b><?= __('LOCAL MODE', 'okupanel') ?></b>: <?= __('display the status locally only.', 'okupanel') ?></div>
			<div><input type="radio" name="okupanel_fedmsg_mode" value="broadcast"<?php if ($mode == 'broadcast') echo ' checked'; ?> /> <b><?= __('INTER-OKUPANEL MODE', 'okupanel') ?></b>: <?= __('display the status both locally and on federated okupanels.', 'okupanel') ?></div>
			<div><input type="radio" name="okupanel_fedmsg_mode" value="alert"<?php if ($mode == 'alert') echo ' checked'; ?> /> <b><?= __('ALERT MODE', 'okupanel') ?></b>: <?= __('display the status in red, higher in the sidebar, and both locally and on federated okupanels - <b>Use only on very urgent needs!</b>', 'okupanel') ?></div>

			<div class="okupanel-fedmsg-secret-url"><?= ($stand_page ? __('Remember to keep the current URL secret!', 'okupanel') : sprintf(__('Bookmark <a %s>this secret URL</a> into your laptop and phone to update the 3 previous fields without logging', 'okupanel'), 'target="_blank" href="'.okupanel_url('trigger_urgent_message/'.$token.'/').'" style="text-decoration: underline !important"')) ?></div>
			<?php
				$sidebar = get_option('okupanel_right_panel', '');
				if (!$stand_page
					|| !preg_match('#\[okupanel_federated_message_regular\]#', $sidebar)
					|| !preg_match('#\[okupanel_federated_message_regular\]#', $sidebar)){
					?><div><?= __('You must use [okupanel_federated_message_urgent] and [okupanel_federated_message_regular] in your okupanel\'s sidebar, repectively for urgent and regular message (both yours and other okupanels\' messages).', 'okupanel') ?></div><?php
				}
			?>
		</div>
	</div>
	<?php
}

add_action('okupanel_save_settings', function(){
	okupanel_fedmsg_save_settings();
});

function okupanel_fedmsg_save_settings(){
	update_option('okupanel_fedmsg_mode', $_POST['okupanel_fedmsg_mode']);
	foreach (array('fedmsg_msg') as $k)
		update_option('okupanel_'.$k, sanitize_text_field(@$_POST['okupanel_'.$k]));

}

add_action('okupanel_action_trigger_urgent_message', function($input_token){
	$token = get_option('okupanel_fedmsg_urgent_token', false);
	if ($token && $token == $input_token){
		@session_start();
		if (!empty($_POST['submit'])){
			okupanel_fedmsg_save_settings();
			$_SESSION['okupanel_fedmsg_saved'] = true;
			?>
			<script> window.location = window.location;</script>
			<?php
			exit();
		} else if (!empty($_SESSION['okupanel_fedmsg_saved'])){
			$_SESSION['okupanel_fedmsg_saved'] = 0;
			echo '<div>'.__('Fields saved!', 'okupanel').'</div>';
		}
		?>
		<style>
			h1 {
				font-size: 22px;
				margin-bottom: 10px;
			}
			textarea {
				width: 100%;
				height: 100px;
				padding: 5px 10px;
			}
			input[type=checkbox] {
				position: absolute;
				left: 0;
			}
			form {
				padding: 20px;
				font-size: 18px;
			}
			.submit {
				padding: 10px 20px;
			}
			.okupanel-settings-field-fedmsg > label {
				display: none;
			}
		</style>
		<form action="" method="POST">
			<h1><?= sprintf(__('Status of %s', 'okupanel'), get_option('okupanel_fed_name', '')) ?>:</h1>
			<?php okupanel_fedmsg_fields(true) ?>
			<br>
			<input type="submit" value="<?= esc_attr(__('Update', 'okupanel')) ?>" name="submit" class="submit" />
		</form>
		<?php
		exit();
	}
}, 0, 1);


add_shortcode('okupanel_federated_message_urgent', function(){
	return okupanel_federated_message(true);
});

add_shortcode('okupanel_federated_message_regular', function(){
	return okupanel_federated_message(false);
});

add_filter('okupanel_action_graph_output', function($graph){
	$mode = get_option('okupanel_fedmsg_mode', 'local');
	if (($msg = trim(get_option('okupanel_fedmsg_msg', '')))
		&& in_array($mode, array('broadcast', 'alert')))
		$graph['message'] = array(
			'msg' => $msg,
			'urgent' => $mode == 'alert'
		);
	return $graph;
});

function okupanel_federated_message($urgent){
	$mode = get_option('okupanel_fedmsg_mode', 'local');

	$msgs = array();
	if (($msg = trim(get_option('okupanel_fedmsg_msg', ''))) && $urgent == ($mode == 'alert'))
		$msgs[] = array(
			'node_name' => get_option('okupanel_fed_name', ''),
			'msg' => $msg,
			'urgent' => $urgent,
		);

	foreach (okupanel_fed_get_nodes() as $node)
		if ($node['result'] && isset($node['result']['message'], $node['result']['message']['msg']) && ($msg = trim($node['result']['message']['msg'])) && $urgent == $node['result']['message']['urgent'])
			$msgs[] = array(
				'node_name' => $node['result']['federated_name'],
				'msg' => $msg,
				'urgent' => $urgent,
			);

	if (!empty($_GET['add_fake_msg'])){
		$msgs[] = $urgent ? array(
			'node_name' => 'La Villana',
			'msg' => 'Falsa alerta de desalojo',
			'urgent' => true,
		) : array(
			'node_name' => 'Centro EVA',
			'msg' => 'Campaña de defensa #SomosLaEVA',
			'urgent' => false,
		);
		if (!$urgent)
			$msgs[] = array(
				'node_name' => 'Sputnik',
				'msg' => 'Ha pasado la tormenta!',
				'urgent' => true,
			);
	}

	if (!$msgs)
		return '';

	ob_start();
	//if (empty($_REQUEST['action'])){
		$color = $urgent ? 'red' : '#FFFF00';
		?><style>
			.okupanel-fedmsg-wrap.okupanel-fedmsg-<?= ($urgent ? 'urgent' : 'not-urgent') ?> .okupanel-fedmsg-col-name {
				margin: 4px 0 0 0;
				font-size: 18px;
				font-weight: bold;
			}
			.okupanel-fedmsg.okupanel-most-important.okupanel-fedmsg-<?= ($urgent ? 'urgent' : 'not-urgent') ?> .okupanel-fedmsg-wrap,
			.okupanel-fedmsg.okupanel-most-important.okupanel-fedmsg-<?= ($urgent ? 'urgent' : 'not-urgent') ?> .okupanel-fedmsg-wrap *,
			.okupanel-fedmsg.okupanel-most-important.okupanel-fedmsg-<?= ($urgent ? 'urgent' : 'not-urgent') ?>,
			.okupanel-most-important.okupanel-fedmsg-<?= ($urgent ? 'urgent' : 'not-urgent') ?> > i {
				<?php if ($urgent){ ?>
					color: <?= $color ?> !important;
					border-color: <?= $color ?> !important;
				<?php } ?>
			}
			<?php if ($urgent){ ?>
				.okupanel-fedmsg.okupanel-most-important.okupanel-fedmsg-<?= ($urgent ? 'urgent' : 'not-urgent') ?> .okupanel-fedmsg-wrap .okupanel-fedmsg-date.okupanel-most-important-location {
					color: white !important;
				}
			<?php } ?>
			.okupanel-most-important-right.okupanel-fedmsg-wrap {
				margin-bottom: 6px;
			}
		</style>
		<?php
	//}
	foreach ($msgs as $msg){
		?>
		<div class="okupanel-most-important-right okupanel-fedmsg-wrap">
			<div class="okupanel-fedmsg-date okupanel-most-important-location">
				<?php
					echo htmlentities(okupanel_substr($msg['node_name'], 0, 20)).':';
				?>
			</div>
			<div class="okupanel-fedmsg-col">
				<div class="okupanel-fedmsg-col-name"><?= htmlentities(okupanel_substr($msg['msg'], 0, 70)) ?></div>
			</div>
		</div>
		<?php
	}
	return '<div class="okupanel-fedmsg okupanel-most-important okupanel-fedmsg-'.($urgent ? 'urgent' : 'not-urgent').'"><i class="fa fa-'.($urgent ? 'warning' : 'commenting-o').'"></i>'.ob_get_clean().'<div style="clear: both;"></div></div>';
}
