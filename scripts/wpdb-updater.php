<?php
$sites = array_map('trim', file('/tmp/wpmt-sites.txt'));

foreach($sites as $site) {
	// assumes the site is WPMT
	$url = 'http://' . $site . '/wordpress/wp-admin/upgrade.php';
	$content = get_url($url);

	if (empty($content)) {
		output('could not reach site', $site);
		continue;
	}

	if (!is_upgrade_page($content)) {
		output('could not find upgrade page: ', $url);
		continue;
	}

	if (!needs_database_upgrade($content)) {
		output('database up-to-date', $site);
		continue;
	}

	$url .= '?step=1';
	$content = get_url($url);

	if (update_failed($content)) {
		output('database update failed', $site);
		continue;
	}

	output('database updated', $site);
}

function get_url($url) {
  	$curl_options = array(
  		CURLOPT_USERAGENT => 'viabot',
    	CURLOPT_TIMEOUT => 10,
    	CURLOPT_RETURNTRANSFER => true,
    	CURLOPT_FOLLOWLOCATION => true
    );

	  $request = curl_init($url);
	  curl_setopt_array($request, $curl_options);
	  $response = curl_exec($request);
	  curl_close($request);

	  return $response;
}

function is_upgrade_page($content) {
	return strpos($content, 'WordPress &rsaquo; Update') !== false;
}

function needs_database_upgrade($content) {
	return strpos($content, 'Database Update Required') !== false;
}

function update_failed($content) {
	return strpos($content, 'Update Complete') === false;
}

function output($message, $site) {
	echo $message . ': ' . $site . PHP_EOL;
}
