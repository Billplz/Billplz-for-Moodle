<?php

require 'vendor/autoload.php';

use Billplz\API;
use Billplz\Connect;

$connnect = (new Connect('4e49de80-1670-4606-84f8-2f1d33a38670'))->detectMode();
//$connect->setMode(true); // true: staging | false: production (default)

$parameter = array(
    'collection_id' => 'bbrgyvvo',
    'payment_methods' => array(
        ['payment_methods[][code]' => 'fpx'],
        ['payment_methods[][code]' => 'billplz'],
        ['payment_methods[][code]' => 'boost'],
    )
);

$billplz = new API($connnect);
$response = $billplz->updatePaymentMethod($parameter);

echo '<pre>'.print_r($response, true).'</pre>';
