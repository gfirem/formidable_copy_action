<?php
/**
 * @package WordPress
 * @subpackage Formidable, formidable_copy_action
 * @author GFireM
 * @copyright 2017
 * @link http://www.gfirem.com
 * @license http://www.apache.org/licenses/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormidableCopyActionManager {
	protected static $plugin_slug = 'formidable-copy-action';
	protected static $plugin_short = 'FormidableCopyAction';
	protected static $version = '2.1.1';
	
	public function __construct() {
		require_once COPY_ACTION_CLASSES_PATH . 'FormidableCopyActionLogs.php';
		try {
			if ( class_exists( 'FrmAppHelper' ) && method_exists( 'FrmAppHelper', 'pro_is_installed' )
			     && FrmAppHelper::pro_is_installed() ) {
				require_once COPY_ACTION_CLASSES_PATH . 'FormidableCopyActionAdmin.php';
				new FormidableCopyActionAdmin();
				if ( FormidableCopyActionFreemius::getFreemius()->is_paying_or_trial() ) {
					add_action( 'frm_registered_form_actions', array( $this, 'register_action' ) );
				}
			} else {
				add_action( 'admin_notices', array( $this, 'required_formidable_pro' ) );
			}
		} catch ( Exception $ex ) {
			FormidableCopyActionLogs::log( array(
				'action'         => get_class( $this ),
				'object_type'    => FormidableCopyActionManager::getShort(),
				'object_subtype' => 'loading_dependency',
				'object_name'    => $ex->getMessage(),
			) );
		}
		
	}

	public function required_formidable_pro() {
		?>
		<div class="error">
			<p>
				<?php
				_e( '<b>Formidable Copy Action</b> requires that Formidable Pro version 2.0 or greater be installed. Until then, keep plugin activated only to continue enjoying this insightful message.', 'formidable_key_field-locale' );
				?>
			</p>
		</div>
		<?php
	}
	
	/**
	 * Register action
	 *
	 * @param $actions
	 *
	 * @return mixed
	 */
	public function register_action( $actions ) {
		$actions['formidable_copy'] = 'FormidableCopyAction';
		include_once COPY_ACTION_CLASSES_PATH . 'FormidableCopyAction.php';
		
		return $actions;
	}
	
	public static function getShort() {
		return self::$plugin_short;
	}
	
	public static function getSlug() {
		return self::$plugin_slug;
	}
	
	public static function getVersion() {
		return self::$version;
	}
	
	public function get_version() {
		return self::$version;
	}
	
	public static function load_plugins_dependency() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	
	public static function is_formidable_active() {
		self::load_plugins_dependency();
		
		return is_plugin_active( 'formidable/formidable.php' );
	}
	
	/**
	 * Translate string to main Text Domain
	 *
	 * @param $str
	 *
	 * @return string
	 */
	public static function t( $str ) {
		return __( $str, 'formidable_copy_action-locale' );
	}
}
