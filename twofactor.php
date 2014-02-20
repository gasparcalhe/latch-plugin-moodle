
<?php

/*
 * TWO FACTOR
 * It generates the view for the two factor submit process and
 * forces logout previously
 *
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

global $DB, $OUTPUT, $PAGE;
$idauxA = $DB->get_record('modules', array('name' => 'mlatch'), 'id');
$idaux = $idauxA->id;
$cmidA = $DB->get_record('course_modules', array('module' => $idaux), 'id');
$cmid = $cmidA->id;

require_logout();

$mlatch = 'Second Factor'; 
$PAGE->set_url('/mod/mlatch/twofactor.php?id='.$cmid); 
$PAGE->set_title($mlatch); 

echo $OUTPUT->header();

echo $OUTPUT->heading('Latch Second Factor');
echo $OUTPUT->box('Introduce your OTP in the field below: ');

echo '<center><form method="post" action="../login/index.php">
     <input type="text" name="otp">
    <input type="submit" value="Submit my OTP"> 
     <input type="hidden" name="username" id="username" size="15" value="' . htmlspecialchars($_POST['username']) . '">
     <input type="hidden" name="password" id="password" size="15" value="' . htmlspecialchars($_POST['password']) . '">      
     <enctype="text/plain">
     </form></center>';

