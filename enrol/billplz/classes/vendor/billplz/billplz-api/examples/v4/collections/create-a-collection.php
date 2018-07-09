<?php

require 'vendor/autoload.php';
error_reporting(E_ALL);
ini_set('display_errors', 'On');

use Billplz\API;
use Billplz\Connect;

$connnect = (new Connect('4e49de80-1670-4606-84f8-2f1d33a38670'))->detectMode();
//$connect->setMode(true); // true: staging | false: production (default)

$billplz = new API($connnect);

$collectionName = 'My First Collection';

$optional  = array(
    /* Does not supported to post logo */
    'split_header' => '',
    /* This code does not support setting up 2 split payments receiver due to same array key value issues */
    'split_payments[]' => array(
            'email' => 'wan@billplz.com',
            'fixed_cut' => '100',
            'variable_cut' => '',
            'stack_order' => '0'
    )
);
$response = $billplz->createCollection($collectionName);
//$response = $billplz->createCollection($collectionName, $optional);

echo '<pre>'.print_r($response, true).'</pre>';
