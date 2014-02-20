<?php

/**
 * Library of interface functions and constants for module mlatch
 * 
 * Here is performed the AUTHENTICATION with LATCH
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the mlatch specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 */
require_once("Latch.php");
require_once("LatchResponse.php");
require_once("Error.php");


defined('MOODLE_INTERNAL') || die();

/** example constant */
//define('MLATCH_ULTIMATE_ANSWER', 42);

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function mlatch_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:         return true;
        case FEATURE_SHOW_DESCRIPTION:  return true;

        default:                        return null;
    }
}

/**
 * Saves a new instance of the mlatch into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $mlatch An object from the form in mod_form.php
 * @param mod_mlatch_mod_form $mform
 * @return int The id of the newly inserted mlatch record
 */
function mlatch_add_instance(stdClass $mlatch, mod_mlatch_mod_form $mform = null) {
    global $DB;

    $mlatch->timecreated = time();

    # You may have to add extra stuff in here #

    return $DB->insert_record('mlatch', $mlatch);
}

/**
 * Updates an instance of the mlatch in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $mlatch An object from the form in mod_form.php
 * @param mod_mlatch_mod_form $mform
 * @return boolean Success/Fail
 */
function mlatch_update_instance(stdClass $mlatch, mod_mlatch_mod_form $mform = null) {
    global $DB;

    $mlatch->timemodified = time();
    $mlatch->id = $mlatch->instance;

    # You may have to add extra stuff in here #

    return $DB->update_record('mlatch', $mlatch);
}

/**
 * Removes an instance of the mlatch from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function mlatch_delete_instance($id) {
    global $DB;

    if (! $mlatch = $DB->get_record('mlatch', array('id' => $id))) {
        return false;
    }

    # Delete any dependent records here #

    $DB->delete_records('mlatch', array('id' => $mlatch->id));

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function mlatch_user_outline($course, $user, $mod, $mlatch) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $mlatch the module instance record
 * @return void, is supposed to echp directly
 */
function mlatch_user_complete($course, $user, $mod, $mlatch) {
}


/**
 * AUNTHENTICATION
 * 
 * From the loggin event it performs the authentication for accounts paired with
 * Latch. It is executed everytime a paired user enters in Moodle
 * 
 */


function latch_aunthentication(\core\event\user_loggedin $event) {

    global $USER;
    global $DB;
    global $SESSION;

    $current_user = $USER->id;
    $config_table = 'config';
    $accounts_table = 'mlatch_accounts';

    // if the user introduces its otp
    if (isset($_POST['otp'])) {

        $otpLatchA = $DB->get_record($accounts_table, array('userid' => $current_user), 'two_factor');
        $otpLatch = $otpLatchA->two_factor;
        $otpUser = $_POST['otp'];

        // checks if the token introduced is correct
        if ($otpLatch === $otpUser) {
            // before continue deletes the two factor field in the data base
            $updates = new stdClass();
            $idA = $DB->get_record('mlatch_accounts', array('userid' => $current_user), 'id');
            $updates->two_factor = '';
            $updates->id = $idA->id;
            $DB->update_record($accounts_table, $updates);
            return true;
        } else {
            // if the token is not rigth forces the log out of the user and exit
            require_logout();
            return true;
        }
    }

    // checks if the user is paired
    $isPaired = $DB->get_record('mlatch_accounts', array('userid' => $current_user));
    if (!$isPaired) {
        return true;
    } else {

        $aValuesAppId = $DB->get_record($config_table, array('name' => 'App_Id'), 'value');
        $aValuesSecret = $DB->get_record($config_table, array('name' => 'App_Secret'), 'value');
        $aValuesHost = $DB->get_record($config_table, array('name' => 'Latch_Host'), 'value');

        $host = $aValuesHost->value;
        $appId = $aValuesAppId->value;
        $appSec = $aValuesSecret->value;

        if (!empty($host)) {
            Latch::setHost(rtrim($host, '/'));
        }

        $accountUsr = $DB->get_record($accounts_table, array('userid' => $current_user));
        $accountId = $accountUsr->accountid;
        if (empty($appId) || empty($appSec)) {
            return false;
        } else {
            $api = new Latch($appId, $appSec);
            $statusResponse = $api->status($accountId);

            $dataResponse = $statusResponse->getData();
            $responseError = $statusResponse->getError(); 

            if (empty($dataResponse) && !empty($responseError)) {
                return true;
            }

            // LOCKED OR NOT LOCKED: 'on' = unlocked; 'off' = locked;

            $pass = $dataResponse->{"operations"}->{$appId}->{"status"};

            // UNLOCK AND TWO FACTOR
            // $pass: 'on' = unlocked; 'off' = locked;

            if ($pass === "on") {
                if (!empty($dataResponse->{"operations"}->{$appId}->{"two_factor"})) {
                    // performs two factor
                    $twoFactor = $dataResponse->{"operations"}->{$appId}->{"two_factor"};
                    $token2F = $twoFactor->token;
                    // stores the token on the database
                    $current_id = $USER->id;
                    $updates = new stdClass();
                    $idA = $DB->get_record('mlatch_accounts', array('userid' => $current_user), 'id');

                    $updates->two_factor = $token2F;
                    $updates->id = $idA->id;
                    $DB->update_record($accounts_table, $updates);
                    include 'twoFactor.php';
                    die(); // the execution of the function dies here
                } else {
                    // let the user pass
                    return true;
                }
            } else {
                // forces the logout
                require_logout();
            }
        }
    }
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in mlatch activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function mlatch_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link mlatch_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function mlatch_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see mlatch_get_recent_mod_activity()}

 * @return void
 */
function mlatch_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function mlatch_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function mlatch_get_extra_capabilities() {
    return array();
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Is a given scale used by the instance of mlatch?
 *
 * This function returns if a scale is being used by one mlatch
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $mlatchid ID of an instance of this module
 * @return bool true if the scale is used by the given mlatch instance
 */
function mlatch_scale_used($mlatchid, $scaleid) {
    global $DB;

    /** @example */
    if ($scaleid and $DB->record_exists('mlatch', array('id' => $mlatchid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of mlatch.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @return boolean true if the scale is used by any mlatch instance
 */
function mlatch_scale_used_anywhere($scaleid) {
    global $DB;

    /** @example */
    if ($scaleid and $DB->record_exists('mlatch', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the give mlatch instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $mlatch instance object with extra cmidnumber and modname property
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return void
 */
function mlatch_grade_item_update(stdClass $mlatch, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    /** @example */
    $item = array();
    $item['itemname'] = clean_param($mlatch->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax']  = $mlatch->grade;
    $item['grademin']  = 0;

    grade_update('mod/mlatch', $mlatch->course, 'mod', 'mlatch', $mlatch->id, 0, null, $item);
}

/**
 * Update mlatch grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $mlatch instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
function mlatch_update_grades(stdClass $mlatch, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    /** @example */
    $grades = array(); // populate array of grade objects indexed by userid

    grade_update('mod/mlatch', $mlatch->course, 'mod', 'mlatch', $mlatch->id, 0, $grades);
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function mlatch_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for mlatch file areas
 *
 * @package mod_mlatch
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function mlatch_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the mlatch file areas
 *
 * @package mod_mlatch
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the mlatch's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function mlatch_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding mlatch nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the mlatch module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function mlatch_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the mlatch settings
 *
 * This function is called when the context for the page is a mlatch module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $mlatchnode {@link navigation_node}
 */
function mlatch_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $mlatchnode=null) {
}
