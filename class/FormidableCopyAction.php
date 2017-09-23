<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class FormidableCopyAction extends FrmFormAction {
	protected $form_default = array( 'wrk_name' => '' );
	
	public function __construct() {
		try {
			// check if is paying
			if ( class_exists( "FrmProAppController" ) ) {
				add_action( 'admin_head', array( $this, 'admin_style' ) );
				add_action( 'frm_trigger_formidable_copy_create_action', array( $this, 'onCreate' ), 10, 3 );
				add_action( 'frm_trigger_formidable_copy_update_action', array( $this, 'onUpdate' ), 10, 3 );
				add_action( 'frm_trigger_formidable_copy_delete_action', array( $this, 'onDelete' ), 10, 3 );
				$action_ops = array(
					'classes'  => 'dashicons dashicons-admin-page copy_action_icon',
					'limit'    => 99,
					'active'   => true,
					'priority' => 50,
					'event'    => array( 'create', 'update', 'import', 'delete' ),
				);
				$this->FrmFormAction( 'formidable_copy', FormidableCopyActionManager::t( 'Formidable Copy Action' ), $action_ops );
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
	
	/**
	 * Add styles to action icon
	 */
	public function admin_style() {
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
	 * Formidable create action
	 *
	 * @param $action
	 * @param $entry
	 * @param $form
	 */
	public function onCreate( $action, $entry, $form ) {
		$this->processAction( $action, $entry );
	}
	
	/**
	 * Formidable update action
	 *
	 * @param $action
	 * @param $entry
	 * @param $form
	 */
	public function onUpdate( $action, $entry, $form ) {
		$this->processAction( $action, $entry );
	}
	
	/**
	 * Formidable update action
	 *
	 * @param $action
	 * @param $entry
	 * @param $form
	 */
	public function onDelete( $action, $entry, $form ) {
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
		//This are the fields need to persist in the destination
		$persist_fields = array();
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
						if ( ! empty( $not_exist[0] ) ) {
							if ( ! is_numeric( $not_exist[0] ) ) {
								$field_id = FrmField::get_id_by_key( $not_exist[0] );
							} else {
								$field_id = $not_exist[0];
							}
							$persist_fields[ $val['name'] ] = $field_id;
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
			$this->insert_in_destination( $action, $destination_id, $metas, $persist_fields );
		} else {
			foreach ( $metas as $key => $item ) {
				$this->insert_in_destination( $action, $destination_id, $item, $persist_fields );
			}
		}
	}
	
	/**
	 * Validate the entry if necessary
	 *
	 * @param      $action
	 * @param      $data
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
	 * @param $persist_fields
	 */
	private function insert_in_destination( $action, $destination_id, $item, $persist_fields ) {
		//Process the data to insert in the target form
		$data = array(
			'form_id'                             => $destination_id,
			'frm_user_id'                         => get_current_user_id(),
			'frm_submit_entry_' . $destination_id => wp_create_nonce( 'frm_submit_entry_nonce' ),
			'item_meta'                           => $item,
		);
		if ( ! empty( $action->post_content['form_destination_primary_enabled'] ) && $action->post_content['form_destination_primary_enabled'] == "1" && ! empty( $action->post_content['form_destination_primary_key'] ) ) {
			$search       = $data["item_meta"][ $action->post_content['form_destination_primary_key'] ];
			$result       = FrmEntryMeta::search_entry_metas( $search, $action->post_content['form_destination_primary_key'], "LIKE" );
			$primary_data = $data["item_meta"][ $action->post_content['form_destination_primary_key'] ];
			unset( $data["item_meta"][ $action->post_content['form_destination_primary_key'] ] );
			$errors = $this->validate_entries( $action, $data );
			if ( empty( $errors ) ) {
				$data["item_meta"][ $action->post_content['form_destination_primary_key'] ] = $primary_data;
				if ( ! empty( $result ) && is_array( $result ) ) {
					foreach ( $result as $entry_id ) {
						if ( ! empty( $action->post_content['form_destination_persist_enabled'] ) && '1' === $action->post_content['form_destination_persist_enabled'] ) {
							$data["item_meta"] = $this->get_target_value_for_field( $entry_id, $data["item_meta"], $persist_fields );
						}
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
	
	/**
	 * Set the field value from the target field, it is used when the persist option is set to true
	 *
	 * @param $entry_id
	 * @param $meta
	 * @param $persist_fields
	 *
	 * @return array
	 */
	public function get_target_value_for_field( $entry_id, $meta, $persist_fields ) {
		$target_entry = FrmEntry::getOne( $entry_id, true );
		foreach ( $persist_fields as $target_key => $source_keys ) {
			if ( empty( $meta[ $target_key ] ) ) {
				$meta[ $target_key ] = $target_entry->metas[ $target_key ];
			}
		}
		
		return $meta;
	}
	
	/**
	 * Get the HTML for your action settings
	 *
	 * @param array $form_action
	 * @param array $args
	 *
	 * @return string|void
	 */
	public function form( $form_action, $args = array() ) {
		extract( $args );
		$form             = $args['form'];
		$fields           = $args['values']['fields'];
		$action_control   = $this;
		$allow_validation = "";
		if ( $form_action->post_content['form_validate_data'] == "1" ) {
			$allow_validation = "checked='checked'";
		}
		$form_destination_repeatable = "";
		if ( $form_action->post_content['form_destination_repeatable'] == "1" ) {
			$form_destination_repeatable = "checked='checked'";
		}
		$allow_update = "";
		if ( ! empty( $form_action->post_content['form_destination_primary_enabled'] ) && $form_action->post_content['form_destination_primary_enabled'] == '1' ) {
			$show_primary_key = "";
			$allow_update     = "checked='checked'";
		} else {
			$show_primary_key = "style='display:none;'";
		}
		$allow_persist = "";
		if ( ! empty( $form_action->post_content['form_destination_persist_enabled'] ) && $form_action->post_content['form_destination_persist_enabled'] == '1' ) {
			$allow_persist = "checked='checked'";
		}
		if ( $form->status === 'published' ) {
			?>
			<style>
				<?php echo "#pda-loading-".$this->number."{ display: none; }"; ?>
				<?php echo "#primary-loading-".$this->number."{ display: none; }"; ?>
			</style>
			<input type="hidden" name="form-nonce-<?= $this->number ?>" id="form-nonce-<?= $this->number ?>" form-copy-security="<?= base64_encode( 'get_form_fields' ); ?>">
			<input type="hidden" value="<?= esc_attr( $form_action->post_content['form_id'] ); ?>" name="<?php echo $action_control->get_field_name( 'form_id' ) ?>">
			<input type="hidden" value="<?= esc_attr( $form_action->post_content['form_destination_data'] ); ?>" name="<?php echo $action_control->get_field_name( 'form_destination_data' ) ?>">
			<h3 id="copy_section"><?= FormidableCopyActionManager::t( 'Put data to destination form ' ) ?></h3>
			<hr/>
			<table class="form-table frm-no-margin">
				<tbody id="copy-table-body">
				<tr>
					<th>
						<label for="allow_validation_<?= $this->number ?>"> <strong><?= FormidableCopyActionManager::t( ' Validate destination: ' ); ?></strong></label>
					</th>
					<td>
						<input type="checkbox" <?= $allow_validation ?> name="<?php echo $action_control->get_field_name( 'form_validate_data' ) ?>" id="allow_validation_<?= $this->number ?>" value="1"/>
						<?= FormidableCopyActionManager::t( "If you check this, the action validate the entry values before insert into the destination form." ) ?>
					</td>
				</tr>
				<tr>
					<th>
						<label for="repeatable_section_<?= $this->number ?>"> <strong><?= FormidableCopyActionManager::t( ' Repeatable as Single: ' ); ?></strong></label>
					</th>
					<td>
						<input type="checkbox" <?= $form_destination_repeatable ?> name="<?php echo $action_control->get_field_name( 'form_destination_repeatable' ) ?>" id="repeatable_section_<?= $this->number ?>" value="1"/>
						<?= FormidableCopyActionManager::t( "If you check this, each item in a repeatable section will be send to the destination." ) ?>
					</td>
				</tr>
				<tr>
					<th><label> <strong><?= FormidableCopyActionManager::t( ' Form destination: ' ); ?></strong></label></th>
					<td>
						<?php FrmFormsHelper::forms_dropdown( $action_control->get_field_name( 'form_destination_id' ), $form_action->post_content['form_destination_id'], array( 'inc_children' => 'include' ) ); ?>
						<input type="button" value="<?= FormidableCopyActionManager::t( "Select" ) ?>" id="copy-select-form-btn-<?= $this->number ?>" name="copy-select-form-btn">
						<img id="pda-loading-<?= $this->number ?>" src="/wp-content/plugins/formidable/images/ajax_loader.gif" alt="Procesando"/>
					</td>
				</tr>
				<tr>
					<th>
						<label for="allow_update_<?= $this->number ?>"> <strong><?= FormidableCopyActionManager::t( ' Update destination: ' ); ?></strong></label>
					</th>
					<td>
						<input type="checkbox" class="fac_allow_primary_update" <?= $allow_update ?> action-id="<?php echo $this->number; ?>" form-copy-security="<?= base64_encode( 'get_form_update_fields' ); ?>" target_form="<?php echo $form_action->post_content['form_destination_id'] ?>" target="<?php echo $action_control->get_field_name( 'form_destination_primary_key' ) ?>" persist="<?php echo $action_control->get_field_name( 'form_destination_persist_enabled' ) ?>" name="<?php echo $action_control->get_field_name( 'form_destination_primary_enabled' ) ?>" id="allow_update_<?= $this->number ?>" value="1"/>
						<?= FormidableCopyActionManager::t( "If you check this, if the primary field selected exist in the destination the entry will be updated." ) ?>
					</td>
				</tr>
				<tr <?php echo "$show_primary_key"; ?>>
					<th>
						<label for="allow_persist_<?= $this->number ?>"> <strong><?= FormidableCopyActionManager::t( ' Persist data: ' ); ?></strong></label>
					</th>
					<td>
						<input type="checkbox" class="fac_allow_pesist" <?= $allow_persist ?> name="<?php echo $action_control->get_field_name( 'form_destination_persist_enabled' ) ?>" id="allow_persist_<?= $this->number ?>" value="1"/>
						<?= FormidableCopyActionManager::t( "This option is to persist the target data when the source form save an empty field." ) ?>
					</td>
				</tr>
				<tr <?php echo "$show_primary_key"; ?>>
					<th><label> <strong><?= FormidableCopyActionManager::t( ' Primary Field: ' ); ?></strong></label></th>
					<td>
						<select name="<?php echo $action_control->get_field_name( 'form_destination_primary_key' ) ?>" id="<?php echo $action_control->get_field_name( 'form_destination_primary_key' ) ?>">
							<?php
							if ( ! empty( $form_action->post_content['form_destination_id'] ) ) {
								echo FormidableCopyActionAdmin::getUpdateFields( $form_action->post_content['form_destination_id'], $form_action->post_content['form_destination_primary_key'] );
							}
							?>
						</select>
						<img id="primary-loading-<?= $this->number ?>" src="/wp-content/plugins/formidable/images/ajax_loader.gif" alt="Procesando"/>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<hr/>
					</td>
				</tr>
				</tbody>
			</table>
			<table class="form-table frm-no-margin" id="copy-table-content-<?= $this->number ?>">
				<?php
				if ( isset( $form_action->post_content['form_destination_id'] ) && ! empty( $form_action->post_content['form_destination_id'] ) ) {
					echo FormidableCopyActionAdmin::getFormFields( $this->number, $form_action->post_content['form_destination_id'], $form_action->post_content['form_destination_data'] );
				}
				?>
			</table>
			<?php
		} else {
			echo FormidableCopyActionManager::t( 'The form need to published.' );
		}
		$language = substr( get_bloginfo( 'language' ), 0, 2 );
		?>
		<script>



			function get_update_fields($, actionId, form_drop_down, form_copy_security, target, persist) {
				$("#primary-loading-" + actionId).show();
				$.post("<?= admin_url( 'admin-ajax.php' ); ?>", {
					'action': 'get_form_update_fields',
					'form_destination_id': form_drop_down.val(),
					'form-copy-security': form_copy_security
				}, function (data) {
					if (data) {
						$("[name='" + target + "']").empty().append(data).parent().parent().show();
						$("[name='" + persist + "']").empty().append(data).parent().parent().show();
					}
					else {
						alert("Error, contact to administrator!");
					}

				}).fail(function () {
					alert("Error, contact to administrator!");
				}).always(function () {
					$("#primary-loading-" + actionId).hide();
				});
			}

			jQuery(document).ready(function ($) {
				var ajax_url = "<?= admin_url( 'admin-ajax.php' ); ?>";
				var fac_allow_update = $(".fac_allow_primary_update");
				var form_drop_down = $("[name='<?php echo $action_control->get_field_name( 'form_destination_id' ) ?>']");
				fac_allow_update.click(function () {
					var target = $(this).attr("target");
					var persist = $(this).attr("persist");
					var form_copy_security = $(this).attr("form-copy-security");
					var actionId = $(this).attr("action-id");
					if ($(this).is(":checked")) {
						if (form_drop_down.val()) {
							get_update_fields($, actionId, form_drop_down, form_copy_security, target, persist);
						}
						else {
							alert("Please select a destination form.");
							$(this).attr('checked', false);
						}
					}
					else {
						$("[name='" + target + "']").parent().parent().hide();
						$("[name='" + persist + "']").parent().parent().hide();
					}
				});

				jQuery(".frm_single_formidable_copy_settings").each(function () {
					var actionId = $(this).attr("data-actionkey");

					jQuery(".frm_form_settings").submit(function (e) {
						var json = JSON.stringify($("textarea.frm_formidable_copy_field_" + actionId).serializeArray());
						$("[name='frm_formidable_copy_action[" + actionId + "][post_content][form_destination_data]']").val(json);
					});

					$("#copy-select-form-btn-" + actionId).click(function () {
						$("#pda-loading-" + actionId).show();
						$.post("<?= admin_url( 'admin-ajax.php' ); ?>", {
							'action': 'get_form_fields',
							'form_destination_id': $("[name='frm_formidable_copy_action[" + actionId + "][post_content][form_destination_id]']").val(),
							'form-copy-security': $("#form-nonce-" + actionId).attr('form-copy-security'),
							'form-instance-number': <?= $this->number ?>
						}, function (data) {
							if (data) {
								$("#copy-table-content-" + actionId).empty().append(data);
								if ($(".fac_allow_primary_update").is(":checked")) {
									get_update_fields(
										$,
										actionId,
										$("[name='<?php echo $action_control->get_field_name( 'form_destination_id' ) ?>']"),
										$(".fac_allow_primary_update").attr("form-copy-security"),
										$(".fac_allow_primary_update").attr("target")
									);
								}
							}
							else {
								alert("Error, contact to administrator!");
							}

						}).fail(function () {
							alert("Error, contact to administrator!");
						}).always(function () {
							$("#pda-loading-" + actionId).hide();
						});
					});
				});
			});
			
			<?php if(isset( $form_action->post_content['form_destination_id'] ) && ! empty( $form_action->post_content['form_destination_id'] )){ ?>
			
			<?php }    ?>
		</script>
		<?php
	}
	
	/**
	 * Add the default values for your options here
	 */
	function get_defaults() {
		$result = array(
			'form_id'                          => $this->get_field_name( 'form_id' ),
			'form_destination_id'              => '',
			'form_destination_data'            => '',
			'form_validate_data'               => '',
			'form_destination_primary_enabled' => '',
			'form_destination_primary_key'     => '',
			'form_destination_repeatable'      => '',
			'form_destination_persist_enabled' => '',
		);
		if ( $this->form_id != null ) {
			$result['form_id'] = $this->form_id;
		}
		
		return $result;
	}
}