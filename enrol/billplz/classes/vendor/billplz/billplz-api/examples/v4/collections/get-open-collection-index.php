<?php

require 'vendor/autoload.php';

use Billplz\API;
use Billplz\Connect;

$connnect = (new Connect('4e49de80-1670-4606-84f8-2f1d33a38670'))->detectMode();
//$connect->setMode(true); // true: staging | false: production (default)

$billplz = new API($connnect);
$response = $billplz->getOpenCollectionIndex();
//$response = $billplz->getCollectionIndex(array('page'=>'1', 'status'=>'active'));

echo '<pre>'.print_r($response, true).'</pre>';
