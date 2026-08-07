<?php
/**
 * Email-safe signature table markup (copied to clipboard).
 *
 * Expects $context from esp_build_signature_context().
 *
 * @var array<string, mixed> $context
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$generated     = $context['generated'];
$tertiary      = $context['tertiary'];
$company_logo  = $context['company_logo'];
$company_logo_w = (int) $context['company_logo_w'];
$company_logo_h = (int) $context['company_logo_h'];
$site_url_raw  = $context['site_url_raw'];
$site_domain   = $context['site_domain'];
$phone_digits  = $context['phone_digits'];
$phone_display = $context['phone_display'];
$header_alt    = $context['header_alt'];
?>
<table width="380" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:380px;">
	<tr>
		<td style="padding:0;font-size:0;line-height:0;">
			<img src="<?php echo esc_url( $generated['header']['url'] ); ?>" width="<?php echo esc_attr( $generated['header']['width'] ); ?>" height="<?php echo esc_attr( $generated['header']['height'] ); ?>" alt="<?php echo esc_attr( $header_alt ); ?>" style="display:block;border:0;" />
		</td>
	</tr>
	<tr>
		<td style="padding:15px 0 0 0;">
			<table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
				<tr>
					<td valign="middle" style="padding:0 14px 0 6px;font-size:0;line-height:0;">
						<a href="<?php echo esc_url( $site_url_raw ); ?>" style="display:block;text-decoration:none;">
							<img src="<?php echo esc_url( $company_logo ); ?>" width="<?php echo esc_attr( $company_logo_w ); ?>" height="<?php echo esc_attr( $company_logo_h ); ?>" alt="<?php echo esc_attr( $site_domain ); ?>" style="display:block;border:0;" />
						</a>
					</td>
					<td valign="middle" style="padding:0;">
						<table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
							<?php if ( $phone_display ) : ?>
								<tr>
									<td height="20" valign="middle" style="height:20px;padding:0;font-size:0;line-height:0;">
										<a href="tel:<?php echo esc_attr( $phone_digits ); ?>" style="display:block;text-decoration:none;">
											<img src="<?php echo esc_url( $generated['phone']['url'] ); ?>" width="<?php echo esc_attr( $generated['phone']['width'] ); ?>" height="<?php echo esc_attr( $generated['phone']['height'] ); ?>" alt="<?php echo esc_attr( $phone_display ); ?>" style="display:block;border:0;" />
										</a>
									</td>
								</tr>
							<?php endif; ?>
							<tr>
								<td height="20" valign="middle" style="height:20px;padding:0;font-size:0;line-height:0;">
									<a href="<?php echo esc_url( $site_url_raw ); ?>" style="display:block;text-decoration:none;">
										<img src="<?php echo esc_url( $generated['site']['url'] ); ?>" width="<?php echo esc_attr( $generated['site']['width'] ); ?>" height="<?php echo esc_attr( $generated['site']['height'] ); ?>" alt="<?php echo esc_attr( $site_domain ); ?>" style="display:block;border:0;" />
									</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td style="padding:17px 0 0 0;">
			<table width="321" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:321px;">
				<tr>
					<td height="1" style="height:1px;padding:0;font-size:1px;line-height:1px;background-color:<?php echo esc_attr( $tertiary ); ?>;">&nbsp;</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
