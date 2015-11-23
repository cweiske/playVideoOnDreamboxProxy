***********************************
Play video on Dreambox proxy server
***********************************
Server for the "Play video on Dreambox" android app.

Accepts an URL, runs ``youtube-dl`` on it to extract the video
URL and lets the Dreambox satellite receiver play this file.


=====
Usage
=====
Send the web site URL via POST to ``play.php``::

    $ curl -XPOST --data http://example.org/page.htm\
          -H 'Content-type: text/plain'\
          http://proxy.example.org/play.php

=======
License
=======
This application is available under the AGPLv3 or later.
