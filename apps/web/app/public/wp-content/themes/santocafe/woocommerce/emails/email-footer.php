<?php
/**
 * Email Footer — Santo Café override.
 *
 * Cierra el contenedor abierto en `email-header.php` y agrega el pie de marca
 * con datos de contacto y legales. Si el comercio configuró un texto de pie en
 * WooCommerce, lo respetamos; si no, usamos el de la empresa.
 *
 * @see woocommerce/templates/emails/email-footer.php
 * @package santocafe
 * @version 10.4.0
 */

defined( 'ABSPATH' ) || exit;

$email = $email ?? null;

/* Texto de pie configurable; fallback a los datos de la empresa. */
$sc_footer_text = get_option( 'woocommerce_email_footer_text' );
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
											<td colspan="2" valign="middle" id="credit">
												<?php if ( ! empty( $sc_footer_text ) ) : ?>
													<?php
													echo wp_kses_post(
														wpautop( wptexturize( apply_filters( 'woocommerce_email_footer_text', $sc_footer_text, $email ) ) )
													);
													?>
												<?php else : ?>
													<p style="margin:0 0 12px;">
														¿Dudas con tu pedido? Escríbenos a
														<a href="mailto:santocafespecialtycoffee@gmail.com">santocafespecialtycoffee@gmail.com</a>
														o al +56&nbsp;9&nbsp;5141&nbsp;4791.
													</p>
													<p style="margin:0;">
														SANTO CAFÉ SPECIALTY COFFEE SPA · RUT 78.245.225-8<br>
														San Pío X 2390 Of 803, Santiago · <a href="<?php echo esc_url( home_url() ); ?>">santocafe.cl</a>
													</p>
												<?php endif; ?>
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
