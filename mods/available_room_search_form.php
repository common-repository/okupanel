<?php
// adds a search interface for looking up available rooms

if (!defined('ABSPATH'))
	exit();


// /okupanel/availability/?from=2018-09-21-at-18-30&to=2018-09-24-at-21-30

add_filter('okupanel_textline_fields', 'okupanel_avail_fields_textline_save');
function okupanel_avail_fields_textline_save($fields){
	$fields[] = 'avail_rooms';
	$fields[] = 'notavail_rooms';
	return $fields;
}

add_action('okupanel_print_extra_textarea_fields_2', 'okupanel_avail_fields');
function okupanel_avail_fields(){
	?>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Available rooms', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><input name="okupanel_avail_rooms" value="<?= esc_attr(get_option('okupanel_avail_rooms', '')) ?>" /></div>
			<div><?= __('Comma-separated, well formatted rooms that will be added to rooms autodetected from future events.', 'okupanel') ?></div>
		</div>
	</div>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Rooms to avoid listing in availability page', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><input name="okupanel_notavail_rooms" value="<?= esc_attr(get_option('okupanel_notavail_rooms', '')) ?>" /></div>
			<div><?= __('Comma-separated, well formatted rooms that will be striped out from available rooms.', 'okupanel') ?></div>
		</div>
	</div>
	<?php
}


add_action('okupanel_action_availability', function(){

	if (isset($_GET['from_date'], $_GET['from_time']))
		$from = $_GET['from_date'].' '.$_GET['from_time'].':00';
	else
		$from = !empty($_GET['from']) && preg_match('#^(\d{4}-\d{2}-\d{2})-at-(\d{2})-(\d{2})$#i', $_GET['from'], $m) ? $m[1].' '.$m[2].':'.$m[3].':00' : (!empty($_GET['from']) ? $_GET['from'] : null);

	if (isset($_GET['to_date'], $_GET['to_time']))
		$to = $_GET['to_date'].' '.$_GET['to_time'].':00';
	else
		$to = !empty($_GET['to']) && preg_match('#^(\d{4}-\d{2}-\d{2})-at-(\d{2})-(\d{2})$#i', $_GET['to'], $m) ? $m[1].' '.$m[2].':'.$m[3].':00' : (!empty($_GET['to']) ? $_GET['to'] : null);

	if (!$from)
		$from = get_date_from_gmt(date_i18n('Y-m-d H', time()).':00:00');
	else if (date('Y-m-d H:i:s', strtotime($from)) !== $from || $from >= $to)
		die('bad from date');

	if (!$to)
		$to = get_date_from_gmt(date_i18n('Y-m-d H', strtotime('+2 hour')).':00:00');
	else if (date('Y-m-d H:i:s', strtotime($to)) !== $to)
		die('bad to date');

	$from = strtotime($from);
	$to = strtotime($to);

	?>
	<style>
		body {
			line-height: normal;
		}
		.okupanel-roomtag {
			display: inline-block;
			vertical-align: top;
			padding: 5px 10px;
			font-weight: bold;
			margin: 0 5px 5px 0;
			border-radius: 5px;
		}
		.okupanel-roomtag-avail {
			background: green;
			color: white;
		}
		.okupanel-roomtag-used {
			background: red;
			color: white;
		}
		table {
			border-collapse: collapse;
		}
		table td,
		table th {
			border: 1px solid #ccc;
			padding: 10px 20px;
		}
		button {
			padding: 5px 10px;
			font-size: 16px;
		}
	</style>
	<?php

	echo '<form action="" method="GET">';

	echo '<h1>Disponibilidad de salas en '.(($fed_name = get_option('okupanel_fed_name', '')) ? $fed_name : get_bloginfo('name')).':</h1>';
	echo '<table border="1">';

	echo '<tr><td>del </td><td><input type="date" value="'.esc_attr(date('Y-m-d', $from)).'" name="from_date" /> </td><td>a las </td><td><input type="time" value="'.esc_attr(date('H:i', $from)).'" name="from_time" /> </td></tr>';
	echo '<tr><td>al </td><td><input type="date" value="'.esc_attr(date('Y-m-d', $to)).'" name="to_date" /> </td><td>a las </td><td><input type="time" value="'.esc_attr(date('H:i', $to)).'" name="to_time" /> </td></tr>';

	echo '</table>';

	echo '<br><div><button type="submit">Comprobar disponibilidad</button></div><br>';
	echo '</form>';

	$events = $notavail = $avail = array();
	foreach (explode(',', get_option('okupanel_notavail_rooms', '')) as $room)
		if ($room = trim($room))
			$notavail[] = $room;

	foreach (okupanel_get_all_events(true) as $e){
		if (!empty($e['node']) || okupanel_is_featured($e))
			continue;
		if ($e['start'] < $to && $e['end'] > $from)
			$events[] = $e;
		else if (!empty($e['location']) && !in_array($e['location'], $notavail))
			$avail[$e['location']] = $e['location'];//'<span class="okupanel-roomtag okupanel-roomtag-avail">'.$e['location'].'</span>';
	}

	foreach (explode(',', get_option('okupanel_avail_rooms', '')) as $room)
		if ($room = trim($room))
			$avail[$room] = $room;//'<span class="okupanel-roomtag okupanel-roomtag-avail">'.$room.'</span>';

	if ($events){

		okupanel_sort_events_by_location_name($events);
		//$events = array_reverse($events);

		$locs = array();
		foreach ($events as $e)
			if ($e['location']){
				unset($avail[$e['location']]);
				$locs[] = '<span class="okupanel-roomtag okupanel-roomtag-used">'.$e['location'].'</span>';
			}
		$locs = array_unique(array_reverse($locs));

		$avail = array_unique(array_values($avail));
		okupanel_sort_events_by_location_name($avail);
		$avail = array_reverse($avail);


		?>
		<table border="1">
			<tr><td>Salas disponibles: </td><td><?php
$floor = null; $trs = array(); $tr = '';
foreach ($avail as $a){
	if ($floor !== null && okupanel_get_floor_from_location($a, true) != $floor){
	   if ($tr)
	      $trs[$floor] = $tr;
	   $tr = '';
	  }
	  $floor = okupanel_get_floor_from_location($a, true);

$tr .= '<span class="okupanel-roomtag okupanel-roomtag-avail">'.$a.'</span>';
}
if ($tr) $trs[$floor] = $tr;

echo implode('<br>', array_reverse(array_values($trs)));
?></td></tr>
			<tr><td>Salas reservadas: </td><td><?= implode('', $locs) ?></td></tr>
		</table>
		<br>
		<?php

		echo '<table border="1"><tr><th>Sala reservada</th><th>Desde</th><th>Hasta</th><th>Actividad</th></tr>';

		$pat = date('Y-m-d', $from) == date('Y-m-d', $to) ? 'H:i' : 'Y-m-d H:i';
		foreach ($events as $e)
			echo '<tr><td><span class="okupanel-roomtag okupanel-roomtag-used">'.($e['location'] ? $e['location'] : '?').'</span></td><td>'.date_i18n($pat, $e['start']).'</td><td>'.date_i18n($pat, $e['end']).'</td><td>'.$e['summary'].'</td></tr>';
		echo '</table>';

	} else
		echo 'Todas las salas son disponibles';


	exit();
});
