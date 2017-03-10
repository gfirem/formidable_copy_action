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
	
	/**
	 * @return Freemius
	 */
	public static function getFreemius() {
		global $gfirem_fs;
		
		return $gfirem_fs;
	}
	
	// Create a helper function for easy SDK access.
	public static function start_freemius() {
		global $gfirem_fs;
		
		if ( ! isset( $gfirem_fs ) ) {
			// Include Freemius SDK.
			require_once dirname( __FILE__ ) . '/include/freemius/start.php';
			
			$gfirem_fs = fs_dynamic_init( array(
				'id'               => '868',
				'slug'             => 'formidable-copy-action',
				'type'             => 'plugin',
				'public_key'       => 'pk_cbebc9d6d2a9f18e7258a6ecacbcb',
				'is_premium'       => true,
				'is_premium_only'  => true,
				'has_addons'       => false,
				'has_paid_plans'   => true,
				'is_org_compliant' => false,
				'menu'             => array(
					'slug'       => 'formidable-copy-action',
					'first-path' => 'admin.php?page=formidable-copy-action',
					'support'    => false,
				),
				// Set the SDK to work in a sandbox mode (for development & testing).
				// IMPORTANT: MAKE SURE TO REMOVE SECRET KEY BEFORE DEPLOYMENT.
				'secret_key'       => 'sk_&J7YknEdK2+Hz=$x(7<djiA63vn4L',
			) );
		}
		
		return $gfirem_fs;
	}
}