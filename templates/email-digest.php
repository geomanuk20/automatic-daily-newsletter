<?php
/**
 * HTML Email Digest Template
 * 
 * Variables available:
 * @var array  $posts            Array of formatted post items
 * @var string $site_name        Name of WordPress site
 * @var string $site_url         URL of WordPress site
 * @var string $site_logo        URL to logo image if configured
 * @var string $current_date     Formatted date string
 * @var string $preheader_text   Inbox preview text
 * @var string $primary_color    Hex code for brand accent color (e.g. #2563eb)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$primary_color = ! empty( $primary_color ) ? $primary_color : '#2563eb';
$featured_post = ! empty( $posts ) ? $posts[0] : null;
$other_posts   = ( count( $posts ) > 1 ) ? array_slice( $posts, 1 ) : array();
?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title><?php echo esc_html( $site_name ); ?> - Daily Digest</title>
	<!--[if mso]>
	<noscript>
		<xml>
			<o:OfficeDocumentSettings>
				<o:PixelsPerInch>96</o:PixelsPerInch>
			</o:OfficeDocumentSettings>
		</xml>
	</noscript>
	<![endif]-->
	<style type="text/css">
		body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
		table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
		img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
		body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
		@media screen and (max-width: 600px) {
			.email-container { width: 100% !important; margin: auto !important; }
			.stack-column, .stack-column-dir { display: block !important; width: 100% !important; max-width: 100% !important; direction: ltr !important; }
			.mobile-padding { padding-left: 18px !important; padding-right: 18px !important; }
			.mobile-thumb { width: 100% !important; height: auto !important; margin-bottom: 12px !important; }
			.mobile-hide { display: none !important; }
		}
	</style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9;">

	<!-- Hidden Preheader Preview Text (Spam-safe whitespace padding) -->
	<div style="display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all; font-family: sans-serif;">
		<?php echo esc_html( $preheader_text ); ?>
		&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
	</div>

	<!-- Background Wrapper -->
	<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; padding: 24px 0;">
		<tr>
			<td align="center">
				
				<!-- Main Email Container (Max 600px width) -->
				<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -1px rgba(0, 0, 0, 0.04);" class="email-container">
					
					<!-- Top Solid Black Line -->
					<tr>
						<td style="background-color: #000000; height: 5px; line-height: 5px; font-size: 1px;">&nbsp;</td>
					</tr>

					<!-- Editorial Newspaper Header Section (Manorama Style) -->
					<tr>
						<td style="padding: 24px 32px 14px 32px; background-color: #ffffff; text-align: center;" align="center" class="mobile-padding">
							<table border="0" cellpadding="0" cellspacing="0" width="100%">
								<tr>
									<td align="center">
										<!-- Logo -->
										<?php if ( ! empty( $site_logo ) ) : ?>
											<a href="<?php echo esc_url( $site_url ); ?>" target="_blank" style="text-decoration: none; display: inline-block;">
												<img src="<?php echo esc_url( $site_logo ); ?>" alt="<?php echo esc_attr( $site_name ); ?>" style="max-height: 80px; max-width: 380px; width: auto; height: auto; display: block; margin: 0 auto; border: 0;" />
											</a>
										<?php else : ?>
											<!-- Styled Fallback Brand Logo -->
											<a href="<?php echo esc_url( $site_url ); ?>" target="_blank" style="text-decoration: none; display: inline-block;">
												<table border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
														<?php 
															$first_char = function_exists( 'mb_substr' ) ? mb_substr( $site_name, 0, 1 ) : substr( $site_name, 0, 1 );
															$brand_initial = ! empty( $site_name ) ? strtoupper( $first_char ) : 'N'; 
														?>
														<td style="background-color: #e11d48; color: #ffffff; font-size: 16px; font-weight: 900; padding: 4px 8px; border-radius: 4px; font-family: sans-serif; text-align: center; vertical-align: middle;"><?php echo esc_html( $brand_initial ); ?></td>
														<td style="padding-left: 8px; font-size: 24px; font-weight: 800; color: #004b87; font-family: sans-serif; letter-spacing: -0.5px; vertical-align: middle;">
															<?php echo esc_html( $site_name ); ?>
														</td>
													</tr>
												</table>
											</a>
										<?php endif; ?>

										<!-- Editorial Serif Publication Title -->
										<?php
										$custom_header_opt = get_option( 'adnl_header_title', '' );
										if ( ! empty( $custom_header_opt ) ) {
											$display_title = str_replace( '{site_name}', $site_name, $custom_header_opt );
										} elseif ( ! empty( $header_title ) ) {
											$display_title = str_replace( '{site_name}', $site_name, $header_title );
										} else {
											$display_title = $site_name . ' Newsletter';
										}
										?>
										<h1 style="font-family: Georgia, 'Times New Roman', Times, serif; font-size: 27px; line-height: 34px; font-weight: 700; color: #111827; margin: 14px 0 6px 0; letter-spacing: -0.5px;">
											<?php echo esc_html( $display_title ); ?>
										</h1>

										<!-- Edition Date in Uppercase -->
										<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 12px; font-weight: 600; color: #4b5563; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 16px 0;">
											<?php echo esc_html( strtoupper( wp_date( 'l, F j, Y' ) ) ); ?>
										</div>

										<!-- Full-width Divider with Centered Accent Bar -->
										<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;">
											<tr>
												<td style="border-top: 1px solid #d1d5db; line-height: 0; font-size: 0;" align="center">
													<div style="display: inline-block; width: 64px; height: 3px; background-color: <?php echo esc_attr( ! empty( $primary_color ) ? $primary_color : '#e11d48' ); ?>; margin-top: -2px; border-radius: 1px;"></div>
												</td>
											</tr>
										</table>

									</td>
								</tr>
							</table>
						</td>
					</tr>

					<!-- Introduction / Top Stories Banner -->
					<tr>
						<td style="padding: 20px 32px 12px 32px; background-color: #ffffff;" class="mobile-padding">
							<table border="0" cellpadding="0" cellspacing="0" width="100%">
								<tr>
									<td style="border-bottom: 2px solid #f1f5f9; padding-bottom: 12px;">
										<span style="color: #0f172a; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: inline-block;">
											Today's Highlights (<?php echo intval( count( $posts ) ); ?> Stories)
										</span>
									</td>
								</tr>
							</table>
						</td>
					</tr>

					<?php if ( $featured_post ) : ?>
					<!-- FEATURED HERO STORY -->
					<tr>
						<td style="padding: 12px 32px 24px 32px;" class="mobile-padding">
							<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0;">
								<?php if ( ! empty( $featured_post['thumbnail_url'] ) ) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( $featured_post['permalink'] ); ?>" target="_blank">
											<img src="<?php echo esc_url( $featured_post['thumbnail_url'] ); ?>" alt="<?php echo esc_attr( $featured_post['title'] ); ?>" width="600" style="width: 100%; max-height: 280px; object-fit: cover; display: block; border-top-left-radius: 10px; border-top-right-radius: 10px;" />
										</a>
									</td>
								</tr>
								<?php endif; ?>
								<tr>
									<td style="padding: 24px;">
										<!-- Category & Read Time -->
										<table border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 10px;">
											<tr>
												<td style="background-color: #eff6ff; color: <?php echo esc_attr( $primary_color ); ?>; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 4px 10px; border-radius: 4px;">
													<?php echo esc_html( $featured_post['category'] ); ?>
												</td>
												<td style="color: #64748b; font-size: 12px; padding-left: 10px;">
													<?php echo esc_html( $featured_post['read_time'] ); ?> &bull; <?php echo esc_html( $featured_post['date'] ); ?>
												</td>
											</tr>
										</table>

										<!-- Title -->
										<h1 style="margin: 0 0 12px 0; font-size: 20px; line-height: 28px; font-weight: 700; color: #0f172a;">
											<a href="<?php echo esc_url( $featured_post['permalink'] ); ?>" target="_blank" style="color: #0f172a; text-decoration: none;">
												<?php echo esc_html( $featured_post['title'] ); ?>
											</a>
										</h1>

										<!-- Excerpt -->
										<p style="margin: 0 0 18px 0; font-size: 14px; line-height: 22px; color: #475569;">
											<?php echo esc_html( $featured_post['excerpt'] ); ?>
										</p>

										<!-- CTA Button -->
										<table border="0" cellpadding="0" cellspacing="0">
											<tr>
												<td align="center" style="border-radius: 6px; background-color: <?php echo esc_attr( $primary_color ); ?>;">
													<a href="<?php echo esc_url( $featured_post['permalink'] ); ?>" target="_blank" style="font-size: 13px; font-weight: 600; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 6px; display: inline-block;">
														Read Full Story &rarr;
													</a>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<?php endif; ?>

					<?php if ( ! empty( $other_posts ) ) : ?>
					<!-- SECONDARY STORIES SECTION -->
					<tr>
						<td style="padding: 12px 32px 24px 32px;" class="mobile-padding">
							<table border="0" cellpadding="0" cellspacing="0" width="100%">
								<?php foreach ( $other_posts as $index => $item ) : ?>
								<tr>
									<td style="padding: 16px 0; <?php echo ( $index < count( $other_posts ) - 1 ) ? 'border-bottom: 1px solid #e2e8f0;' : ''; ?>">
										<table border="0" cellpadding="0" cellspacing="0" width="100%">
											<tr>
												<?php if ( ! empty( $item['thumbnail_url'] ) ) : ?>
												<!-- Left Thumbnail -->
												<td width="120" valign="top" style="padding-right: 18px;" class="stack-column">
													<a href="<?php echo esc_url( $item['permalink'] ); ?>" target="_blank">
														<img src="<?php echo esc_url( $item['thumbnail_url'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" width="120" height="85" style="width: 120px; height: 85px; object-fit: cover; border-radius: 6px; display: block;" class="mobile-thumb" />
													</a>
												</td>
												<?php endif; ?>
												
												<!-- Content -->
												<td valign="top" class="stack-column">
													<div style="font-size: 11px; font-weight: 600; color: <?php echo esc_attr( $primary_color ); ?>; text-transform: uppercase; margin-bottom: 4px;">
														<?php echo esc_html( $item['category'] ); ?> &bull; <span style="color: #94a3b8; text-transform: none;"><?php echo esc_html( $item['date'] ); ?></span>
													</div>
													<h2 style="margin: 0 0 6px 0; font-size: 15px; line-height: 20px; font-weight: 700;">
														<a href="<?php echo esc_url( $item['permalink'] ); ?>" target="_blank" style="color: #0f172a; text-decoration: none;">
															<?php echo esc_html( $item['title'] ); ?>
														</a>
													</h2>
													<p style="margin: 0 0 6px 0; font-size: 13px; line-height: 18px; color: #64748b;">
														<?php echo esc_html( $item['excerpt'] ); ?>
													</p>
													<a href="<?php echo esc_url( $item['permalink'] ); ?>" target="_blank" style="font-size: 12px; font-weight: 600; color: <?php echo esc_attr( $primary_color ); ?>; text-decoration: none;">
														Read More &rarr;
													</a>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<?php endforeach; ?>
							</table>
						</td>
					</tr>
					<?php endif; ?>

					<!-- FOOTER SECTION (Clean Light Editorial Styling) -->
					<?php
					$bg_color_footer  = ! empty( $footer_bg_color ) ? $footer_bg_color : '#f8fafc';
					$text_footer_desc = ! empty( $footer_text ) ? $footer_text : ( 'You received this email because you subscribed to daily news updates on ' . $site_name . '.' );
					$text_footer_copy = ! empty( $footer_copyright ) ? $footer_copyright : ( '© ' . date( 'Y' ) . ' ' . $site_name . '. All rights reserved.' );
					?>
					<tr>
						<td style="padding: 28px 32px; background-color: <?php echo esc_attr( $bg_color_footer ); ?>; border-top: 1px solid #e2e8f0; text-align: center; color: #64748b; font-size: 12px; line-height: 18px;" class="mobile-padding">
							<?php if ( ! empty( $show_primary_tip ) ) : ?>
							<div style="margin: 0 0 16px 0; padding: 10px 14px; background-color: #ffffff; border: 1px dashed #cbd5e1; border-radius: 6px; font-size: 11px; line-height: 16px; color: #475569; text-align: center;">
								📬 <strong>Primary Inbox Tip:</strong> If this email appeared in your Promotions tab or Spam folder, please drag it to your <strong>Primary</strong> tab or mark it as "Not Spam". A quick reply or adding us to your contacts also ensures 100% daily delivery!
							</div>
							<?php endif; ?>
							<p style="margin: 0 0 8px 0; font-weight: 700; color: #1e293b; font-size: 13px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
								<?php echo esc_html( ! empty( $header_title ) ? $header_title : $site_name . ' Newsletter' ); ?>
							</p>
							<p style="margin: 0 0 12px 0; color: #64748b;">
								<?php echo esc_html( $text_footer_desc ); ?>
							</p>
							<p style="margin: 0;">
								<a href="{{UNSUBSCRIBE_URL}}" style="color: #dc2626; text-decoration: underline; font-weight: 600;">
									Unsubscribe from this newsletter
								</a>
								&nbsp;&bull;&nbsp;
								<a href="<?php echo esc_url( $site_url ); ?>" target="_blank" style="color: #475569; text-decoration: underline; font-weight: 500;">
									Visit Website
								</a>
							</p>
							<p style="margin: 14px 0 0 0; color: #94a3b8; font-size: 11px;">
								<?php echo esc_html( $text_footer_copy ); ?>
							</p>
						</td>
					</tr>

				</table>
				<!-- End Main Container -->

			</td>
		</tr>
	</table>

</body>
</html>
