<?php

class multiLevelTemplateParser
{
    var $text;
    var $language;
    var $base_module;

    var $lines_start_tag = "{lines}";
    var $lines_end_tag = "{/lines}";
    var $line_rel_start_tag = "{line_rels}";
    var $line_rel_end_tag = "{/line_rels}";

    var $img_height_start_tag = "{h}";
    var $img_height_end_tag = "{/h}";
    var $img_width_start_tag = "{w}";
    var $img_width_end_tag = "{/w}";

    var $wysiwyg_start_tag = "{wysiwyg}";
    var $wysiwyg_end_tag = "{/wysiwyg}";

    var $format_start_tag = "{f}";
    var $format_end_tag = "{/f}";

    var $line_related_modules;

    var $field_start_tag = "{var}";
    var $field_end_tag = "{/var}";
    var $field_related_modules;

    /**
     * Loads variables used in the template parser and controls the template parser process.
     * @param $text
     * @param $module
     * @param $id
     * @return bool
     */

    public function parse_template($text, $module, $id)
    {
        if ($text && $module && $id) {

            $this->text = $text;

            require_once("modules/AOS_PDF_Templates/templateLanguage.php");
            $templateLanguage = new templateLanguage();

            $this->language = $templateLanguage->get_language($module, $id);

            $base_module = BeanFactory::getBean($module, $id);
            $this->base_module = BeanFactory::getBean($module, $id);

            $this->text = $this->populate_lines($this->base_module, $this->text);

            $this->parse_wysiwyg($base_module);


            $this->parse_if_statements();


            $fields = $this->find_template_fields();

            $replace_field_values = $this->var_build_replace_array($this->base_module, $fields);

            $this->text = $this->replace_template_variables($this->text,$replace_field_values);

            $this->parse_images($base_module);

            return $this->text;

        } else {
            return false;
        }

    }

    /**
     * Outputs the wysiwyg fields as parsed html not code.
     * @param $module
     */

    public function parse_wysiwyg($module){

        global $sugar_config;

        //Get each if statement
        $i = 0;

        $wysiwyg = $this->get_string_between_tags($this->wysiwyg_start_tag, $this->wysiwyg_end_tag, $this->text);

        foreach ($wysiwyg as $html) {

            $var = reset($this->get_string_between_tags($this->field_start_tag,$this->field_end_tag,$html));

            $field_array = $this->get_field_from_var($var);

            $value = html_entity_decode($module->$field_array['fieldname']);

            $this->text = str_replace($this->wysiwyg_start_tag . $html . $this->wysiwyg_end_tag, $value, $this->text);

        }
    }


    /**
     *
     * @param $module
     */

    public function parse_images($module){

        global $sugar_config;

        //Get each if statement
        $i = 0;

        $images = $this->get_string_between_tags("{img}", "{/img}", $this->text);

        foreach ($images as $img) {

            $var = reset($this->get_string_between_tags($this->field_start_tag,$this->field_end_tag,$img));

            if($var){
                $field_array = $this->get_field_from_var($var);

                $height = reset($this->get_string_between_tags($this->img_height_start_tag,$this->img_height_end_tag,$img));
                $width = reset($this->get_string_between_tags($this->img_width_start_tag,$this->img_width_end_tag,$img));

                if($height && $width){
                    $replace_string = "<img style='height:" . $height ."; width:" . $width . ";' src='" . $sugar_config['upload_dir'] . $field_array['fieldname'] . "'/>";
                }else if($height){
                    $replace_string = "<img style='height:" . $height .";' src='" . $sugar_config['upload_dir'] . $field_array['fieldname'] . "'/>";
                }else if($width){
                    $replace_string = "<img style='width:" . $width .";' src='" . $sugar_config['upload_dir'] . $field_array['fieldname'] . "'/>";
                }else{
                    $replace_string = "<img src='" . $sugar_config['upload_dir'] . $field_array['fieldname'] . "'/>";
                }

                $this->text = str_replace("{img}" . $img . "{/img}",$replace_string,$this->text);
            }else{
                $this->text = str_replace("{img}" . $img . "{/img}",'',$this->text);
            }



        }
    }

