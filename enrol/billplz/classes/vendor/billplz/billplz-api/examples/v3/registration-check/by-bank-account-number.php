<?php

require 'vendor/autoload.php';

use Billplz\API;
use Billplz\Connect;

$connnect = (new Connect('4e49de80-1670-4606-84f8-2f1d33a38670'))->detectMode();
//$connect->setMode(true); // true: staging | false: production (default)

$billplz = new API($connnect);
/* This feature is to check for registered (Merchant) account with Billplz */
$response = $billplz->bankAccountCheck('300');
//$response = $billplz->bankAccountCheck(array('300','9230248'));

echo '<pre>'.print_r($response, true).'</pre>';
