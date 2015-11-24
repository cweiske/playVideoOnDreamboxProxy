***********************************
Play video on Dreambox proxy server
***********************************
Server for the `"Play video on Dreambox" android app`__.

Accepts an URL, runs `youtube-dl`__ on it to extract the video
URL and lets the Dreambox__ satellite receiver play this file.


__ http://cweiske.de/playVideoOnDreambox.htm#android
__ http://rg3.github.io/youtube-dl/
__ http://dream-multimedia-tv.de/


=====
Setup
=====
Point your web server's document root to the ``www/`` directory.

Altenatively symlink the ``www/play.php`` file into your document root
directory.


Configuration
=============
You can adjust the path to ``youtube-dl`` and the Dreambox host name
or IP address by creating a config file in ``data/config.php``.

Simply copy ``data/config.php.dist`` onto ``data/config.php`` and adjust it.


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
This application is available under the `AGPL v3`__ or later.

__ http://www.gnu.org/licenses/agpl.html


======
Author
======
Written by `Christian Weiske`__, cweiske@cweiske.de

__ http://cweiske.de/