    /**
     * Takes a template line string and populates all the variables for the string.
     * @param $fields
     * @param $fields_values
     * @param $line_relationship
     * @param $line
     * @return mixed
     */

    public function line_replace_in_template($fields, $fields_values, $line_relationship, $line)
    {

        $replace_lines = "";
        $temp_line = $this->line_remove_rel_tags($line, $line_relationship);
        $var_replaced_line = $temp_line;

        $i = 0;

        foreach ($fields_values as $field => $value) {

            $var_replaced_line = str_replace($this->field_start_tag . $field . $this->field_end_tag, $value, $var_replaced_line);

        }

        return $var_replaced_line;
    }

    /**
     * Removes relationship tags from template line string.
     * @param $line
     * @param $line_relationship
     * @return mixed
     */


    public function line_remove_rel_tags($line, $line_relationship)
    {
        return str_replace($this->line_rel_start_tag . $line_relationship . $this->line_rel_end_tag, "", $line);

    }

    /**Recursive function
     * @param SugarBean $base
     * @param string $text
     * @return mixed|string
     */

    public function populate_lines(SugarBean $base, $text = '')
    {

        //Load all the lines for a template.
        $lines = $this->get_line_between_tags($this->lines_start_tag, $this->lines_end_tag, $text);

        //Foreach line
        foreach ($lines as $line) {
            $replace_line = $line;

            //Parse the template line to get the line relationship.
            $line_relationship_string = $this->line_get_relationship($line);
            //Check that we are not already using the relationship if we are remove it from the relationship string because we don't want to load aos_quotes relationship on aos_quotes module.
            $line_relationships = $this->validate_line_relationships($base, $line_relationship_string);
            //Load all the relationships for the base module.
            $line_modules = $this->line_get_base_module($base, $line_relationships);
            if(count($line_modules)) {
                $replace_lines = '';
                //Foreach of the line modules.
                foreach ($line_modules as $line_module) {
                    //Call the function recursively to load all the inner lines for the loaded line.
                    $replace_line = $this->populate_lines($line_module, $line);
                    //Loads all the fields needed for the current line.
                    $line_fields = $this->line_get_fields($line, $line_relationship_string);
                    //Loads all the values for the fields to make a field => value array.
                    $line_fields_values = $this->line_get_field_values($line_fields, $line_module);
                    //Takes the line and populates all the variables from the $line_fields_values array.
                    $replace_lines .= $this->line_replace_in_template($line_fields, $line_fields_values, $line_relationship_string, $replace_line);
                }
                //Replace the populated lines in the templates.
                $text = str_replace($this->lines_start_tag . $line . $this->lines_end_tag, $replace_lines, $text);
            }else{
                $text = str_replace($this->lines_start_tag . $line . $this->lines_end_tag, "No related records found.", $text);
            }

        }
        return $text;
    }


    /**
     * makes sure the line relationship belongs to the base module.
     * @param $base
     * @param $line_relationships
     * @return array|bool
     */

    public function validate_line_relationships($base, $line_relationships)
    {
        $relationships = $this->get_relationships_from_var($line_relationships);


        $i = 0;
        while ($i < count($relationships)) {
            if ($base->table_name == $relationships[$i]) {
                unset($relationships[$i]);
            }

            $i++;

        }

        return $relationships;

    }


    /**Loads the relationship for the lines.
     * @param $line
     * @return mixed
     */

    public function line_get_relationship($line)
    {

        $line_rel = reset($this->get_string_between_tags($this->line_rel_start_tag, $this->line_rel_end_tag, $line));

        return $line_rel;
    }

    /**
     * Loads all the relationships for a module.
     * @param $base_modules
     * @param $relationships
     * @return mixed
     */

