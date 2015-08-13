<?php
//ini_set('display_errors',1);
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

/*********************************************************************************
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 * SuiteCRM is an extension to SugarCRM Community Edition developed by Salesagility Ltd.
 * Copyright (C) 2011 - 2014 Salesagility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo and "Supercharged by SuiteCRM" logo. If the display of the logos is not
 * reasonably feasible for  technical reasons, the Appropriate Legal Notices must
 * display the words  "Powered by SugarCRM" and "Supercharged by SuiteCRM".
 ********************************************************************************/
class Popup_Picker
{

    /**
     * sole constructor
     */
    function Popup_Picker()
    {
    }

    /**
     *
     */
    function process_page()
    {

        global $beanList, $beanFiles, $mod_strings, $app_strings;

        $bean = $beanList[$_REQUEST['module_name']];
        require_once($beanFiles[$bean]);
        $focus = new $bean;

        if (!empty($_REQUEST['record'])) {

            $result = $focus->retrieve($_REQUEST['record']);
            if ($result == null) {
                sugar_die($app_strings['ERROR_NO_RECORD']);
            }
        }

        $xtpl = new XTemplate ('modules/Activities/Popup_picker.html');

        $xtpl->assign('MOD', $mod_strings);
        $xtpl->assign('APP', $app_strings);
        insert_popup_header();
        //output header
        echo "<table width='100%' cellpadding='0' cellspacing='0'><tr><td>";
        echo getClassicModuleTitle($focus->module_dir, array(translate('LBL_MODULE_NAME', $focus->module_dir), $focus->name), false);
        echo "</td><td align='right' class='moduleTitle'>";
        echo "<A href='javascript:print();' class='utilsLink'>" . SugarThemeRegistry::current()->getImage('print', "border='0' align='absmiddle'", 13, 13, ".gif", $app_strings['LNK_PRINT']) . "</a>&nbsp;<A href='javascript:print();' class='utilsLink'>" . $app_strings['LNK_PRINT'] . "</A>\n";
        echo "</td></tr></table>";
        echo "<script src='modules/Activities/scroll_data_loader.js'></script>";

        $this->getSummaryView($xtpl);

        $xtpl->parse("history");
        $xtpl->out("history");
        insert_popup_footer();
    }

    public function getSummaryView($xtpl)
    {

        $offset = 0;
        $activity_fields = $this->retrieve_and_format_data($offset,'80');

        foreach($activity_fields as $activity_row){
            $xtpl->assign("ACTIVITY", $activity_row);
            $xtpl->assign("ACTIVITY_MODULE_PNG", SugarThemeRegistry::current()->getImage($activity_row['MODULE'] . '', 'border="0"', null, null, '.gif', $activity_row['NAME']));


            if (!empty($activity_row['DESCRIPTION'])) {
                $xtpl->parse("history.row.description");
            }
            $xtpl->parse("history.row");

        }

    }

    public function retrieve_and_format_data($offset = 0, $max_rows = 50)
    {

        global $db, $app_list_strings;

        $activity_fields = array();

        if(empty($_SESSION['retrieve_module']) && $_REQUEST['module_name'] != ''){
            $_SESSION['retrieve_module'] = $_REQUEST['module_name'];
        }

        $query = $this->getSummaryQuery();
        $result = $db->limitQuery($query, $offset, $max_rows);

        $i = 0;
        while ($activity = $db->fetchByAssoc($result)) {

            $bean = BeanFactory::getBean($activity['module'], $activity['id']);

            if (!$bean->ACLAccess('view')) continue;

            $activity_fields[$i] = array(
                'ID' => $bean->id,
                'NAME' => $bean->name,
                'MODULE' => $activity['module'],
                'CONTACT_NAME' => $activity['contact_name'],
                'CONTACT_ID' => $activity['contact_id'],
                'DATE' => $bean->date_modified,
                'DESCRIPTION' => $bean->description,
                'DATE_TYPE' => $activity['date_type']
            );

            switch ($activity['module']) {
                case 'Calls':
                    $activity_fields[$i]['STATUS'] = $app_list_strings['call_status_dom'][$activity['status']];
                    break;
                case 'Meetings':
                    $activity_fields[$i]['STATUS'] = $app_list_strings['meeting_status_dom'][$activity['status']];
                    break;
                case 'Tasks':
                    $activity_fields[$i]['STATUS'] = $app_list_strings['task_status_dom'][$activity['status']];
                    break;
                case 'Notes':
                    if (isset($activity['filename']) && trim($activity['filename']) != '') {
                        $activity_fields[$i]['ATTACHMENT'] = "<a href='index.php?entryPoint=download&id=" . $activity['id'] . "&type=Notes' target='_blank'>" . SugarThemeRegistry::current()->getImage("attachment", "border='0' align='absmiddle'", null, null, '.gif', $activity['filename']) . "</a>";
                    }
                    break;
            }

            if (isset($activity['parent_type'])) $activity_fields[$i]['PARENT_MODULE'] = $activity['parent_type'];

            $i++;
        }

        return $activity_fields;

    }

