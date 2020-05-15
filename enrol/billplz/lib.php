<?php
/**
 * Billplz enrolment plugin.
 *
 * This plugin allows you to set up paid courses.
 *
 * @package    enrol_billplz
 */

defined('MOODLE_INTERNAL') || die();

class enrol_billplz_plugin extends enrol_plugin
{

    public function get_currencies()
    {
        $codes = array('MYR');
        $currencies = array();
        foreach ($codes as $c) {
            $currencies[$c] = new lang_string($c, 'core_currencies');
        }

        return $currencies;
    }

    /**
     * Returns optional enrolment information icons.
     *
     * This is used in course list for quick overview of enrolment options.
     *
     * We are not using single instance parameter because sometimes
     * we might want to prevent icon repetition when multiple instances
     * of one type exist. One instance may also produce several icons.
     *
     * @param array $instances all enrol instances of this type in one course
     * @return array of pix_icon
     */
    public function get_info_icons(array $instances)
    {
        $found = false;
        foreach ($instances as $instance) {
            if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
                continue;
            }
            if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
                continue;
            }
            $found = true;
            break;
        }
        if ($found) {
            return array(new pix_icon('icon', get_string('pluginname', 'enrol_billplz'), 'enrol_billplz'));
        }
        return array();
    }

    public function roles_protected()
    {
        // users with role assign cap may tweak the roles later
        return false;
    }

    public function allow_unenrol(stdClass $instance)
    {
        // users with unenrol cap may unenrol other users manually - requires enrol/billplz:unenrol
        return true;
    }

    public function allow_manage(stdClass $instance)
    {
        // users with manage cap may tweak period and status - requires enrol/billplz:manage
        return true;
    }

    public function show_enrolme_link(stdClass $instance)
    {
        return ($instance->status == ENROL_INSTANCE_ENABLED);
    }

    /**
     * Returns true if the user can add a new instance in this course.
     * @param int $courseid
     * @return boolean
     */
    public function can_add_instance($courseid)
    {
        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/billplz:config', $context)) {
            return false;
        }

        // multiple instances supported - different cost for different roles
        return true;
    }

    /**
     * We are a good plugin and don't invent our own UI/validation code path.
     *
     * @return boolean
     */
    public function use_standard_editing_ui()
    {
        return true;
    }

    /**
     * Add new instance of enrol plugin.
     * @param object $course
     * @param array $fields instance fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, array $fields = null)
    {
        if ($fields && !empty($fields['cost'])) {
            $fields['cost'] = unformat_float($fields['cost']);
        }
        return parent::add_instance($course, $fields);
    }

    /**
     * Update instance of enrol plugin.
     * @param stdClass $instance
     * @param stdClass $data modified instance fields
     * @return boolean
     */
    public function update_instance($instance, $data)
    {
        if ($data) {
            $data->cost = unformat_float($data->cost);
        }
        return parent::update_instance($instance, $data);
    }

    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function enrol_page_hook(stdClass $instance)
    {
        global $CFG, $USER, $OUTPUT, $PAGE, $DB;

        ob_start();

        if ($DB->record_exists('user_enrolments', array('userid' => $USER->id, 'enrolid' => $instance->id))) {
            return ob_get_clean();
        }

        if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
            return ob_get_clean();
        }

        if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
            return ob_get_clean();
        }

        $course = $DB->get_record('course', array('id' => $instance->courseid));
        $context = context_course::instance($course->id);

        $shortname = format_string($course->shortname, true, array('context' => $context));
        $strloginto = get_string("loginto", "", $shortname);
        $strcourses = get_string("courses");

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

        if ((float) $instance->cost <= 0) {
            $cost = (float) $this->get_config('cost');
        } else {
            $cost = (float) $instance->cost;
        }

        if (abs($cost) < 0.01) {
            // no cost, other enrolment methods (instances) should be used
            echo '<p>' . get_string('nocost', 'enrol_billplz') . '</p>';
        } else {
            // Calculate localised and "." cost, make sure we send Billplz the same value
            $localisedcost = format_float($cost, 2, true);
            $cost = format_float($cost, 2, false);

            if (isguestuser()) {
                // force login only for guest user, not real users with guest role
                $wwwroot = $CFG->wwwroot;
                echo '<div class="mdl-align"><p>' . get_string('paymentrequired') . '</p>';
                echo '<p><b>' . get_string('cost') . ": $instance->currency $localisedcost" . '</b></p>';
                echo '<p><a href="' . $wwwroot . '/login/">' . get_string('loginsite') . '</a></p>';
                echo '</div>';
            } else {
                $instancename = $this->get_instance_name($instance);
                include $CFG->dirroot . '/enrol/billplz/enrol.html';
            }
        }

        return $OUTPUT->box(ob_get_clean());
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid)
    {
        global $DB;
        if ($step->get_task()->get_target() == backup::TARGET_NEW_COURSE) {
            $merge = false;
        } else {
            $merge = array(
                'courseid' => $data->courseid,
                'enrol' => $this->get_name(),
                'roleid' => $data->roleid,
                'cost' => $data->cost,
                'currency' => $data->currency,
            );
        }
        if ($merge and $instances = $DB->get_records('enrol', $merge, 'id')) {
            $instance = reset($instances);
            $instanceid = $instance->id;
        } else {
            $instanceid = $this->add_instance($course, (array) $data);
        }
        $step->set_mapping('enrol', $oldid, $instanceid);
    }

    /**
     * Restore user enrolment.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $oldinstancestatus
     * @param int $userid
     */
    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance, $userid, $oldinstancestatus)
    {
        $this->enrol_user($instance, $userid, null, $data->timestart, $data->timeend, $data->status);
    }

    /**
     * Return an array of valid options for the status.
     *
     * @return array
     */
    protected function get_status_options()
    {
        $options = array(ENROL_INSTANCE_ENABLED => get_string('yes'),
            ENROL_INSTANCE_DISABLED => get_string('no'));
        return $options;
    }

    /**
     * Return an array of valid options for the roleid.
     *
     * @param stdClass $instance
     * @param context $context
     * @return array
     */
    protected function get_roleid_options($instance, $context)
    {
        if ($instance->id) {
            $roles = get_default_enrol_roles($context, $instance->roleid);
        } else {
            $roles = get_default_enrol_roles($context, $this->get_config('roleid'));
        }
        return $roles;
    }

    /**
     * Add elements to the edit instance form.
     *
     * @param stdClass $instance
     * @param MoodleQuickForm $mform
     * @param context $context
     * @return bool
     */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context)
    {

        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));
        $mform->setType('name', PARAM_TEXT);

        $options = $this->get_status_options();
        $mform->addElement('select', 'status', get_string('status', 'enrol_billplz'), $options);
        $mform->setDefault('status', $this->get_config('status'));

        $mform->addElement('text', 'cost', get_string('cost', 'enrol_billplz'), array('size' => 4));
        $mform->setType('cost', PARAM_RAW);
        $mform->setDefault('cost', format_float($this->get_config('cost'), 2, true));

        $billplzcurrency = $this->get_currencies();
        $mform->addElement('select', 'currency', get_string('currency', 'enrol_billplz'), $billplzcurrency);
        $mform->setDefault('currency', $this->get_config('currency'));

        $roles = $this->get_roleid_options($instance, $context);
        $mform->addElement('select', 'roleid', get_string('assignrole', 'enrol_billplz'), $roles);
        $mform->setDefault('roleid', $this->get_config('roleid'));

        $options = array('optional' => true, 'defaultunit' => 86400);
        $mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_billplz'), $options);
        $mform->setDefault('enrolperiod', $this->get_config('enrolperiod'));
        $mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_billplz');

        $options = array('optional' => true);
        $mform->addElement('date_time_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_billplz'), $options);
        $mform->setDefault('enrolstartdate', 0);
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_billplz');

        $options = array('optional' => true);
        $mform->addElement('date_time_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_billplz'), $options);
        $mform->setDefault('enrolenddate', 0);
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_billplz');

        if (enrol_accessing_via_instance($instance)) {
            $warningtext = get_string('instanceeditselfwarningtext', 'core_enrol');
            $mform->addElement('static', 'selfwarn', get_string('instanceeditselfwarning', 'core_enrol'), $warningtext);
        }
    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param object $instance The instance loaded from the DB
     * @param context $context The context of the instance we are editing
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     * @return void
     */
    public function edit_instance_validation($data, $files, $instance, $context)
    {
        $errors = array();

        if (!empty($data['enrolenddate']) and $data['enrolenddate'] < $data['enrolstartdate']) {
            $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_billplz');
        }

        $cost = str_replace(get_string('decsep', 'langconfig'), '.', $data['cost']);
        if (!is_numeric($cost)) {
            $errors['cost'] = get_string('costerror', 'enrol_billplz');
        }

        $validstatus = array_keys($this->get_status_options());
        $validcurrency = array_keys($this->get_currencies());
        $validroles = array_keys($this->get_roleid_options($instance, $context));
        $tovalidate = array(
            'name' => PARAM_TEXT,
            'status' => $validstatus,
            'currency' => $validcurrency,
            'roleid' => $validroles,
            'enrolperiod' => PARAM_INT,
            'enrolstartdate' => PARAM_INT,
            'enrolenddate' => PARAM_INT,
        );

        $typeerrors = $this->validate_param_types($data, $tovalidate);
        $errors = array_merge($errors, $typeerrors);

        return $errors;
    }

    /**
     * Execute synchronisation.
     * @param progress_trace $trace
     * @return int exit code, 0 means ok
     */
    public function sync(progress_trace $trace)
    {
        $this->process_expirations($trace);
        return 0;
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance)
    {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/billplz:config', $context);
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance)
    {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/billplz:config', $context);
    }
}
