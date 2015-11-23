<?php
$youtubedlPath = '/usr/bin/youtube-dl';
$dreamboxHost  = 'dreambox';

$cfgFile = __DIR__ . '/../data/config.php';
if (file_exists($cfgFile)) {
    include $cfgFile;
}

$pageUrl  = getPageUrl();
$videoUrl = extractVideoUrl($pageUrl, $youtubedlPath);
header('Video-URL: ' . $videoUrl);
playVideoOnDreambox($videoUrl, $dreamboxHost);

function getPageUrl()
{
    if (!isset($_SERVER['CONTENT_TYPE'])) {
        errorInput('Content type header missing');
    } else if ($_SERVER['CONTENT_TYPE'] != 'text/plain') {
        errorInput('Content type is not text/plain but ' . $_SERVER['CONTENT_TYPE']);
    }
    $pageUrl = file_get_contents('php://input');
    $parts = parse_url($pageUrl);
    if ($parts === false) {
        errorInput('Invalid URL in POST data');
    } else if (!isset($parts['scheme'])) {
        errorInput('Invalid URL in POST data: No scheme');
    } else if ($parts['scheme'] !== 'http' && $parts['scheme'] !== 'https') {
        errorInput('Invalid URL in POST data: Non-HTTP scheme');
    }
    return $pageUrl;
}

function extractVideoUrl($pageUrl, $youtubedlPath)
{
    $cmd = $youtubedlPath
        . ' --quiet'
        . ' --get-url'
        . ' ' . escapeshellarg($pageUrl)
        . ' 2>&1';

    $lastLine = exec($cmd, $output, $exitCode);
    if ($exitCode !== 0) {
        if (strpos($lastLine, 'Unsupported URL') !== false) {
            errorOut('Unsupported URL', '400 Unsupported URL (No video found)');
        } else {
            errorOut('youtube-dl error: ' . $lastLine);
        }
    }
    return $lastLine;
}

function playVideoOnDreambox($videoUrl, $dreamboxHost)
{
    ini_set('track_errors', 1);
    $xml = file_get_contents('http://' . $dreamboxHost . '/web/session');
    if ($xml === false) {
        errorOut('Failed to fetch dreambox session token: ' . $php_errormsg);
    }
    $sx = simplexml_load_string($xml);
    $token = (string) $sx;

    $playUrl = 'http://' . $dreamboxHost
        . '/web/mediaplayerplay'
        . '?file=4097:0:1:0:0:0:0:0:0:0:'
        . str_replace('%3A', '%253A', rawurlencode($videoUrl));

    $ctx = stream_context_create(
        array(
            'http' => array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => 'sessionid=' . $token,
                //'ignore_errors' => true
            )
        )
    );
    $ret = file_get_contents($playUrl, false, $ctx);
    if ($ret !== false) {
        header('HTTP/1.0 200 OK');
        echo "Video play request sent to dreambox\n";
        exit(0);
    } else {
        errorOut(
            'Failed to send video play request to dreambox: ' . $php_errormsg
        );
    }
}

function errorInput($msg)
{
    header('HTTP/1.0 400 Bad Request');
    header('Content-type: text/plain');
    echo $msg . "\n";
    exit(1);
}

function errorOut($msg, $httpStatus = '500 Internal Server Error')
{
    header('HTTP/1.0 ' . $httpStatus);
    header('Content-type: text/plain');
    echo $msg . "\n";
    exit(2);
}
?>