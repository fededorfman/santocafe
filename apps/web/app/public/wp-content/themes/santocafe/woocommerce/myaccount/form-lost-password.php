<?php
/**
 * Lost password form — Santo Café override.
 * Centered narrow card with theme styles. Preserves WC fields, nonce and hooks.
 *
 * @see woocommerce/templates/myaccount/form-lost-password.php
 * @version 9.2.0
 */
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_lost_password_form' );
?>

<div class="sc-account">
    <div class="sc-account__card">
        <h2 class="sc-account__title">Recuperar contraseña</h2>

        <form method="post" class="woocommerce-ResetPassword lost_reset_password js-validate" novalidate>

            <p class="sc-account__sub">
                <?php echo apply_filters( 'woocommerce_lost_password_message', esc_html__( 'Ingresá tu email o usuario y te enviaremos un enlace para crear una nueva contraseña.', 'santocafe' ) ); ?>
            </p>

            <p class="woocommerce-form-row form-row">
                <label for="user_login">Email</label>
                <input class="woocommerce-Input woocommerce-Input--text input-text" type="email"
                       name="user_login" id="user_login" autocomplete="email" required aria-required="true" />
            </p>

            <?php do_action( 'woocommerce_lostpassword_form' ); ?>

            <p class="woocommerce-form-row form-row">
                <input type="hidden" name="wc_reset_password" value="true" />
                <button type="submit" class="btn btn--primary btn--full" value="Enviar enlace">Enviar enlace</button>
            </p>

            <?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>

            <a class="sc-account__link" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
                ← Volver a iniciar sesión
            </a>

        </form>
    </div>
</div>

<?php
do_action( 'woocommerce_after_lost_password_form' );
