<?php
/**
 * Login / Register form — Santo Café override.
 * Centered, narrow cards with theme styles. Preserves WC fields, nonces and hooks.
 *
 * @see woocommerce/templates/myaccount/form-login.php
 */
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_customer_login_form' );

$sc_registration = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
?>

<div class="sc-account">

    <!-- Login -->
    <div class="sc-account__card">
        <h2 class="sc-account__title">Iniciar sesión</h2>

        <form class="woocommerce-form woocommerce-form-login login" method="post">

            <?php do_action( 'woocommerce_login_form_start' ); ?>

            <p class="woocommerce-form-row form-row">
                <label for="username">Email o usuario</label>
                <input type="text" name="username" id="username" autocomplete="username"
                       value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>"
                       required aria-required="true" />
            </p>

            <p class="woocommerce-form-row form-row">
                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" autocomplete="current-password"
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

            <a class="sc-account__link" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">
                ¿Olvidaste tu contraseña?
            </a>

            <?php do_action( 'woocommerce_login_form_end' ); ?>

        </form>
    </div>

    <?php if ( $sc_registration ) : ?>
    <!-- Register -->
    <div class="sc-account__card">
        <h2 class="sc-account__title">Crear cuenta</h2>
        <p class="sc-account__sub">Registrate para comprar más rápido y seguir tus pedidos.</p>

        <form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?>>

            <?php do_action( 'woocommerce_register_form_start' ); ?>

            <?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
            <p class="woocommerce-form-row form-row">
                <label for="reg_username">Usuario</label>
                <input type="text" name="username" id="reg_username" autocomplete="username"
                       value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>"
                       required aria-required="true" />
            </p>
            <?php endif; ?>

            <p class="woocommerce-form-row form-row">
                <label for="reg_email">Email</label>
                <input type="email" name="email" id="reg_email" autocomplete="email"
                       value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>"
                       required aria-required="true" />
            </p>

            <?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
            <p class="woocommerce-form-row form-row">
                <label for="reg_password">Contraseña</label>
                <input type="password" name="password" id="reg_password" autocomplete="new-password"
                       required aria-required="true" />
            </p>
            <?php else : ?>
            <p class="sc-account__sub">Te enviaremos un enlace para definir tu contraseña por email.</p>
            <?php endif; ?>

            <?php do_action( 'woocommerce_register_form' ); ?>

            <?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>

            <button type="submit" class="btn btn--outline btn--full woocommerce-form-register__submit"
                    name="register" value="Crear cuenta">Crear cuenta</button>

            <?php do_action( 'woocommerce_register_form_end' ); ?>

        </form>
    </div>
    <?php endif; ?>

</div>

<?php do_action( 'woocommerce_after_customer_login_form' );
