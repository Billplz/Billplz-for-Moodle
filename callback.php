<?php

// Disable moodle specific debug messages and any errors in output,
// comment out when debugging or better look into error log!
define('NO_DEBUG_DISPLAY', true);//Change to true before push to github

// @codingStandardsIgnoreLine This script does not require login.
require("../../config.php");
require("$CFG->dirroot/enrol/billplz/classes/vendor/autoload.php");
require_once("$CFG->dirroot/enrol/billplz/lib.php");
require_once($CFG->libdir.'/eventslib.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->libdir . '/filelib.php');

use Billplz\API;
use Billplz\Connect;

$plugin = enrol_get_plugin('billplz');
try {
    $data = Connect::getXSignature($plugin->get_config('billplzx_signature'));
} catch (\Exception $e) {
    exit($e->getMessage());
}

if (!$data['paid']) {
    exit;
}

// Make sure we are enabled in the first place.
if (!enrol_is_enabled('billplz')) {
    http_response_code(503);
    throw new moodle_exception('errdisabled', 'enrol_billplz');
}

$connnect = (new Connect($plugin->get_config('billplzapi_key')))->detectMode();
$billplz = new API($connnect);
list($rheader, $rbody) = $billplz->toArray($billplz->getBill($data['id']));
$user_course_instance = $rbody['reference_1'];
$custom = explode('-', $user_course_instance);

$data = new stdClass();

if (empty($custom) || count($custom) < 3) {
    throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Invalid value of the request param: custom');
}

$data->bill_id          = $rbody['id'];
$data->userid           = (int)$custom[0];
$data->courseid         = (int)$custom[1];
$data->instanceid       = (int)$custom[2];
$data->payment_gross    = format_float($rbody['amount'] / 100, 2, false);
$data->payment_currency = 'MYR';
$data->timeupdated      = time();

$user = $DB->get_record("user", array("id" => $data->userid), "*", MUST_EXIST);
$course = $DB->get_record("course", array("id" => $data->courseid), "*", MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);

$PAGE->set_context($context);

$plugin_instance = $DB->get_record("enrol", array("id" => $data->instanceid, "enrol" => "billplz", "status" => 0), "*", MUST_EXIST);

/// Now read the response and check if everything is OK.

if ($rbody['paid']) {
    /* Prevent multiple update */
    if ($existing = $DB->get_record("enrol_billplz", array("bill_id" => $rbody['id']), "*", IGNORE_MULTIPLE)) {
            \enrol_paypal\util::message_paypal_error_to_admin("Transaction ".$rbody['id']." is being repeated!", $data);
            die;
    }

    if (!$user = $DB->get_record('user', array('id'=>$data->userid))) {
        /* Check that user exists */
        die;
    }

    if (!$course = $DB->get_record('course', array('id'=>$data->courseid))) {
        /* Check that course exists */
        die;
    }

    $coursecontext = context_course::instance($course->id, IGNORE_MISSING);

        // Check that amount paid is the correct amount
    if ((float) $plugin_instance->cost <= 0) {
        $cost = (float) $plugin->get_config('cost');
    } else {
        $cost = (float) $plugin_instance->cost;
    }

    if ($data->payment_gross < $cost) {
        /* Payment amount not match */
        die;
    }

    // Use the queried course's full name for the item_name field.
    $data->item_name = $course->fullname;

    // ALL CLEAR !

    $DB->insert_record("enrol_billplz", $data);

    if ($plugin_instance->enrolperiod) {
        $timestart = time();
        $timeend   = $timestart + $plugin_instance->enrolperiod;
    } else {
        $timestart = 0;
        $timeend   = 0;
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
    $mailadmins   = $plugin->get_config('mailadmins');
    $shortname = format_string($course->shortname, true, array('context' => $context));

    if (!empty($mailstudents)) {
        $a = new stdClass();
        $a->coursename = format_string($course->fullname, true, array('context' => $coursecontext));
        $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id";

        $eventdata = new \core\message\message();
        $eventdata->courseid          = $course->id;
        $eventdata->modulename        = 'moodle';
        $eventdata->component         = 'enrol_billplz';
        $eventdata->name              = 'billplz_enrolment';
        $eventdata->userfrom          = empty($teacher) ? core_user::get_noreply_user() : $teacher;
        $eventdata->userto            = $user;
        $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
        $eventdata->fullmessage       = get_string('welcometocoursetext', '', $a);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = '';
        message_send($eventdata);
    }

    if (!empty($mailteachers) && !empty($teacher)) {
        $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
        $a->user = fullname($user);

        $eventdata = new \core\message\message();
        $eventdata->courseid          = $course->id;
        $eventdata->modulename        = 'moodle';
        $eventdata->component         = 'enrol_billplz';
        $eventdata->name              = 'billplz_enrolment';
        $eventdata->userfrom          = $user;
        $eventdata->userto            = $teacher;
        $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
        $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = '';
        message_send($eventdata);
    }

    if (!empty($mailadmins)) {
        $a->course = format_string($course->fullname, true, array('context' => $coursecontext));
        $a->user = fullname($user);
        $admins = get_admins();
        foreach ($admins as $admin) {
            $eventdata = new \core\message\message();
            $eventdata->courseid          = $course->id;
            $eventdata->modulename        = 'moodle';
            $eventdata->component         = 'enrol_billplz';
            $eventdata->name              = 'billplz_enrolment';
            $eventdata->userfrom          = $user;
            $eventdata->userto            = $admin;
            $eventdata->subject           = get_string("enrolmentnew", 'enrol', $shortname);
            $eventdata->fullmessage       = get_string('enrolmentnewuser', 'enrol', $a);
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml   = '';
            $eventdata->smallmessage      = '';
            message_send($eventdata);
        }
    }
}
/* Not considering if the callback payment status is not paid */
//echo '<pre>'.print_r($data, true).'</pre>';
echo 'Callback';
