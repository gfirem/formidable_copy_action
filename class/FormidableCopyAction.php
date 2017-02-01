<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormidableCopyAction extends FrmFormAction {
	
	protected $form_default = array( 'wrk_name' => '' );
	
	public function __construct() {
		$action_ops = array(
			'classes'  => 'dashicons dashicons-admin-page copy_action_icon',
			'limit'    => 99,
			'active'   => true,
			'priority' => 50,
			'event'    => array( 'create', 'update', 'import' ),
		);
		
		$this->FrmFormAction( 'formidable_copy', FormidableCopyActionManager::t( 'Formidable Copy Action' ), $action_ops );
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
		$form           = $args['form'];
		$fields         = $args['values']['fields'];
		$action_control = $this;
		
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
			$allow_persist     = "checked='checked'";
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
                        <label for="allow_validation_<?= $this->number ?>"> <b><?= FormidableCopyActionManager::t( ' Validate destination: ' ); ?></b></label>
                    </th>
                    <td>
                        <input type="checkbox" <?= $allow_validation ?> name="<?php echo $action_control->get_field_name( 'form_validate_data' ) ?>" id="allow_validation_<?= $this->number ?>" value="1"/>
						<?= FormidableCopyActionManager::t( "If you check this, the action validate the entry values before insert into the destination form." ) ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="repeatable_section_<?= $this->number ?>"> <b><?= FormidableCopyActionManager::t( ' Repeatable as Single: ' ); ?></b></label>
                    </th>
                    <td>
                        <input type="checkbox" <?= $form_destination_repeatable ?> name="<?php echo $action_control->get_field_name( 'form_destination_repeatable' ) ?>" id="repeatable_section_<?= $this->number ?>" value="1"/>
						<?= FormidableCopyActionManager::t( "If you check this, each item in a repeatable section will be send to the destination." ) ?>
                    </td>
                </tr>
                <tr>
                    <th><label> <b><?= FormidableCopyActionManager::t( ' Form destination: ' ); ?></b></label></th>
                    <td>
						<?php FrmFormsHelper::forms_dropdown( $action_control->get_field_name( 'form_destination_id' ), $form_action->post_content['form_destination_id'], array( 'inc_children' => 'include' ) ); ?>
                        <input type="button" value="<?= FormidableCopyActionManager::t( "Select" ) ?>" id="copy-select-form-btn-<?= $this->number ?>" name="copy-select-form-btn">
                        <img id="pda-loading-<?= $this->number ?>" src="/wp-content/plugins/formidable/images/ajax_loader.gif" alt="Procesando"/>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="allow_update_<?= $this->number ?>"> <b><?= FormidableCopyActionManager::t( ' Update destination: ' ); ?></b></label>
                    </th>
                    <td>
                        <input type="checkbox" class="fac_allow_primary_update" <?= $allow_update ?> action-id="<?php echo $this->number; ?>" form-copy-security="<?= base64_encode( 'get_form_update_fields' ); ?>" target_form="<?php echo $form_action->post_content['form_destination_id'] ?>" target="<?php echo $action_control->get_field_name( 'form_destination_primary_key' ) ?>" persist="<?php echo $action_control->get_field_name( 'form_destination_persist_enabled' ) ?>" name="<?php echo $action_control->get_field_name( 'form_destination_primary_enabled' ) ?>" id="allow_update_<?= $this->number ?>" value="1"/>
						<?= FormidableCopyActionManager::t( "If you check this, if the primary field selected exist in the destination the entry will be updated." ) ?>
                    </td>
                </tr>
                <tr <?php echo "$show_primary_key"; ?>>
                    <th>
                        <label for="allow_persist_<?= $this->number ?>"> <b><?= FormidableCopyActionManager::t( ' Persist data: ' ); ?></b></label>
                    </th>
                    <td>
                        <input type="checkbox" class="fac_allow_pesist" <?= $allow_persist ?> name="<?php echo $action_control->get_field_name( 'form_destination_persist_enabled' ) ?>" id="allow_persist_<?= $this->number ?>" value="1"/>
						<?= FormidableCopyActionManager::t( "This option is to persist the target data when the source form save an empty field." ) ?>
                    </td>
                </tr>
                <tr <?php echo "$show_primary_key"; ?>>
                    <th><label> <b><?= FormidableCopyActionManager::t( ' Primary Field: ' ); ?></b></label></th>
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
			function myToggleAllowedShortCodes(id) {
				if (typeof(id) == 'undefined') {
					id = '';
				}
				var c = id;

				if (id !== '') {
					var $ele = jQuery(document.getElementById(id));
					if ($ele.attr('class') && id !== 'wpbody-content' && id !== 'content' && id !== 'dyncontent' && id != 'success_msg') {
						var d = $ele.attr('class').split(' ')[0];
						if (d == 'frm_long_input' || typeof d == 'undefined') {
							d = '';
						} else {
							id = jQuery.trim(d);
						}
						c = c + ' ' + d;
					}
				}
				jQuery('#frm-insert-fields-box,#frm-conditionals,#frm-adv-info-tab,#frm-html-tags,#frm-layout-classes,#frm-dynamic-values').removeClass().addClass('tabs-panel ' + c);

				if (id == 'frm_formidable_copy_field_<?= $this->number ?>') {
					jQuery('.frm_code_list a').removeClass('frm_noallow').addClass('frm_allow');
					jQuery('.frm_code_list a.hide_' + id).addClass('frm_noallow').removeClass('frm_allow');
				} else {
					jQuery('.frm_code_list a').addClass('frm_noallow').removeClass('frm_allow');
				}
			}

			jQuery(document).on('focusin click', 'form input, form textarea, #wpcontent', function (e) {
				e.stopPropagation();
				if (jQuery(this).is(':not(:submit, input[type=button])') && jQuery(this).hasClass("frm_formidable_copy_field_<?= $this->number ?>")) {
					var id = jQuery(this).attr('id');
					myToggleAllowedShortCodes(id);
				}
			});


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