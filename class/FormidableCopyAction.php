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
			'event'    => array( 'create', 'update' ),
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
		if ( $form->status === 'published' ) {
			?>
			<style>
				<?= "#pda-loading-".$this->number ?> {
					display: none;
				}
			</style>
			<input type="hidden" name="form-nonce-<?= $this->number ?>" id="form-nonce-<?= $this->number ?>" form-copy-security="<?= base64_encode( 'get_form_fields' ); ?>">
			<input type="hidden" value="<?= esc_attr( $form_action->post_content['form_id'] ); ?>" name="<?php echo $action_control->get_field_name( 'form_id' ) ?>">
			<input type="hidden" value="<?= esc_attr( $form_action->post_content['form_destination_data'] ); ?>" name="<?php echo $action_control->get_field_name( 'form_destination_data' ) ?>">
			<h3 id="copy_section"><?= FormidableCopyActionManager::t( 'Put data to destination form ' ) ?></h3>
			<hr/>
			<table class="form-table frm-no-margin">
				<tbody id="copy-table-body">
				<tr>
					<th><label> <b><?= FormidableCopyActionManager::t( ' Form destination: ' ); ?></b></label></th>
					<td>
						<?php FrmFormsHelper::forms_dropdown( $action_control->get_field_name( 'form_destination_id' ), $form_action->post_content['form_destination_id'], array( 'inc_children' => 'include' ) ); ?>
						<input type="button" value="<?= FormidableCopyActionManager::t( "Select" ) ?>" id="copy-select-form-btn-<?= $this->number ?>" name="copy-select-form-btn">
						<img id="pda-loading-<?= $this->number ?>" src="/wp-content/plugins/formidable/images/ajax_loader.gif" alt="Procesando"/>
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

			jQuery(document).ready(function ($) {
				var ajax_url = "<?= admin_url('admin-ajax.php'); ?>";
				jQuery(".frm_single_formidable_copy_settings").each(function () {
					var actionId = $(this).attr("data-actionkey");

					jQuery(".frm_form_settings").submit(function (e) {
						var json = JSON.stringify($("textarea.frm_formidable_copy_field_" + actionId).serializeArray());
						$("[name='frm_formidable_copy_action[" + actionId + "][post_content][form_destination_data]']").val(json);
					});

					$("#copy-select-form-btn-" + actionId).click(function () {
						$("#pda-loading-" + actionId).show();
						$.post("<?= admin_url('admin-ajax.php'); ?>", {
							'action': 'get_form_fields',
							'form_destination_id': $("[name='frm_formidable_copy_action[" + actionId + "][post_content][form_destination_id]']").val(),
							'form-copy-security': $("#form-nonce-" + actionId).attr('form-copy-security'),
							'form-instance-number': <?= $this->number ?>
						}, function (data) {
							if (data) {
								$("#copy-table-content-" + actionId).empty();
								$("#copy-table-content-" + actionId).append(data);
							}
							else {
								alert("Error, contacte al administrador!");
							}

						}).fail(function () {
							alert("Error, contacte al administrador!");
						}).always(function () {
							$("#pda-loading-" + actionId).hide();
						});
					});
				});
			});

			<?php if(isset($form_action->post_content['form_destination_id']) && !empty($form_action->post_content['form_destination_id'])){ ?>

			<?php }	?>
		</script>
	<?php
	}

	/**
	 * Add the default values for your options here
	 */
	function get_defaults() {
		$result = array(
			'form_id'               => $this->get_field_name( 'form_id' ),
			'form_destination_id'   => '',
			'form_destination_data' => '',
		);

		if ( $this->form_id != null ) {
			$result['form_id'] = $this->form_id;
		}

		return $result;
	}
}