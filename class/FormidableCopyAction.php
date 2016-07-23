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
			'event'    => [ 'create', 'update' ],
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
				#pda-loading {
					display: none;
				}
			</style>
			<input type="hidden" name="form-nonce" id="form-nonce" form-copy-security="<?= base64_encode( 'get_form_fields' ); ?>">
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
						<input type="button" value="<?= FormidableCopyActionManager::t( "Select" ) ?>" id="copy-select-form-btn" name="copy-select-form-btn">
						<img id="pda-loading" src="/wp-content/plugins/formidable/images/ajax_loader.gif" alt="Procesando"/>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<hr/>
					</td>
				</tr>
				</tbody>
			</table>
			<table class="form-table frm-no-margin" id="copy-table-content">
				<?php
				if ( isset( $form_action->post_content['form_destination_id'] ) && ! empty( $form_action->post_content['form_destination_id'] ) ) {
					echo FormidableCopyActionAdmin::getFormFields( $form_action->post_content['form_destination_id'], $form_action->post_content['form_destination_data'] );
				}
				?>
			</table>
			<script type='text/javascript' src='<?= site_url(); ?>/wp-content/plugins/formidable_copy_action/js/tinymce/tinymce.min.js'></script>
			<script type='text/javascript' src='<?= site_url(); ?>/wp-includes/js/jquery/jquery.form.min.js'></script>


		<?php
		} else {
			echo FormidableCopyActionManager::t( 'The form need to published.' );
		}
		$language = substr( get_bloginfo( 'language' ), 0, 2 );
		?>
		<script>
			jQuery(document).ready(function ($) {
				var ajax_url = "<?= admin_url('admin-ajax.php'); ?>";
				var selectFormBtn = $("#copy-select-form-btn");
				var security = $("#form-nonce").attr('form-copy-security');

				jQuery(".frm_form_settings").submit(function(e){
					tinymce.triggerSave();
					var json = JSON.stringify($( "textarea.frm_formidable_copy" ).serializeArray());
					$("[name^='frm_formidable_copy_action'][name$='[post_content][form_destination_data]']").val(json);
				});

				selectFormBtn.click(function () {
					$("#pda-loading").show();
					$.post("/wp-admin/admin-ajax.php", {
						'action': 'get_form_fields',
						'form_destination_id': $("[name^='frm_formidable_copy_action'][name$='[post_content][form_destination_id]']").val(),
						'form-copy-security': security
					}, function (data) {
						if (data) {
							$("#copy-table-content").empty();
							$("#copy-table-content").append(data);
							loadTinyMce();
						}
						else {
							alert("Error, contacte al administrador!");
						}

					}).fail(function () {
						alert("Error, contacte al administrador!");
					}).always(function () {
						$("#pda-loading").hide();
					});
				});
			});

			function loadTinyMce() {
				tinymce.init({
					selector: '.copy_editor',
					automatic_uploads: true,
					language: '<?= $language ?>',
					relative_urls: false,
					remove_script_host: false,
					force_p_newlines: false,
					forced_root_block: '',
					toolbar: "undo redo | styleselect | bold italic | forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link | code",
					plugins: 'code textcolor colorpicker'
				});
			}

			<?php if(isset($form_action->post_content['form_destination_id']) && !empty($form_action->post_content['form_destination_id'])){ ?>
			loadTinyMce();
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