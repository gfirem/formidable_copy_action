<?php
/**
 * @package WordPress
 * @subpackage Formidable, formidable_copy_action
 * @author GFireM
 * @copyright 2017
 * @link http://www.gfirem.com
 * @license http://www.apache.org/licenses/
 *
 */

/*
 * @since             1.0.0
 * @package           formidable_copy_action
 *
 * @wordpress-plugin
 * Plugin Name:       Formidable Copy Action
 * Plugin URI:        http://www.gfirem.com/copy-entries/
 * Description:       Add action to push data to another form. You can validate or update if exist.
 * Version:           2.0.3
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

if ( ! class_exists( 'formidable_copy_action' ) ) {
	
	require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'FormidableCopyActionFreemius.php';
	FormidableCopyActionFreemius::start_freemius();
	
	class formidable_copy_action {
		
		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;
		
		/**
		 * Initialize the plugin.
		 */
		private function __construct() {
			$this->constants();
			$this->load_plugin_textdomain();
			require_once COPY_ACTION_CLASSES_PATH . '/include/WP_Requirements.php';
			require_once COPY_ACTION_CLASSES_PATH . 'FormidableCopyActionRequirements.php';
			$this->requirements = new FormidableCopyActionRequirements( 'formidable_copy_action-locale' );
			require_once COPY_ACTION_CLASSES_PATH . 'FormidableCopyActionManager.php';
			if ( $this->requirements->satisfied() ) {
				new FormidableCopyActionManager();
			} else {
				$fauxPlugin = new WP_Faux_Plugin( FormidableCopyActionManager::t( 'Formidable Copy Action' ), $this->requirements->getResults() );
				$fauxPlugin->show_result( COPY_ACTION_BASE_NAME );
			}
		}
		
		private function constants(){
			define( 'COPY_ACTION_BASE_NAME', plugin_basename( __FILE__ ) );
			define( 'COPY_ACTION_URL_PATH', trailingslashit( wp_normalize_path( plugin_dir_url( __FILE__ ) ) ) );
			define( 'COPY_ACTION_CSS_PATH', COPY_ACTION_URL_PATH . 'assets/css/' );
			define( 'COPY_ACTION_JS_PATH', COPY_ACTION_URL_PATH . 'assets/js/' );
			define( 'COPY_ACTION_CLASSES_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR );
		}
		
		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}
			
			return self::$instance;
		}
		
		/**
		 * Load the plugin text domain for translation.
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'formidable_copy_action-locale', false, basename( dirname( __FILE__ ) ) . '/languages' );
		}
	}
	
	add_action( 'plugins_loaded', array( 'formidable_copy_action', 'get_instance' ), 1);
}