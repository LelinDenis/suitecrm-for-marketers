<?php
/**
 * Created by PhpStorm.
 * User: lewis
 * Date: 20/01/15
 * Time: 10:22
 */


require_once("modules/AOW_WorkFlow/aow_utils.php");

class AOS_PDF_TemplatesController extends SugarController
{

    protected function action_getModuleFields()
    {
        if (!empty($_REQUEST['aos_pdf_templates']) && $_REQUEST['aos_pdf_templates'] != '') {
            if (isset($_REQUEST['rel_field']) && $_REQUEST['rel_field'] != '') {
                $module = getRelatedModule($_REQUEST['aos_pdf_templates'], $_REQUEST['rel_field']);
            } else {
                $module = $_REQUEST['aos_pdf_templates'];
            }
            $val = !empty($_REQUEST['aor_value']) ? $_REQUEST['aor_value'] : '';
            echo getModuleFields($module, $_REQUEST['view'], $val);
        }
        die;

    }

    public function action_getModuleTreeData()
    {
        if (!empty($_REQUEST['aos_pdf_templates']) && $_REQUEST['aos_pdf_templates'] != '') {
            ob_start();
            $data = getModuleTreeData($_REQUEST['aos_pdf_templates']);
            ob_clean();
            echo $data;
        }
        die;
    }


    protected function action_getModuleRelationships()
    {
        if (!empty($_REQUEST['aos_pdf_templates']) && $_REQUEST['aos_pdf_templates'] != '') {
            echo getModuleRelationships($_REQUEST['aos_pdf_templates']);
        }
        die;
    }

    public function action_editview(){

        global $sugar_config;

        $this->view = 'edit';
        $GLOBALS['view'] = $this->view;

        if($_REQUEST['return_id'] && $this->bean->id == "") {

            $parent_template = new AOS_PDF_Templates();
            $parent_template->retrieve($_REQUEST['return_id']);

            $this->bean->type = $parent_template->type;
            $this->bean->description = $parent_template->description;
            $this->bean->pdfheader = $parent_template->pdfheader;
            $this->bean->pdffooter = $parent_template->pdffooter;
            $this->bean->name = $parent_template->name;
            $this->bean->field_defs['default_language']['options'] = "default_language_child";

        }else{
            $this->bean->field_defs['default_language']['options'] = "default_language_parent";
        }

        if($this->bean->parent_template == 1 && $this->bean->id){
            $this->bean->field_defs['default_language']['options'] = "default_language_parent";
        }elseif($this->bean->parent_template == 0 && $this->bean->id){
            $this->bean->field_defs['default_language']['options'] = "default_language_child";
        }
    }

}