<?php

// Disable moodle specific debug messages and any errors in output,
// comment out when debugging or better look into error log!
define('NO_DEBUG_DISPLAY', true);

// @codingStandardsIgnoreLine This script does not require login.
require "../../config.php";
require_once "$CFG->dirroot/enrol/billplz/lib.php";
require_once $CFG->libdir . '/enrollib.php';
// require_once $CFG->libdir . '/filelib.php';
require "$CFG->dirroot/enrol/billplz/classes/api.php";
require "$CFG->dirroot/enrol/billplz/classes/connect.php";

use enrol_billplz\Connect;

// Make sure we are enabled in the first place.
if (!enrol_is_enabled('billplz')) {
    http_response_code(503);
    throw new moodle_exception('errdisabled', 'enrol_billplz');
}

$plugin = enrol_get_plugin('billplz');

try {
    $data = Connect::getXSignature($plugin->get_config('billplz_x_signature'));
} catch (\Exception $e) {
    http_response_code(403);
    exit($e->getMessage());
}

if (!$data['paid']) {
    exit;
}

$billplz_table = $DB->get_record("enrol_billplz", array("bill_id" => $data['id']));
if ($billplz_table->payment_status) {
    exit;
} else {
    $billplz_table->payment_status = $data['paid'];
}
$billplz_table->time_updated = time();
$DB->update_record("enrol_billplz", $billplz_table, false);

if (!$plugin_instance = $DB->get_record("enrol", array(
    "id" => $billplz_table->instance_id,
    "enrol" => "billplz",
    "courseid" => $billplz_table->course_id,
    "status" => 0)
)) {
    exit;
}

$PAGE->set_context($context);

if (!$user = $DB->get_record('user', array('id' => $billplz_table->user_id))) {
    /* Check that user exists */
    die;
}

if (!$course = $DB->get_record('course', array('id' => $billplz_table->course_id))) {
    /* Check that course exists */
    die;
}
$context = context_course::instance($course->id, MUST_EXIST);

$coursecontext = context_course::instance($course->id, IGNORE_MISSING);

if ($plugin_instance->enrolperiod) {
    $timestart = time();
    $timeend = $timestart + $plugin_instance->enrolperiod;
} else {
    $timestart = 0;
    $timeend = 0;
}

// Enrol user
$plugin->enrol_user($plugin_instance, $user->id, $plugin_instance->roleid, $timestart, $timeend);

// Pass $view=true to filter hidden caps if the user cannot see them
if ($users = get_users_by_capability(
    $context,
    'moodle/course:update',
    'u.*',
    'u.id ASC',
    '',
    '',
    '',
    '',
    false,
    true
)) {
    $users = sort_by_roleassignment_authority($users, $context);
    $teacher = array_shift($users);
} else {
    $teacher = false;
}

$mailstudents = $plugin->get_config('mailstudents');
$mailteachers = $plugin->get_config('mailteachers');
$mailadmins = $plugin->get_config('mailadmins');
$shortname = format_string($course->shortname, true, array('context' => $context));

if (!empty($mailstudents)) {
    $a = new stdClass();
    $a->coursename = format_string($course->fullname, true, array('context' => $coursecontext));
    $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id";

    $eventdata = new \core\message\message();
    $eventdata->courseid = $course->id;
    $eventdata->modulename = 'moodle';
    $eventdata->component = 'enrol_billplz';
    $eventdata->name = 'billplz_enrolment';
    $eventdata->userfrom = empty($teacher) ? core_user::get_noreply_user() : $teacher;
    $eventdata->userto = $user;
    $eventdata->subject = get_string("enrolmentnew", 'enrol', $shortname);
    $eventdata->fullmessage = get_string('welcometocoursetext', '', $a);
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml = '';
    $eventdata->smallmessage = '';
    message_send($eventdata);
}

if (!empty($mailteachers) && !empty($teacher)) {
    $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
    $a->user = fullname($user);

    $eventdata = new \core\message\message();
    $eventdata->courseid = $course->id;
    $eventdata->modulename = 'moodle';
    $eventdata->component = 'enrol_billplz';
    $eventdata->name = 'billplz_enrolment';
    $eventdata->userfrom = $user;
    $eventdata->userto = $teacher;
    $eventdata->subject = get_string("enrolmentnew", 'enrol', $shortname);
    $eventdata->fullmessage = get_string('enrolmentnewuser', 'enrol', $a);
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml = '';
    $eventdata->smallmessage = '';
    message_send($eventdata);
}

if (!empty($mailadmins)) {
    $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
    $a->user = fullname($user);
    $admins = get_admins();
    foreach ($admins as $admin) {
        $eventdata = new \core\message\message();
        $eventdata->courseid = $course->id;
        $eventdata->modulename = 'moodle';
        $eventdata->component = 'enrol_billplz';
        $eventdata->name = 'billplz_enrolment';
        $eventdata->userfrom = $user;
        $eventdata->userto = $admin;
        $eventdata->subject = get_string("enrolmentnew", 'enrol', $shortname);
        $eventdata->fullmessage = get_string('enrolmentnewuser', 'enrol', $a);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = '';
        $eventdata->smallmessage = '';
        message_send($eventdata);
    }
}