    public function line_get_base_module($base_modules, $relationships)
    {

        $rel_count = count($relationships);

        $i = 0;

        //If there are more than one relationship load only one base module unless you are on the last relationship.

        foreach ($relationships as $rel) {

            if ($i == $rel_count - 1) {

                if (is_object($base_modules)) {
                    $base_modules = $base_modules->get_linked_beans($rel);
                }

            } else {

                if (is_object($base_modules)) {

                    $temp = $base_modules->get_linked_beans($rel);
                    $base_modules = $temp[0];

                }
            }

            $i++;
        }

        return $base_modules;

    }

    /**
     * Gets all the field values for an array of fields from a particular module and also passes them through the file type handler.
     * @param $fields
     * @param $module
     * @return String
     */

    public function line_get_field_values($fields, $module)
    {
        foreach ($fields as $field) {

            $field_array = $this->get_field_from_var($field);
            $return_line_fields_values[$field] = $this->handle_field_types($module->field_defs[$field_array['fieldname']], $module->$field_array['fieldname'] , '', '', $field_array['format']);

        }


        return $return_line_fields_values;

    }

    /**Gets all the fields for a line and validates if they are part of the parent relationship defined in the line_rel.
     * @param $line
     * @param $line_relationship
     * @return array|string
     */


    public function line_get_fields($line, $line_relationship)
    {

        $field_names = array();

        $fields = $this->get_string_between_tags($this->field_start_tag, $this->field_end_tag, $line);
        $fields = $this->line_validate_fields($fields, $line_relationship);

        return $fields;
    }

    /**
     * Validates if they are part of the parent relationship defined in the line_rel.
     * @param $fields
     * @param $relationship
     * @return array|string
     */


    public function line_validate_fields($fields, $relationship)
    {

        $valid_fields = "";

        foreach ($fields as $field) {

            $field_rel = $this->strip_rel_from_var($field);

            if ($field_rel == $relationship) {
                $valid_fields[] = $field;
            }

        }

        return $valid_fields;
    }


    /**
     * Loops through the template finds fields and removes them from the stripped string till there are no more vars left.
     * @return bool|void
     */

    public function find_template_fields()
    {
        return $this->get_string_between_tags($this->field_start_tag, $this->field_end_tag, $this->text);

    }

    /**
     * Searches for fields that are encased between start and end tags and stores them in the fields array.
     * @param $start
     * @param $end
     * @return array()
     */

    public function get_line_between_tags($start, $end, $text)
    {
        $return_values = array();

        if ($start && $end && strpos($text,$start) !== false && strpos($text,$end) !== false ) {

            while (strpos($text, $start) !== false && strpos($text, $end) !== false) {


                $start_len = strlen($start);
                $end_len = strlen($end);

                $string = " " . $text;


                $start_position = strpos($string, $start);
                $end_position = strpos($string, $end);
                $temp_position = $start_position + $start_len;


                while (strpos($text, $start, $temp_position) && strpos($text, $start, $temp_position) < $end_position) {

                    $temp_position = strpos($text, $start, $temp_position) + $start_len;
                    $end_position = strpos($string, $end, $end_position + $end_len);

                }

                $start_position += strlen($start);
                $len = $end_position - $start_position;
                $field = substr($string, $start_position, $len);
                $return_values[] = $field;

                $text = str_replace($start . $field . $end,"",$text);


            }
            return $return_values;

        }else{
            return false;
        }

    }


    /** Gets all occurances of a string between two tags, can also return the field wrapped in the tags.
     * @param $start
     * @param $end
     * @param $text
     * @param bool $return_tags
     * @return array|string
     */

    public function get_string_between_tags($start, $end, $text,$return_tags = false)
    {

        if ($start && $end) {

            $string = " " . $text;
            $return_values = array();

            while (strpos($string, $start) !== false && strpos($string, $end) !== false) {

                $ini = strpos($string, $start);
                if ($ini == 0) {
                    return $return_values;
                }
                $ini += strlen($start);
                $len = strpos($string, $end, $ini) - $ini;
                $field = substr($string, $ini, $len);

                if($return_tags){
                    $return_values[] = $start . $field . $end;
                }else{
                    $return_values[] = $field;
                }

                $string = str_replace($start . $field . $end, '', $string);
            }
        }

        return $return_values;
    }

