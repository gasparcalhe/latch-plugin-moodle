<?php


/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


// // Trigger login event from moodlelib.php
//    $event = \core\event\user_loggedin::create(
//        array(
//            'userid' => $USER->id,
//            'objectid' => $USER->id,
//            'other' => array('username' => $USER->username),
//        )
//    );
//    $event->trigger();


//$handlers = array (
//
//    'user_loggedin' => array ( //user_loggedin = nombre del evento (declarado en el core code)
//        'handlerfile'      => '/mod/mlatch/mlatch.php', # directorio del plog-in actualizar si lo cambio!
//        'handlerfunction'  => 'latch_aunthentication', # funcion dentro del .php para autenticar
//        'schedule'         => 'instant',
//        'internal'         => 1,
//    )
//	
//)


$observers = array(

    // User logging in
    array(
        'eventname' => '\core\event\user_loggedin',
        'callback' => 'latch_aunthentication',
        'includefile' => '/mod/mlatch/lib.php'
    )
);

	
?>