<?php

global $cache_stop;
$cache_stop = false;

// Use this only if you can't or don't want to modify the .htaccess
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cache_stop = true;
    return false;
}

if ($_SERVER['QUERY_STRING'] != '') {
    $cache_stop = true;
    return false;
}

if (defined('SID') && SID != '') {
    $cache_stop = true;
    return false;
}

if (isset($_COOKIE['cache_disable'])) {
    $cache_stop = true;
    return false;
}

if (isset($_SERVER['HTTP_CACHE_CONTROL']) && $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache') {
    $cache_stop = true;
    return false;
}

if (isset($_SERVER['HTTP_PRAGMA']) && $_SERVER['HTTP_PRAGMA'] == 'no-cache') {
    $cache_stop = true;
    return false;
}

if (HC_REJECT_AGENTS_ENABLED && isset($_SERVER['HTTP_USER_AGENT'])) {
    if (preg_match('#(HC_REJECT_AGENTS)#i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
        $cache_stop = true;
        return false;
    }
}


if (!empty($_COOKIE)) {
    foreach ($_COOKIE as $n => $v) {
        if (substr($n, 0, 20) == 'wordpress_logged_in_') {
            $cache_stop = true;
            return false;
        }

        if (substr($n, 0, 12) == 'wp-postpass_') {
            $cache_stop = true;
            return false;
        }

        if (HC_REJECT_COMMENT_AUTHORS && substr($n, 0, 14) == 'comment_author') {
            $cache_stop = true;
            return false;
        }
        if (HC_REJECT_COOKIES_ENABLED) {
            if (preg_match('#(HC_REJECT_COOKIES)#i', strtolower($n))) {
                $cache_stop = true;
                return false;
            }
        }
    }
}

$hc_group = '';

if (HC_HTTPS && hyper_cache_is_ssl()) {
    $hc_group .= '-https';
}

if (hyper_cache_is_mobile()) {
// Bypass
    if (HC_MOBILE == 2) {
        $cache_stop = true;
        return false;
    }
    $hc_group .= '-mobile';
}



//$hc_file = ABSPATH . 'wp-content/cache/lite-cache' . $_SERVER['REQUEST_URI'] . '/index' . $hc_group . '.html';
$hc_uri = hyper_cache_sanitize_uri($_SERVER['REQUEST_URI']);

$hc_file = 'HC_FOLDER/' . strtolower($_SERVER['HTTP_HOST']) . $hc_uri . '/index' . $hc_group . '.html';
if (HC_GZIP == 1) {
    $hc_gzip = isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false;
} else {
    $hc_gzip = false;
}

if ($hc_gzip) {
    $hc_file .= '.gz';
}

if (!is_file($hc_file)) {
    return false;
}

$hc_file_time = filemtime($hc_file);

if (HC_MAX_AGE > 0 && $hc_file_time < time() - (HC_MAX_AGE * 3600))
    return false;

if (array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER)) {
    $hc_if_modified_since = strtotime(preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]));
    if ($hc_if_modified_since >= $hc_file_time) {
        header("HTTP/1.0 304 Not Modified");
        flush();
        die();
    }
}

header('Content-Type: text/html;charset=UTF-8');
header('Last-Modified: ' . gmdate("D, d M Y H:i:s", $hc_file_time) . ' GMT');

if (HC_MOBILE == 0) {
    header('Vary: Accept-Encoding');
} else {
    header('Vary: Accept-Encoding,User-Agent');
}

if (HC_BROWSER_CACHE) {
    if (HC_BROWSER_CACHE_HOURS != 0) {
        $hc_cache_max_age = HC_BROWSER_CACHE_HOURS * 3600;
    } else {
        // If there is not a default expire time, use 24 hours.
        if (HC_MAX_AGE > 0) {
            $hc_cache_max_age = time() + (HC_MAX_AGE * 3600) - $hc_file_time;
        } else {
            $hc_cache_max_age = time() + (24 * 3600) - $hc_file_time;
        }
    }
    header('Cache-Control: max-age=' . $hc_cache_max_age);
    header('Expires: ' . gmdate("D, d M Y H:i:s", time() + $hc_cache_max_age) . " GMT");
} else {
    header('Cache-Control: must-revalidate');
    header('Pragma: no-cache');
}

header('X-Hyper-Cache: hit' . $hc_group);
if ($hc_gzip) {
    header('Content-Encoding: gzip');
    header('Content-Length: ' . filesize($hc_file));
    echo file_get_contents($hc_file);
} else {
    header('Content-Length: ' . filesize($hc_file));
    echo file_get_contents($hc_file);
}
flush();
die();

function hyper_cache_sanitize_uri($uri) {
    $uri = preg_replace('/[^a-zA-Z0-9\.\/\-_]+/', '_', $uri);
    $uri = preg_replace('/\/+/', '/', $uri);
    $uri = rtrim($uri, '.-_/');
    if (empty($uri) || $uri[0] != '/') {
        $uri = '/' . $uri;
    }
    return $uri;
}

function hyper_cache_is_mobile() {
// Do not detect
    if (HC_MOBILE == 0)
        return false;
    if (defined('IS_PHONE'))
        return IS_PHONE;
    return preg_match('#(HC_MOBILE_AGENTS)#i', strtolower($_SERVER['HTTP_USER_AGENT']));
}

// From WordPress!
function hyper_cache_is_ssl() {
	if ( isset($_SERVER['HTTPS']) ) {
		if ( 'on' == strtolower($_SERVER['HTTPS']) )
			return true;
		if ( '1' == $_SERVER['HTTPS'] )
			return true;
	} elseif ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
		return true;
	}
	return false;
}
