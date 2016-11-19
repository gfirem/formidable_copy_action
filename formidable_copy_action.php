<?php
/*
 * Plugin Name:       Formidable copy action
 * Plugin URI:        https://github.com/gfirem/formidable_copy_action
 * Description:       Action to add data to other form. Some possible uses is like log to send feed data to other form
 * Version:           1.06
 * Author:            Guillermo Figueroa Mesa
 * Author URI:        http://wwww.gfirem.com
 * Text Domain:       formidable_copy_action-locale
 * License:           Apache License 2.0
 * License URI:       http://www.apache.org/licenses/
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'COPY_ACTION_CSS_PATH', plugin_dir_url( __FILE__ ) . 'css/' );

require_once plugin_dir_path( __FILE__ ) . 'class/FormidableCopyActionManager.php';

require_once 'plugin-update-checker/plugin-update-checker.php';

$myUpdateChecker = PucFactory::buildUpdateChecker( 'http://gfirem.com/update-services/?action=get_metadata&slug=formidable_copy_action', __FILE__ );
$myUpdateChecker->addQueryArgFilter( 'appendFormidableCopyActionQueryArgsCredentials' );

/**
 * Append the order key to the update server URL
 *
 * @param $queryArgs
 *
 * @return
 */
function appendFormidableCopyActionQueryArgsCredentials( $queryArgs ) {
	$queryArgs['order_key'] = get_option( FormidableCopyActionManager::getShort() . 'licence_key', '' );

	return $queryArgs;
}

function FormidableCopyActionBootLoader() {
	add_action( 'plugins_loaded', 'setFormidableCopyActionTranslation' );
	$manager = new FormidableCopyActionManager();
	$manager->run();
}

function checkRequiredFormidableCopyAction() {
	if ( ! class_exists( "FrmHooksController" ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			FormidableCopyActionManager::t( 'This plugins required Formidable to run!' ),
			FormidableCopyActionManager::t( 'Formidable Copy Action' ),
			array( 'back_link' => true )
		);
	}
}

register_activation_hook( __FILE__, "checkRequiredFormidableCopyAction" );

/**
 * Add translation files
 */
function setFormidableCopyActionTranslation() {
	load_plugin_textdomain( 'formidable_copy_action-locale', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

FormidableCopyActionBootLoader();