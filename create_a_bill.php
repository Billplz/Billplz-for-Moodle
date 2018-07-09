<?php

// @codingStandardsIgnoreLine This script does not require login.
require("../../config.php");
require("$CFG->dirroot/enrol/billplz/classes/vendor/autoload.php");
//require_once("lib.php");

use Billplz\API;
use Billplz\Connect;

// Make sure we are enabled in the first place.
if (!enrol_is_enabled('billplz')) {
    http_response_code(503);
    throw new moodle_exception('errdisabled', 'enrol_billplz');
}

/// Keep out casual intruders
if (empty($_POST) or !empty($_GET)) {
    http_response_code(400);
    throw new moodle_exception('invalidrequest', 'core_error');
}

$plugin = enrol_get_plugin('billplz');

$raw_string = $_POST['amount']. $_POST['custom'];
$filtered_string = preg_replace("/[^a-zA-Z0-9]+/", "", $raw_string);
$new_hash = hash_hmac('sha256', $filtered_string, $plugin->get_config('billplzx_signature'));
$hash = $_POST['hash'];

if (strcmp($new_hash, $hash)) {
    exit('Calculated Hash does not valid. Contact developer for more information.');
}

$connnect = (new Connect($plugin->get_config('billplzapi_key')))->detectMode();
$billplz = new API($connnect);

$parameter = array(
    'collection_id' => $plugin->get_config('billplzcollection_id'),
    'email' => $_POST['email'],
    'mobile'=> $_POST['mobile'],
    'name' => trim($_POST['name']),
    'amount' => strval($_POST['amount'] * 100),
    'callback_url' => "$CFG->wwwroot/enrol/billplz/callback.php",
    'description' => mb_substr(trim($_POST['description']), 0, 199)
);
$optional = array(
    'redirect_url' => "$CFG->wwwroot/enrol/billplz/redirect.php",
    'reference_1_label' => mb_substr('Ref', 0, 19),
    'reference_1' => mb_substr($_POST['custom'], 0, 119),
    'reference_2_label' => '',
    'reference_2' => ''
);

list($rheader, $rbody) = $billplz->toArray($billplz->createBill($parameter, $optional, '0'));

//echo '<pre>'.print_r($rbody, true). '</pre>';
header('Location: ' . $rbody['url']);
