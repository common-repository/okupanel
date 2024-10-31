<?php

// use a regexp to add a hashtag at the begining of specific lines

if (!defined('ABSPATH'))
	exit();

add_filter('okupanel_tr_vars', function($vars){
	
	foreach (explode("\n", get_option('okupanel_htd_hashtags', '')) as $line){
		$line = trim($line);
		if (empty($line))
			continue;
			
		if (preg_match('#^\s*\#(\S+)\s+(\#[^\#]+\#[a-z]*)\s*$#iu', $line, $m)){
			
			if (!empty($vars['e']['summary']) && @preg_match($m[2], $vars['e']['summary'], $m2)){
				
				$vars['hashtag_type'] = 'okupanelhtd';
				$vars['hashtag'] = $m[1];
				$vars['short_title'] = $vars['tr']['summary']['title'] = '<span class="okupanel-summary-hashtag"><span>#'.$m[1].'</span></span> '.htmlentities(okupanel_ucfirst(trim(count($m2) > 1 ? $m2[1] : $vars['e']['summary'])));
			}
		}
	}
	
	return $vars;
});


add_action('okupanel_print_extra_textarea_fields_2', 'okupanel_htd_widget_fields');
function okupanel_htd_widget_fields(){
	?>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Hashtags', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><textarea name="okupanel_htd_hashtags"><?= esc_textarea(get_option('okupanel_htd_hashtags', '')) ?></textarea></div>
			<div>format: #FAQin18 #any regexp to match#iu<br>if the regexp matches groups, the first group matched is kept as the line summary</div>
		</div>
	</div>
	<?php
}

add_filter('okupanel_textline_fields', 'okupanel_htd_widget_fields_save');
function okupanel_htd_widget_fields_save($fields){
	$fields[] = 'htd_hashtags';
	return $fields;
}



