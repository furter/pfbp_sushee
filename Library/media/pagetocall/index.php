<?php
header('HTTP/1.1 301 Moved Permanently');
header('location: list.xml');
header('Connection: close');
exit();