<?php
/**
 * Login / Register form — Santo Café override.
 * Centered, narrow cards with theme styles. Preserves WC fields, nonces and hooks.
 *
 * @see woocommerce/templates/myaccount/form-login.php
 * @version 9.9.0
 */
defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_customer_login_form' );

$sc_registration = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
?>

<div class="sc-account">

    <!-- Login -->
    <div class="sc-account__card">
        <h2 class="sc-account__title">Iniciar sesión</h2>

        <?php if ( $sc_registration ) : ?>
        <p class="sc-account__switch">
            ¿No tienes cuenta? <a href="#sc-register">Regístrate</a>
        </p>
        <?php endif; ?>

        <form class="woocommerce-form woocommerce-form-login login js-validate" method="post" novalidate>

            <?php do_action( 'woocommerce_login_form_start' ); ?>

            <?php if ( empty( $_POST['register'] ) ) { woocommerce_output_all_notices(); } ?>

            <p class="woocommerce-form-row form-row">
                <label for="username">Email</label>
                <input type="email" name="username" id="username" autocomplete="email" spellcheck="false"
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

            <a class="sc-account__link" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">
                ¿Olvidaste tu contraseña?
            </a>

            <?php do_action( 'woocommerce_login_form_end' ); ?>

        </form>
    </div>

    <?php if ( $sc_registration ) : ?>
    <?php // Decorative separator — only shown when the cards stack on mobile. ?>
    <?php get_template_part( 'template-parts/divider' ); ?>
    <!-- Register -->
    <div class="sc-account__card" id="sc-register">
        <h2 class="sc-account__title">Crear cuenta</h2>
        <p class="sc-account__sub">Regístrate para comprar más rápido y seguir tus pedidos.</p>

        <form method="post" class="woocommerce-form woocommerce-form-register register js-validate" novalidate <?php do_action( 'woocommerce_register_form_tag' ); ?>>

            <?php do_action( 'woocommerce_register_form_start' ); ?>

            <?php if ( ! empty( $_POST['register'] ) ) { woocommerce_output_all_notices(); } ?>

            <div class="sc-account__row2">
                <p class="woocommerce-form-row form-row">
                    <label for="reg_first_name">Nombre</label>
                    <input type="text" name="first_name" id="reg_first_name" autocomplete="given-name"
                           value="<?php echo ( ! empty( $_POST['first_name'] ) ) ? esc_attr( wp_unslash( $_POST['first_name'] ) ) : ''; ?>"
                           required aria-required="true" />
                </p>

                <p class="woocommerce-form-row form-row">
                    <label for="reg_last_name">Apellido</label>
                    <input type="text" name="last_name" id="reg_last_name" autocomplete="family-name"
                           value="<?php echo ( ! empty( $_POST['last_name'] ) ) ? esc_attr( wp_unslash( $_POST['last_name'] ) ) : ''; ?>"
                           required aria-required="true" />
                </p>
            </div>

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
                <?php
                // Prefill: POST (reintento) o ?reg_email= (invitación desde "pedido recibido").
                $sc_reg_email = ! empty( $_POST['email'] )
                    ? wp_unslash( $_POST['email'] )
                    : ( ! empty( $_GET['reg_email'] ) ? sanitize_email( wp_unslash( $_GET['reg_email'] ) ) : '' );
                ?>
                <input type="email" name="email" id="reg_email" autocomplete="email" spellcheck="false"
                       value="<?php echo esc_attr( $sc_reg_email ); ?>"
                       required aria-required="true" />
            </p>

            <?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
            <p class="woocommerce-form-row form-row validate-password">
                <label for="reg_password">Contraseña</label>
                <input type="password" name="password" id="reg_password" autocomplete="new-password" spellcheck="false"
                       minlength="8" required aria-required="true" />
                <small class="sc-account__hint">Mínimo 8 caracteres, con al menos una letra y un número.</small>
            </p>
            <?php else : ?>
            <p class="sc-account__sub">Te enviaremos un enlace para definir tu contraseña por email.</p>
            <?php endif; ?>

            <?php do_action( 'woocommerce_register_form' ); ?>

            <?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>

            <button type="submit" class="btn btn--primary btn--full woocommerce-form-register__submit"
                    name="register" value="Crear cuenta">Crear cuenta</button>

            <?php do_action( 'woocommerce_register_form_end' ); ?>

        </form>
    </div>
    <?php endif; ?>

</div>

<?php do_action( 'woocommerce_after_customer_login_form' );
