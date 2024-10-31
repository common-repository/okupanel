<?php

// add "turnos de kafeta" events automatically from a pad, and adds the [okupanel_today_kafeta] shortcode


if (!defined('ABSPATH'))
	exit();


add_filter('okupanel_textline_fields', 'okupanel_kafeta_pad_fields_textline_save');
function okupanel_kafeta_pad_fields_textline_save($fields){
	$fields[] = 'pad_kafeta_url';
	return $fields;
}

add_action('okupanel_print_extra_textarea_fields_2', 'okupanel_pad_kafeta_fields');
function okupanel_pad_kafeta_fields(){
	?>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Pad kafeta URL', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><input type="text" name="okupanel_pad_kafeta_url" value="<?= esc_attr(get_option('okupanel_pad_kafeta_url', '')) ?>" /></div>
		</div>
	</div>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Kafeta\'s openng days', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div>
			<?php
			$monday = strtotime('next monday');
			$days = get_option('okupanel_pad_kafeta_days', array(0, 1, 2, 3, 4, 5));
			for ($i=0; $i<7; $i++){
				?>
				<label><input type="checkbox" name="okupanel_pad_kafeta_days_<?= $i ?>" <?php if (in_array($i, $days)) echo 'checked'; ?> /> <?= date_i18n('D', $i ? strtotime('+'.$i.' days', $monday) : $monday) ?></label>
				<?php
			}
			?>
			</div>
		</div>
	</div>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Kafeta opens at', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><input type="text" name="okupanel_pad_kafeta_open" value="<?= esc_attr(get_option('okupanel_pad_kafeta_open', '')) ?>" /></div>
			<div>Format: HH:mm</div>
		</div>
	</div>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Kafeta closes at', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><input type="text" name="okupanel_pad_kafeta_close" value="<?= esc_attr(get_option('okupanel_pad_kafeta_close', '')) ?>" /></div>
			<div>Format: HH:mm</div>
		</div>
	</div>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Alert missing kafeta turn (regular call)', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><input type="number" step="1" min="0" name="okupanel_pad_kafeta_missing_days_ahead" value="<?= esc_attr(get_option('okupanel_pad_kafeta_missing_days_ahead', '10')) ?>" style="width: 60px" /> days ahead</div>
		</div>
	</div>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Alert missing kafeta turn as urgent/red', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><input type="number" step="1" min="0" name="okupanel_pad_kafeta_missing_days_ahead_urgent" value="<?= esc_attr(get_option('okupanel_pad_kafeta_missing_days_ahead_urgent', '5')) ?>" style="width: 60px" /> days ahead</div>
		</div>
	</div>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Number of missing kafeta turn alerts', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><input type="number" step="1" min="1" name="okupanel_pad_kafeta_missing_amount" value="<?= esc_attr(get_option('okupanel_pad_kafeta_missing_amount', '1')) ?>" style="width: 60px" /> alerts</div>
			<div>Format: numeric</div>
		</div>
	</div>
	<?php
}

add_action('okupanel_save_settings', function(){
	$days = array();
	for ($i=0; $i<7; $i++)
		if (!empty($_POST['okupanel_pad_kafeta_days_'.$i]))
			$days[] = $i;
	update_option('okupanel_pad_kafeta_days', $days);

	update_option('okupanel_pad_kafeta_open', $_POST['okupanel_pad_kafeta_open']);
	update_option('okupanel_pad_kafeta_close', $_POST['okupanel_pad_kafeta_close']);
	update_option('okupanel_pad_kafeta_missing_days_ahead', $_POST['okupanel_pad_kafeta_missing_days_ahead']);
	update_option('okupanel_pad_kafeta_missing_days_ahead_urgent', $_POST['okupanel_pad_kafeta_missing_days_ahead_urgent']);
	update_option('okupanel_pad_kafeta_missing_amount', $_POST['okupanel_pad_kafeta_missing_amount']);
});

