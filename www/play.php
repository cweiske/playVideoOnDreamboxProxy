<?php
$youtubedlPath = '/usr/bin/youtube-dl';
$dreamboxHost  = 'dreambox';

require_once __DIR__ . '/functions.php';
$cfgFile = __DIR__ . '/../data/config.php';
if (file_exists($cfgFile)) {
    include $cfgFile;
}

$pageUrl  = getPageUrl();
$json     = getYoutubeDlJson($pageUrl, $youtubedlPath);
$videoUrl = extractVideoUrlFromJson($json);
if (php_sapi_name() == 'cli') {
    echo $videoUrl .  "\n";
} else {
    header('Video-URL: ' . $videoUrl);
}
playVideoOnDreambox($videoUrl, $dreamboxHost);


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
    syslog(LOG_ERR, 'playVideoOnDreamboxProxy: ' . $httpStatus . ': ' . $msg);
    exit(2);
}
?>