    function getSummaryQuery()
    {

        if($_SESSION['retrieve_module'] === 'Contacts'){
            return "(SELECT 'Tasks' as module, tasks.id , tasks.name , tasks.status , LTRIM(RTRIM(CONCAT(IFNULL(contacts.first_name,''),' ',IFNULL(contacts.last_name,'')))) contact_name , tasks.contact_id , ' ' contact_name_owner , ' ' contact_name_mod , tasks.date_modified , tasks.date_entered , tasks.date_due as date_start , jt1.user_name assigned_user_name , tasks.assigned_user_id , jt1.created_by assigned_user_name_owner , 'Users' assigned_user_name_mod, 0 reply_to_status , tasks.parent_id , tasks.parent_type , ' ' filename , ' ' assigned_user_owner , ' ' assigned_user_mod , tasks.created_by , 'tasks' panel_name, NULL recurring_source FROM tasks LEFT JOIN contacts contacts ON tasks.contact_id=contacts.id AND contacts.deleted=0 AND contacts.deleted=0 LEFT JOIN users jt1 ON tasks.assigned_user_id=jt1.id AND jt1.deleted=0 AND jt1.deleted=0 INNER JOIN contacts tasks_rel ON tasks.contact_id=tasks_rel.id AND tasks_rel.deleted=0 where ( tasks.contact_id='b69de058-7bb4-2448-5c33-5502c9105813' AND (tasks.status='Completed' OR tasks.status='Deferred')) AND tasks.deleted=0) UNION ALL ( SELECT 'Tasks' as module, tasks.id , tasks.name , tasks.status , LTRIM(RTRIM(CONCAT(IFNULL(contacts.first_name,''),' ',IFNULL(contacts.last_name,'')))) contact_name , tasks.contact_id , ' ' contact_name_owner , ' ' contact_name_mod , tasks.date_modified , tasks.date_entered , tasks.date_due as date_start , jt1.user_name assigned_user_name , tasks.assigned_user_id , jt1.created_by assigned_user_name_owner , 'Users' assigned_user_name_mod, 0 reply_to_status , tasks.parent_id , tasks.parent_type , ' ' filename , ' ' assigned_user_owner , ' ' assigned_user_mod , tasks.created_by , 'tasks_parent' panel_name, NULL recurring_source FROM tasks LEFT JOIN contacts contacts ON tasks.contact_id=contacts.id AND contacts.deleted=0 AND contacts.deleted=0 LEFT JOIN users jt1 ON tasks.assigned_user_id=jt1.id AND jt1.deleted=0 AND jt1.deleted=0 INNER JOIN contacts tasks_parent_rel ON tasks.parent_id=tasks_parent_rel.id AND tasks_parent_rel.deleted=0 AND tasks.parent_type = 'Contacts' where ( tasks.parent_id='b69de058-7bb4-2448-5c33-5502c9105813' AND (tasks.status='Completed' OR tasks.status='Deferred')) AND tasks.deleted=0 ) UNION ALL ( SELECT 'Meetings' as module, meetings.id , meetings.name , meetings.status , ' ' contact_name , ' ' contact_id , ' ' contact_name_owner , ' ' contact_name_mod , meetings.date_modified , meetings.date_entered , meetings.date_start , jt1.user_name assigned_user_name , meetings.assigned_user_id , jt1.created_by assigned_user_name_owner , 'Users' assigned_user_name_mod, 0 reply_to_status , meetings.parent_id , meetings.parent_type , ' ' filename , ' ' assigned_user_owner , ' ' assigned_user_mod , meetings.created_by , 'meetings' panel_name, meetings.recurring_source FROM meetings LEFT JOIN meetings_cstm ON meetings.id = meetings_cstm.id_c LEFT JOIN users jt1 ON meetings.assigned_user_id=jt1.id AND jt1.deleted=0 AND jt1.deleted=0 INNER JOIN meetings_contacts ON meetings.id=meetings_contacts.meeting_id AND meetings_contacts.contact_id='b69de058-7bb4-2448-5c33-5502c9105813' AND meetings_contacts.deleted=0 where ((meetings.status='Held' OR meetings.status='Not Held')) AND meetings.deleted=0 ) UNION ALL ( SELECT 'Calls' as module, calls.id , calls.name , calls.status , ' ' contact_name , ' ' contact_id , ' ' contact_name_owner , ' ' contact_name_mod , calls.date_modified , calls.date_entered , calls.date_start , jt1.user_name assigned_user_name , calls.assigned_user_id , jt1.created_by assigned_user_name_owner , 'Users' assigned_user_name_mod, 0 reply_to_status , calls.parent_id , calls.parent_type , ' ' filename , ' ' assigned_user_owner , ' ' assigned_user_mod , calls.created_by , 'calls' panel_name, calls.recurring_source FROM calls LEFT JOIN users jt1 ON calls.assigned_user_id=jt1.id AND jt1.deleted=0 AND jt1.deleted=0 INNER JOIN calls_contacts ON calls.id=calls_contacts.call_id AND calls_contacts.contact_id='b69de058-7bb4-2448-5c33-5502c9105813' AND calls_contacts.deleted=0 where ((calls.status='Held' OR calls.status='Not Held')) AND calls.deleted=0 ) UNION ALL ( SELECT 'Notes' as module, notes.id , notes.name , ' ' status , LTRIM(RTRIM(CONCAT(IFNULL(contacts.first_name,''),' ',IFNULL(contacts.last_name,'')))) contact_name , notes.contact_id , ' ' contact_name_owner , ' ' contact_name_mod , notes.date_modified , notes.date_entered , NULL date_start, jt1.user_name assigned_user_name , notes.assigned_user_id , jt1.created_by assigned_user_name_owner , 'Users' assigned_user_name_mod, 0 reply_to_status , notes.parent_id , notes.parent_type , notes.filename , ' ' assigned_user_owner , ' ' assigned_user_mod , notes.created_by , 'notes' panel_name, NULL recurring_source FROM notes LEFT JOIN contacts contacts ON notes.contact_id=contacts.id AND contacts.deleted=0 AND contacts.deleted=0 LEFT JOIN users jt1 ON notes.assigned_user_id=jt1.id AND jt1.deleted=0 AND jt1.deleted=0 INNER JOIN contacts notes_rel ON notes.contact_id=notes_rel.id AND notes_rel.deleted=0 where ( notes.contact_id='b69de058-7bb4-2448-5c33-5502c9105813') AND notes.deleted=0 ) UNION ALL ( SELECT 'Emails' as module, emails.id , emails.name , emails.status , ' ' contact_name , ' ' contact_id , ' ' contact_name_owner , ' ' contact_name_mod , emails.date_modified , emails.date_entered , NULL date_start, jt0.user_name assigned_user_name , emails.assigned_user_id , jt0.created_by assigned_user_name_owner , 'Users' assigned_user_name_mod, emails.reply_to_status , emails.parent_id , emails.parent_type , ' ' filename , ' ' assigned_user_owner , ' ' assigned_user_mod , emails.created_by , 'emails' panel_name, NULL recurring_source FROM emails LEFT JOIN users jt0 ON emails.assigned_user_id=jt0.id AND jt0.deleted=0 AND jt0.deleted=0 INNER JOIN emails_beans ON emails.id=emails_beans.email_id AND emails_beans.bean_id='b69de058-7bb4-2448-5c33-5502c9105813' AND emails_beans.deleted=0 AND emails_beans.bean_module = 'Contacts' where emails.deleted=0 ) UNION ALL ( SELECT 'Emails' as module, emails.id , emails.name , emails.status , ' ' contact_name , ' ' contact_id , ' ' contact_name_owner , ' ' contact_name_mod , emails.date_modified , emails.date_entered , NULL date_start, jt0.user_name assigned_user_name , emails.assigned_user_id , jt0.created_by assigned_user_name_owner , 'Users' assigned_user_name_mod, emails.reply_to_status , emails.parent_id , emails.parent_type , ' ' filename , ' ' assigned_user_owner , ' ' assigned_user_mod , emails.created_by , 'linkedemails' panel_name, NULL recurring_source FROM emails LEFT JOIN users jt0 ON emails.assigned_user_id=jt0.id AND jt0.deleted=0 AND jt0.deleted=0 JOIN (select DISTINCT email_id from emails_email_addr_rel eear join email_addr_bean_rel eabr on eabr.bean_id ='b69de058-7bb4-2448-5c33-5502c9105813' and eabr.bean_module = 'Contacts' and eabr.email_address_id = eear.email_address_id and eabr.deleted=0 where eear.deleted=0 and eear.email_id not in (select eb.email_id from emails_beans eb where eb.bean_module ='Contacts' and eb.bean_id = 'b69de058-7bb4-2448-5c33-5502c9105813') ) derivedemails on derivedemails.email_id = emails.id where emails.deleted=0 ) ORDER BY date_entered desc";
        }
        if($_SESSION['retrieve_module'] === 'Accounts'){
            return "(SELECT 'Tasks' as module, tasks.id , tasks.name , tasks.status , LTRIM(RTRIM(CONCAT(IFNULL(contacts.first_name,''),' ',IFNULL(contacts.last_name,'')))) contact_name , tasks.contact_id , ' ' contact_name_owner , ' ' contact_name_mod , tasks.date_modified , tasks.date_entered , tasks.date_due as date_start , jt1.user_name assigned_user_name , tasks.assigned_user_id , jt1.created_by assigned_user_name_owner , 'Users' assigned_user_name_mod, 0 reply_to_status , tasks.parent_id , tasks.parent_type , ' ' filename , ' ' assigned_user_owner , ' ' assigned_user_mod , tasks.created_by , 'tasks' panel_name, NULL recurring_source FROM tasks LEFT JOIN contacts contacts ON tasks.contact_id=contacts.id AND contacts.deleted=0 AND contacts.deleted=0 LEFT JOIN users jt1 ON tasks.assigned_user_id=jt1.id AND jt1.deleted=0 AND jt1.deleted=0 INNER JOIN contacts tasks_rel ON tasks.contact_id=tasks_rel.id AND tasks_rel.deleted=0 where ( tasks.contact_id='b69de058-7bb4-2448-5c33-5502c9105813' AND (tasks.status='Completed' OR tasks.status='Deferred')) AND tasks.deleted=0) UNION ALL ( SELECT 'Tasks' as module, tasks.id , tasks.name , tasks.status , LTRIM(RTRIM(CONCAT(IFNULL(contacts.first_name,''),' ',IFNULL(contacts.last_name,'')))) contact_name , tasks.contact_id , ' ' contact_name_owner , ' ' contact_name_mod , tasks.date_modified , tasks.date_entered , tasks.date_due as date_start , jt1.user_name assigned_user_name , tasks.assigned_user_id , jt1.created_by assigned_user_name_owner , 'Users' assigned_user_name_mod, 0 reply_to_status , tasks.parent_id , tasks.parent_type , ' ' filename , ' ' assigned_user_owner , ' ' assigned_user_mod , tasks.created_by , 'tasks_parent' panel_name, NULL recurring_source FROM tasks LEFT JOIN contacts contacts ON tasks.contact_id=contacts.id AND contacts.deleted=0 AND contacts.deleted=0 LEFT JOIN users jt1 ON tasks.assigned_user_id=jt1.id AND jt1.deleted=0 AND jt1.deleted=0 INNER JOIN contacts tasks_parent_rel ON tasks.parent_id=tasks_parent_rel.id AND tasks_parent_rel.deleted=0 AND tasks.parent_type = 'Contacts' where ( tasks.parent_id='b69de058-7bb4-2448-5c33-5502c9105813' AND (tasks.status='Completed' OR tasks.status='Deferred')) AND tasks.deleted=0 ) UNION ALL ( SELECT 'Meetings' as module, meetings.id , meetings.name , meetings.status , ' ' contact_name , ' ' contact_id , ' ' contact_name_owner , ' ' contact_name_mod , meetings.date_modified , meetings.date_entered , meetings.date_start , jt1.user_name assigned_user_name , meetings.assigned_user_id , jt1.created_by assigned_user_name_owner , 'Users' assigned_user_name_mod, 0 reply_to_status , meetings.parent_id , meetings.parent_type , ' ' filename , ' ' assigned_user_owner , ' ' assigned_user_mod , meetings.created_by , 'meetings' panel_name, meetings.recurring_source FROM meetings LEFT JOIN meetings_cstm ON meetings.id = meetings_cstm.id_c LEFT JOIN users jt1 ON meetings.assigned_user_id=jt1.id AND jt1.deleted=0 AND jt1.deleted=0 INNER JOIN meetings_contacts ON meetings.id=meetings_contacts.meeting_id AND meetings_contacts.contact_id='b69de058-7bb4-2448-5c33-5502c9105813' AND meetings_contacts.deleted=0 where ((meetings.status='Held' OR meetings.status='Not Held')) AND meetings.deleted=0 ) UNION ALL ( SELECT 'Calls' as module, calls.id , calls.name , calls.status , ' ' contact_name , ' ' contact_id , ' ' contact_name_owner , ' ' contact_name_mod , calls.date_modified , calls.date_entered , calls.date_start , jt1.user_name assigned_user_name , calls.assigned_user_id , jt1.created_by assigned_user_name_owner , 'Users' assigned_user_name_mod, 0 reply_to_status , calls.parent_id , calls.parent_type , ' ' filename , ' ' assigned_user_owner , ' ' assigned_user_mod , calls.created_by , 'calls' panel_name, calls.recurring_source FROM calls LEFT JOIN users jt1 ON calls.assigned_user_id=jt1.id AND jt1.deleted=0 AND jt1.deleted=0 INNER JOIN calls_contacts ON calls.id=calls_contacts.call_id AND calls_contacts.contact_id='b69de058-7bb4-2448-5c33-5502c9105813' AND calls_contacts.deleted=0 where ((calls.status='Held' OR calls.status='Not Held')) AND calls.deleted=0 ) UNION ALL ( SELECT 'Notes' as module, notes.id , notes.name , ' ' status , LTRIM(RTRIM(CONCAT(IFNULL(contacts.first_name,''),' ',IFNULL(contacts.last_name,'')))) contact_name , notes.contact_id , ' ' contact_name_owner , ' ' contact_name_mod , notes.date_modified , notes.date_entered , NULL date_start, jt1.user_name assigned_user_name , notes.assigned_user_id , jt1.created_by assigned_user_name_owner , 'Users' assigned_user_name_mod, 0 reply_to_status , notes.parent_id , notes.parent_type , notes.filename , ' ' assigned_user_owner , ' ' assigned_user_mod , notes.created_by , 'notes' panel_name, NULL recurring_source FROM notes LEFT JOIN contacts contacts ON notes.contact_id=contacts.id AND contacts.deleted=0 AND contacts.deleted=0 LEFT JOIN users jt1 ON notes.assigned_user_id=jt1.id AND jt1.deleted=0 AND jt1.deleted=0 INNER JOIN contacts notes_rel ON notes.contact_id=notes_rel.id AND notes_rel.deleted=0 where ( notes.contact_id='b69de058-7bb4-2448-5c33-5502c9105813') AND notes.deleted=0 ) UNION ALL ( SELECT 'Emails' as module, emails.id , emails.name , emails.status , ' ' contact_name , ' ' contact_id , ' ' contact_name_owner , ' ' contact_name_mod , emails.date_modified , emails.date_entered , NULL date_start, jt0.user_name assigned_user_name , emails.assigned_user_id , jt0.created_by assigned_user_name_owner , 'Users' assigned_user_name_mod, emails.reply_to_status , emails.parent_id , emails.parent_type , ' ' filename , ' ' assigned_user_owner , ' ' assigned_user_mod , emails.created_by , 'emails' panel_name, NULL recurring_source FROM emails LEFT JOIN users jt0 ON emails.assigned_user_id=jt0.id AND jt0.deleted=0 AND jt0.deleted=0 INNER JOIN emails_beans ON emails.id=emails_beans.email_id AND emails_beans.bean_id='b69de058-7bb4-2448-5c33-5502c9105813' AND emails_beans.deleted=0 AND emails_beans.bean_module = 'Contacts' where emails.deleted=0 ) UNION ALL ( SELECT 'Emails' as module, emails.id , emails.name , emails.status , ' ' contact_name , ' ' contact_id , ' ' contact_name_owner , ' ' contact_name_mod , emails.date_modified , emails.date_entered , NULL date_start, jt0.user_name assigned_user_name , emails.assigned_user_id , jt0.created_by assigned_user_name_owner , 'Users' assigned_user_name_mod, emails.reply_to_status , emails.parent_id , emails.parent_type , ' ' filename , ' ' assigned_user_owner , ' ' assigned_user_mod , emails.created_by , 'linkedemails' panel_name, NULL recurring_source FROM emails LEFT JOIN users jt0 ON emails.assigned_user_id=jt0.id AND jt0.deleted=0 AND jt0.deleted=0 JOIN (select DISTINCT email_id from emails_email_addr_rel eear join email_addr_bean_rel eabr on eabr.bean_id ='b69de058-7bb4-2448-5c33-5502c9105813' and eabr.bean_module = 'Contacts' and eabr.email_address_id = eear.email_address_id and eabr.deleted=0 where eear.deleted=0 and eear.email_id not in (select eb.email_id from emails_beans eb where eb.bean_module ='Contacts' and eb.bean_id = 'b69de058-7bb4-2448-5c33-5502c9105813') ) derivedemails on derivedemails.email_id = emails.id where emails.deleted=0 ) ORDER BY date_entered desc";
        }

    }

}