<?php

global $hyper_cache_stop;

$hyper_cache_stop = false;

// If no-cache header support is enabled and the browser explicitly requests a fresh page, do not cache
if ($hyper_cache_nocache &&
    ((!empty($_SERVER['HTTP_CACHE_CONTROL']) && $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache') ||
     (!empty($_SERVER['HTTP_PRAGMA']) && $_SERVER['HTTP_PRAGMA'] == 'no-cache'))) return hyper_cache_exit();

// Do not cache post request (comments, plugins and so on)
if ($_SERVER["REQUEST_METHOD"] == 'POST') return hyper_cache_exit();

// Try to avoid enabling the cache if sessions are managed with request parameters and a session is active
if (defined('SID') && SID != '') return hyper_cache_exit();

$hyper_uri = $_SERVER['REQUEST_URI'];
$hyper_qs = strpos($hyper_uri, '?');

if ($hyper_qs !== false) {
    if ($hyper_cache_strip_qs) $hyper_uri = substr($hyper_uri, 0, $hyper_qs);
    else if (!$hyper_cache_cache_qs) return hyper_cache_exit();
}

if (strpos($hyper_uri, 'robots.txt') !== false) return hyper_cache_exit();

// Checks for rejected url
if ($hyper_cache_reject !== false) {
    foreach($hyper_cache_reject as $uri) {
        if (substr($uri, 0, 1) == '"') {
            if ($uri == '"' . $hyper_uri . '"') return hyper_cache_exit();
        }
        if (substr($hyper_uri, 0, strlen($uri)) == $uri) return hyper_cache_exit();
    }
}

if ($hyper_cache_reject_agents !== false) {
    $hyper_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    foreach ($hyper_cache_reject_agents as $hyper_a) {
        if (strpos($hyper_agent, $hyper_a) !== false) return hyper_cache_exit();
    }
}

// Do nested cycles in this order, usually no cookies are specified
if ($hyper_cache_reject_cookies !== false) {
    foreach ($hyper_cache_reject_cookies as $hyper_c) {
        foreach ($_COOKIE as $n=>$v) {
            if (substr($n, 0, strlen($hyper_c)) == $hyper_c) return hyper_cache_exit();
        }
    }
}

// Do not use or cache pages when a wordpress user is logged on

foreach ($_COOKIE as $n=>$v) {
// If it's required to bypass the cache when the visitor is a commenter, stop.
    if ($hyper_cache_comment && substr($n, 0, 15) == 'comment_author_') return hyper_cache_exit();

    // SHIT!!! This test cookie makes to cache not work!!!
    if ($n == 'wordpress_test_cookie') continue;
    // wp 2.5 and wp 2.3 have different cookie prefix, skip cache if a post password cookie is present, also
    if (substr($n, 0, 14) == 'wordpressuser_' || substr($n, 0, 10) == 'wordpress_' || substr($n, 0, 12) == 'wp-postpass_') {
        return hyper_cache_exit();
    }
}

// Do not cache WP pages, even if those calls typically don't go throught this script
if (strpos($hyper_uri, '/wp-') !== false) return hyper_cache_exit();

// Multisite
if (function_exists('is_multisite') && is_multisite() && strpos($hyper_uri, '/files/') !== false) return hyper_cache_exit();

// prefix host and HTTPS
$is_https = $_SERVER['SERVER_PORT'] == 443 || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$hyper_uri = $_SERVER['HTTP_HOST'] . $is_https . $hyper_uri;

// The name of the file with html and other data
$hyper_cache_name = md5($hyper_uri);
$hc_file = $hyper_cache_path . $hyper_cache_name . hyper_mobile_type() . '.dat';

if (!file_exists($hc_file)) {
    hyper_cache_start(false);
    return;
}

$hc_file_time = @filemtime($hc_file);
$hc_file_age = time() - $hc_file_time;

if ($hc_file_age > $hyper_cache_timeout) {
    hyper_cache_start();
    return;
}

$hc_invalidation_time = @filemtime($hyper_cache_path . '_global.dat');
if ($hc_invalidation_time && $hc_file_time < $hc_invalidation_time) {
    hyper_cache_start();
    return;
}

if (array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER)) {
    $if_modified_since = strtotime(preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]));
    if ($if_modified_since >= $hc_file_time) {
        header($_SERVER['SERVER_PROTOCOL'] . " 304 Not Modified");
        flush();
        die();
    }
}

