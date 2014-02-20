<?php
/*
 * VIEW
 * Prints the views for Pairing and Unpairing
 *
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n = optional_param('n', 0, PARAM_INT);  // mlatch instance ID - it should be named as the first character of the module

if ($id) {
    $cm = get_coursemodule_from_id('mlatch', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $mlatch = $DB->get_record('mlatch', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $mlatch = $DB->get_record('mlatch', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $mlatch->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('mlatch', $mlatch->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

add_to_log($course->id, 'mlatch', 'view', "view.php?id={$cm->id}", $mlatch->name, $cm->id);

//Page header

$PAGE->set_url('/mod/mlatch/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($mlatch->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$PAGE->set_cacheable(false);

echo $OUTPUT->header();

if ($mlatch->intro) { // Conditions to show the intro can change to look for own settings or whatever
    echo $OUTPUT->box(format_module_intro('mlatch', $mlatch, $cm->id), 'generalbox mod_introbox', 'mlatchintro');
}

// MLatch code
// in case the user is already paired it asks for unpair
$current_user = $USER->id;
$isPaired = $DB->get_record('mlatch_accounts', array('userid' => $current_user));
$sesskey = sesskey();

if ($isPaired) {
    //------- Unpairing Form -------//
    echo $OUTPUT->heading(get_string('titleunpairing','mlatch'));
    echo $OUTPUT->box(get_string('typeunpairing','mlatch'));
    echo '<center><form method="post" action="pairing.php">
       <input type="hidden" name="sesskey" value="' . $sesskey . '">
       <input type="checkbox" name="unpair">
       <input type="submit" style="font-size: 15px; color:white; background-color: #09d6db" value="'.get_string('unpairmyaccount','mlatch').'">
       <enctype="text/plain">
       </form></center>';
} else {
    //------- Pairing Form -------//
    echo $OUTPUT->heading(get_string('titlepairing','mlatch'));
    echo $OUTPUT->box(get_string('typepairing','mlatch'));

    echo '<center><form method="post" action="pairing.php">
        <input type="hidden" name="sesskey" value="' . $sesskey . '">
       <input type="text" name="pairingtoken">
       <input type="submit" style="font-size: 15px; color:white;  background-color: #09d6db" value="'.get_string('pairmyaccount','mlatch').'">
       <enctype="text/plain">
       </form></center>';
}

echo '<center
     <br><br><br><br>
    <img src="pix/icon.png" alt="latch icon" width="30" height="30" ></img>
    </center>';

echo $OUTPUT->footer();
