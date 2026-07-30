<?php
/**
 * Campos custom de Santo Café en la pantalla de edición de usuario (wp-admin).
 *
 * Expone y permite editar los meta de usuario que hoy solo se cargan desde
 * el front-end ("Mi cuenta"): fecha de cumpleaños y opt-out de correos
 * promocionales. `sc_valid_birthday()` vive en inc/woocommerce.php.
 *
 * @package santocafe
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renderiza la sección "Santo Café" en profile.php / user-edit.php.
 *
 * @param WP_User $user Usuario que se está editando.
 */
function sc_render_user_profile_fields( $user ) {
	if ( ! current_user_can( 'edit_user', $user->ID ) ) {
		return;
	}
	$birthday = get_user_meta( $user->ID, 'sc_birthday', true );
	$optout   = (bool) get_user_meta( $user->ID, 'sc_email_optout', true );
	wp_nonce_field( 'sc_user_profile_fields', 'sc_user_profile_fields_nonce' );
	?>
	<h2>Santo Café</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="sc_birthday">Fecha de cumpleaños</label></th>
			<td>
				<input type="date" name="sc_birthday" id="sc_birthday" value="<?php echo esc_attr( $birthday ); ?>" class="regular-text" />
				<p class="description">Dispara el email automático de cumpleaños. Formato AAAA-MM-DD.</p>
			</td>
		</tr>
		<tr>
			<th><label for="sc_email_optout">Correos promocionales</label></th>
			<td>
				<label>
					<input type="checkbox" name="sc_email_optout" id="sc_email_optout" value="1" <?php checked( $optout ); ?> />
					Dado de baja (no recibe cumpleaños, reposición, reseña, reactivación ni carrito abandonado)
				</label>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'sc_render_user_profile_fields' );
add_action( 'edit_user_profile', 'sc_render_user_profile_fields' );

/**
 * Guarda los campos custom al actualizar el usuario desde wp-admin.
 *
 * @param int $user_id ID del usuario editado.
 */
function sc_save_user_profile_fields( $user_id ) {
	if ( ! isset( $_POST['sc_user_profile_fields_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['sc_user_profile_fields_nonce'] ), 'sc_user_profile_fields' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	$raw_birthday = sanitize_text_field( wp_unslash( $_POST['sc_birthday'] ?? '' ) );
	if ( '' === $raw_birthday ) {
		delete_user_meta( $user_id, 'sc_birthday' );
	} elseif ( function_exists( 'sc_valid_birthday' ) && sc_valid_birthday( $raw_birthday ) ) {
		update_user_meta( $user_id, 'sc_birthday', $raw_birthday );
	}

	if ( ! empty( $_POST['sc_email_optout'] ) ) {
		update_user_meta( $user_id, 'sc_email_optout', 1 );
	} else {
		delete_user_meta( $user_id, 'sc_email_optout' );
	}
}
add_action( 'personal_options_update', 'sc_save_user_profile_fields' );
add_action( 'edit_user_profile_update', 'sc_save_user_profile_fields' );
