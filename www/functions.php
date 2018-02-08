<?php
function getPageUrl()
{
    global $argv, $argc;
    if (php_sapi_name() == 'cli') {
        if ($argc < 2) {
            errorInput('No URL given as command line parameter');
        }
        $pageUrl = $argv[1];
    } else {
        if (!isset($_SERVER['CONTENT_TYPE'])) {
            errorInput('Content type header missing');
        } else if ($_SERVER['CONTENT_TYPE'] != 'text/plain') {
            errorInput('Content type is not text/plain but ' . $_SERVER['CONTENT_TYPE']);
        }
        $pageUrl = file_get_contents('php://input');
    }
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

function getYoutubeDlJson($pageUrl, $youtubedlPath)
{
    $cmd = $youtubedlPath
        . ' --quiet'
        . ' --dump-json'
        . ' ' . escapeshellarg($pageUrl)
        . ' 2> /dev/null';

    $lastLine = exec($cmd, $output, $exitCode);
    if ($exitCode !== 0) {
        if (strpos($lastLine, 'Unsupported URL') !== false) {
            errorOut(
                'Unsupported URL  at ' . $pageUrl,
                '406 Unsupported URL (No video found)'
            );
        } else {
            errorOut('youtube-dl error: ' . $lastLine);
        }
    }

    $json = implode("\n", $output);
}

function extractVideoUrlFromJson($json)
{
    $data = json_decode($json);
    if ($data === null) {
        errorOut('Cannot decode JSON: ' . json_last_error_msg());
    }

    $url = null;
    foreach ($data->formats as $format) {
        if (strpos($format->format, 'hls') !== false) {
            //dreambox 7080hd does not play hls files
            continue;
        }
        if ($format->protocol == 'http_dash_segments') {
            //split up into multiple small files
            continue;
        }
        $url = $format->url;
    }

    if ($url === null) {
        //use URL chosen by youtube-dl
        $url = $data->url;
    }

    if ($url == '') {
        errorOut(
            'No video URL found',
            '406 No video URL found'
        );
    }
    return $url;
}

function playVideoOnDreambox($videoUrl, $dreamboxHost)
{
    ini_set('track_errors', 1);
    $xml = @file_get_contents('http://' . $dreamboxHost . '/web/session');
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
?>
