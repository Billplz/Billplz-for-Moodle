<?php
/**
 * Billplz enrolments plugin settings and presets.
 *
 * @package    enrol_billplz
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    //--- settings ------------------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_billplz_settings', '', get_string('pluginname_desc', 'enrol_billplz')));

    $settings->add(new admin_setting_configtext('enrol_billplz/billplz_api_key', get_string('api_key', 'enrol_billplz'), get_string('api_key_desc', 'enrol_billplz'), '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('enrol_billplz/billplz_collection_id', get_string('collection_id', 'enrol_billplz'), get_string('collection_id_desc', 'enrol_billplz'), '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('enrol_billplz/billplz_x_signature', get_string('x_signature', 'enrol_billplz'), get_string('x_signature_desc', 'enrol_billplz'), '', PARAM_TEXT));

    $settings->add(new admin_setting_configcheckbox('enrol_billplz/mailstudents', get_string('mailstudents', 'enrol_billplz'), '', 0));

    $settings->add(new admin_setting_configcheckbox('enrol_billplz/mailteachers', get_string('mailteachers', 'enrol_billplz'), '', 0));

    $settings->add(new admin_setting_configcheckbox('enrol_billplz/mailadmins', get_string('mailadmins', 'enrol_billplz'), '', 0));

    // Note: let's reuse the ext sync constants and strings here, internally it is very similar,
    //       it describes what should happen when users are not supposed to be enrolled any more.
    $options = array(
        ENROL_EXT_REMOVED_KEEP => get_string('extremovedkeep', 'enrol'),
        ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol'),
        ENROL_EXT_REMOVED_UNENROL => get_string('extremovedunenrol', 'enrol'),
    );
    $settings->add(new admin_setting_configselect('enrol_billplz/expiredaction', get_string('expiredaction', 'enrol_billplz'), get_string('expiredaction_help', 'enrol_billplz'), ENROL_EXT_REMOVED_SUSPENDNOROLES, $options));

    //--- enrol instance defaults ----------------------------------------------------------------------------
    $settings->add(new admin_setting_heading(
        'enrol_billplz_defaults',
        get_string('enrolinstancedefaults', 'admin'),
        get_string('enrolinstancedefaults_desc', 'admin')
    ));

    $options = array(ENROL_INSTANCE_ENABLED => get_string('yes'),
        ENROL_INSTANCE_DISABLED => get_string('no'));
    $settings->add(new admin_setting_configselect(
        'enrol_billplz/status',
        get_string('status', 'enrol_billplz'),
        get_string('status_desc', 'enrol_billplz'),
        ENROL_INSTANCE_DISABLED,
        $options
    ));

    $settings->add(new admin_setting_configtext('enrol_billplz/cost', get_string('cost', 'enrol_billplz'), '', 0, PARAM_FLOAT, 4));

    $billplzcurrency = enrol_get_plugin('billplz')->get_currencies();
    $settings->add(new admin_setting_configselect('enrol_billplz/currency', get_string('currency', 'enrol_billplz'), '', 'MYR', $billplzcurrency));

    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect(
            'enrol_billplz/roleid',
            get_string('defaultrole', 'enrol_billplz'),
            get_string('defaultrole_desc', 'enrol_billplz'),
            $student->id,
            $options
        ));
    }

    $settings->add(new admin_setting_configduration(
        'enrol_billplz/enrolperiod',
        get_string('enrolperiod', 'enrol_billplz'),
        get_string('enrolperiod_desc', 'enrol_billplz'),
        0
    ));
}
