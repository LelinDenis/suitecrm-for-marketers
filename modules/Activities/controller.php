<?php
/**
 * Created by PhpStorm.
 * User: lewis
 * Date: 12/08/15
 * Time: 13:30
 */
require_once('modules/Activities/Popup_picker.php');

class ActivitiesController extends SugarController{

    public function action_loadSummaryData(){

        $popup_picker = new Popup_Picker();
        echo json_encode($popup_picker->retrieve_and_format_data($_REQUEST['offset']));

    }

}