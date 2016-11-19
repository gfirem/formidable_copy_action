<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class FormidableCopyActionManager {
	/**
	 * @var FormidableCopyActionLoader
	 */
	protected $loader;

	protected $plugin_slug;
	private static $plugin_short = 'FormidableCopyAction';

	protected static $version;

	public function __construct() {

		$this->plugin_slug = 'formidable-copy-action';

		self::$version = '1.06';

		$this->load_dependencies();
		$this->define_admin_hooks();

	}

	static function getShort() {
		return self::$plugin_short;
	}

	static function getVersion() {
		return self::$version;
	}

	private function load_dependencies() {

		require_once plugin_dir_path( __FILE__ ) . 'FormidableCopyActionLoader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class/FormidableCopyActionLogs.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class/FormidableCopyActionAdmin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class/FormidableCopyActionSettings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'class/GManagerFactory.php';


		$this->loader = new FormidableCopyActionLoader();
	}

	private function define_admin_hooks() {
		$gManager = GManagerFactory::buildManager( 'FormidableCopyActionManager', 'formidable_copy_action', self::getShort() );
		$admin    = new FormidableCopyActionAdmin( $this->get_version(), $this->plugin_slug, $gManager );
		$logs = new FormidableCopyActionLogs();

		$this->loader->add_action( 'admin_head', $admin, 'admin_' . self::getShort() . '_style' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_' . self::getShort() . '_style' );
		$this->loader->add_action( 'wp_ajax_get_form_fields', $admin, 'ajax' . self::getShort() . 'GetFormFields' );
		$this->loader->add_action( 'wp_ajax_nopriv_get_form_fields', $admin, 'ajax' . self::getShort() . 'GetFormFields' );

		$this->loader->add_action( 'frm_registered_form_actions', $admin, 'add' . self::getShort() );
		$this->loader->add_action( 'frm_trigger_formidable_copy_create_action', $admin, 'on' . self::getShort() . 'Create', 10, 3 );
		$this->loader->add_action( 'frm_trigger_formidable_copy_update_action', $admin, 'on' . self::getShort() . 'Update', 10, 3 );

		$this->loader->add_filter( 'plugin_action_links', $admin, 'add' . self::getShort() . 'SettingLink', 10, 5 );
		$this->loader->add_action( 'frm_add_settings_section', $admin, 'add' . self::getShort() . 'SettingPage', 10, 3 );

		$this->loader->add_filter( 'wp_kses_allowed_html', $admin, 'allowedHtml' . self::getShort(), 10, 2 );
		add_shortcode( "form-copy-security", array( $admin, 'formSec' . self::getShort() . 'Content' ) );
	}

	public function run() {
		$this->loader->run();
	}

	public function get_version() {
		return self::$version;
	}

	/**
	 * Translate string to main Domain
	 *
	 * @param $str
	 *
	 * @return string|void
	 */
	public static function t( $str ) {
		return __( $str, 'formidable_copy_action-locale' );
	}
}