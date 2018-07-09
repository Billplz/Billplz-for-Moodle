<?php
/**
 * Billplz utility script
 *
 * @package    enrol_billplz
 * @copyright  2018 Wan @ Billplz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require("../../config.php");
require("$CFG->dirroot/enrol/billplz/classes/vendor/autoload.php");
require_once("$CFG->dirroot/enrol/billplz/lib.php");

use Billplz\API;
use Billplz\Connect;

$plugin = enrol_get_plugin('billplz');
try {
    $data = Connect::getXSignature($plugin->get_config('billplzx_signature'));
} catch (\Exception $e) {
    redirect($CFG->wwwroot, $e->getMessage(), 10);
}

if (!$data['paid']) {
    redirect($CFG->wwwroot);
}

$connnect = (new Connect($plugin->get_config('billplzapi_key')))->detectMode();
$billplz = new API($connnect);
list($rheader, $rbody) = $billplz->toArray($billplz->getBill($data['id']));
$user_course_instance = $rbody['reference_1'];
$user_course_instance_array = explode('-', $user_course_instance);
$id = $user_course_instance_array[1];

if (!$course = $DB->get_record("course", array("id"=>$id))) {
    redirect($CFG->wwwroot);
}

$context = context_course::instance($course->id, MUST_EXIST);
$PAGE->set_context($context);

require_login();

if (!empty($SESSION->wantsurl)) {
    $destination = $SESSION->wantsurl;
    unset($SESSION->wantsurl);
} else {
    $destination = "$CFG->wwwroot/course/view.php?id=$course->id";
}

$fullname = format_string($course->fullname, true, array('context' => $context));

if (is_enrolled($context, null, '', true)) {
    redirect($destination, get_string('paymentthanks', '', $fullname));
} else {   /// Somehow they aren't enrolled yet!  :-(
    $PAGE->set_url($destination);
    echo $OUTPUT->header();
    $a = new stdClass();
    $a->teacher = get_string('defaultcourseteacher');
    $a->fullname = $fullname;
    notice(get_string('paymentsorry', '', $a), $destination);
}
