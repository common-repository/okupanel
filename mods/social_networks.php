<?php
// Social networks handling



add_filter('okupanel_network_accounts', function($accounts){
	$accounts += array(
		'facebook' => get_option('okupanel_facebook'),
		'twitter' => get_option('okupanel_twitter'),
		'youtube' => get_option('okupanel_youtube'),
	);
	return $accounts;
});


add_filter('okupanel_textline_fields', 'okupanel_social_textline_save');
function okupanel_social_textline_save($fields){
	$fields[] = 'facebook';
	$fields[] = 'twitter';
	$fields[] = 'youtube';
	return $fields;
}


add_action('okupanel_print_extra_textarea_fields_2', 'okupanel_social_fields');
function okupanel_social_fields(){
	
	foreach (array(
		'facebook' => 'Facebook',
		'twitter' => 'Twitter',
		'youtube' => 'YouTube',
	) as $n => $l){
		?>
		<div class="okupanel-field okupanel-settings-field">
			<label><?= sprintf(__('%s URL', 'okupanel'), $l) ?></label>
			<div class="okupanel-field-inner">
				<div><input name="okupanel_<?= $n ?>" value="<?= esc_attr(get_option('okupanel_'.$n, '')) ?>" /></div>
			</div>
		</div>
		<?php
	}
}