// Load it and check is it's still valid
$hyper_data = @unserialize(file_get_contents($hc_file));

if (!$hyper_data) {
    hyper_cache_start();
    return;
}

if ($hyper_data['type'] == 'home' || $hyper_data['type'] == 'archive') {

    $hc_invalidation_archive_file =  @filemtime($hyper_cache_path . '_archives.dat');
    if ($hc_invalidation_archive_file && $hc_file_time < $hc_invalidation_archive_file) {
        hyper_cache_start();
        return;
    }
}

// Valid cache file check ends here

if (!empty($hyper_data['location'])) {
    header('Location: ' . $hyper_data['location']);
    flush();
    die();
}

// It's time to serve the cached page

if (!$hyper_cache_browsercache) {
    // True if browser caching NOT enabled (default)
    header('Cache-Control: no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
}
else {
    $maxage = $hyper_cache_timeout - $hc_file_age;
    header('Cache-Control: max-age=' . $maxage);
    header('Expires: ' . gmdate("D, d M Y H:i:s", time() + $maxage) . " GMT");
}

// True if user ask to NOT send Last-Modified
if (!$hyper_cache_lastmodified) {
    header('Last-Modified: ' . gmdate("D, d M Y H:i:s", $hc_file_time). " GMT");
}

header('Content-Type: ' . $hyper_data['mime']);
if (isset($hyper_data['status']) && $hyper_data['status'] == 404) header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found");

// Send the cached html
if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false &&
    (($hyper_cache_gzip && !empty($hyper_data['gz'])) || ($hyper_cache_gzip_on_the_fly && function_exists('gzencode')))) {
    header('Content-Encoding: gzip');
    header('Vary: Accept-Encoding');
    if (!empty($hyper_data['gz'])) {
        echo $hyper_data['gz'];
    }
    else {
        echo gzencode($hyper_data['html']);
    }
}
else {
// No compression accepted, check if we have the plain html or
// decompress the compressed one.
    if (!empty($hyper_data['html'])) {
    //header('Content-Length: ' . strlen($hyper_data['html']));
        echo $hyper_data['html'];
    }
    else if (function_exists('gzinflate')) {
        $buffer = hyper_cache_gzdecode($hyper_data['gz']);
        if ($buffer === false) echo 'Error retrieving the content';
        else echo $buffer;
    }
    else {
        // Cannot decode compressed data, serve fresh page
        return false;
    }
}
flush();
die();


function hyper_cache_start($delete=true) {
    global $hc_file;

    if ($delete) @unlink($hc_file);
    foreach ($_COOKIE as $n=>$v ) {
        if (substr($n, 0, 14) == 'comment_author') {
            unset($_COOKIE[$n]);
        }
    }
    ob_start('hyper_cache_callback');
}

// From here Wordpress starts to process the request

