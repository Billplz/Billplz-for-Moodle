<?php

require 'vendor/autoload.php';
use Billplz\Connect;

$x_signature_key = 'S-0Sq67GFD9Y5iXmi5iXMKsA';
$data = Connect::getXSignature($x_signature_key);

echo '<pre>'.print_r($data, true).'</pre>';
