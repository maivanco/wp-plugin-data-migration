<?php
namespace ITCDataMigration\Admin;

class AdminMenu
{
    public function init(){
        add_action( 'admin_menu', [$this, 'registerAdminMenu']);
    }

    public function registerAdminMenu(){
        add_menu_page(
            __( 'ITC Data Migration', 'itc-dm' ),
            'ITC Data Migration',
            'manage_options',
            'itc-data-migration',
            [$this, 'renderScreen'],
            'dashicons-database-export',
        );
        add_submenu_page(
            'itc-data-migration',
            'Export Data with ACF',
            'Export Data with ACF',
            'manage_options',
            'admin.php?page=itc-dm-export',
            [$this, 'renderScreen']
        );
        add_submenu_page(
            'itc-data-migration',
            'Import Data with ACF',
            'Import Data with ACF',
            'manage_options',
            'admin.php?page=itc-dm-import',
            [$this, 'renderScreen']
        );
    }

    public function renderScreen(){
        $currentScreen = get_current_screen()->id;
        switch($currentScreen){
            case 'itc-data-migration_page_admin?page=itc-dm-export':
                itc_dm_get_partial('export');
                break;
            case 'itc-data-migration_page_admin?page=itc-dm-import':
                itc_dm_get_partial('import');
                break;
            default:
                itc_dm_get_partial('intro');
        }
    }


}

$itcMigrationAdminMenu = new AdminMenu();
$itcMigrationAdminMenu->init();