    /**
     * @param $base_module
     * @param $fields
     */


    public function var_build_replace_array($base_module, $fields, $raw = false)
    {

        //Foreach of the variables to be replaced.

        foreach ($fields as $field) {
            $parent_module = "";
            $field_array = $this->get_field_from_var($field);
            $relationships = $this->get_relationships_from_var($field);
            $relcount = count($relationships);


            if ($field_array['fieldname']) {
                //If we have related modules else just use the base module to find the fields.
                if ($relationships) {

                    //If we have more than one relationship to load
                    if (count($relationships) > 1) {

                        $i = 0;
                        while ($i < count($relationships)) {

                            //If we are on the first element it must be the parent relationship to load.
                            if ($i == 0) {
                                $parent_module = $this->load_related_module($relationships[0], $base_module, 'parent');

                                //Once the parent relationship is loaded we can load the child relationships from that which become the parent element for the next relationship.
                            } else {

                                $parent_module = $this->load_related_module($relationships[$i], $parent_module, 'child');

                            }

                            //If we are on the last relationship then we can find the value and replace it.
                            if ($i == $relcount - 1) {
                                $replace_field_values[$field] = $this->handle_field_types($parent_module->field_defs[$field_array['fieldname']], $parent_module->$field_array['fieldname'], $raw, $parent_module->id, $field_array['format']);
                            }

                            $i++;
                        }

                    } else {

                        //If we have just one relationship just load and replace the value.
                        $parent_module = $this->load_related_module($relationships[0], $base_module, 'parent');
                        $replace_field_values[$field] = $this->handle_field_types($parent_module->field_defs[$field_array['fieldname']], $parent_module->$field_array['fieldname'], $raw, $parent_module->id, $field_array['format']);
                    }


                } else {

                    //No relationships just load it from base module.
                    $replace_field_values[$field] = $this->handle_field_types($base_module->field_defs[$field_array['fieldname']], $base_module->$field_array['fieldname'], $raw, $base_module->id, $field_array['format']);

                }


            }else{
                $replace_field_values[$field] = "";
            }
        }

        return $replace_field_values;

    }

    /**
     * Find Fields name from the template variable string
     * @param $var
     * @return mixed
     */

    public function get_field_from_var($var)
    {
        $field = array();

        $var = str_replace("rel:", "", $var);
        $var = str_replace("field:", "", $var);
        $var = str_replace("raw_", "", $var);
        $var_exploded = explode(",", $var);
        $count = count($var_exploded);

        $field['format'] = reset($this->get_string_between_tags($this->format_start_tag,$this->format_end_tag,$var_exploded[$count - 1]));
        $field['fieldname'] = str_replace($this->format_start_tag . $field['format'] . $this->format_end_tag, "", $var_exploded[$count - 1]);

        return $field;

    }

    /**
     * Find Fields name from the template variable string
     * @param $var
     * @return mixed
     */

    public function strip_rel_from_var($var)
    {

        $var = $this->remove_portion_after_character($var, ",field");
        return $var;

    }

    /**
     * Finds all the relationships from the template variable string.
     * @param $var
     * @return array|bool
     */

    public function get_relationships_from_var($var)
    {

        $relationships = array();

        $var_exploded = explode(",", $var);

        foreach ($var_exploded as $element) {
            if (substr($element, 0, 4) == "rel:") {
                $relationships[] = substr($element, 4);
            }

        }
        if (count($relationships)) {
            return $relationships;
        } else {
            return false;
        }

    }


    /**
     * Saves the loaded modules so we do not need to load them again.
     * @param $base_module
     * @param string $related_module
     * @param string $relationship
     */

    public function save_related_module($base_module, $related_module = "", $relationship = "")
    {

        if ($related_module && $relationship) {

            $base_module->$relationship = $related_module;

        } else {
            $this->field_related_modules[$base_module->module_dir] = $base_module;

        }

    }

