<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class FormidableCopyActionAdmin {
	public function __construct() {
		$this->add_menu();
		if ( FormidableCopyActionFreemius::getFreemius()->is_paying_or_trial() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_js' ) );
			add_action( 'wp_ajax_get_form_fields', array( $this, 'ajaxGetFormFields' ) );
			add_action( 'wp_ajax_get_form_update_fields', array( $this, 'ajaxGetUpdateFields' ) );
			add_filter( 'wp_kses_allowed_html', array( $this, 'allowedHtml' ), 10, 2 );
			add_shortcode( "form-copy-security", array( $this, 'formSecContent' ) );
		}
	}
	
	private function add_menu() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'fs_is_submenu_visible_' . FormidableCopyActionManager::getSlug(), array( $this, 'handle_sub_menu' ), 10, 2 );
	}
	
	public function handle_sub_menu( $is_visible, $menu_id ) {
		if ( $menu_id == 'account' ) {
			$is_visible = false;
		}
		
		return $is_visible;
	}
	
	/**
	 * Adding the Admin Page
	 */
	public function admin_menu() {
		add_menu_page( FormidableCopyActionManager::t( 'Copy Entries' ), FormidableCopyActionManager::t( 'Copy Entries' ), 'manage_options', FormidableCopyActionManager::getSlug(), array( $this, 'screen' ), 'dashicons-admin-page' );
	}
	
	public function screen() {
		FormidableCopyActionFreemius::getFreemius()->get_logger()->entrance();
		FormidableCopyActionFreemius::getFreemius()->_account_page_load();
		FormidableCopyActionFreemius::getFreemius()->_account_page_render();
	}
	
	/**
	 * Include script
	 */
	public function enqueue_js() {
		wp_register_script( 'formidable_copy_action', COPY_ACTION_JS_PATH . 'formidable_copy_action.js', array( "jquery" ), true );
		wp_enqueue_script( 'formidable_copy_action' );
	}
	
	/**
	 * Allow new tags to process shortCodes
	 *
	 * @param $allowedPostTags
	 * @param $context
	 *
	 * @return mixed
	 */
	public function allowedHtml( $allowedPostTags, $context ) {
		if ( $context == 'post' ) {
			$allowedPostTags['input']['form-copy-security'] = 1;
			$allowedPostTags['input']['value']              = 1;
		}
		
		return $allowedPostTags;
	}
	
	/**
	 * Return nonce for given action in shortCode
	 *
	 * @param      $attr
	 * @param null $content
	 *
	 * @return string
	 */
	public function formSecContent( $attr, $content = null ) {
		$internal_attr = shortcode_atts( array(
			'act' => 'get_form_field',
		), $attr );
		$nonce = base64_encode( $internal_attr['act'] );
		
		return $nonce;
	}
	
	/**
	 * Get formatted body of table with fields of forms
	 *
	 * @param $instanceNumber
	 * @param $formId
	 * @param $formData
	 *
	 * @return string
	 */
	public static function getFormFields( $instanceNumber, $formId, $formData ) {
		$fields = FrmField::getAll( array( 'fi.form_id' => $formId ), 'field_order' );
		$result = "<tbody>";
		foreach ( $fields as $field ) {
			$field            = (array) $field;
			$unusedFieldsType = array( 'divider', 'end_divider', 'file', 'captcha' );
			if ( ! in_array( $field['type'], $unusedFieldsType ) ) {
				$class = '';
				if ( $field['type'] == 'textarea' ) {
					$class = 'copy_editor';
				}
				$value = '';
				if ( isset( $formData ) ) {
					$jsonData = json_decode( $formData );
					if ( $jsonData != null ) {
						foreach ( $jsonData as $val ) {
							$val = (array) $val;
							if ( $field['id'] == $val['name'] ) {
								$value = $val['value'];
							}
						}
					}
				}
				$result .= '
				<tr>
					<th>
						<label for="' . $field['field_key'] . '"> <strong>' . $field['name'] . '</strong></label>
					</th>
					<td>
						<textarea id="' . $field['field_key'] . '" name="' . $field['id'] . '" class="frm_copy_action_field frm_formidable_copy_field_' . $instanceNumber . ' ' . $class . ' large-text">' . $value . '</textarea>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<hr/>
					</td>
				</tr>';
			}
		}
		
		return $result . "</tbody>";
	}
	
	/**
	 * Get field to select the primary key
	 *
	 * @param $formId
	 * @param $selected_field_id
	 *
	 * @return string
	 *
	 */
	public static function getUpdateFields( $formId, $selected_field_id = "-1" ) {
		$fields = FrmField::get_all_for_form( $formId );
		if ( empty( $selected_field_id ) || $selected_field_id == "-1" ) {
			$result = '<option value="-1" selected="selected" ></option>';
		} else {
			$result = '<option value="-1"></option>';
		}
		foreach ( $fields as $field ) {
			$field            = (array) $field;
			$selected_text    = '';
			$unusedFieldsType = array( 'divider', 'end_divider', 'file', 'captcha' );
			if ( ! in_array( $field['type'], $unusedFieldsType ) ) {
				if ( $field['id'] == $selected_field_id ) {
					$selected_text = 'selected="selected"';
				}
				$result .= '<option value="' . $field['id'] . '" ' . $selected_text . ' >' . $field['name'] . '</option>';
			}
		}
		
		return $result;
	}
	
	/**
	 * Ajax response to get forms fields
	 */
	public function ajaxGetFormFields() {
		if ( ! ( is_array( $_POST ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}
		if ( ! isset( $_POST['action'] ) || base64_decode( $_POST['form-copy-security'] ) != $_POST['action'] ) {
			die();
		}
		echo self::getFormFields( FrmAppHelper::get_post_param( 'form-instance-number' ), FrmAppHelper::get_post_param( 'form_destination_id' ), null );
		die();
	}
	
	/**
	 * Ajax response to get forms fields
	 */
	public function ajaxGetUpdateFields() {
		if ( ! ( is_array( $_POST ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}
		if ( ! isset( $_POST['action'] ) || base64_decode( $_POST['form-copy-security'] ) != $_POST['action'] ) {
			die();
		}
		echo self::getUpdateFields( FrmAppHelper::get_post_param( 'form_destination_id' ) );
		die();
	}
}