function okupanel_kafeta_pad_get_events($pad_url, $debug = false){
	static $events = null;
	if ($events !== null)
		return $events;

if (
($cache = get_option('okupanel_kafeta_pad_'.base64_encode($pad_url)))
&& $cache['time'] > strtotime(okupanel_is_cron() ? '-6 minutes' : '-10 minutes')
&& !$debug && (!current_user_can('manage_options') || empty($_GET['update']))){
if (!empty($_GET['debug2'])) die("CACHE");
return $cache['v'];
}

if (!($content = okupanel_fetch(rtrim($pad_url, '/').'/export/txt'))){
$events = false;
if (!empty($_GET['debug2'])) die("bad");
return $cache ? $cache['v'] : false;
}
if (!empty($_GET['debug2'])) die("OK");
	//if (!empty($_GET['debug']) && current_user_can('manage_options'))
           //     print_r($content);

	$months = array();
	for ($m=1; $m<=12; $m++)
		$months[$m] = date_i18n('F', mktime(0,0,0,$m, 1, date('Y')));

	$months_data = array();

	if ($matches = preg_split('#\n\s*('.implode('|', $months).'|'.remove_accents(implode('|', $months)).')#ius', $content, -1, PREG_SPLIT_DELIM_CAPTURE)){

	     array_shift($matches);
		$is_month = true;
		while ($bit = array_shift($matches)){
		      	    if (!empty($_GET['debug4']) && current_user_can('manage_options'))
			       	print_r($bit);
			if ($is_month){
				$cmonth = null;
				foreach ($months as $m => $month){
					if (preg_match('#^('.$month.'|'.remove_accents($month).')#ius', $bit)){
						$cmonth = $m;
						break;
					}
				}
				if ($cmonth){
					$d = $last_day = strtotime(date('Y').'-'.str_pad($cmonth, 2, '0', STR_PAD_LEFT).'-28');
					if (isset($months_data[date('Y-m', $d)])){
						$cmonth = null;
						continue;
					}
					
					while (date_i18n('Y-m', strtotime('+1 day', $last_day)) == date_i18n('Y-m', $d))
						$last_day = strtotime('+1 day', $last_day);

					$ctime = strtotime(date('Y').'-'.str_pad($cmonth, 2, '0', STR_PAD_LEFT));
					if (abs(time() - $ctime) > 4 * MONTH_IN_SECONDS){
						$ctime = strtotime(date('Y', strtotime('-1 year')).'-'.str_pad($cmonth, 2, '0', STR_PAD_LEFT));
						if (abs(time() - $ctime) > 4 * MONTH_IN_SECONDS)
							$ctime = strtotime(date('Y', strtotime('+1 year')).'-'.str_pad($cmonth, 2, '0', STR_PAD_LEFT));
					}

					if (time() >= strtotime('+1 day', $last_day)){
						array_shift($matches);
						$is_month = true;
//						break;
					} else

					$is_month = false;
				}
			} else {
				if ($cmonth){
					$done = $cols = array();
					$last = 0;
					$bad_lines = 0;

					foreach (explode("\n", $bit) as $line){
					
						if (preg_match('#^.*?\s*([0-9]+)\s*:?(?:-*>)?\s*(.*)$#ius', $line, $m3) && !in_array(intval($m3[1]), $done) && intval($m3[1]) > $last){
							if ((intval($m3[1]) < 10 && $last > 15) || intval($m3[1]) > intval(date_i18n('d', $last_day)) || $bad_lines > 15)
								break;
							$done[] = $last = intval($m3[1]);
							$cols[$last] = preg_match('#^x+$#iu', trim($m3[2])) ? '' : trim($m3[2]);
							$bad_lines = 0;
						} else if (preg_match('#^\s*'.implode('|', $months).'#iu', $line)
							|| preg_match('#^\s*'.remove_accents(implode('|', $months)).'#iu', remove_accents($line)))
							break;
						else
							$bad_lines++;

					}
					$cmonth = null;
					if ($cols){
						$ncols = array();
						$min_date = get_date_from_gmt(date('Y-m-d H:i:s'));
						//echo 'min: '.$min_date.'<br>';
						foreach ($cols as $n => $col){
							$date = date('Y-m', $ctime).'-'.str_pad($n, 2, '0', STR_PAD_LEFT).' '.trim(get_option('okupanel_pad_kafeta_open', '23:59')).':00';
							if (empty($col))
								continue;

							if (date('Y-m-d', strtotime($date)) >= date('Y-m-d', strtotime($min_date))){
								//echo 'in '.$date.'<br>';

								// remove blocks that end with *
								$public_names = array();
								foreach (explode(',', $col) as $name)
									if ($name = trim($name)){
										$public_names[] = $name;
									}

								if ($public_names)
									$ncols[$n] = implode(', ', $public_names);

							}// else
								//echo 'out '.$date.'<br>';

							/* else if (!empty($col)) {
								echo $date.' < '.$min_date.'<br>';
							}*/
						}

						if ($ncols)
							$months_data[date('Y-m', $ctime)] = $ncols;
						if (count($months_data) >= 4)
							break;
					}
				}
				$is_month = true;
			}

		}

	}
	if ($debug){
		foreach ($months_data as $month => $cols){
			foreach ($cols as $n => $names){
				$d = strtotime($month.'-'.str_pad($n, 2, '0', STR_PAD_LEFT));
				echo date('Y-m-d', $d).': '.$names.'<br>';
			}
		}

		die();
	}
	if (!empty($_GET['debug3']) && current_user_can('manage_options')){
	                           print_r($months_data); die();
	}
	$events = array();
	foreach ($months_data as $month => $cols){
		foreach ($cols as $n => $names){
			$d = date_i18n('Y-m-d', strtotime($month.'-'.str_pad($n, 2, '0', STR_PAD_LEFT)));
			//echo 'adding: '.$d.' ('.$month.'-'.str_pad($n, 2, '0', STR_PAD_LEFT).'<br>';
			$events[$d] = $names;
		}
	}
	if (!empty($_GET['debug']) && current_user_can('manage_options'))
	                   print_r($events);
	if ($events)
		update_option('okupanel_kafeta_pad_'.base64_encode($pad_url), array('time' => time(), 'v' => $events));
	return $events;
}


