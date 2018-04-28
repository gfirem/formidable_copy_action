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