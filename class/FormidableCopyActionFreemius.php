<?php
/**
 * @package WordPress
 * @subpackage Formidable, formidable_copy_action
 * @author GFireM
 * @copyright 2017
 * @link http://www.gfirem.com
 * @license http://www.apache.org/licenses/
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

class FormidableCopyActionFreemius {
	
	public static $plugins_slug = 'formidable-copy-action';
	
	/**
	 * @return Freemius
	 */
	public static function getFreemius() {
		global $gfirem;
		
		return $gfirem[ self::$plugins_slug ]['freemius'];
	}
	
	public static function start_freemius() {
		global $gfirem;
		
		if ( ! isset( $gfirem[ self::$plugins_slug ]['freemius'] ) ) {
			require_once dirname( __FILE__ ) . '/include/freemius/start.php';
			try {
				$gfirem[ self::$plugins_slug ]['freemius'] = fs_dynamic_init( array(
					'id'                  => '868',
					'slug'                => 'formidable-copy-action',
					'type'                => 'plugin',
					'public_key'          => 'pk_cbebc9d6d2a9f18e7258a6ecacbcb',
					'is_premium'          => true,
					'is_premium_only'     => true,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					'is_org_compliant'    => false,
					'trial'               => array(
						'days'               => 14,
						'is_require_payment' => true,
					),
					'menu'                => array(
						'slug'           => 'formidable-copy-action',
						'first-path'     => 'admin.php?page=formidable-copy-action',
						'support'        => false,
					),
					// Set the SDK to work in a sandbox mode (for development & testing).
					// IMPORTANT: MAKE SURE TO REMOVE SECRET KEY BEFORE DEPLOYMENT.
					'secret_key'          => 'sk_&J7YknEdK2+Hz=$x(7<djiA63vn4L',
				) );
			} catch ( Exception $ex ) {
				$gfirem[ self::$plugins_slug ]['freemius'] = false;
			}
		}
		
		return $gfirem[ self::$plugins_slug ]['freemius'];
	}
}
