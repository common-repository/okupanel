<?php

// display projects from a pad using the [okupanel_projects] shortcode


add_filter('okupanel_textline_fields', 'okupanel_projects_fields_textline_save');
function okupanel_projects_fields_textline_save($fields){
$fields[] = 'projects_pad';
return $fields;
}

add_action('okupanel_print_extra_textarea_fields_2', 'okupanel_projects_fields');
function okupanel_projects_fields(){
?>
<div class="okupanel-field okupanel-settings-field">
<label><?= __('Projects\' Pad URL', 'okupanel') ?>:</label>
<div class="okupanel-field-inner">
<div><input name="okupanel_projects_pad" value="<?= esc_attr(get_option('okupanel_projects_pad', '')) ?>" /></div>
</div>
</div>
<?php
}

add_shortcode('okupanel_projects', 'okupanel_projects_shortcode');
function okupanel_projects_shortcode($atts = array(), $content = ''){
	 $url = get_option('okupanel_projects_pad', '');
	 if (empty($url)) return '';

	 $txt = okupanel_fetch(rtrim($url, '/').'/export/txt');
	 if (empty($txt)) return '';

	 $projects = preg_split('#NOMBRE:#iu', $txt, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	 array_shift($projects);
	 array_shift($projects);
	 if (empty($projects)) return '';

	 $projects_html = array('active' => array(), 'stopped' => array(), 'complete' => array(), 'else' => array());
	 foreach ($projects as $t){
	 	 $p = array(
		    'name' => preg_match('#(.*)#iu', $t, $m) ? trim($m[1]) : '',		
		    'description' => preg_match('#DESCRIPCI.N[^:]*:(.*)#iu', $t, $m) ? trim($m[1]) : '',
		    'status' => preg_match('#ESTADO[^:]*:(.*)#iu', $t, $m) ? trim($m[1]) : '',
		    'visibility' => preg_match('#VISIBILIDAD[^:]*:(.*)#iu', $t, $m) ? trim($m[1]) : '',
		    'needs' => preg_match('#NECESIDADES[^:]*:(.*)#iu', $t, $m) ? trim($m[1]) : '',
		    'contact' => preg_match('#CONTACTO[^:]*:(.*)#iu', $t, $m) ? trim($m[1]) : '',
		 );
		 if (empty($p['name']) || empty($p['contact']))
		    continue;

	 	 if (!strcasecmp($p['status'], 'activo')){
		 $key = 'active';
		    $icon = 'circle-o'; $color = 'green';
		 } else if (!strcasecmp($p['status'], 'parado')){
		 $key = 'stopped';
		    $icon = 'circle-o'; $color = 'orange';
		 } else if (!strcasecmp($p['status'], 'completado')){
		 $key = 'complete';
		    $icon = 'check-circle'; $color = 'green';
		 } else {
		 $key = 'else';
		    $icon = 'question-circle'; $color = 'orange';
		 }
		 if (preg_match('#^(.*)\[([^\]]+)\]$#iu', $p['name'], $m)){
		    $p['name'] = $m[1];
		    $p['tag'] = $m[2];
		 }
		 ob_start();
	 	 ?>
	 <div style="margin-bottom: 20px" class="okupanel-project" data-okupanel-project-name="<?= esc_attr($p['name']) ?>">
	 	 <div style="font-size: 18px; margin-bottom: 6px; margin-left: 3px"><i title="<?= esc_attr(htmlentities($p['status'])) ?>" class="fa fa-<?= $icon ?>" style="font-size: 20px; margin-right: 8px; color: <?= $color ?>"></i> <?= htmlentities($p['name']) ?><?php if (!empty($p['tag'])) echo '<span class="okupanel-project-tag" style="font-size: 13px; padding: 2px 4px; font-weight: bold; font-family: Roboto; float: right; background: '.$color.'; color: black; margin: 0 0 4px 15px; border-radius: 3px;">'.htmlentities($p['tag']).'</span>'; ?></div>
		 <div style="margin: 3px 0 3px 40px; color: #eee; font-size: 90%; font-family: Roboto">
		          <div><?= htmlentities(rtrim($p['description'], '.').'.') ?></div>
		 	  <div style="margin-top: 8px; color: #aaa">
			  <?php if (!empty($p['needs'])){ ?>
			       <div><i class="fa fa-bullhorn"></i> Necesita: <?= $p['needs'] ?></div>
			       <?php } ?>
			       <div><i class="fa fa-arrow-right"></i> Colaborar: <?= $p['contact'] ?></div>
			  </div>
		 </div>
	 </div>
	 	 <?php
		 $projects_html[$key][] = ob_get_clean();
	 }
	 if (empty($_GET['fullscreen']))
	 	 foreach ($projects_html as &$prs)
	 	 	 shuffle($prs);
	unset($prs);

	ob_start();
	 if (!empty($_GET['fullscreen'])){
	    ?>
<script type="text/javascript">

jQuery(document).ready(function(){
var process_projects = true;
var projects_offset = 0;
var projects_count = 0;
setInterval(function(){
if (!process_projects)
   return;
   projects_count = jQuery('.okupanel-project').length;
   projects_offset++;
   if (projects_offset >= projects_count)
      projects_offset = 0;
var p = jQuery('.okupanel-project').first();
p.slideUp('slow', function(){
p.appendTo(jQuery('.okupanel-projects'));
okupanel_projects_check_doublons();
p.slideDown('slow', function(){
process_projects = true;
});
});
}, 10000);


function okupanel_projects_check_doublons(){
    var okupanel_projects_order = [];
    jQuery('.okupanel-project').each(function(){
    var n = jQuery(this).data('okupanel-project-name');
	if (jQuery.inArray(n, okupanel_projects_order) >= 0)
	   jQuery(this).remove();
	else
	   	okupanel_projects_order.push(n);
    });		
}

okupanel_add_filter('after_update', function(){

if (projects_offset)
   jQuery('.okupanel-project').slice(0, projects_offset).appendTo(jQuery('.okupanel-projects'));
});

});
</script>
<?php
	 }
	 echo implode('', $projects_html['active']).implode('', $projects_html['stopped']).implode('', $projects_html['else']).implode('', $projects_html['else']);

	 return '<div class="okupanel-projects">'.ob_get_clean().'</div>';
}
