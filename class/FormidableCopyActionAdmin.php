<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormidableCopyActionAdmin {
	protected $version;
	private $slug;
	private $gManager;

	public function __construct( $version, $slug, $gManager ) {
		$this->version  = $version;
		$this->slug     = $slug;
		$this->gManager = $gManager;
	}

	/**
	 * Register copy action to formidable
	 *
	 * @param $actions
	 *
	 * @return mixed
	 */
	public function addFormidableCopyAction( $actions ) {
		$actions['formidable_copy'] = 'FormidableCopyAction';
		include_once( plugin_dir_path( dirname( __FILE__ ) ) . 'class/FormidableCopyAction.php' );

		return $actions;
	}

	/**
	 * Add setting page to global formidable settings
	 *
	 * @param $sections
	 *
	 * @return mixed
	 */
	public function addFormidableCopyActionSettingPage( $sections ) {
		$sections['copy'] = array(
			'class'    => 'FormidableCopyActionSettings',
			'function' => 'route',
		);

		return $sections;
	}

	/**
	 * Add a "Settings" link to the plugin row in the "Plugins" page.
	 *
	 * @param $actions
	 * @param string $pluginFile
	 *
	 * @return array
	 */
	public function addFormidableCopyActionSettingLink( $actions, $pluginFile ) {
		if ( 'formidable_copy_action/formidable_copy_action.php' == $pluginFile ) {
			$link = sprintf( '<a href="%s">%s</a>', esc_attr( admin_url( 'admin.php?page=formidable-settings&t=copy_settings' ) ), FormidableCopyActionManager::t( "Settings" ) );
			array_unshift( $actions, $link );
		}

		return $actions;
	}

	/**
	 * Add styles to action icon
	 */
	public function admin_FormidableCopyAction_style() {
		$current_screen = get_current_screen();
		if ( $current_screen->id === 'toplevel_page_formidable' ) {
			?>
			<style>
				.frm_formidable_copy_action.frm_bstooltip.frm_active_action.dashicons.dashicons-admin-page.copy_action_icon {
					height: auto;
					width: auto;
					font-size: 13px;
				}

				.frm_form_action_icon.dashicons.dashicons-admin-page.copy_action_icon {
					height: auto;
					width: auto;
					font-size: 13px;
				}
			</style>
		<?php
		}
	}

	/**
	 * Generic point to add style files
	 */
	public function enqueue_FormidableCopyAction_style() {
		wp_enqueue_style( 'jquery' );
		wp_enqueue_style(
			'formidable_copy_action',
			COPY_ACTION_CSS_PATH . 'formidable_copy_action.css'
		);
	}

	/**
	 * Allow new tags to process shortCodes
	 *
	 * @param $allowedPostTags
	 * @param $context
	 *
	 * @return mixed
	 */
	public function allowedHtmlFormidableCopyAction( $allowedPostTags, $context ) {
		if ( $context == 'post' ) {
			$allowedPostTags['input']['form-copy-security'] = 1;
			$allowedPostTags['input']['value']              = 1;
		}

		return $allowedPostTags;
	}

	/**
	 * Return nonce for given action in shortCode
	 *
	 * @param $attr
	 * @param null $content
	 *
	 * @return string
	 */
	public function formSecFormidableCopyActionContent( $attr, $content = null ) {
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
		global $frm_field;
		$fields = $frm_field->getAll( array( 'fi.form_id' => $formId ), 'field_order' );

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
						<label for="' . $field['field_key'] . '"> <b>' . $field['name'] . '</b></label>
					</th>
					<td>
						<textarea id="' . $field['field_key'] . '" name="' . $field['id'] . '" class="frm_formidable_copy_field_' . $instanceNumber . ' ' . $class . ' large-text">' . $value . '</textarea>
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
	 * Ajax response to get forms fields
	 */
	public function ajaxFormidableCopyActionGetFormFields() {
		if ( ! ( is_array( $_POST ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		if ( ! isset( $_POST['action'] ) || base64_decode( $_POST['form-copy-security'] ) != $_POST['action'] ) {
			die();
		}

		echo self::getFormFields( $_POST['form-instance-number'], $_POST['form_destination_id'], null );

		die();
	}

	/**
	 * Formidable create action
	 *
	 * @param $action
	 * @param $entry
	 * @param $form
	 */
	public function onFormidableCopyActionCreate( $action, $entry, $form ) {
		$this->processAction( $action, $entry );
	}

	/**
	 * Formidable update action
	 *
	 * @param $action
	 * @param $entry
	 * @param $form
	 */
	public function onFormidableCopyActionUpdate( $action, $entry, $form ) {
		$this->processAction( $action, $entry );
	}

	/**
	 * Process source action to create entry in destination form
	 *
	 * @param $action
	 * @param $entry
	 */
	private function processAction( $action, $entry ) {
		$entry          = (array) $entry;
		$destination_id = $action->post_content['form_destination_id'];
		if ( empty( $destination_id ) ) {
			return;
		}

		$destination_data = $action->post_content['form_destination_data'];
		if ( empty( $destination_data ) ) {
			return;
		}
		$metas    = array();
		$jsonData = json_decode( $destination_data );
		if ( $jsonData != null ) {
			foreach ( $jsonData as $val ) {
				$val        = (array) $val;
				$shortCodes = FrmFieldsHelper::get_shortcodes( $val['value'], $entry['form_id'] );
				$content    = apply_filters( 'frm_replace_content_shortcodes', $val['value'], FrmEntry::getOne( $entry['id'] ), $shortCodes );
				FrmProFieldsHelper::replace_non_standard_formidable_shortcodes( array(), $content );
				$metas[ $val['name'] ] = do_shortcode( $content );
			}
		}

		$data = array(
			'form_id'                             => $destination_id,
			'frm_user_id'                         => get_current_user_id(),
			'frm_submit_entry_' . $destination_id => wp_create_nonce( 'frm_submit_entry_nonce' ),
			'item_meta'                           => $metas,
		);

		if ( ! empty( $action->post_content['form_validate_data'] ) && $action->post_content['form_validate_data'] == "1" ) {
			$errors = FrmEntryValidate::validate( $data );

			if ( empty( $errors ) ) {
				FrmEntry::create( $data );
			} else {
				$error_str = "";
				foreach ( $errors as $key => $value ) {
					$error_str .= $key . " : " . $value . "<br/>";
				}
				FormidableCopyActionLogs::log( array(
					'action'         => "Create",
					'object_type'    => FormidableCopyActionManager::getShort(),
					'object_subtype' => "validation_error",
					'object_name'    => $error_str,
				) );
			}
		} else {
			FrmEntry::create( $data );
		}
	}
}