    /**
     * Returns the parent module from the related modules array if it exists.
     * @param $module
     * @return bool
     */

    public function parent_related_module_exists($module)
    {
        if ($this->field_related_modules[$module] && !is_array($this->field_related_modules[$module])) {

            return $this->field_related_modules[$module];

        } else {

            return false;
        }


    }

    /**
     * Returns the parent module from the related modules array if it exists
     * @param $parent
     * @param $child
     * @return bool
     */

    public function child_related_module_exists($parent, $child)
    {

        if ($this->field_related_modules[$parent]->$child && !is_array($this->field_related_modules[$parent]->$child)) {

            return $this->field_related_modules[$parent]->$child;

        } else {

            return false;

        }
    }


    /**
     * Loads the related module and calls the functions to replace the variables.
     * @param $relationship
     * @param $base_module
     * @param $relation
     * @return bool
     */

    public function load_related_module($relationship, $base_module, $relation)
    {
        $module_created = false;

        if ($relation == "parent") {
            $related_module = $this->parent_related_module_exists(ucfirst($relationship));
        } else {
            $related_module = $this->child_related_module_exists($base_module->module_dir, ucfirst($relationship));

        }

        if (!$related_module) {

            //For php compatability as
            if (is_object($base_module)) {
                $temp = $base_module->get_linked_beans($relationship);
                $related_module = $temp[0];
            }


            $module_created = true;

        }

        if ($relation == "child" && $module_created == true) {

            $this->save_related_module($base_module, $related_module, ucfirst($relationship));

        } else if ($relation == "parent" && $module_created == true) {

            $this->save_related_module($related_module, '', '');

        }

        return $related_module;
    }


    /**
     * @param $text
     * @param $replace_field_values
     * @return mixed
     */

    public function replace_template_variables($text,$replace_field_values)
    {
        foreach ($replace_field_values as $variable => $value) {
            $text = str_replace($this->field_start_tag . $variable . $this->field_end_tag, $value, $text);
        }
        return $text;
    }

    /**
     * @param $field_type
     * @param $field_value
     * @return String
     */

    public function handle_field_types($field_type, $field_value, $raw = true, $id = null, $format = null)
    {
        global $sugar_config , $current_user;

        $app_list_strings = return_app_list_strings_language($this->language);

        if ($field_value != "" || $field_value != NULL) {
            switch ($field_type['type']) {
                case "currency":
                    $field_value = currency_format_number($field_value, $params = array('currency_symbol' => false));
                    break;
                case "multienum":
                    $field_value = implode(', ', unencodeMultienum($field_value));
                    $field_value = $app_list_strings[$field_type['options']][$field_value];
                    if($raw){
                        $field_value = array_search($field_value, $app_list_strings[$field_type['options']]);
                    }
                    break;
                case  "enum":
                    $field_value = $app_list_strings[$field_type['options']][$field_value];
                    if($raw){
                        $field_value = array_search($field_value, $app_list_strings[$field_type['options']]);
                    }
                    break;
                case  "image":
                    if($id){
                        $field_value = "{var}" . $id . "_" . $field_type['name'] . "{/var}";
                    }
                    break;
                case  "date":
                case  "datetime":
                    if($format){
                        $prefs = $current_user->getUserDateTimePreferences();

                        if($field_type['type'] == "date"){
                            $existing_data_obj = DateTime::createFromFormat($prefs['date'],$field_value);
                        }

                        if($field_type['type'] == "datetime"){
                            $existing_data_obj = DateTime::createFromFormat($prefs['date'] .' '. $prefs['time'] ,$field_value);
                        }

                        $field_value = $existing_data_obj->format($format);

                    }
                    break;
            }
            return $field_value;
        } else {
            return "";
        }


    }

    /**
     * Handles ifstatements and replaces them in $this->text.
     */

