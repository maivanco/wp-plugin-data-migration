<?php
function itc_dm_get_partial($template_file, $template_data = []){
    $template_file = ITC_DM_DIR . 'partials/' . $template_file.'.php';

	if (file_exists($template_file)) {
        ob_start();
        extract($template_data); // Extract the variables from the data array
    	require_once $template_file;
        echo ob_get_clean();
    } else {
        throw new Exception("Template file not found: $template_file");
    }
}