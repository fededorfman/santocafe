<?php
/**
 * Login form (global) — Santo Café override.
 *
 * Lo usa WooCommerce en la "puerta" de pedido recibido / ver pedido cuando la
 * orden es de una cuenta registrada y el visitante no está logueado. Le damos
 * la misma estética de tarjeta que el login de "Mi cuenta" (myaccount/form-login),
 * preservando el campo `redirect` para volver al pedido tras iniciar sesión.
 *
 * @see woocommerce/templates/global/form-login.php
 * @var string $message Mensaje opcional sobre el formulario.
 * @var bool   $hidden  Si el form arranca oculto.
 * @var string $redirect URL de retorno tras el login.
 * @package santocafe
 * @version 9.2.0
 */

defined( 'ABSPATH' ) || exit;

if ( is_user_logged_in() ) {
	return;
}
?>

<div class="sc-account sc-account--gate">
    <div class="sc-account__card">
        <h2 class="sc-account__title">Inicia sesión para ver tu pedido</h2>
        <p class="sc-account__sub">
            <?php echo ! empty( $message ) ? wp_kses_post( $message ) : 'Esta orden pertenece a una cuenta registrada. Accede para ver el detalle.'; ?>
        </p>

        <form class="woocommerce-form woocommerce-form-login login js-validate" method="post" <?php echo $hidden ? 'style="display:none;"' : ''; ?> novalidate>

            <?php do_action( 'woocommerce_login_form_start' ); ?>

            <?php woocommerce_output_all_notices(); // errores de login (clave incorrecta, etc.) ?>

            <p class="woocommerce-form-row form-row">
                <label for="username">Usuario o email</label>
                <input type="text" name="username" id="username" autocomplete="username" spellcheck="false"
                       value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>"
                       required aria-required="true" />
            </p>

            <p class="woocommerce-form-row form-row">
                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" autocomplete="current-password" spellcheck="false"
                       required aria-required="true" />
            </p>

            <?php do_action( 'woocommerce_login_form' ); ?>

            <label class="sc-account__remember">
                <input type="checkbox" name="rememberme" id="rememberme" value="forever" />
                <span>Recordarme</span>
            </label>

            <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>

            <button type="submit" class="btn btn--primary btn--full woocommerce-form-login__submit"
                    name="login" value="Iniciar sesión">Iniciar sesión</button>

            <?php if ( ! empty( $redirect ) ) : ?>
                <input type="hidden" name="redirect" value="<?php echo esc_url( $redirect ); ?>" />
            <?php endif; ?>

            <a class="sc-account__link" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">
                ¿Olvidaste tu contraseña?
            </a>

            <?php do_action( 'woocommerce_login_form_end' ); ?>

        </form>
    </div>
</div>
