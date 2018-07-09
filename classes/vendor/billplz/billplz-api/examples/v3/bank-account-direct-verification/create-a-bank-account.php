<?php

require 'vendor/autoload.php';

use Billplz\API;
use Billplz\Connect;

$connnect = (new Connect('4e49de80-1670-4606-84f8-2f1d33a38670'))->detectMode();
//$connect->setMode(true); // true: staging | false: production (default)

$parameter = array(
    'name'=>'Insan Bertuah' ,
    'id_no'=>'1311231231',
    'acc_no'=>'999988887756',
    'code'=>'MBBEMYKL',
    'organization'=>'true'
);

$billplz = new API($connnect);
$response = $billplz->createBankAccount($parameter);

echo '<pre>'.print_r($response, true).'</pre>';
