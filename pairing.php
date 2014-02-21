<?php

/*
 *
 * PAIRING OPERATIONS
 * This script performs Pairing and Unpairing operations for Latch accounts
 * 
 */

require_once("Latch.php");
require_once("LatchResponse.php");
require_once('../../config.php'); // for access to db in get_record() and similar

class MLatchPairingOperations {

    public function pairingOperations() {

        global $DB;
        global $USER;
        global $PAGE;

        $table = 'config';
        $accounts_table = 'mlatch_accounts';

        // $cmid stores the course module identificator
        $cmid = $this->getModuleId();

        // Distinguish between Pairing and Unpairing process
        if (!empty($_REQUEST['pairingtoken'])) {
            $token = $_REQUEST['pairingtoken'];
        } else {
            $token = false;
        }

        if (!empty($_POST['unpair'])) {
            $unpair = $_POST['unpair'];
        } else {
            $unpair = false;
        }
        
        if((!$unpair)&&(!$token)&&empty($_POST['flag'])){
             $mens = get_string('emptytoken','mlatch');
             $host = './view.php?id=' . $cmid;
             redirect($host, $mens);
        }

        // Gets the application data from the DB
        $aValuesAppId = $DB->get_record($table, array('name' => 'App_Id'), 'value');
        $aValuesSecret = $DB->get_record($table, array('name' => 'App_Secret'), 'value');
        $aValuesHost = $DB->get_record($table, array('name' => 'Latch_Host'), 'value');

        $appId = $aValuesAppId->value;
        $appSec = $aValuesSecret->value;
        $host = $aValuesHost->value;

        if (!empty($host)) {
            Latch::setHost(rtrim($host, '/'));
        }

        if (!empty($appId) && !empty($appSec)) {
            $api = new Latch($appId, $appSec);

// --------------  Performs Pairing --------------------
            // if the user has an unempty field Account Id in the DB it should not
            // continue

            $current_id = $USER->id;
            $isAccountSet = $DB->get_record($accounts_table, array('userid' => $current_id), 'accountid');
            $sesskeyUser = $_POST['sesskey'];
            if (!empty($token) && !$isAccountSet && confirm_sesskey($sesskeyUser)) {

                $pairResponse = $api->pair($token);
                $dataResponse = $pairResponse->getData();
                $dataError = $pairResponse->getError();

                if (!empty($dataError) && empty($dataResponse)) {
                    $mens = get_string('badtoken','mlatch');// 'Token not found, wrong or expired'
                }
                else if (!empty($dataError)) {
                    $mens =get_string('alreadypair','mlatch');// 'Account already paired'
                } else {

                    $dataResponse = $pairResponse->getData();

                    // Records the Account Id on the Database

                    if (!empty($dataResponse)) {
                        $accountId = $dataResponse->{"accountId"};

                        // obtains the user id of the customer who is logged
                        // and then is stored with the Account Id
                        $record = new stdClass();
                        $current_id = $USER->id;
                        $record->userid = $current_id;
                        $record->accountid = $accountId;
                        $DB->insert_record($accounts_table, $record, false);

                        $mens = get_string('pairingok','mlatch');// 'Pairing Succesful'
                    }
//                
                }
                $host = './view.php?id=' . $cmid;
                redirect($host, $mens);
            } else {

                //------------------ Performs Unpairing --------------------

                if ($unpair === "on") {

                    $current_user = $USER->id;
                    $accountUsr = $DB->get_record($accounts_table, array('userid' => $current_user));
                    $accountId = $accountUsr->accountid;

                    $unpairResponse = $api->unpair($accountId);
                    $dataResponse = $unpairResponse->getData();
                    $dataError = $unpairResponse->getError();
                    // Deletes Account Id from the BD (also the second factor if exist)                    
                    $DB->delete_records('mlatch_accounts', array('userid' => $current_user));
                    if (empty($dataResponse)) {
                        $mens = get_string('unpairok','mlatch');// 'Unpair Succesfull'
                    }
                } else {
                    if (!empty($dataError)) {
                        $mens = get_string('unpairnotok','mlatch');// 'Error unpairing';
                    } // unpair = off
                    if(!empty($_POST['flag'])){
                    $mens = get_string('checkbox','mlatch');// 'Please click on the checkbox!'
                    }
                }

                $host = './view.php?id=' . $cmid;
                redirect($host, $mens);
            }
        } else {
            $mens = get_string('emptyIdSecret','mlatch');
             $host = './view.php?id=' . $cmid;
             redirect($host, $mens);
        }
    }

    /*
     * Obtains the course module id from the Data Base
     */

    private function getModuleId() {
        global $DB;
        $idauxA = $DB->get_record('modules', array('name' => 'mlatch'), 'id');
        $idaux = $idauxA->id;
        $cmidA = $DB->get_record('course_modules', array('module' => $idaux), 'id');
        $cmid = $cmidA->id;
        return $cmid;
    }

}

$pairingToken = new MLatchPairingOperations();
$pairingToken->pairingOperations();