add_action('admin_head', function(){
	if (current_user_can('manage_options') && !empty($_GET['pad_kafeta']) && ($url = get_option('okupanel_pad_kafeta_url', ''))){
		okupanel_kafeta_pad_get_events($url, true);
		die();
	}
});

add_filter('okupanel_events', function($events){
	$url = get_option('okupanel_pad_kafeta_url', '');
	if (!$url)
		return $events;
	$featured_dates = array();
	foreach ($events as $e)
		if (okupanel_is_featured($e))
			$featured_dates[] = date_i18n('Y-m-d', $e['start']);

	foreach (okupanel_kafeta_pad_get_events($url) as $date => $names)
		if (!in_array($date, $featured_dates) && ($names = okupanel_kafeta_pad_lint_name($names))){
			//echo 'inserting '.$names.' on '.$date.'<br>';
			$events[] = array(
				'origin' => 'kafeta-pad-mod',
				'id' => 'kafeta-pad-'.$date,
				'summary' => $names,
				'description' => '',
				'featured' => true,
				'location' => null,
				'status' => 'confirmed',
				'created' => strtotime('-1 month'),
				'created_gmt' => strtotime('-1 month'),
				'start' => strtotime(get_date_from_gmt($date.' '.trim(get_option('okupanel_pad_kafeta_open', '00:00')).':00')),
				'start_gmt' => strtotime(($date.' '.trim(get_option('okupanel_pad_kafeta_open', '00:00')).':00')),
				'end' => strtotime('+24 hours', strtotime(get_date_from_gmt($date.' '.trim(get_option('okupanel_pad_kafeta_close', '00:00')).':00'))),
				'end_gmt' => strtotime('+24 hours', strtotime(($date.' '.trim(get_option('okupanel_pad_kafeta_close', '00:00')).':00'))),
				'updated' => strtotime('-1 month'),
				'updated_gmt' => strtotime('-1 month'),
				'htmlLink' => null,
				'recurrence' => '',
				'exceptions' => array(),
			);
		}
	return $events;
});

function okupanel_kafeta_pad_lint_name($line){
	$names = array();
	foreach (explode(',', $line) as $name){
		$name = preg_replace('#\([^\)]*\)#', '', trim($name));
		$name = trim(preg_replace('#\s+#', ' ', $name));
		if (!preg_match('#\*\s*$#', $name))
			$names[] = $name;
	}
	return $names ? implode(', ', $names) : false;
}

