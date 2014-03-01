<?php
header('HTTP/1.1 301 Moved Permanently');
header('location: templates.xml');
header('Connection: close');
exit();