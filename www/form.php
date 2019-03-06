<?php
$dreamboxUrlNoAuth = preg_replace('#//[^@]+@#', '//', $dreamboxUrl);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
 <head>
  <title>playVideoOnDreamboxProxy</title>
  <meta name="author" content="Christian Weiske"/>
  <meta name="generator" content="playVideoOnDreamboxProxy"/>
 </head>
 <body>
  <h1>playVideoOnDreamboxProxy</h1>
  <p>
   This proxy application allows you to play videos on your Dreambox;
   either direct video URLs or videos linked in HTML pages.
  </p>
  <p>
   You may use the
   <a href="https://cweiske.de/playVideoOnDreambox.htm#android">Android app</a>
   or the
   <a href="https://cweiske.de/playVideoOnDreambox.htm#firefox">Firefox browser extension</a>
   to control it directly.
  </p>


  <h2>Video URL input</h2>
  <p>
   Enter the URL of a video or a web site that contains a video:
  </p>
  <form method="post" action="">
   <input type="url" name="url" required="" autofocus=""
          placeholder="URL"
          size="40"/>
   <button type="submit">Play on Dreambox</button>
  </form>
  <p>
   Video will be played on
   <a href="<?= $dreamboxUrlNoAuth ?>"><?= $dreamboxUrlNoAuth ?></a>.
  </p>


  <hr/>
  <p>
   Licensed under the
   <a href="http://www.gnu.org/licenses/agpl.html">AGPL v3+</a>.
   <a href="https://cweiske.de/playVideoOnDreambox.htm">Homepage</a>.
  </p>
 </body>
</html>