    public function parse_if_statements()
    {
        //Get each if statement
        $i = 0;

        // Template_ifstatements are the
        $template_ifstatements = $this->get_string_between_tags("{ifstatement}", "{/ifstatement}", $this->text, true);

        $parsed_ifstatements = $this->get_if_raw_variables($template_ifstatements);

        foreach ($parsed_ifstatements as $ifstatement) {

            $values = array();
            $if_statement_values = array();

            $values[] = $this->get_string_between_tags("{else}", "{/else}", $ifstatement);
            $values[] = $this->get_string_between_tags("{elseif}", "{/elseif}", $ifstatement);
            $values[] = $this->get_string_between_tags("{if}", "{/if}", $ifstatement);

            foreach($values as $value){

                if(is_array($value)){
                    $if_statement_values = array_merge($if_statement_values,$value);
                }
            }

            $j = 0;

            while ($j < count($if_statement_values)) {


                if (strpos($if_statement_values[$j], '{/}')) {

                    $condition = $this->is_true_check($this->remove_portion_after_character($if_statement_values[$j], "{/}"));

                    if ($condition) {
                        $this->replace_if_statement($if_statement_values[$j], $template_ifstatements[$i]);
                    }else{
                        $this->replace_if_statement($if_statement_values[0],$template_ifstatements[$i]);
                    }
                }

                if ($j == count($if_statement_values) - 1) {
                    $this->replace_if_statement($if_statement_values[$j], $template_ifstatements[$i]);
                }
                $j++;

            }

            $i++;

        }

    }

    /**
     * @param $string_array
     * @return array
     */

    public function get_if_raw_variables($string_array){
        $return_strings = array();

        foreach($string_array as $string){

            if(strpos($string,"{img}") === false){
                $fields = $this->get_string_between_tags($this->field_start_tag,$this->field_end_tag,$string,false);

                $replace_array = $this->var_build_replace_array($this->base_module,$fields, $raw = true, $this->base_module->id);

                $return_strings[] = $this->replace_template_variables($string,$replace_array);
            }else{
                $return_strings[] = $string;
            }


        }
        return $return_strings;

    }

    /**
     * @param $value
     * @param $ifstatement
     */

    public function replace_if_statement($value, $ifstatement)
    {


        if (strpos($value, '{/}')) {
            $value = str_replace("{/}", "", $this->remove_portion_before_character($value, "{/}"));
        }

        $this->text = str_replace($ifstatement, $value, $this->text);
    }


