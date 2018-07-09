<?php

require 'vendor/autoload.php';

use Billplz\API;
use Billplz\Connect;

$connnect = (new Connect('4e49de80-1670-4606-84f8-2f1d33a38670'))->detectMode();
//$connect->setMode(true); // true: staging | false: production (default)

$parameter = array(
    'mass_payment_instruction_collection_id' => 'go0voexz',
    'bank_code'=> 'MBBEMYKL',
    'bank_account_number' => '300',
    'identity_number'=>'JJJ',
    'name' => 'SS',
    'description' => 'ntah berantah',
    'total' => '200'
);
$optional = array(
    'email' => 'wan@billplz.com',
    'notification'=>'false',
    'recipient_notification' => 'false'
);

$billplz = new API($connnect);
$response = $billplz->createMPI($parameter);
//$response = $billplz->createMPI($parameter,$optional);

echo '<pre>'.print_r($response, true).'</pre>';
