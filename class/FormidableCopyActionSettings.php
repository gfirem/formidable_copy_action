<?php

class FormidableCopyActionSettings {

	public static function route() {

		$action = isset( $_REQUEST['frm_action'] ) ? 'frm_action' : 'action';
		$action = FrmAppHelper::get_param( $action );
		if ( $action == 'process-form' ) {
			return self::process_form();
		} else {
			return self::display_form();
		}
	}

	/**
	 * @internal var gManager GManager_1_0
	 */
	public static function display_form( ) {
		$gManager = GManagerFactory::buildManager('FormidableCopyActionManager', 'formidable_copy_action', FormidableCopyActionManager::getShort());
		$key  = get_option( FormidableCopyActionManager::getShort() . 'licence_key' );
		?>
		<h3 class="frm_first_h3"><?= FormidableCopyActionManager::t( "Licence Data for Copy Action" ) ?></h3>
		<table class="form-table">
			<tr>
				<td width="150px"><?= FormidableCopyActionManager::t( "Version: " ) ?></td>
				<td>
					<span><?= FormidableCopyActionManager::getVersion() ?></span>
				</td>
			</tr>
			<tr class="form-field" valign="top">
				<td width="150px">
					<label for="key"><?= FormidableCopyActionManager::t( "Order Key: " ) ?></label>
					<span class="frm_help frm_icon_font frm_tooltip_icon" title="" data-original-title="<?= FormidableCopyActionManager::t( "Order key send to you with order confirmation, to get updates." ) ?>"></span>
				</td>
				<td><input type="text" name="<?= FormidableCopyActionManager::getShort() ?>_key" id="<?= FormidableCopyActionManager::getShort() ?>_key" value="<?= $key ?>"/></td>
			</tr>
			<tr class="form-field" valign="top">
				<td width="150px"><?= FormidableCopyActionManager::t( "Key status: " ) ?></label></td>
				<td><?= $gManager->getStatus() ?></td>
			</tr>
		</table>
	<?php
	}

	public static function process_form() {
		if ( isset( $_POST[ FormidablePatternFieldManager::getShort() . '_key' ] ) && ! empty( $_POST[ FormidablePatternFieldManager::getShort() . '_key' ] ) ) {
			$gManager = GManagerFactory::buildManager('FormidableCopyActionManager', 'formidable_copy_action', FormidableCopyActionManager::getShort());
			$gManager->activate($_POST[ FormidablePatternFieldManager::getShort() . '_key' ]);
			update_option( FormidableCopyActionManager::getShort() . 'licence_key', $_POST[ FormidablePatternFieldManager::getShort() . '_key' ] );
		}
		self::display_form();
	}
}