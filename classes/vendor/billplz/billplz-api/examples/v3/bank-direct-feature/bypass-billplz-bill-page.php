<?php

require 'vendor/autoload.php';

use Billplz\API;
use Billplz\Connect;

$connnect = (new Connect('4e49de80-1670-4606-84f8-2f1d33a38670'))->detectMode();
//$connect->setMode(true); // true: staging | false: production (default)

$billplz = new API($connnect);
$fpxBanks = $billplz->toArray($billplz->getFpxBanks());
foreach ($fpxBanks[1]['banks'] as $bank) {
    if ($bank['active']) {
        $activeBank = $bank['name'];
        break;
    }
}

$parameter = array(
    'collection_id' => 'salahpulok',
    'email'=>'wan@billplz.com',
    'mobile'=>'0141234567',
    'name'=>'Lol',
    'amount'=>'200',
    'callback_url'=>'https://google.com',
    'description'=>'I am testing. Please ignore'
);

$optional = array(
    'redirect_url' => 'https://google.com',
    'reference_1_label' => 'Bank Code',
    'reference_1' => $activeBan,
    'reference_2_label' => 'Customer ID',
    'reference_2' => '111',
    /* 'deliver' => 'false' */
    /* Do not set due_at. The bills will expired in 30 days from creation */
);

/*
*  '0': Deliver false
*  '1': Deliver Email
*  '2': Deliver SMS
*  '3': Deliver Email & SMS
*/
$deliver = '0';

//$response = $billplz->createBill($parameter);
//$response = $billplz->createBill($parameter, $optional) ;
$response = $billplz->createBill($parameter, $optional, $deliver) ;

$response = $billplz->bypassBillplzPage($response[1]);

echo '<pre>'.print_r($response, true).'</pre>';
