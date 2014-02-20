<?php

/* 
 * SETTINGS
 * Settings file for de Latch Module
 * 
 * 
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    
    global $DB;

    
    $latch_host_def = 'https://latch.elevenpaths.com/';
    $latch_host = array(0 => $latch_host_def);
    
    $settings->add(new admin_setting_configtext('App_Id', 'App Id', get_string('appid', 'mlatch'), null, PARAM_ACTION, 20));

    $settings->add(new admin_setting_configtext('App_Secret', 'App Secret', get_string('appsec', 'mlatch'), null, PARAM_ACTION, 40));

    $settings->add(new admin_setting_configtext('Latch_Host', 'Latch Host', get_string('apphost', 'mlatch'), $latch_host_def, PARAM_URL, null)); // indica a que servidor va a hacer la peticion
    // *******************************************************************
}

