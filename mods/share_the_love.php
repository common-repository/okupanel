<?php
// adds a love icon next to any event with "love" inside

if (!defined('ABSPATH'))
	exit();


add_filter('okupanel_panel_tr', 'okupanel_panel_tr_share_the_love', 0, 2);
function okupanel_panel_tr_share_the_love($tr, $e){
	if (preg_match('#love|'.preg_quote(__('love', 'okupanel'), '#').'#iu', $e['summary']))
		okupanel_add_to_summary($tr, null, 'sharethelove', '<i class="fa fa-heart"></i>');
	return $tr;
}