add_shortcode('okupanel_today_kafeta', function($atts = array()){
	if (apply_filters('okupanel_skip_event', false, null, true, array()))
		return '';

	if (empty($atts))
		$atts = array();
	$atts += array(
		'title' => 'En kafeta %s',
	);

	ob_start();
	//if (empty($_REQUEST['action'])){
		?><style>
			.okupanel-today-kafeta-col-logo {
				margin: 0;
				width: 100px;
				float: right;
				margin-left: 16px;
				clear: right;
			}
			.okupanel-today-kafeta-col-logo img {
				width: 100%;
				margin: 0;
			}
			.okupanel-today-kafeta-col-name,
			.okupanel-today-kafeta-col-name a {
				margin: 4px 0 0 0;
				color: white;
				font-size: <?= (okupanel_is_fullscreen() ? '24' : '18') ?>px;
				font-weight: bold;
			}
		</style>
		<?php
	//}

	foreach (okupanel_get_all_events(false) as $e)
		if ($names = okupanel_is_featured($e)){

			$cols = array();
			foreach (explode(',', $names) as $name)
				if ($name = okupanel_kafeta_pad_lint_name($name)){
					$cols[] = array(
						'name' => $name,
						'id' => apply_filters('okupanel_featured_detect_post_id', null, $name)
					);
				}
			if (!$cols)
				continue;

			$i=0;
			foreach ($cols as $c){
				if (!empty($c['id']) && ($thumb_id = get_post_thumbnail_id($c['id']))){
					?>
					<div class="okupanel-today-kafeta-col">
						<?php
						echo '<div class="okupanel-today-kafeta-col-logo" style="'.($i ? 'margin-top: 9px;' : '').'"><a href="'.get_permalink($c['id']).'" target="_blank">'.wp_get_attachment_image($thumb_id, 'medium').'</a></div>';
						?>
					</div>
					<?php
					$i++;
				}
			}
			?>
			<div class="okupanel-most-important-right">
				<div class="okupanel-today-kafeta-date okupanel-most-important-location">
					<?php
						if (date_i18n('Y-m-d H:i', $e['start_gmt']) <= date_i18n('Y-m-d H:i')
							&& date_i18n('Y-m-d H:i', $e['end_gmt']) >= date_i18n('Y-m-d H:i'))
							$day = __('now', 'okupanel');

						else if (date_i18n('Y-m-d', $e['start_gmt']) == date_i18n('Y-m-d')){
							$day = __('today', 'okupanel');
							if (date_i18n('H:i') < OKUPANEL_PRECISE_DAY_BEFORE)
								$day .= ' '.date_i18n('l');

						} else if (date_i18n('Y-m-d', $e['start_gmt']) == date_i18n('Y-m-d', strtotime('+1 day'))){
							$day = __('tomorrow', 'okupanel');
							if (date_i18n('H:i') < OKUPANEL_PRECISE_DAY_BEFORE)
								$day .= ' '.date_i18n('l');

						} else {
							$diff = $e['start'] - strtotime(date('Y-m-d').' 00:00:00');
							$day = sprintf(__('el %s', 'okupanel'), date_i18n(__('l', 'okupanel'), $e['start']));
						}
						echo sprintf($atts['title'], $day).':';

						/*sprintf(
							$e['start_gmt'] > time()
							? __('%s at %s', 'okupanel')
							: __('%s since %s', 'okupanel'), $day, date_i18n('G:i', $e['start']));*/
					?>
				</div>
				<?php
				for ($i=0; $i<count($cols); $i++){
					?>
					<div class="okupanel-today-kafeta-col">
						<div class="okupanel-today-kafeta-col-name"><?= ($i ? '+ ' : '') ?><?php if (!empty($cols[$i]['id'])){ ?><a href="<?= get_permalink($cols[$i]['id']) ?>" target="_blank"><?php } ?><?= $cols[$i]['name'] ?><?php if (!empty($cols[$i]['id'])){ ?></a><?php } ?></div>
					</div>
					<?php
				} ?>
			</div>
			<?php
			return '<div class="okupanel-today-kafeta okupanel-most-important"><i class="fa fa-beer"></i>'.ob_get_clean().'<div style="clear: both;"></div></div>';
		}
	return ob_get_clean();
});