// Called whenever the page generation is ended
function hyper_cache_callback($buffer) {
    global $hyper_cache_notfound, $hyper_cache_stop, $hyper_cache_charset, $hyper_cache_home, $hyper_cache_redirects, $hyper_redirect, $hc_file, $hyper_cache_name, $hyper_cache_browsercache, $hyper_cache_timeout, $hyper_cache_lastmodified, $hyper_cache_gzip, $hyper_cache_gzip_on_the_fly;

    if (!function_exists('is_home')) return $buffer;
    if (!function_exists('is_front_page')) return $buffer;
    
    if (function_exists('apply_filters')) $buffer = apply_filters('hyper_cache_buffer', $buffer);

    if ($hyper_cache_stop) return $buffer;

    if (!$hyper_cache_notfound && is_404()) {
        return $buffer;
    }

    if (strpos($buffer, '</body>') === false) return $buffer;

    // WP is sending a redirect
    if ($hyper_redirect) {
        if ($hyper_cache_redirects) {
            $data['location'] = $hyper_redirect;
            hyper_cache_write($data);
        }
        return $buffer;
    }

    if ((is_home() || is_front_page()) && $hyper_cache_home) {
        return $buffer;
    }

    if (is_feed() && !$hyper_cache_feed) {
        return $buffer;
    }

    if (is_home() || is_front_page()) $data['type'] = 'home';
    else if (is_feed()) $data['type'] = 'feed';
        else if (is_archive()) $data['type'] = 'archive';
            else if (is_single()) $data['type'] = 'single';
                else if (is_page()) $data['type'] = 'page';
    $buffer = trim($buffer);

    // Can be a trackback or other things without a body. We do not cache them, WP needs to get those calls.
    if (strlen($buffer) == 0) return '';

    if (!$hyper_cache_charset) $hyper_cache_charset = 'UTF-8';

    if (is_feed()) {
        $data['mime'] = 'text/xml;charset=' . $hyper_cache_charset;
    }
    else {
        $data['mime'] = 'text/html;charset=' . $hyper_cache_charset;
    }

    $buffer .= '<!-- hyper cache: ' . $hyper_cache_name . ' ' . date('y-m-d h:i:s') .' -->';

    $data['html'] = $buffer;

    if (is_404()) $data['status'] = 404;

    hyper_cache_write($data);

    if ($hyper_cache_browsercache) {
        header('Cache-Control: max-age=' . $hyper_cache_timeout);
        header('Expires: ' . gmdate("D, d M Y H:i:s", time() + $hyper_cache_timeout) . " GMT");
    }

    // True if user ask to NOT send Last-Modified
    if (!$hyper_cache_lastmodified) {
        header('Last-Modified: ' . gmdate("D, d M Y H:i:s", @filemtime($hc_file)). " GMT");
    }
    
    if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false &&
        (($hyper_cache_gzip && !empty($data['gz'])) || ($hyper_cache_gzip_on_the_fly && !empty($data['html']) && function_exists('gzencode')))) {
        header('Content-Encoding: gzip');
        header('Vary: Accept-Encoding');
        if (empty($data['gz'])) {
            $data['gz'] = gzencode($data['html']);
        }
        return $data['gz'];
    }

    return $buffer;
}

function hyper_cache_write(&$data) {
    global $hc_file, $hyper_cache_store_compressed;

    $data['uri'] = $_SERVER['REQUEST_URI'];

    // Look if we need the compressed version
    if ($hyper_cache_store_compressed && !empty($data['html']) && function_exists('gzencode')) {
        $data['gz'] = gzencode($data['html']);
        if ($data['gz']) unset($data['html']);
    }
    $file = fopen($hc_file, 'w');
    fwrite($file, serialize($data));
    fclose($file);
}

function hyper_mobile_type() {
    global $hyper_cache_mobile, $hyper_cache_mobile_agents, $hyper_cache_plugin_mobile_pack;

    if ($hyper_cache_plugin_mobile_pack) {
        @include_once ABSPATH . 'wp-content/plugins/wordpress-mobile-pack/plugins/wpmp_switcher/lite_detection.php';
        if (function_exists('lite_detection')) {
            $is_mobile = lite_detection();
            if (!$is_mobile) return '';
            include_once ABSPATH . 'wp-content/plugins/wordpress-mobile-pack/themes/mobile_pack_base/group_detection.php';
            if (function_exists('group_detection')) {
                return 'mobile' . group_detection();
            }
            else return 'mobile';
        }
    }

    if (!isset($hyper_cache_mobile) || $hyper_cache_mobile_agents === false) return '';

    $hyper_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    if (!empty($hyper_cache_mobile_agents)) {
        foreach ($hyper_cache_mobile_agents as $hyper_a) {
            if (strpos($hyper_agent, $hyper_a) !== false) {
                if (strpos($hyper_agent, 'iphone') || strpos($hyper_agent, 'ipod')) {
                    return 'iphone';
                }
                else {
                    return 'pda';
                }
            }
        }
    }
    return '';
}

function hyper_cache_gzdecode ($data) {

    $flags = ord(substr($data, 3, 1));
    $headerlen = 10;
    $extralen = 0;

    $filenamelen = 0;
    if ($flags & 4) {
        $extralen = unpack('v' ,substr($data, 10, 2));

        $extralen = $extralen[1];
        $headerlen += 2 + $extralen;
    }
    if ($flags & 8) // Filename

        $headerlen = strpos($data, chr(0), $headerlen) + 1;
    if ($flags & 16) // Comment

        $headerlen = strpos($data, chr(0), $headerlen) + 1;
    if ($flags & 2) // CRC at end of file

        $headerlen += 2;
    $unpacked = gzinflate(substr($data, $headerlen));
    return $unpacked;
}

function hyper_cache_exit() {
    global $hyper_cache_gzip_on_the_fly;

    if ($hyper_cache_gzip_on_the_fly && extension_loaded('zlib')) ob_start('ob_gzhandler');
    return false;
}
