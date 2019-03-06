<?php
function getPageUrl()
{
    global $argv, $argc;
    if (php_sapi_name() == 'cli') {
        if ($argc < 2) {
            errorInput('No URL given as command line parameter');
        }
        $pageUrl = $argv[1];
    } else if (!isset($_SERVER['CONTENT_TYPE'])) {
        errorInput('Content type header missing');
    } else if ($_SERVER['CONTENT_TYPE'] == 'text/plain') {
        //Android app
        $pageUrl = file_get_contents('php://input');
    } else if ($_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded') {
        //Web form
        if (!isset($_POST['url'])) {
            errorInput('"url" POST parameter missing');
        }
        $pageUrl = $_POST['url'];
    } else {
        errorInput('Content type is not text/plain but ' . $_SERVER['CONTENT_TYPE']);
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
        . ' --no-playlist'//would otherwise cause multiple json blocks
        . ' --quiet'
        . ' --dump-json'
        . ' ' . escapeshellarg($pageUrl);

    $descriptors = [
        1 => ['pipe', 'w'],//stdout
        2 => ['pipe', 'w'],//stderr
    ];
    $proc = proc_open($cmd, $descriptors, $pipes);
    if ($proc === false) {
        errorOut('Error running youtube-dl');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);

    $exitCode = proc_close($proc);

    if ($exitCode === 0) {
        //stdout contains the JSON data
        return $stdout;
    }

    if (strlen($stderr)) {
        $lines = explode("\n", trim($stderr));
        $lastLine = end($lines);
    } else {
        $lines = explode("\n", trim($stdout));
        $lastLine = end($lines);
    }

    if ($exitCode === 127) {
        errorOut(
            'youtube-dl not found at ' . $youtubedlPath,
            '500 youtube-dl not found'
        );
    } else if (strpos($lastLine, 'Unsupported URL') !== false) {
        errorOut(
            'Unsupported URL  at ' . $pageUrl,
            '406 Unsupported URL (No video found)'
        );
    }

    errorOut('youtube-dl error: ' . $lastLine);
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

function playVideoOnDreambox($videoUrl, $dreamboxUrl)
{
    ini_set('track_errors', 1);
    $xml = @file_get_contents($dreamboxUrl . '/web/session');
    if ($xml === false) {
        if (!isset($http_response_header)) {
            errorOut(
                'Error fetching dreambox web interface token: '
                . $GLOBALS['lastError']
            );
        }

        list($http, $code, $message) = explode(
            ' ', $http_response_header[0], 3
        );
        if ($code == 401) {
            //dreambox web interface authentication has been enabled
            errorOut(
                'Error: Web interface authentication is required',
                '401 Dreambox web authentication required'
            );
        } else {
            errorOut(
                'Failed to fetch dreambox session token: ' . $php_errormsg,
                $code . ' ' . $message
            );
        }
    }
    $sx = simplexml_load_string($xml);
    $token = (string) $sx;

    $playUrl = $dreamboxUrl
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
        if (php_sapi_name() != 'cli') {
            header('HTTP/1.0 200 OK');
        }
        echo "Video play request sent to dreambox\n";
        exit(0);
    } else {
        errorOut(
            'Failed to send video play request to dreambox: ' . $php_errormsg
        );
    }
}

function errorHandlerStore($number, $message, $file, $line)
{
    $GLOBALS['lastError'] = $message;
    return false;
}
$GLOBALS['lastError'] = null;
?>
