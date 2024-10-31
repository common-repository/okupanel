<?php

// add maps to event popups and a [okupanel_nodes_map] shortcode to show all nodes on a map


if (!defined('ABSPATH'))
	exit();

add_action('okupanel_action_map', function(){
	
	$markers = $mmarkers = array();
	$coords = array(0, 0);
	$extremes = array(null, null, null, null);
	
	foreach (okupanel_fed_get_nodes() as $n)
		if (!empty($n['result']['address_url']) && preg_match('#^.*?@(-?[0-9\.]+),(-?[0-9\.]+)#iu', $n['result']['address_url'], $c)){
			$mmarkers[] = $c[1].'%2C'.$c[2];
			$markers[] = $c[1].','.$c[2].',lightblue'.(count($markers)+1);
			$coords[0] += floatval($c[1]);
			$coords[1] += floatval($c[2]);
			$extremes[0] = $extremes[0] !== null ? max($extremes[0], floatval($c[2])) : floatval($c[2]);
			$extremes[1] = $extremes[1] !== null ? min($extremes[1], floatval($c[1])) : floatval($c[1]);
			$extremes[2] = $extremes[2] !== null ? min($extremes[2], floatval($c[2])) : floatval($c[2]);
			$extremes[3] = $extremes[3] !== null ? max($extremes[3], floatval($c[1])) : floatval($c[1]);
		}
	
	if (!$markers)
		die('no node to show');
	
	$width = 600;
	$height = 300;
	
	$angle = $extremes[2] - $extremes[0];
	if ($angle < 0) 
		$angle += 360;
	$zoom = round(log($width * 360 / $angle / 4) / log(2));

	$params = array(
		'size' => $width.'x'.$height,
		'maptype' => 'mapnik',
		'zoom' => $zoom,
		'center' => ($coords[0]/count($markers)).','.($coords[1]/count($markers)),
	);
	
	$url = 'http://staticmap.openstreetmap.de/staticmap.php?'.http_build_query($params).'&markers='.implode('|', $markers);
	
	
	$params = array(
		'size' => $width.'x'.$height,
		'key' => '339514043465-frg8r0o6p48bcaq4b3m8l8hec0f1lnmt.apps.googleusercontent.com',
		'language' => get_locale(),
		'maptype' => 'hybrid',
		'scale' => 2,
	); 
	//zoom=17&size=&key=&markers=40.4004603,-3.6653902&maptype=hybrid&language=es
	
	$google_url = 'https://maps.googleapis.com/maps/api/staticmap?'.http_build_query($params);
	foreach ($markers as $m)
		$google_url .= '&markers='.urlencode($m);
		
	//  https://www.openstreetmap.org/export/embed.html?bbox=  -3.679518699645996  %2C   40.37715150036452  %2C  -3.633642196655274  %2C  40.39669893904165  &amp;layer=mapnik&amp;marker=  40.38692592883968  %2C  -3.6565589904785156
	
	// https://www.google.com/maps/search/C+Montseny,+35/@  40.4005475  ,  -3.6646268  ,17z
	
	// OUTPUT: https://www.openstreetmap.org/export/embed.html?bbox=-3.6643999%2C40.3903923%2C-3.6974925%2C40.4005475&layer=mapnik&marker=40.4005475%2C-3.6646268%2C40.3954497%2C-3.6643999%2C40.3903923%2C-3.6974925
		
		
	$iframe_url = 'https://www.openstreetmap.org/export/embed.html?bbox='.urlencode(implode(',', $extremes)).'&layer=mapnik&marker='.implode('&marker=', $mmarkers);
	
	?><html><head>
		<script src="http://www.openlayers.org/api/OpenLayers.js"></script>
		<script>
			function init() {
				var map = new OpenLayers.Map("mapdiv");

				function get_marker_lonlat(coord1, coord2){
					return new OpenLayers.LonLat(coord1, coord2)
						.transform(
							new OpenLayers.Projection("EPSG:4326"), // transform from WGS 1984
							map.getProjectionObject() // to Spherical Mercator Projection
						);
				}

				map.addLayer(new OpenLayers.Layer.OSM());

				var lonLat = get_marker_lonlat( <?= ($coords[0]/count($markers)) ?>, <?= ($coords[1]/count($markers)) ?> );

				var zoom=16;

				var markers = new OpenLayers.Layer.Markers( "Markers" );
				map.addLayer(markers);
				
				<?php 
				foreach ($markers as $m){
					$m = explode(',', $m);
					?>
					markers.addMarker(new OpenLayers.Marker(get_marker_lonlat( <?= $m[0] ?>, <?= $m[1] ?> )));
					<?php
				}
				?>

				map.setCenter (lonLat, zoom);
			}
		</script>
	</head>
	<body onload="init()">
		
		<div><a href="<?= $iframe_url ?>"><?= $iframe_url ?></a><br><iframe width="425" height="350" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="<?= $iframe_url ?>" style="border: 1px solid black"></iframe></div>

		<div><a href="<?= $url ?>"><?= $url ?></a><br><img src="<?= esc_attr($url) ?>" /></div>
		
		<div><a href="<?= $google_url ?>"><?= $google_url ?></a><br><img src="<?= esc_attr($google_url) ?>" /></div>

		<div id="mapdiv"></div>
	</body></html><?php
	exit();
});
