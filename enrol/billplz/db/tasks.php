<?php

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => '\enrol_billplz\task\process_expirations',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 0
    )
);