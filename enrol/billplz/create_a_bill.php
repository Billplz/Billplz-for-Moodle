<?php

// @codingStandardsIgnoreLine This script does not require login.
require "../../config.php";
require "$CFG->dirroot/enrol/billplz/classes/api.php";
require "$CFG->dirroot/enrol/billplz/classes/connect.php";
//require_once("lib.php");

// Make sure we are enabled in the first place.
if (!enrol_is_enabled('billplz')) {
    http_response_code(503);
    throw new moodle_exception('errdisabled', 'enrol_billplz');
}

use enrol_billplz\API;
use enrol_billplz\Connect;

/// Keep out casual intruders
if (!isset($_POST['instance_id']) || !isset($_POST['course_id'])) {
    http_response_code(400);
    throw new moodle_exception('invalidrequest', 'core_error');
}

require_login();

$plugin = enrol_get_plugin('billplz');

$connnect = (new Connect($plugin->get_config('billplz_api_key')))->detectMode();
$billplz = new API($connnect);

$plugin_instance = $DB->get_record("enrol", array(
    "id" => $_POST['instance_id'],
    "enrol" => "billplz",
    "courseid" => $_POST['course_id'],
    "status" => 0,

), "*", MUST_EXIST);

$course = $DB->get_record('course', array('id' => $plugin_instance->courseid));
$context = context_course::instance($course->id);
$description = format_string($course->fullname, true, array('context' => $context));

if ((float) $plugin_instance->cost <= 0) {
    $cost = (float) $plugin->get_config('cost');
} else {
    $cost = (float) $plugin_instance->cost;
}

$parameter = array(
    'collection_id' => trim($plugin->get_config('billplz_collection_id')),
    'email' => $USER->email,
    'mobile' => !empty($USER->phone1) ? $USER->phone1 : $USER->phone2,
    'name' => fullname($USER),
    'amount' => strval($cost * 100),
    'callback_url' => "$CFG->wwwroot/enrol/billplz/callback.php",
    'description' => mb_substr(trim($description), 0, 199),
);

$optional = array(
    'redirect_url' => "$CFG->wwwroot/enrol/billplz/redirect.php",
    'reference_1_label' => 'Course ID',
    'reference_1' => $course->id,
);

list($rheader, $rbody) = $billplz->toArray($billplz->createBill($parameter, $optional));

if ($rheader === 200) {
    $data = new stdClass();
    $data->bill_id = $rbody['id'];
    $data->course_id = $course->id;
    $data->user_id = $USER->id;
    $data->instance_id = $plugin_instance->id;
    $data->payment_status = $rbody['paid'];
    $data->time_updated = time();
    $DB->insert_record("enrol_billplz", $data);
    header('Location: ' . $rbody['url']);
} else {
    throw new moodle_exception('invalidrequest', 'core_error', '', null, print_r($rbody, true));
    echo '<pre>' . print_r($rbody, true) . '</pre>';
}
