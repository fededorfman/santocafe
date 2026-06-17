<?php
/**
 * Email Footer — Santo Café override.
 *
 * Cierra el contenedor abierto en `email-header.php` y agrega el pie de marca
 * oscuro: CTA a redes sociales, íconos de Instagram y WhatsApp, datos de
 * contacto/legales y copyright con año dinámico. Compartido por todos los
 * correos transaccionales de WooCommerce.
 *
 * @see woocommerce/templates/emails/email-footer.php
 * @package santocafe
 * @version 10.4.0
 */

defined( 'ABSPATH' ) || exit;

$email = $email ?? null;

/**
 * Base URL de los íconos de email. En producción resuelve al dominio público;
 * overrideable para testing local (las imágenes de santocafe.local no cargan en
 * clientes externos como Gmail hasta que el sitio esté publicado).
 *
 * @param string $base URL absoluta de la carpeta assets/images/email.
 */
$sc_assets    = apply_filters( 'sc_email_assets_url', get_stylesheet_directory_uri() . '/assets/images/email' );
$sc_instagram = 'https://instagram.com/santocafespecialtycoffee';
$sc_whatsapp  = 'https://wa.me/56951414791';
$sc_year      = date_i18n( 'Y' );
?>
															</div>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<!-- Footer -->
							<tr>
								<td valign="top">
									<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_footer" role="presentation">
										<tr>
											<td colspan="2" valign="top" id="credit" style="background-color:#1a1310; padding:34px 44px 32px;">
												<p style="margin:0 0 22px; font-size:15px; line-height:1.6; color:#ffffff; font-weight:600; text-align:center;">
													Síguenos en redes sociales para enterarte de todas nuestras promociones, novedades y consejos de barista.
												</p>
												<table border="0" cellpadding="0" cellspacing="0" align="center" role="presentation" style="margin:0 auto 22px;">
													<tr>
														<td style="padding:0 9px;">
															<a href="<?php echo esc_url( $sc_instagram ); ?>" target="_blank" style="text-decoration:none;"><img src="<?php echo esc_url( $sc_assets . '/instagram.png' ); ?>" width="28" height="28" alt="Instagram" style="display:block; width:28px; height:28px; border:0;" /></a>
														</td>
														<td style="padding:0 9px;">
															<a href="<?php echo esc_url( $sc_whatsapp ); ?>" target="_blank" style="text-decoration:none;"><img src="<?php echo esc_url( $sc_assets . '/whatsapp.png' ); ?>" width="28" height="28" alt="WhatsApp" style="display:block; width:28px; height:28px; border:0;" /></a>
														</td>
													</tr>
												</table>
												<p style="margin:0 0 6px; font-size:13px; line-height:1.6; color:#c9bfb0; text-align:center;">
													Santo Café · <a href="mailto:hola@santocafe.cl" style="color:#dfb33e; text-decoration:none;">hola@santocafe.cl</a> · +56&nbsp;9&nbsp;5141&nbsp;4791
												</p>
												<p style="margin:0 0 10px; font-size:12px; line-height:1.6; color:#8a7d6b; text-align:center;">
													Santo Café Specialty Coffee SpA · RUT 78.245.225-8 · San Pío X 2390 Of 803, Santiago
												</p>
												<p style="margin:0; font-size:12px; line-height:1.6; color:#8a7d6b; text-align:center;">
													© <?php echo esc_html( $sc_year ); ?> Santo Café
												</p>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</div>
				</td>
			</tr>
		</table>
	</body>
</html>
