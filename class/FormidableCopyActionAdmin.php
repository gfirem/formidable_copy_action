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
	 * Ajax response to get forms fields
	 */
	public function ajaxFormidableCopyActionGetUpdateFields() {
		if ( ! ( is_array( $_POST ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}
		
		if ( ! isset( $_POST['action'] ) || base64_decode( $_POST['form-copy-security'] ) != $_POST['action'] ) {
			die();
		}
		
		echo self::getUpdateFields( $_POST['form_destination_id'] );
		
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
		$destination_id = $action->post_content['form_destination_id'];
		if ( empty( $destination_id ) ) {
			return;
		}
		
		$destination_data = $action->post_content['form_destination_data'];
		if ( empty( $destination_data ) ) {
			return;
		}
		
		$persit_destination_data = ! empty( $action->post_content['form_destination_persist_enabled'] );
		$needed_fields           = array();
		if ( $persit_destination_data ) {
			$jsonData = json_decode( $destination_data );
			if ( $jsonData != null ) {
				$meta_key = array_keys( $entry->metas );
				foreach ( $jsonData as $val ) {
					$val        = (array) $val;
					$shortCodes = FrmFieldsHelper::get_shortcodes( $val['value'], $entry->form_id );
					$size       = count( $shortCodes[2] );
					if ( ! empty( $shortCodes[2] ) && $size == 1 ) {
						$not_exist = array_diff( $shortCodes[2], $meta_key );
						if ( ! empty( $not_exist ) ) {
							$needed_fields[ $val['name'] ] = $not_exist;
						}
					}
				}
				
				if ( ! empty( $needed_fields ) ) {
					$old_values = array();
					foreach ( $needed_fields as $needed_field_key => $needed_field_value ) {
						$value                           = FrmEntryMeta::get_entry_metas_for_field( $needed_field_key );
						$old_values[ $needed_field_key ] = $value[0];
						if ( ! isset( $entry->metas[ $needed_field_key ] ) ) {
							$entry->metas[ $needed_field_value[0] ] = $value[0];
						}
					}
				}
				
			}
		}
		
		$form_destination_repeatable = ! empty( $action->post_content['form_destination_repeatable'] );
		
		//Get data set in the form action
		$metas    = array();
		$jsonData = json_decode( $destination_data );
		if ( $jsonData != null ) {
			foreach ( $jsonData as $val ) {
				$val                   = (array) $val;
				$shortCodes            = FrmFieldsHelper::get_shortcodes( $val['value'], $entry->form_id );
				$fields                = FrmProFormsHelper::has_repeat_field( $entry->form_id, false );
				$existing_repeat_field = array();
				foreach ( $fields as $id => $field ) {
					$existing_repeat_field[] = $field->id;
				}
				$entry_internal = FrmEntry::getOne( $entry->id, true );
				if ( $form_destination_repeatable && ! empty( $existing_repeat_field ) ) {
					foreach ( $entry_internal->metas as $key => $value ) {
						if ( in_array( $key, $existing_repeat_field ) == true && is_array( $value ) == true ) {
							foreach ( $value as $val_key => $val_entry_id ) {
								$field_id = "";
								foreach ( $shortCodes[0] as $short_key => $tag ) {
									$field_id = FrmFieldsHelper::get_shortcode_tag( $shortCodes, $short_key, compact( 'conditional', 'foreach' ) );
								}
								if ( ! empty( $field_id ) ) {
									$sub_entry = FrmEntryMeta::get_entry_meta_by_field( $val_entry_id, $field_id );
									FrmProFieldsHelper::replace_non_standard_formidable_shortcodes( array(), $sub_entry );
									$metas[ $val_entry_id ][ $val['name'] ] = do_shortcode( $sub_entry );
								}
							}
						}
					}
				} else {
					$content = apply_filters( 'frm_replace_content_shortcodes', $val['value'], $entry, $shortCodes );
					FrmProFieldsHelper::replace_non_standard_formidable_shortcodes( array(), $content );
					$metas[ $val['name'] ] = do_shortcode( $content );
				}
			}
			unset( $entry_internal );
		}
		
		if ( ! $form_destination_repeatable ) {
			$this->insert_in_destination( $action, $destination_id, $metas );
		} else {
			foreach ( $metas as $key => $item ) {
				$this->insert_in_destination( $action, $destination_id, $item );
			}
		}
	}
	
	/**
	 * Validate the entry if necessary
	 *
	 * @param $action
	 * @param $data
	 * @param bool $exclude
	 *
	 * @return array
	 */
	private function validate_entries( $action, $data, $exclude = false ) {
		$errors = array();
		if ( ! empty( $action->post_content['form_validate_data'] ) && $action->post_content['form_validate_data'] == "1" ) {
			$errors = FrmEntryValidate::validate( $data, $exclude );
			if ( ! empty( $errors ) ) {
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
		}
		
		return $errors;
	}
	
	/**
	 * Insert data in destination form
	 *
	 * @param $action
	 * @param $destination_id
	 * @param $item
	 */
	private function insert_in_destination( $action, $destination_id, $item ) {
		//Process the data to insert in the target form
		$data = array(
			'form_id'                             => $destination_id,
			'frm_user_id'                         => get_current_user_id(),
			'frm_submit_entry_' . $destination_id => wp_create_nonce( 'frm_submit_entry_nonce' ),
			'item_meta'                           => $item,
		);
		
		if ( ! empty( $action->post_content['form_destination_primary_enabled'] ) && $action->post_content['form_destination_primary_enabled'] == "1"
		     && ! empty( $action->post_content['form_destination_primary_key'] )
		) {
			$search       = $data["item_meta"][ $action->post_content['form_destination_primary_key'] ];
			$result       = FrmEntryMeta::search_entry_metas( $search, $action->post_content['form_destination_primary_key'], "LIKE" );
			$primary_data = $data["item_meta"][ $action->post_content['form_destination_primary_key'] ];
			unset( $data["item_meta"][ $action->post_content['form_destination_primary_key'] ] );
			
			$errors = $this->validate_entries( $action, $data );
			
			if ( empty( $errors ) ) {
				$data["item_meta"][ $action->post_content['form_destination_primary_key'] ] = $primary_data;
				if ( ! empty( $result ) && is_array( $result ) ) {
					foreach ( $result as $entry_id ) {
						FrmEntryMeta::update_entry_metas( $entry_id, $data["item_meta"] );
					}
				} else {
					FrmEntry::create( $data );
				}
			}
		} else {
			$errors = $this->validate_entries( $action, $data );
			
			if ( empty( $errors ) ) {
				FrmEntry::create( $data );
			}
		}
	}
}