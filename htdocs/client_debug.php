<?php

$f = fopen('/tmp/php5_errors.log', 'a');
fwrite($f, "\n\n\n\n\n\n");
fclose($f);
$f = fopen('/tmp/cartoclient_log', 'a');
fwrite($f, "\n\n\n\n\n\n");
fclose($f);

?>