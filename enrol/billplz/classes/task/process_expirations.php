<?php

namespace enrol_billplz\task;

defined('MOODLE_INTERNAL') || die();

class process_expirations extends \core\task\scheduled_task {

    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('processexpirationstask', 'enrol_billplz');
    }

    /**
     * Run task for processing expirations.
     */
    public function execute() {
        $enrol = enrol_get_plugin('billplz');
        $trace = new \text_progress_trace();
        $enrol->process_expirations($trace);
    }

}