    //breaks down the if statement into AND / OR sections and evaluates if each section is true
    function is_true_check($value)
    {
        //get AND and OR logical operators
        $logical_ops = explode('{lo}', $value);
        $op_section = $this->is_true($logical_ops[0]); //evaluates the first condition of the if statement
        $count = 0;
        foreach ($logical_ops as $logical_op) { //evaluates the individual AND / OR sections of the if statement.
            if ($count > 0) {

                $and_start = $this->startsWith($logical_op, "{and}");
                $and_end = $this->endsWith($logical_op, "{/and}");
                $or_start = $this->startsWith($logical_op, "{or}");
                $or_ends = $this->endsWith($logical_op, "{/or}");

                if ($and_start === true && $and_end == true) {
                    $and = str_replace("{and}", '', $logical_op);
                    $and = str_replace("{/and}", '', $and);
                    $and_sections[] = $this->is_true($and);
                }
                if ($or_start === true && $or_ends === true) {
                    $or = str_replace("{or}", '', $logical_op);
                    $or = str_replace("{/or}", '', $or);
                    $or_sections[] = $this->is_true($or);
                }
            }
            $count++;
        }
        //If any section of the if statement is false then return false
        if (!isset($and_sections) && !isset($or_sections)) {

            if ($op_section === false) {
                return false;
            } else return true;
        }
        if (isset($and_sections) && !isset($or_sections)) {

            if ($op_section === false) {
                return false;
            }
            foreach ($and_sections as $section) {
                if ($section === false) {
                    return false;
                }
            }
            return true;
        } else if (!isset($and_sections) && isset($or_sections)) {

            foreach ($or_sections as $section) {
                if ($op_section || $section === true) {
                    return true;
                }
            }
            return false;
        }
        /* else if(isset($and_sections) && isset($or_sections)){
             $warn= 'Cant use both "And" and "Or" in the same if statement';
         }*/
    }

//evaluates if the individual section is true
    function is_true($value)
    {
        //get the operator
        $values = explode('{/op}', $value);
        $op = explode('{op}', $values[0]);

        $v1 = trim($op[0]);//actual value
        $v1 = str_replace("\xA0", ' ', $v1);
        $v2 = trim($values[1]);//value to test against
        $v2 = str_replace("\xA0", ' ', $v2);

        //check if v1 is marked as a number
        if ($this->startsWith($v1, "{int}") && $this->endsWith($v1, "{int}")) {
            //Strip away {int} tags
            $v1 = str_replace("{int}", '', $v1);
            //if its a numeric value make it an int
            if (is_numeric($v1)) {
                $v1 = (int)$v1;
            }
        } else if ($this->startsWith($v1, "{float}") && $this->endsWith($v1, "{float}")) {
            //Strip away {float} tags
            $v1 = str_replace("{float}", '', $v1);
            $v1 = (float)unformat_number($v1);
            if (is_float($v1)) {
                $v1 = (float)$v1;
            }
        } else {
            $v1 = str_replace("{int}", '', $v1);
            $v1 = str_replace("{float}", '', $v1);
        }

        //check if v2 is marked as a number
        if ($this->startsWith($v2, "{int}") && $this->endsWith($v2, "{int}")) {
            $v2 = str_replace("{int}", '', $v2);//Strip away {int} tags
            //if its a numeric value make it an int
            if (is_numeric($v2)) {
                $v2 = (int)$v2;
            }
        } elseif ($this->startsWith($v2, "{float}") && $this->endsWith($v2, "{float}")) {
            //Strip away {float} tags
            $v2 = str_replace("{float}", '', $v2);
            $v2 = (float)unformat_number($v2);
            if (is_float($v2)) {
                $v2 = (float)$v2;
            }
        } else {
            $v2 = str_replace("{int}", '', $v2);
            $v2 = str_replace("{float}", '', $v2);
        }

        switch ($op[1]) {
            case "=="://check if operator is == "equals"
                if ($v1 == $v2) {
                    return true;
                } else {
                    return false;
                }
                break;
            case "!=": //check if operator is != "not equal"
                if ($v1 != $v2) {
                    return true;
                } else {
                    return false;
                }
                break;
            case ">"://check if operator is > "greater than"
                if ($v1 > $v2) {
                    return true;
                } else {
                    return false;
                }
                break;
            case "<": //check if operator is < "less than"
                if ($v1 < $v2) {
                    return true;
                } else {
                    return false;
                }
                break;
            case ">="://check if operator is > "greater than or equal to"
                if ($v1 >= $v2) {
                    return true;
                } else {
                    return false;
                }
                break;
            case "<="://check if operator is <= "less than or equal to"
                if ($v1 <= $v2) {
                    return true;
                } else {
                    return false;
                }
                break;
            case "==="://check if operator is ===
                if ($v1 === $v2) {
                    return true;
                } else {
                    return false;
                }
                break;
            default:
                return false;
        }
    }

    //function that takes a string and returns if it starts with the specified character
    function startsWith($haystack, $needle)
    {
        return $needle === "" || strpos($haystack, $needle) === 0;
    }

    //functions that takes a string and return if it ends with the specified character
    function endsWith($haystack, $needle)
    {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }

    //Function for basic field validation (present and neither empty nor only white space)
    function IsNullOrEmptyString($question)
    {
        return (!isset($question) || trim($question) === '');
    }


    function remove_portion_after_character($variable, $character)
    {
        return substr($variable, 0, strpos($variable, $character));
    }

    function remove_portion_before_character($variable, $character)
    {
        return strstr($variable, $character);
    }
}
