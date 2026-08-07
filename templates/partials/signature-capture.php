<?php
/**
 * Live html2canvas capture markup (never copied to email).
 *
 * @var array<string, mixed> $context
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$primary        = $context['primary'];
$secondary      = $context['secondary'];
$neutral        = $context['neutral'];
$heading_css    = $context['heading_css'];
$body_css       = $context['body_css'];
$avatar_display = (int) $context['avatar_display'];
$avatar_url     = $context['avatar_url'];
$title          = $context['title'];
$job_title      = $context['job_title'];
$phone_display  = $context['phone_display'];
$site_domain    = $context['site_domain'];
$generated      = $context['generated'];
?>
<table width="380" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:380px;">
	<tr>
		<td style="padding:0;font-size:0;line-height:0;">
			<?php if ( $generated['header'] ) : ?>
				<img src="<?php echo esc_url( $generated['header']['url'] ); ?>" width="<?php echo esc_attr( $generated['header']['width'] ); ?>" height="<?php echo esc_attr( $generated['header']['height'] ); ?>" alt="<?php echo esc_attr( $context['header_alt'] ); ?>" style="display:block;border:0;" />
			<?php else : ?>
				<div class="esp-header esp-render" data-field="header">
					<div class="esp-header-bar"></div>
					<div class="esp-header-slant"></div>
					<div class="esp-header-avatar">
						<?php if ( $avatar_url ) : ?>
							<img class="esp-header-avatar-img" src="<?php echo esc_url( $avatar_url ); ?>" width="<?php echo esc_attr( $avatar_display ); ?>" height="<?php echo esc_attr( $avatar_display ); ?>" alt="" />
						<?php endif; ?>
					</div>
					<div class="esp-header-name"><?php echo esc_html( $title ); ?></div>
					<?php if ( $job_title ) : ?>
						<div class="esp-header-title"><?php echo esc_html( $job_title ); ?></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<td style="padding:15px 0 0 0;">
			<table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
				<tr>
					<td valign="middle" style="padding:0 14px 0 6px;font-size:0;line-height:0;">
						<a href="<?php echo esc_url( $context['site_url_raw'] ); ?>" style="display:block;">
							<img src="<?php echo esc_url( $context['company_logo'] ); ?>" width="<?php echo esc_attr( (int) $context['company_logo_w'] ); ?>" height="<?php echo esc_attr( (int) $context['company_logo_h'] ); ?>" alt="<?php echo esc_attr( $site_domain ); ?>" style="display:block;border:0;" />
						</a>
					</td>
					<td valign="middle" style="padding:0;">
						<table border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
							<?php if ( $phone_display ) : ?>
								<tr>
									<td height="20" valign="middle" style="height:20px;padding:0;font-size:0;line-height:0;">
										<a href="tel:<?php echo esc_attr( $context['phone_digits'] ); ?>" style="display:block;text-decoration:none;">
											<?php if ( $generated['phone'] ) : ?>
												<img src="<?php echo esc_url( $generated['phone']['url'] ); ?>" width="<?php echo esc_attr( $generated['phone']['width'] ); ?>" height="<?php echo esc_attr( $generated['phone']['height'] ); ?>" alt="<?php echo esc_attr( $phone_display ); ?>" style="display:block;border:0;" />
											<?php else : ?>
												<span class="esp-mono esp-render" data-field="phone"><?php echo esc_html( $phone_display ); ?></span>
											<?php endif; ?>
										</a>
									</td>
								</tr>
							<?php endif; ?>
							<tr>
								<td height="20" valign="middle" style="height:20px;padding:0;font-size:0;line-height:0;">
									<a href="<?php echo esc_url( $context['site_url_raw'] ); ?>" style="display:block;text-decoration:none;">
										<?php if ( $generated['site'] ) : ?>
											<img src="<?php echo esc_url( $generated['site']['url'] ); ?>" width="<?php echo esc_attr( $generated['site']['width'] ); ?>" height="<?php echo esc_attr( $generated['site']['height'] ); ?>" alt="<?php echo esc_attr( $site_domain ); ?>" style="display:block;border:0;" />
										<?php else : ?>
											<span class="esp-mono esp-render" data-field="site"><?php echo esc_html( $site_domain ); ?></span>
										<?php endif; ?>
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
					<td height="1" style="height:1px;padding:0;font-size:1px;line-height:1px;background-color:<?php echo esc_attr( $context['tertiary'] ); ?>;">&nbsp;</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
