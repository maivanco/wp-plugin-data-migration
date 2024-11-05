<?php
/**
 * @package Akismet
 */
/*
Plugin Name: ITC Data Migration
Plugin URI: https://it-consultis.com
Description: This plugin will help you move specific data from one domain to other domains
Version: 5.1
Requires at least: 5.0
Requires PHP: 5.2
Author: Michael Tran
Author URI: https://it-consultis.com
License: GPLv2 or later
Text Domain: itc-data-migration
*/

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define('ITC_DM_DIR',plugin_dir_path( __FILE__ ));
define('ITC_DM_URL',plugin_dir_url( __FILE__ ));

define('ITC_DM_CSS_URL', plugin_dir_url( __FILE__ ) .'assets/css/');
define('ITC_DM_JS_URL', plugin_dir_url( __FILE__ ) .'assets/js/');

define('ITC_DM_VERSION', getenv('ENVIRONMENT') == 'local' ? current_time('timestamp') : '1.0' );

require_once ITC_DM_DIR . 'vendor/autoload.php';

require_once ITC_DM_DIR . 'helpers/functions.php';

require_once ITC_DM_DIR . 'hooks/admin-hooks.php';
require_once ITC_DM_DIR . 'hooks/admin-menu.php';