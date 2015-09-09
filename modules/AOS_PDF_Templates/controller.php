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


}