add_shortcode('okupanel_missing_kafeta', function(){

	if (apply_filters('okupanel_skip_event', false, null, true, array()))
		return '';

	if (empty($atts))
		$atts = array();
	$atts += array(
		'title' => 'Turnos de kafeta faltando',
		'singular_msg' => 'Se necesita turno de kafeta',
		'plural_msg' => 'Se necesitan turnos de kafeta',
		'amount' => get_option('okupanel_pad_kafeta_missing_amount', '1'),
	);

	ob_start();

	$featured = array();
	$max_date = trim(get_option('okupanel_pad_kafeta_missing_days_ahead', '10'));
	$max_date = $max_date ? strtotime('+'.$max_date.' days') : strtotime('+30 days');

	foreach (okupanel_get_all_events(false) as $e)
		if (($names = okupanel_is_featured($e)) && !in_array(date_i18n('Y-m-d', $e['start_gmt']), $featured)){
			$featured[] = date_i18n('Y-m-d', $e['start_gmt']);
			//echo date_i18n('Y-m-d', $e['start_gmt']).'<br>';
		}

	if ($url = get_option('okupanel_pad_kafeta_url', ''))
		foreach (okupanel_kafeta_pad_get_events($url) as $date => $names)
			$featured[] = $date;

	$missing = array();
	$days = get_option('okupanel_pad_kafeta_days', array(0, 1, 2, 3, 4, 5));

	for ($d = strtotime(date_i18n('Y-m-d').' 00:00:00'); $d<$max_date; $d = strtotime('+1 day', $d)){
		//echo 'CHECKING '.date_i18n('Y-m-d', $d).'<br>';
		if (!in_array(date_i18n('Y-m-d', $d), $featured) && in_array(intval(date_i18n('N', $d))-1, $days))
			$missing[] = $d;
	}

	//array_splice($missing, 0, 0, array(strtotime('2018-10-24 00:00:00')));

	if (!$missing)
		return '';
	array_splice($missing, intval($atts['amount']));


	//if (empty($_REQUEST['action'])){

		$max_date = trim(get_option('okupanel_pad_kafeta_missing_days_ahead_urgent', '5'));
		$max_date = $max_date ? strtotime('+'.$max_date.' days') : strtotime('+5 days');

		foreach ($missing as $d){
			$color = date_i18n('Y-m-d', $d) < date_i18n('Y-m-d', $max_date) ? 'red' : '#FFFF00';
			break;
		}

		?><style>
		.okupanel-missing-kafeta-col-name > ul { margin: 0; padding: 0; }
			.okupanel-missing-kafeta-col-logo {
				margin: 0;
				width: 100px;
				float: right;
				margin-left: 16px;
				clear: right;
			}
			.okupanel-missing-kafeta-col-logo img {
				width: 100%;
				margin: 0;
			}
			.okupanel-missing-kafeta-col-name {
				margin: 4px 0 0 0;
				font-size: <?= (okupanel_is_fullscreen() ? 24 : 18) ?>px;
				font-weight: bold;
			}
			.okupanel-missing-kafeta-wrap {
				color: <?= $color ?> !important;
			}
			.okupanel-missing-kafeta.okupanel-most-important {
				border-color: <?= $color ?> !important;
			}
			.okupanel-missing-kafeta-col-name li {
				list-style: circle;
				margin-left: 20px;
				margin-top: 6px;
			}
		</style>
		<?php
	//}
	?>
	<div class="okupanel-most-important-right okupanel-missing-kafeta-wrap">
		<div class="okupanel-missing-kafeta-date okupanel-most-important-location">
			<?php
				echo (count($missing) > 1 ? $atts['plural_msg'] : $atts['singular_msg']).':';

				/*sprintf(
					$e['start_gmt'] > time()
					? __('%s at %s', 'okupanel')
					: __('%s since %s', 'okupanel'), $day, date_i18n('G:i', $e['start']));*/
			?>
		</div>
		<?php
		foreach ($missing as $d){
			?>
			<div class="okupanel-missing-kafeta-col">
				<div class="okupanel-missing-kafeta-col-name"><ul><?php
					if (date_i18n('Y-m-d', $d) == date_i18n('Y-m-d')){
						echo '<li>'.__('today', 'okupanel');
						if (date_i18n('H:i') < OKUPANEL_PRECISE_DAY_BEFORE)
							echo ' '.date_i18n('l');
						echo '</li>';

					} else if (date_i18n('Y-m-d', $d) == date_i18n('Y-m-d', strtotime('+1 day'))){
						echo '<li>'.__('tomorrow', 'okupanel');
						if (date_i18n('H:i') < OKUPANEL_PRECISE_DAY_BEFORE)
							echo ' '.date_i18n('l', $d);
						echo '</li>';

					} else if ($d - time() < 5 * DAY_IN_SECONDS){
						echo '<li>'.sprintf(__('this %s', 'okupanel'), date_i18n('l', $d)).'</li>';

					} else if (date_i18n('Y-m', $d) == date_i18n('Y-m'))
						echo '<li>'.date_i18n('l j', $d).'</li>';

					else
						echo '<li>'.date_i18n('l j \\d\\e M', $d).'</li>';
				?></ul></div>
			</div>
			<?php
		} ?>
	</div>
	<?php
	return '<div class="okupanel-missing-kafeta okupanel-most-important"><i class="fa fa-warning"></i>'.ob_get_clean().'<div style="clear: both;"></div></div>';
});

