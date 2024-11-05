<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

add_action('admin_enqueue_scripts', 'itc_migrate_data_load_admin_scripts');

function itc_migrate_data_load_admin_scripts() {
    // Check the current screen
    $screen = get_current_screen();
    // Load the script only on the specified screen(s)
    if ( $screen->id == 'itc-data-migration_page_admin?page=itc-dm-export') {
        wp_enqueue_style('itc-dm-select2', ITC_DM_CSS_URL . 'select2.min.css', [] , ITC_DM_VERSION);
        wp_enqueue_style('itc-dm', ITC_DM_CSS_URL . 'itc-data-migration.css', [] , ITC_DM_VERSION);

        wp_enqueue_script('itc-dm-select2', ITC_DM_JS_URL . 'select2.min.js', array('jquery'), ITC_DM_VERSION, true);
        wp_enqueue_script('itc-dm-export', ITC_DM_JS_URL . 'export-data.js', array('jquery'), ITC_DM_VERSION, true);
    }

    if ( $screen->id == 'itc-data-migration_page_admin?page=itc-dm-import') {
        wp_enqueue_script('itc-dm-export', ITC_DM_JS_URL . 'import-data.js', array('jquery'), ITC_DM_VERSION, true);
    }
}

add_action( 'wp_ajax_itc_md_admin_request', 'itc_md_admin_requests');

function itc_md_admin_requests(){

    switch ($_REQUEST['method']) {
        case 'get_posts_by_post_type':
            $qrHandlers = new ITCDataMigration\WPQuery();
            $post_params = isset($_REQUEST['post_params']) ? $_REQUEST['post_params'] : [];
            $response = $qrHandlers->getPostsBy($post_params);
            break;

        case 'export_data':
            $exporter = new ITCDataMigration\Exporter();
            $needed_post_types = isset($_REQUEST['export_data']) ? $_REQUEST['export_data'] : [];
            $response = $exporter->exportData($needed_post_types);
            break;

        case 'import_data':
            $importHandlers = new ITCDataMigration\Importer();
            $jsonFile = isset($_FILES['json-file']) ? $_FILES['json-file'] : [];
            $response = $importHandlers->importData($jsonFile);
            break;

        default:
            $response = [
                'status' => 'error',
                'msg' => 'Invalid requests'
            ];
    }

    wp_send_json($response);
}