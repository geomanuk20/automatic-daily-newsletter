<?php
/**
 * Admin Dashboard & Settings View
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ADNL_Cron' ) && defined( 'ADNL_PLUGIN_DIR' ) && file_exists( ADNL_PLUGIN_DIR . 'includes/class-cron.php' ) ) {
	require_once ADNL_PLUGIN_DIR . 'includes/class-cron.php';
}

$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';
?>

<div class="wrap adnl-wrap">

	<?php if ( isset( $_GET['imported'] ) ) : ?>
		<div class="notice notice-success is-dismissible" style="margin: 15px 0;">
			<p>
				<strong><?php esc_html_e( 'Subscriber Import Complete:', 'auto-daily-newsletter' ); ?></strong>
				<?php
				printf(
					esc_html__( '%d subscribers imported successfully. %d duplicate(s) skipped. %d invalid row(s) ignored.', 'auto-daily-newsletter' ),
					intval( $_GET['imported'] ),
					intval( $_GET['skipped'] ?? 0 ),
					intval( $_GET['invalid'] ?? 0 )
				);
				?>
			</p>
		</div>
	<?php elseif ( isset( $_GET['import_error'] ) ) : ?>
		<div class="notice notice-error is-dismissible" style="margin: 15px 0;">
			<p>
				<strong><?php esc_html_e( 'Import Failed:', 'auto-daily-newsletter' ); ?></strong>
				<?php echo esc_html( sanitize_text_field( wp_unslash( urldecode( $_GET['import_error'] ) ) ) ); ?>
			</p>
		</div>
	<?php elseif ( isset( $_GET['logs_cleared'] ) ) : ?>
		<div class="notice notice-success is-dismissible" style="margin: 15px 0;">
			<p>
				<strong><?php esc_html_e( 'Success:', 'auto-daily-newsletter' ); ?></strong>
				<?php esc_html_e( 'All newsletter delivery logs have been cleared successfully.', 'auto-daily-newsletter' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Plugin Header Banner -->
	<div class="adnl-header">
		<div class="adnl-header-left">
			<div class="adnl-icon-badge">
				<img src="<?php echo esc_url( ADNL_PLUGIN_URL . 'assets/images/icon-128x128.png' ); ?>" alt="Auto Daily Newsletter" />
			</div>
			<div>
				<h1><?php esc_html_e( 'Auto Daily Newsletter', 'auto-daily-newsletter' ); ?></h1>
				<p class="adnl-tagline"><?php esc_html_e( 'Automated daily news post digests &bull; SMTP/API Delivery &bull; Subscriber Management', 'auto-daily-newsletter' ); ?></p>
			</div>
		</div>
		<div class="adnl-header-actions">
			<button type="button" class="button button-secondary" id="adnl-btn-preview-digest">
				<span class="dashicons dashicons-visibility"></span> <?php esc_html_e( 'Preview Today\'s Email', 'auto-daily-newsletter' ); ?>
			</button>
			<button type="button" class="button button-primary" id="adnl-btn-manual-send">
				<span class="dashicons dashicons-controls-play"></span> <?php esc_html_e( 'Send Today\'s Digest Now', 'auto-daily-newsletter' ); ?>
			</button>
		</div>
	</div>

	<!-- Navigation Tabs -->
	<nav class="nav-tab-wrapper adnl-nav-tabs">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=auto-daily-newsletter&tab=dashboard' ) ); ?>" class="nav-tab <?php echo 'dashboard' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Dashboard', 'auto-daily-newsletter' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=auto-daily-newsletter&tab=settings' ) ); ?>" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Content & Schedule', 'auto-daily-newsletter' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=auto-daily-newsletter&tab=smtp' ) ); ?>" class="nav-tab <?php echo 'smtp' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-networking"></span> <?php esc_html_e( 'SMTP & Delivery', 'auto-daily-newsletter' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=auto-daily-newsletter&tab=subscribers' ) ); ?>" class="nav-tab <?php echo 'subscribers' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-groups"></span> <?php esc_html_e( 'Subscribers', 'auto-daily-newsletter' ); ?> (<?php echo intval( $total_subscribers ); ?>)
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=auto-daily-newsletter&tab=logs' ) ); ?>" class="nav-tab <?php echo 'logs' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-list-view"></span> <?php esc_html_e( 'Delivery Logs', 'auto-daily-newsletter' ); ?>
		</a>
	</nav>

	<div class="adnl-tab-content">

		<!-- TAB 1: DASHBOARD -->
		<?php if ( 'dashboard' === $active_tab ) : ?>
			<div class="adnl-grid-stats">
				<div class="adnl-card-stat">
					<div class="stat-icon stat-icon-blue"><span class="dashicons dashicons-admin-users"></span></div>
					<div class="stat-data">
						<span class="stat-value"><?php echo intval( $total_subscribers ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Active Subscribers', 'auto-daily-newsletter' ); ?></span>
					</div>
				</div>
				<div class="adnl-card-stat">
					<div class="stat-icon stat-icon-green"><span class="dashicons dashicons-clock"></span></div>
					<div class="stat-data">
						<span class="stat-value" style="font-size: 17px;"><?php echo esc_html( $next_run_formatted ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Next Scheduled Daily Run', 'auto-daily-newsletter' ); ?></span>
					</div>
				</div>
				<div class="adnl-card-stat">
					<div class="stat-icon stat-icon-purple"><span class="dashicons dashicons-email"></span></div>
					<div class="stat-data">
						<span class="stat-value"><?php echo intval( $total_logs ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Total Digests Dispatched', 'auto-daily-newsletter' ); ?></span>
					</div>
				</div>
				<div class="adnl-card-stat">
					<div class="stat-icon stat-icon-orange"><span class="dashicons dashicons-backup"></span></div>
					<div class="stat-data">
						<span class="stat-value" style="font-size: 17px;"><?php echo esc_html( $last_run_formatted ); ?></span>
						<span class="stat-label"><?php esc_html_e( 'Last Sent', 'auto-daily-newsletter' ); ?></span>
					</div>
				</div>
			</div>

			<!-- Quick Subscriber Actions Bar on Dashboard -->
			<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 20px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
				<div style="font-weight: 600; font-size: 14px; color: #0f172a; display: flex; align-items: center; gap: 6px;">
					<span class="dashicons dashicons-groups" style="color: #2563eb;"></span>
					<span><?php esc_html_e( 'Subscriber Actions:', 'auto-daily-newsletter' ); ?></span>
				</div>
				<div style="display: flex; gap: 8px; flex-wrap: wrap;">
					<button type="button" class="button button-primary" id="adnl-btn-add-subscriber-modal-dash">
						<span class="dashicons dashicons-plus-alt" style="margin-top: 2px;"></span> <?php esc_html_e( 'Add Subscriber', 'auto-daily-newsletter' ); ?>
					</button>
					<button type="button" class="button button-secondary" id="adnl-btn-import-subscribers-modal-dash">
						<span class="dashicons dashicons-upload" style="margin-top: 2px;"></span> <?php esc_html_e( 'Import from CSV', 'auto-daily-newsletter' ); ?>
					</button>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=auto-daily-newsletter&action=adnl_export_csv' ), 'adnl_export_subscribers' ) ); ?>" class="button button-secondary">
						<span class="dashicons dashicons-download" style="margin-top: 2px;"></span> <?php esc_html_e( 'Export to CSV', 'auto-daily-newsletter' ); ?>
					</a>
				</div>
			</div>

			<!-- Quick Test Diagnostic Box -->
			<div class="adnl-box" style="margin-top: 24px;">
				<div class="adnl-box-header">
					<h2><?php esc_html_e( 'Quick Test Delivery', 'auto-daily-newsletter' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Send a test email with today\'s post digest to verify your SMTP or server delivery settings.', 'auto-daily-newsletter' ); ?></p>
				</div>
				<div class="adnl-box-body">
					<div class="adnl-inline-form">
						<input type="email" id="adnl-test-email-input" class="regular-text" placeholder="your-email@example.com" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" />
						<button type="button" class="button button-primary" id="adnl-btn-send-test">
							<span class="dashicons dashicons-email-alt"></span> <?php esc_html_e( 'Send Test Email', 'auto-daily-newsletter' ); ?>
						</button>
					</div>
					<div id="adnl-test-status" class="adnl-alert" style="display:none; margin-top: 12px;"></div>
				</div>
			</div>

			<!-- Embed Shortcode & Popup Guide -->
			<div class="adnl-box" style="margin-top: 24px;">
				<div class="adnl-box-header">
					<h2><?php esc_html_e( 'Display Options: Slide-in Popup vs Inline Form', 'auto-daily-newsletter' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Choose how you want visitors to subscribe on your website.', 'auto-daily-newsletter' ); ?></p>
				</div>
				<div class="adnl-box-body">
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
						<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px;">
							<div style="font-weight: 700; color: #0f172a; margin-bottom: 6px; font-size: 14px;">
								✨ 1. Slide-in Popup (Automatic)
							</div>
							<p class="description" style="margin: 0 0 10px 0;">
								<?php esc_html_e( 'Appears automatically on your website after a short delay. No shortcode is required! Configure position and styles under the "Content & Schedule" tab.', 'auto-daily-newsletter' ); ?>
							</p>
							<div style="font-size: 12px; color: #64748b; background: #ffffff; border: 1px dashed #cbd5e1; padding: 6px 10px; border-radius: 4px;">
								💡 <em>Test on your phone:</em> add <code>?popup=1</code> to your site URL to see it immediately.
							</div>
						</div>

						<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px;">
							<div style="font-weight: 700; color: #0f172a; margin-bottom: 6px; font-size: 14px;">
								📝 2. Embedded Form Shortcode
							</div>
							<p class="description" style="margin: 0 0 10px 0;">
								<?php esc_html_e( 'Paste this shortcode inside any post, page, or sidebar widget to display a static embedded signup box:', 'auto-daily-newsletter' ); ?>
							</p>
							<code class="adnl-code-block">[daily_newsletter_form]</code>
						</div>
					</div>
				</div>
			</div>

		<!-- TAB 2: SETTINGS (CONTENT & SCHEDULE) -->
		<?php elseif ( 'settings' === $active_tab ) : ?>
			<form method="post" action="">
				<?php wp_nonce_field( 'adnl_save_settings', 'adnl_save_settings_nonce' ); ?>
				<input type="hidden" name="active_tab" value="settings" />

				<!-- BOX 1: POST AGGREGATION & SCHEDULE -->
				<div class="adnl-box">
					<div class="adnl-box-header">
						<h2><span class="dashicons dashicons-schedule" style="margin-right: 6px;"></span><?php esc_html_e( 'Post Aggregation & Schedule Settings', 'auto-daily-newsletter' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Configure how news articles are selected and the daily schedule when your newsletter is dispatched.', 'auto-daily-newsletter' ); ?></p>
					</div>
					<div class="adnl-box-body">
						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Enable Automated Daily Digest', 'auto-daily-newsletter' ); ?></th>
								<td>
									<label class="adnl-switch">
										<input type="checkbox" name="adnl_enabled" value="1" <?php checked( get_option( 'adnl_enabled', 1 ), 1 ); ?> />
										<span class="adnl-slider"></span>
									</label>
									<p class="description"><?php esc_html_e( 'Turn daily automated newsletter dispatch on or off.', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'News Selection Mode', 'auto-daily-newsletter' ); ?></th>
								<td>
									<fieldset>
										<label style="display:block; margin-bottom: 10px;">
											<input type="radio" name="adnl_selection_mode" value="auto" <?php checked( get_option( 'adnl_selection_mode', 'auto' ), 'auto' ); ?> id="adnl-mode-auto" />
											<strong><?php esc_html_e( 'Automatic (Latest Published News)', 'auto-daily-newsletter' ); ?></strong>
											<p class="description" style="margin: 2px 0 0 24px;"><?php esc_html_e( 'Automatically aggregates the newest 5–10 published posts based on the lookback window.', 'auto-daily-newsletter' ); ?></p>
										</label>
										<label style="display:block;">
											<input type="radio" name="adnl_selection_mode" value="manual" <?php checked( get_option( 'adnl_selection_mode', 'auto' ), 'manual' ); ?> id="adnl-mode-manual" />
											<strong style="color: #2563eb;"><?php esc_html_e( 'User Selected News Only (Curated Mode)', 'auto-daily-newsletter' ); ?></strong>
											<p class="description" style="margin: 2px 0 0 24px;"><?php esc_html_e( 'Send ONLY the specific news stories you select from the list below.', 'auto-daily-newsletter' ); ?></p>
										</label>
									</fieldset>
								</td>
							</tr>
						</table>

						<!-- Curate News Posts Picker (Visible in Manual Mode) -->
						<div id="adnl-manual-selection-container" style="margin: 24px 0; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 20px; <?php echo 'manual' === get_option( 'adnl_selection_mode', 'auto' ) ? '' : 'display:none;'; ?>">
							<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 14px;">
								<div>
									<h3 style="margin:0; font-size: 15px; color: #0f172a; font-weight: 700;">
										<?php esc_html_e( 'Select News Articles for Today\'s Newsletter', 'auto-daily-newsletter' ); ?>
									</h3>
									<p class="description" style="margin: 3px 0 0 0;">
										<?php esc_html_e( 'Check the news stories you want to include. The newsletter will ONLY deliver these selected articles.', 'auto-daily-newsletter' ); ?>
									</p>
								</div>
								<div id="adnl-selected-counter" style="background: #2563eb; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
									<?php
									$selected_ids = get_option( 'adnl_selected_post_ids', array() );
									printf( esc_html__( '%d Articles Selected', 'auto-daily-newsletter' ), count( $selected_ids ) );
									?>
								</div>
							</div>

							<?php
							$recent_posts = array();
							if ( class_exists( 'ADNL_Post_Collector' ) ) {
								$post_collector = new ADNL_Post_Collector();
								if ( method_exists( $post_collector, 'get_recent_posts_for_selection' ) ) {
									$recent_posts = $post_collector->get_recent_posts_for_selection( 100 );
								}
							}
							
							// Fallback for demo mode
							if ( empty( $recent_posts ) && function_exists( 'adnl_get_mock_news_posts' ) ) {
								$recent_posts = adnl_get_mock_news_posts();
							}

							$today_count = 0;
							foreach ( $recent_posts as $p ) {
								if ( ! empty( $p['is_today'] ) ) {
									$today_count++;
								}
							}
							?>

							<!-- Quick Filter & Action Toolbar for Today's News -->
							<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom: 12px; padding: 10px 14px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px;">
								<div style="display:flex; align-items:center; gap:8px; flex-wrap: wrap;">
									<span style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e( 'View:', 'auto-daily-newsletter' ); ?></span>
									<button type="button" class="button adnl-filter-btn <?php echo ( $today_count > 0 ) ? 'button-primary active' : ''; ?>" data-filter="today" id="adnl-filter-today" style="font-weight: 600; border-radius: 16px; font-size: 12px;">
										📅 <?php printf( esc_html__( 'Today\'s News (%d)', 'auto-daily-newsletter' ), $today_count ); ?>
									</button>
									<button type="button" class="button adnl-filter-btn <?php echo ( 0 === $today_count ) ? 'button-primary active' : ''; ?>" data-filter="all" id="adnl-filter-all" style="font-weight: 600; border-radius: 16px; font-size: 12px;">
										📋 <?php printf( esc_html__( 'All News (%d)', 'auto-daily-newsletter' ), count( $recent_posts ) ); ?>
									</button>
								</div>
								<div style="display:flex; align-items:center; gap:8px; flex-wrap: wrap;">
									<input type="text" id="adnl-news-search" placeholder="<?php esc_attr_e( '🔍 Search news title...', 'auto-daily-newsletter' ); ?>" style="padding: 4px 10px; font-size: 12px; width: 200px; border-radius: 6px; border: 1px solid #cbd5e1; height: 30px;" />
									<button type="button" class="button button-primary" id="adnl-btn-select-all-today" style="font-size: 12px; font-weight: 600; height: 30px;">
										✓ <?php esc_html_e( 'Select All Today', 'auto-daily-newsletter' ); ?>
									</button>
									<button type="button" class="button" id="adnl-btn-clear-selection" style="font-size: 12px; height: 30px;">
										✕ <?php esc_html_e( 'Deselect All', 'auto-daily-newsletter' ); ?>
									</button>
								</div>
							</div>

							<div class="adnl-posts-checklist" style="max-height: 480px; overflow-y: auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
								<?php
								if ( ! empty( $recent_posts ) ) :
									foreach ( $recent_posts as $p ) :
										$is_checked = in_array( $p['id'], $selected_ids );
										$is_today   = ! empty( $p['is_today'] );
										?>
										<label style="display:flex; align-items:center; gap: 14px; padding: 12px 16px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.15s; <?php echo $is_checked ? 'background:#eff6ff;' : ''; ?>" 
										       class="adnl-post-select-row" 
										       data-is-today="<?php echo $is_today ? '1' : '0'; ?>"
										       data-title="<?php echo esc_attr( strtolower( $p['title'] ) ); ?>"
										       data-category="<?php echo esc_attr( strtolower( $p['category'] ) ); ?>">
											<input type="checkbox" name="adnl_selected_post_ids[]" value="<?php echo intval( $p['id'] ); ?>" <?php checked( $is_checked ); ?> class="adnl-post-checkbox" style="margin: 0; width: 18px; height: 18px;" />
											<?php if ( ! empty( $p['thumbnail_url'] ) ) : ?>
												<img src="<?php echo esc_url( $p['thumbnail_url'] ); ?>" style="width: 54px; height: 38px; object-fit: cover; border-radius: 4px;" alt="" />
											<?php endif; ?>
											<div style="flex: 1;">
												<div style="font-weight: 600; font-size: 14px; color: #0f172a; line-height: 1.3;">
													<?php echo esc_html( $p['title'] ); ?>
													<?php if ( $is_today ) : ?>
														<span style="background: #dcfce7; color: #166534; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 4px; margin-left: 6px; vertical-align: middle; border: 1px solid #bbf7d0;">📅 <?php esc_html_e( 'TODAY', 'auto-daily-newsletter' ); ?></span>
													<?php endif; ?>
												</div>
												<div style="font-size: 12px; color: #64748b; margin-top: 3px;">
													<span style="color: #2563eb; font-weight: 600;"><?php echo esc_html( $p['category'] ); ?></span> &bull; <?php echo esc_html( $p['date'] ); ?> &bull; <?php echo esc_html( $p['read_time'] ); ?>
												</div>
											</div>
										</label>
										<?php
									endforeach;
								else :
									?>
									<p style="padding: 20px; text-align: center; color: #64748b;">
										<?php esc_html_e( 'No published news posts found.', 'auto-daily-newsletter' ); ?>
									</p>
								<?php endif; ?>
							</div>
						</div>

						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Number of Posts (Auto Mode)', 'auto-daily-newsletter' ); ?></th>
								<td>
									<input type="number" name="adnl_posts_count" min="1" max="20" class="small-text" value="<?php echo esc_attr( get_option( 'adnl_posts_count', 7 ) ); ?>" />
									<p class="description"><?php esc_html_e( 'How many latest news articles to bundle into each daily digest (recommended: 5–10).', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Lookback Timeframe (Hours)', 'auto-daily-newsletter' ); ?></th>
								<td>
									<input type="number" name="adnl_lookback_hours" min="1" max="168" class="small-text" value="<?php echo esc_attr( get_option( 'adnl_lookback_hours', 24 ) ); ?>" />
									<p class="description"><?php esc_html_e( 'Articles published in the last X hours (default: 24 hours).', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Zero / Low Post Fallback', 'auto-daily-newsletter' ); ?></th>
								<td>
									<select name="adnl_fallback_behavior">
										<option value="latest" <?php selected( get_option( 'adnl_fallback_behavior', 'latest' ), 'latest' ); ?>>
											<?php esc_html_e( 'Send the latest available posts (recommended)', 'auto-daily-newsletter' ); ?>
										</option>
										<option value="skip" <?php selected( get_option( 'adnl_fallback_behavior', 'latest' ), 'skip' ); ?>>
											<?php esc_html_e( 'Skip sending if no new posts were published in lookback window', 'auto-daily-newsletter' ); ?>
										</option>
									</select>
									<p class="description"><?php esc_html_e( 'What to do if there are no new posts published during the lookback period.', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Daily Dispatch Time & Timezone', 'auto-daily-newsletter' ); ?></th>
								<td>
									<div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
										<input type="time" name="adnl_schedule_time" id="adnl-schedule-time-input" value="<?php echo esc_attr( get_option( 'adnl_schedule_time', '08:00' ) ); ?>" style="font-size: 16px; font-weight: 700; padding: 4px 10px;" />

										<?php
										$current_tz_choice = get_option( 'adnl_timezone', '' );
										$active_tz         = class_exists( 'ADNL_Cron' ) ? ADNL_Cron::get_timezone() : ( function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' ) );
										$active_now        = new DateTime( 'now', $active_tz );
										?>
										<select name="adnl_timezone" id="adnl-timezone-select" style="max-width: 320px;">
											<option value="" <?php selected( empty( $current_tz_choice ) ); ?>>
												<?php printf( esc_html__( 'WordPress Site Default (%s)', 'auto-daily-newsletter' ), esc_html( wp_timezone_string() ) ); ?>
											</option>
											<optgroup label="<?php esc_attr_e( 'Popular Timezones', 'auto-daily-newsletter' ); ?>">
												<option value="Asia/Kolkata" <?php selected( 'Asia/Kolkata' === $current_tz_choice ); ?>>🇮🇳 India Standard Time (Asia/Kolkata, UTC+5:30)</option>
												<option value="Asia/Dubai" <?php selected( 'Asia/Dubai' === $current_tz_choice ); ?>>🇦🇪 Gulf Standard Time (Asia/Dubai, UTC+4:00)</option>
												<option value="Asia/Dhaka" <?php selected( 'Asia/Dhaka' === $current_tz_choice ); ?>>🇧🇩 Bangladesh Time (Asia/Dhaka, UTC+6:00)</option>
												<option value="Europe/London" <?php selected( 'Europe/London' === $current_tz_choice ); ?>>🇬🇧 London / GMT / BST</option>
												<option value="UTC" <?php selected( 'UTC' === $current_tz_choice ); ?>>🌐 UTC (Coordinated Universal Time)</option>
												<option value="America/New_York" <?php selected( 'America/New_York' === $current_tz_choice ); ?>>🇺🇸 New York / Eastern Time (EST/EDT)</option>
												<option value="America/Chicago" <?php selected( 'America/Chicago' === $current_tz_choice ); ?>>🇺🇸 Chicago / Central Time</option>
												<option value="America/Los_Angeles" <?php selected( 'America/Los_Angeles' === $current_tz_choice ); ?>>🇺🇸 Los Angeles / Pacific Time (PST/PDT)</option>
												<option value="Asia/Singapore" <?php selected( 'Asia/Singapore' === $current_tz_choice ); ?>>🇸🇬 Singapore / SGT (UTC+8:00)</option>
											</optgroup>
											<optgroup label="<?php esc_attr_e( 'All Worldwide Regions', 'auto-daily-newsletter' ); ?>">
												<?php
												$all_tzs = timezone_identifiers_list();
												foreach ( $all_tzs as $tz_id ) :
													?>
													<option value="<?php echo esc_attr( $tz_id ); ?>" <?php selected( $tz_id === $current_tz_choice ); ?>><?php echo esc_html( str_replace( '_', ' ', $tz_id ) ); ?></option>
												<?php endforeach; ?>
											</optgroup>
										</select>

										<button type="button" class="button button-secondary" id="adnl-detect-tz-btn" title="Click to automatically match your device/computer timezone">
											⚡ <?php esc_html_e( 'Set to My Device Timezone', 'auto-daily-newsletter' ); ?>
										</button>
									</div>

									<div style="margin-top: 8px; font-size: 13px; color: #1e40af; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 8px 12px; display: inline-block;">
										🕒 <strong><?php esc_html_e( 'Current Time in Active Timezone:', 'auto-daily-newsletter' ); ?></strong>
										<span id="adnl-live-clock" style="font-weight: 700; color: #1e3a8a;"><?php echo esc_html( $active_now->format( 'g:i A' ) ); ?></span>
										(<span id="adnl-live-tz-name"><?php echo esc_html( $active_tz->getName() ); ?></span>)
									</div>
									<div id="adnl-tz-hint" style="display:none; margin-top: 6px; font-size: 12px; color: #166534; font-weight: 600;"></div>
									<p class="description"><?php esc_html_e( 'Your daily newsletter will dispatch at this exact hour according to the selected timezone.', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Categories Filter', 'auto-daily-newsletter' ); ?></th>
								<td>
									<?php
									$selected_cats = get_option( 'adnl_categories', array() );
									$all_cats = get_categories( array( 'hide_empty' => false ) );
									if ( ! empty( $all_cats ) ) :
										foreach ( $all_cats as $cat ) :
											?>
											<label style="display:inline-block; margin-right: 16px; margin-bottom: 8px;">
												<input type="checkbox" name="adnl_categories[]" value="<?php echo intval( $cat->term_id ); ?>" <?php checked( in_array( $cat->term_id, $selected_cats ) ); ?> />
												<?php echo esc_html( $cat->name ); ?> (<?php echo intval( $cat->count ); ?>)
											</label>
											<?php
										endforeach;
									else :
										esc_html_e( 'No categories found.', 'auto-daily-newsletter' );
									endif;
									?>
									<p class="description"><?php esc_html_e( 'Leave unchecked to include all categories.', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
						</table>
						<div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end;">
							<?php submit_button( __( 'Save Post & Schedule Settings', 'auto-daily-newsletter' ), 'primary', 'submit_schedule', false ); ?>
						</div>
					</div>
				</div>

				<!-- BOX 2: EMAIL TEMPLATE & BRANDING -->
				<div class="adnl-box" style="margin-top: 24px;">
					<div class="adnl-box-header">
						<h2><span class="dashicons dashicons-art" style="margin-right: 6px;"></span><?php esc_html_e( 'Email Template & Branding Settings', 'auto-daily-newsletter' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Customize your daily email digest design, publication logo, header title, brand accent color, and footer.', 'auto-daily-newsletter' ); ?></p>
					</div>
					<div class="adnl-box-body">
						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="adnl_email_subject"><?php esc_html_e( 'Email Subject Format', 'auto-daily-newsletter' ); ?></label>
									<span style="display: block; font-size: 11px; font-weight: normal; color: #64748b; margin-top: 4px;"><?php esc_html_e( 'Randomize or rotate daily', 'auto-daily-newsletter' ); ?></span>
								</th>
								<td>
									<textarea name="adnl_email_subject" id="adnl_email_subject" rows="4" class="large-text" style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, monospace; font-size: 13px; line-height: 1.6; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1;"><?php echo esc_textarea( get_option( 'adnl_email_subject', "[Daily Digest] Today's Top Stories - {date}" ) ); ?></textarea>
									
									<p class="description" style="margin-top: 8px; color: #475569;">
										<strong>💡 Random Subject Rotation:</strong> Enter a single subject line, or <strong>enter multiple subject lines (one per line)</strong>. The system will <strong>randomly select</strong> one on each daily newsletter delivery (works automatically on cron & manual sends).
									</p>

									<!-- Dynamic Tag Inserter Chips -->
									<div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
										<span style="font-size: 12px; font-weight: 600; color: #334155; margin-right: 4px;"><?php esc_html_e( 'Insert Tag:', 'auto-daily-newsletter' ); ?></span>
										<button type="button" class="button button-small adnl-insert-tag" data-tag="{top_story}" title="<?php esc_attr_e( 'Inserts the headline of the first/top news story', 'auto-daily-newsletter' ); ?>">🔥 {top_story}</button>
										<button type="button" class="button button-small adnl-insert-tag" data-tag="{random_story}" title="<?php esc_attr_e( 'Inserts a random news story headline from today\'s digest', 'auto-daily-newsletter' ); ?>">🎲 {random_story}</button>
										<button type="button" class="button button-small adnl-insert-tag" data-tag="{posts_count}" title="<?php esc_attr_e( 'Inserts count of news posts (e.g. 7)', 'auto-daily-newsletter' ); ?>">📰 {posts_count}</button>
										<button type="button" class="button button-small adnl-insert-tag" data-tag="{date}" title="<?php esc_attr_e( 'Inserts formatted today\'s date', 'auto-daily-newsletter' ); ?>">📅 {date}</button>
										<button type="button" class="button button-small adnl-insert-tag" data-tag="{day}" title="<?php esc_attr_e( 'Inserts day of the week (e.g. Friday)', 'auto-daily-newsletter' ); ?>">☀️ {day}</button>
										<button type="button" class="button button-small adnl-insert-tag" data-tag="{site_name}" title="<?php esc_attr_e( 'Inserts site/publication name', 'auto-daily-newsletter' ); ?>">🏷️ {site_name}</button>
										<button type="button" class="button button-small" id="adnl-load-subject-presets" style="margin-left: auto; font-weight: 600; color: #2563eb; border-color: #93c5fd; background: #eff6ff;">
											✨ <?php esc_html_e( 'Load 7-News Random Presets', 'auto-daily-newsletter' ); ?>
										</button>
									</div>

									<!-- Interactive Live Subject Preview Box -->
									<div id="adnl-subject-live-preview-box" style="margin-top: 12px; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #2563eb; border-radius: 6px;">
										<div style="display: flex; align-items: center; justify-content: space-between;">
											<span style="font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; font-size: 11px;">
												<?php esc_html_e( 'Live Subject Preview (Random Simulation)', 'auto-daily-newsletter' ); ?>:
											</span>
											<button type="button" id="adnl-shuffle-preview-btn" class="button button-small" style="font-size: 11px; height: 24px; line-height: 22px;">
												🎲 <?php esc_html_e( 'Shuffle / Pick Another Random', 'auto-daily-newsletter' ); ?>
											</button>
										</div>
										<div id="adnl-subject-preview-text" style="font-size: 14px; font-weight: 600; color: #0f172a; margin-top: 6px; word-break: break-word;">
											<?php
											$sample_posts = function_exists( 'adnl_get_mock_news_posts' ) ? adnl_get_mock_news_posts() : array();
											if ( empty( $sample_posts ) && class_exists( 'ADNL_Post_Collector' ) ) {
												$pc = new ADNL_Post_Collector();
												$sample_posts = $pc->get_latest_news_posts();
											}
											echo esc_html( ADNL_Template_Builder::generate_subject( get_option( 'adnl_email_subject' ), $sample_posts ) );
											?>
										</div>
									</div>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Preheader (Inbox Preview)', 'auto-daily-newsletter' ); ?></th>
								<td>
									<input type="text" name="adnl_preheader_text" class="large-text" value="<?php echo esc_attr( get_option( 'adnl_preheader_text', "Here are today's top stories and news updates." ) ); ?>" />
									<p class="description"><?php esc_html_e( 'Short summary displayed next to the subject line in subscriber email clients.', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Newsletter Header Title', 'auto-daily-newsletter' ); ?></th>
								<td>
									<input type="text" name="adnl_header_title" class="large-text" value="<?php echo esc_attr( get_option( 'adnl_header_title', get_bloginfo( 'name' ) . ' Newsletter' ) ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) . ' Newsletter' ); ?>" />
									<p class="description"><?php esc_html_e( 'The prominent headline text shown below the logo in the newsletter header (e.g. "Daily Morning Briefing", "{site_name} Newsletter"). Supports tag: {site_name}', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Newsletter Logo', 'auto-daily-newsletter' ); ?></th>
								<td>
									<?php 
									$site_logo   = get_option( 'adnl_site_logo', '' ); 
									$logo_height = intval( get_option( 'adnl_logo_height', 70 ) );
									?>
									<div id="adnl-logo-preview-wrap" style="margin-bottom: 12px; <?php echo empty( $site_logo ) ? 'display:none;' : ''; ?>">
										<img id="adnl-logo-preview-img" src="<?php echo esc_url( $site_logo ); ?>" style="max-height: <?php echo esc_attr( $logo_height ); ?>px; max-width: 380px; width: auto; height: auto; border: 1px solid #cbd5e1; padding: 8px; border-radius: 6px; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.06);" />
									</div>
									<div style="display:flex; align-items:center; gap: 10px; flex-wrap: wrap;">
										<input type="text" name="adnl_site_logo" id="adnl-site-logo-input" class="regular-text" placeholder="https://example.com/logo.png" value="<?php echo esc_attr( $site_logo ); ?>" />
										
										<!-- Native file input for direct computer file upload -->
										<input type="file" id="adnl-logo-file-picker" accept="image/*" style="display:none;" />

										<!-- Direct upload from computer -->
										<button type="button" class="button button-primary" id="adnl-direct-upload-logo-btn" style="display:inline-flex; align-items:center; gap:5px; font-weight:600;">
											<span class="dashicons dashicons-upload" style="margin-top:2px;"></span> <?php esc_html_e( 'Upload from Computer', 'auto-daily-newsletter' ); ?>
										</button>

										<!-- Media library -->
										<button type="button" class="button" id="adnl-upload-logo-btn">
											<span class="dashicons dashicons-admin-media" style="margin-top:2px;"></span> <?php esc_html_e( 'Choose from Media Library', 'auto-daily-newsletter' ); ?>
										</button>
										<button type="button" class="button button-link-delete" id="adnl-remove-logo-btn" style="<?php echo empty( $site_logo ) ? 'display:none;' : ''; ?>">
											<?php esc_html_e( 'Remove', 'auto-daily-newsletter' ); ?>
										</button>
									</div>
									<p class="description"><?php esc_html_e( 'Upload your publication logo (PNG, SVG, JPG) directly from your computer or select from Media Library. If empty, your site title will be displayed in the email header.', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Primary Brand Color', 'auto-daily-newsletter' ); ?></th>
								<td>
									<input type="color" name="adnl_primary_color" value="<?php echo esc_attr( get_option( 'adnl_primary_color', '#e11d48' ) ); ?>" />
									<p class="description"><?php esc_html_e( 'Accent color used for header stripe, category badges, and buttons.', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Footer Description Text', 'auto-daily-newsletter' ); ?></th>
								<td>
									<textarea name="adnl_footer_text" rows="3" class="large-text"><?php echo esc_textarea( get_option( 'adnl_footer_text', 'You received this email because you subscribed to daily news updates on {site_name}.' ) ); ?></textarea>
									<p class="description"><?php esc_html_e( 'Text explaining why the subscriber received this email or company address. Supports tag: {site_name}', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Footer Copyright Notice', 'auto-daily-newsletter' ); ?></th>
								<td>
									<input type="text" name="adnl_footer_copyright" class="large-text" value="<?php echo esc_attr( get_option( 'adnl_footer_copyright', '© {year} {site_name}. All rights reserved.' ) ); ?>" />
									<p class="description"><?php esc_html_e( 'Copyright notice at the bottom. Supports tags: {year}, {site_name}', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Footer Background Color', 'auto-daily-newsletter' ); ?></th>
								<td>
									<input type="color" name="adnl_footer_bg_color" value="<?php echo esc_attr( get_option( 'adnl_footer_bg_color', '#f8fafc' ) ); ?>" />
									<p class="description"><?php esc_html_e( 'Background color for the footer section (e.g. #f8fafc light gray, or #ffffff for pure white).', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Primary Inbox Delivery Tip', 'auto-daily-newsletter' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="adnl_show_primary_tip" value="1" <?php checked( get_option( 'adnl_show_primary_tip', 1 ), 1 ); ?> />
										<strong><?php esc_html_e( 'Show "Drag to Primary Tab" delivery tip in email footer', 'auto-daily-newsletter' ); ?></strong>
									</label>
									<p class="description"><?php esc_html_e( 'Helps readers move your newsletter to Gmail\'s Primary tab or whitelist your address, training inbox filters permanently.', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
						</table>
						<div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end;">
							<?php submit_button( __( 'Save Email Branding Settings', 'auto-daily-newsletter' ), 'primary', 'submit_branding', false ); ?>
						</div>
					</div>
				</div>

				<!-- BOX 3: SLIDE-IN POPUP CARD -->
				<div class="adnl-box" style="margin-top: 24px;">
					<div class="adnl-box-header">
						<h2><span class="dashicons dashicons-testimonial" style="margin-right: 6px;"></span><?php esc_html_e( 'Slide-in Newsletter Popup Card Settings', 'auto-daily-newsletter' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Configure the floating slide-in popup widget shown to visitors on your public website.', 'auto-daily-newsletter' ); ?></p>
					</div>
					<div class="adnl-box-body">
						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Slide-in Popup Activation', 'auto-daily-newsletter' ); ?></th>
								<td>
									<div style="display:flex; align-items:center; gap: 14px; flex-wrap: wrap;">
										<label class="adnl-switch">
											<input type="checkbox" name="adnl_popup_enabled" value="1" <?php checked( get_option( 'adnl_popup_enabled', 1 ), 1 ); ?> />
											<span class="adnl-slider"></span>
										</label>
										<button type="button" class="button button-secondary" id="adnl-btn-preview-popup" style="display:inline-flex; align-items:center; gap:6px; font-weight: 600;">
											<span class="dashicons dashicons-visibility" style="margin-top:2px;"></span>
											<?php esc_html_e( 'Preview Popup on Screen', 'auto-daily-newsletter' ); ?>
										</button>
									</div>
									<p class="description"><?php esc_html_e( 'Display a stylish bottom-left corner slide-in card prompting website visitors to subscribe.', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Show Company Logo', 'auto-daily-newsletter' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="adnl_popup_show_logo" id="adnl-popup-show-logo-input" value="1" <?php checked( get_option( 'adnl_popup_show_logo', 1 ), 1 ); ?> />
										<?php esc_html_e( 'Display your publication / company logo at the top of the popup card', 'auto-daily-newsletter' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Frontend Popup Logo Size', 'auto-daily-newsletter' ); ?></th>
								<td>
									<?php $popup_logo_h = intval( get_option( 'adnl_popup_logo_height', 55 ) ); ?>
									<div style="display:flex; align-items:center; gap: 14px; flex-wrap: wrap;">
										<input type="range" name="adnl_popup_logo_height" id="adnl-popup-logo-height-slider" min="25" max="130" value="<?php echo esc_attr( $popup_logo_h ); ?>" style="width: 220px;" />
										<span id="adnl-popup-logo-height-val" style="font-weight: 700; color: #0f172a; min-width: 50px; font-size: 14px;"><?php echo esc_html( $popup_logo_h ); ?>px</span>
										<span class="description"><?php esc_html_e( 'Adjust the logo size for the frontend website popup card independently.', 'auto-daily-newsletter' ); ?></span>
									</div>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Popup Headline', 'auto-daily-newsletter' ); ?></th>
								<td>
									<input type="text" name="adnl_popup_title" class="regular-text" value="<?php echo esc_attr( get_option( 'adnl_popup_title', 'HI THERE!' ) ); ?>" />
									<p class="description"><?php esc_html_e( 'Greeting or headline displayed on the popup card (e.g. "HI THERE!", "STAY UPDATED!").', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Popup Message', 'auto-daily-newsletter' ); ?></th>
								<td>
									<textarea name="adnl_popup_message" rows="2" class="large-text"><?php echo esc_textarea( get_option( 'adnl_popup_message', 'Subscribe to our newsletter for daily news & updates delivered straight to your inbox.' ) ); ?></textarea>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Popup Button Text', 'auto-daily-newsletter' ); ?></th>
								<td>
									<input type="text" name="adnl_popup_button" class="regular-text" style="max-width: 140px;" value="<?php echo esc_attr( get_option( 'adnl_popup_button', 'SUBMIT' ) ); ?>" />
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Popup Button Color', 'auto-daily-newsletter' ); ?></th>
								<td>
									<input type="color" name="adnl_popup_btn_color" id="adnl-popup-btn-color-input" value="<?php echo esc_attr( get_option( 'adnl_popup_btn_color', '#f43f5e' ) ); ?>" />
									<p class="description"><?php esc_html_e( 'Background color for the submit button and close icon.', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Email Input Placeholder', 'auto-daily-newsletter' ); ?></th>
								<td>
									<input type="text" name="adnl_popup_placeholder" class="regular-text" style="max-width: 200px;" value="<?php echo esc_attr( get_option( 'adnl_popup_placeholder', 'Email' ) ); ?>" />
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Popup Position on Screen', 'auto-daily-newsletter' ); ?></th>
								<td>
									<?php $current_pos = get_option( 'adnl_popup_position', 'bottom-left' ); ?>
									<select name="adnl_popup_position" id="adnl-popup-position-select" style="min-width: 260px; font-weight: 600;">
										<option value="bottom-left" <?php selected( $current_pos, 'bottom-left' ); ?>><?php esc_html_e( '↙️ Bottom-Left Corner (Slide-in)', 'auto-daily-newsletter' ); ?></option>
										<option value="bottom-right" <?php selected( $current_pos, 'bottom-right' ); ?>><?php esc_html_e( '↘️ Bottom-Right Corner (Slide-in)', 'auto-daily-newsletter' ); ?></option>
										<option value="top-left" <?php selected( $current_pos, 'top-left' ); ?>><?php esc_html_e( '↖️ Top-Left Corner (Slide-down)', 'auto-daily-newsletter' ); ?></option>
										<option value="top-right" <?php selected( $current_pos, 'top-right' ); ?>><?php esc_html_e( '↗️ Top-Right Corner (Slide-down)', 'auto-daily-newsletter' ); ?></option>
										<option value="center" <?php selected( $current_pos, 'center' ); ?>><?php esc_html_e( '🎯 Center Screen (Modal Pop-up)', 'auto-daily-newsletter' ); ?></option>
									</select>
									<p class="description"><?php esc_html_e( 'Select where the popup newsletter card should appear on your visitor\'s screen.', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Popup Delay (Seconds)', 'auto-daily-newsletter' ); ?></th>
								<td>
									<input type="number" name="adnl_popup_delay" min="0" max="60" class="small-text" value="<?php echo intval( get_option( 'adnl_popup_delay', 3 ) ); ?>" />
									<span class="description"><?php esc_html_e( 'Number of seconds after page load before the popup appears.', 'auto-daily-newsletter' ); ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Popup Re-appearance Time', 'auto-daily-newsletter' ); ?></th>
								<td>
									<?php $popup_freq = intval( get_option( 'adnl_popup_frequency', 30 ) ); ?>
									<select name="adnl_popup_frequency" id="adnl-popup-frequency-select" style="min-width: 280px; font-weight: 600;">
										<option value="0" <?php selected( $popup_freq, 0 ); ?>><?php esc_html_e( '⚡ Every Page Load (No Wait / Always Show)', 'auto-daily-newsletter' ); ?></option>
										<option value="10" <?php selected( $popup_freq, 10 ); ?>><?php esc_html_e( '⏱️ Every 10 Minutes', 'auto-daily-newsletter' ); ?></option>
										<option value="30" <?php selected( $popup_freq, 30 ); ?>><?php esc_html_e( '⏱️ Every Half Hour (30 Minutes - Recommended)', 'auto-daily-newsletter' ); ?></option>
										<option value="60" <?php selected( $popup_freq, 60 ); ?>><?php esc_html_e( '⏱️ Every One Hour (60 Minutes)', 'auto-daily-newsletter' ); ?></option>
										<option value="120" <?php selected( $popup_freq, 120 ); ?>><?php esc_html_e( '⏱️ Every 2 Hours', 'auto-daily-newsletter' ); ?></option>
										<option value="360" <?php selected( $popup_freq, 360 ); ?>><?php esc_html_e( '⏱️ Every 6 Hours', 'auto-daily-newsletter' ); ?></option>
										<option value="720" <?php selected( $popup_freq, 720 ); ?>><?php esc_html_e( '⏱️ Every 12 Hours', 'auto-daily-newsletter' ); ?></option>
										<option value="1440" <?php selected( $popup_freq, 1440 ); ?>><?php esc_html_e( '⏱️ Once a Day (24 Hours)', 'auto-daily-newsletter' ); ?></option>
										<option value="10080" <?php selected( $popup_freq, 10080 ); ?>><?php esc_html_e( '⏱️ Once a Week (7 Days)', 'auto-daily-newsletter' ); ?></option>
									</select>
									<p class="description"><?php esc_html_e( 'How often the popup reappears to a visitor after they close it without subscribing (e.g. Half Hour, One Hour, etc.).', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
						</table>

						<!-- Live In-Admin Popup Card Preview with Centered Company Logo -->
						<div style="margin: 28px 0 20px 0; padding: 24px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
							<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
								<div>
									<h3 style="margin: 0; font-size: 15px; color: #0f172a; font-weight: 700;">
										<?php esc_html_e( 'Live Popup Preview (Centered Logo Card)', 'auto-daily-newsletter' ); ?>
									</h3>
									<p class="description" style="margin: 2px 0 0 0;">
										<?php esc_html_e( 'Clean, focused newsletter subscription card with your publication logo centered at the top.', 'auto-daily-newsletter' ); ?>
									</p>
								</div>
								<button type="button" class="button button-secondary" id="adnl-btn-preview-popup-again" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
									<span class="dashicons dashicons-visibility" style="margin-top: 2px;"></span>
									<span id="adnl-btn-preview-label"><?php printf( esc_html__( 'Trigger %s Preview', 'auto-daily-newsletter' ), esc_html( ucwords( str_replace( '-', ' ', $current_pos ) ) ) ); ?></span>
								</button>
							</div>

							<!-- Rendered Centered Static Popup Card -->
							<?php
							$p_btn_color       = get_option( 'adnl_popup_btn_color', '#f43f5e' );
							$p_placeholder     = get_option( 'adnl_popup_placeholder', 'Email' );
							$p_show_logo       = get_option( 'adnl_popup_show_logo', 1 );
							$raw_admin_msg     = get_option( 'adnl_popup_message', 'Subscribe to our newsletter for daily news & updates delivered straight to your inbox.' );
							$clean_popup_msg   = html_entity_decode( $raw_admin_msg, ENT_QUOTES, 'UTF-8' );
							$popup_logo_h      = intval( get_option( 'adnl_popup_logo_height', 55 ) );
							?>
							<div class="adnl-popup-card" style="max-width: 410px; margin: 0 auto; box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.22), 0 0 0 1px rgba(15, 23, 42, 0.08); border-radius: 12px; overflow: hidden; background: #ffffff; width: 100%;">
								<div class="adnl-popup-body" style="padding: 34px 28px 28px 28px; text-align: center;">
									<!-- Centered Company Logo -->
									<div class="adnl-popup-logo" id="adnl-live-preview-logo-wrap" style="margin: 0 auto 16px auto; display: flex; justify-content: center; align-items: center; text-align: center; <?php echo $p_show_logo ? '' : 'display:none;'; ?>">
										<?php if ( ! empty( $site_logo ) ) : ?>
											<img id="adnl-live-preview-logo-img" src="<?php echo esc_url( $site_logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" height="<?php echo esc_attr( $popup_logo_h ); ?>" style="height: <?php echo esc_attr( $popup_logo_h ); ?>px; max-height: <?php echo esc_attr( $popup_logo_h ); ?>px; max-width: 280px; width: auto; display: block; margin: 0 auto;" />
										<?php else : ?>
											<?php 
											$adm_site_name = get_bloginfo( 'name' );
											$adm_char      = function_exists( 'mb_substr' ) ? mb_substr( $adm_site_name, 0, 1 ) : substr( $adm_site_name, 0, 1 );
											$adm_initial   = ! empty( $adm_site_name ) ? strtoupper( $adm_char ) : 'N'; 
											?>
											<div style="display: inline-flex; align-items: center; gap: 8px; margin: 0 auto;">
												<span style="background: #e11d48; color: #ffffff; font-size: 14px; font-weight: 900; padding: 4px 7px; border-radius: 3px; font-family: sans-serif; line-height: 1;"><?php echo esc_html( $adm_initial ); ?></span>
												<span style="font-size: 20px; font-weight: 800; color: #004b87; font-family: sans-serif; letter-spacing: -0.5px; line-height: 1;">
													<?php echo esc_html( $adm_site_name ); ?>
												</span>
											</div>
										<?php endif; ?>
									</div>

									<h3 class="adnl-popup-title" id="adnl-live-preview-title" style="text-align: center; font-size: 24px; font-weight: 900; letter-spacing: 0.5px; margin: 0 0 8px 0; color: #0f172a;">
										<?php echo esc_html( get_option( 'adnl_popup_title', 'HI THERE!' ) ); ?>
									</h3>
									<p class="adnl-popup-desc" id="adnl-live-preview-desc" style="text-align: center; font-size: 14px; line-height: 1.5; color: #475569; margin: 0 auto 18px auto; max-width: 320px;">
										<?php echo esc_html( $clean_popup_msg ); ?>
									</p>
									<div class="adnl-subscribe-form adnl-popup-form" id="adnl-live-preview-form" style="width: 100%;">
										<div class="adnl-popup-input-wrap" style="margin-bottom: 12px; width: 100%;">
											<input type="email" id="adnl-live-preview-input" placeholder="<?php echo esc_attr( $p_placeholder ); ?>" class="adnl-popup-input" style="background:#ffffff; color:#0f172a; text-align: left; width: 100%; padding: 12px 16px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 14px;" />
										</div>
										<button type="button" class="adnl-submit-btn adnl-popup-submit" id="adnl-live-preview-btn" style="cursor: pointer; width: 100%; padding: 12px 20px; font-size: 14px; font-weight: 700; letter-spacing: 1px; border-radius: 6px; background-color: <?php echo esc_attr( $p_btn_color ); ?>;">
											<span id="adnl-live-preview-btn-text"><?php echo esc_html( get_option( 'adnl_popup_button', 'SUBSCRIBE' ) ); ?></span>
										</button>
										<div id="adnl-live-preview-msg" style="display:none; margin-top: 10px; text-align: center; font-size: 13px; font-weight: 600; color: #16a34a;"></div>
									</div>
								</div>
							</div>
						<div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end;">
							<?php submit_button( __( 'Save Popup Card Settings', 'auto-daily-newsletter' ), 'primary', 'submit_popup', false ); ?>
						</div>
					</div>
				</div>
			</form>

		<!-- TAB 3: SMTP & DELIVERY -->
		<?php elseif ( 'smtp' === $active_tab ) : ?>
			<form method="post" action="">
				<?php wp_nonce_field( 'adnl_save_settings', 'adnl_save_settings_nonce' ); ?>
				<input type="hidden" name="active_tab" value="smtp" />

				<div class="adnl-box">
					<div class="adnl-box-header">
						<h2><?php esc_html_e( 'Email Sender & SMTP Configuration', 'auto-daily-newsletter' ); ?></h2>
					</div>
					<div class="adnl-box-body">

						<!-- Deliverability & Primary Inbox Diagnostic Guide -->
						<div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 18px 22px; margin-bottom: 20px;">
							<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
								<h3 style="margin: 0; color: #166534; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
									<span class="dashicons dashicons-shield" style="color: #16a34a; font-size: 20px;"></span>
									<?php esc_html_e( 'How to Fix: Emails Going to Spam or Promotions Tab', 'auto-daily-newsletter' ); ?>
								</h3>
								<span style="font-size: 11px; background: #dcfce7; color: #15803d; padding: 3px 10px; border-radius: 9999px; font-weight: 700; border: 1px solid #bbf7d0;">
									RFC 8058 & 5322 Compliant
								</span>
							</div>

							<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px; font-size: 13px; line-height: 1.5; color: #1e293b;">
								<div style="background: #ffffff; padding: 14px; border-radius: 6px; border: 1px solid #bbf7d0;">
									<strong style="color: #166534; display: block; margin-bottom: 6px; font-size: 13px;">📥 1. Landing in Gmail "Primary" Tab (vs Promotions):</strong>
									<ul style="margin: 0; padding-left: 18px; color: #334155; font-size: 12px;">
										<li style="margin-bottom: 5px;"><strong>Drag to Primary Once:</strong> Ask subscribers to drag your email from "Promotions" to the "Primary" tab once. When Gmail asks <em>"Do this for future messages?"</em>, click <strong>Yes</strong>.</li>
										<li style="margin-bottom: 5px;"><strong>Add to Contacts:</strong> When readers add your sender address to their address book or Google Contacts, Gmail permanently delivers all issues to Primary.</li>
										<li style="margin-bottom: 5px;"><strong>Primary Whitelist Tip:</strong> We automatically add a subtle "Drag to Primary" tip in your email footer (toggle under <em>Branding</em> tab).</li>
										<li><strong>Clean Subjects:</strong> Avoid excessive capitalization (e.g. "HUGE SALE"), dollar signs, or brackets in subject lines.</li>
									</ul>
								</div>

								<div style="background: #ffffff; padding: 14px; border-radius: 6px; border: 1px solid #bbf7d0;">
									<strong style="color: #166534; display: block; margin-bottom: 6px; font-size: 13px;">🛡️ 2. Preventing Emails Landing in Spam (DNS Setup):</strong>
									<ul style="margin: 0; padding-left: 18px; color: #334155; font-size: 12px;">
										<li style="margin-bottom: 5px;"><strong>SPF Record (TXT):</strong> Add an SPF TXT record on your domain DNS: <code>v=spf1 include:_spf.google.com ~all</code> (for Gmail) or your hosting provider's SPF.</li>
										<li style="margin-bottom: 5px;"><strong>DKIM Key:</strong> Enable DKIM in cPanel / Cloudflare / Hostinger to cryptographically sign outgoing messages.</li>
										<li style="margin-bottom: 5px;"><strong>DMARC Record (TXT):</strong> Add TXT record for <code>_dmarc.yourdomain.com</code> with value <code>v=DMARC1; p=none;</code>.</li>
										<li><strong>Domain Alignment:</strong> Ensure your "From Email" domain matches your SMTP provider domain to pass DMARC checks.</li>
									</ul>
								</div>
							</div>
						</div>

						<!-- SMTP Helper Guide Banner -->
						<div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px;">
							<h3 style="margin: 0 0 8px 0; color: #1e40af; font-size: 14px; font-weight: 700;">
								📧 How to Send Real Emails to Gmail / Subscribers:
							</h3>
							<p style="margin: 0 0 10px 0; color: #1e3a8a; font-size: 13px;">
								To deliver newsletters to real email addresses (like Gmail, Yahoo, Outlook), configure your SMTP details below:
							</p>
							<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px; font-size: 12px; color: #334155;">
								<div style="background: #ffffff; padding: 12px; border-radius: 6px; border: 1px solid #dbeafe;">
									<strong>Google / Gmail SMTP:</strong><br/>
									&bull; Host: <code>smtp.gmail.com</code> | Port: <code>587</code> (TLS)<br/>
									&bull; Username: Your full Gmail address<br/>
									&bull; Password: 16-character <strong>Google App Password</strong> (generate from <em>Google Account &rarr; Security &rarr; 2-Step Verification &rarr; App Passwords</em>).
								</div>
								<div style="background: #ffffff; padding: 12px; border-radius: 6px; border: 1px solid #dbeafe;">
									<strong>Brevo / SendGrid / Amazon SES:</strong><br/>
									&bull; Host: <code>smtp-relay.brevo.com</code> (or provider host)<br/>
									&bull; Port: <code>587</code> (TLS)<br/>
									&bull; Username & Password: From your provider API/SMTP key.
								</div>
							</div>
						</div>

						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Mailer Engine', 'auto-daily-newsletter' ); ?></th>
								<td>
									<fieldset>
										<label style="display: block; margin-bottom: 12px; padding: 12px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;">
											<input type="radio" name="adnl_mailer_type" value="smtp" <?php checked( get_option( 'adnl_mailer_type', 'smtp' ), 'smtp' ); ?> />
											<strong style="color: #15803d; font-size: 14px;"><?php esc_html_e( 'Dedicated Custom SMTP Server (Default & Recommended for 100% Deliverability)', 'auto-daily-newsletter' ); ?></strong>
											<p class="description" style="margin: 4px 0 0 22px; color: #166534;"><?php esc_html_e( 'Directly route newsletters through Gmail, Brevo, SendGrid, Amazon SES, Mailgun, or your hosting SMTP.', 'auto-daily-newsletter' ); ?></p>
										</label>
										<label style="display: block; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
											<input type="radio" name="adnl_mailer_type" value="wp_mail" <?php checked( get_option( 'adnl_mailer_type', 'smtp' ), 'wp_mail' ); ?> />
											<strong style="color: #475569;"><?php esc_html_e( 'Default WordPress Mailer (wp_mail)', 'auto-daily-newsletter' ); ?></strong>
											<p class="description" style="margin: 4px 0 0 22px;"><?php esc_html_e( 'Uses your web server PHP mail() or existing generic WordPress SMTP plugin.', 'auto-daily-newsletter' ); ?></p>
										</label>
									</fieldset>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'From Name', 'auto-daily-newsletter' ); ?></th>
								<td>
									<input type="text" name="adnl_from_name" class="regular-text" value="<?php echo esc_attr( get_option( 'adnl_from_name', get_bloginfo( 'name' ) ) ); ?>" />
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'From Email Address', 'auto-daily-newsletter' ); ?></th>
								<td>
									<input type="email" name="adnl_from_email" class="regular-text" value="<?php echo esc_attr( get_option( 'adnl_from_email', get_bloginfo( 'admin_email' ) ) ); ?>" />
									<p class="description"><?php esc_html_e( 'Emails will be sent from and replies will be routed to this address.', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
						</table>

						<div id="adnl-smtp-credentials-section" style="<?php echo 'smtp' === get_option( 'adnl_mailer_type', 'smtp' ) ? '' : 'display:none;'; ?>">
							<div style="border-top: 1px solid #e2e8f0; padding-top: 20px; margin-top: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
								<h3 style="margin: 0;"><?php esc_html_e( 'Custom SMTP Credentials', 'auto-daily-newsletter' ); ?></h3>
								<div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
									<span style="font-size: 12px; font-weight: 600; color: #475569;"><?php esc_html_e( '1-Click Presets:', 'auto-daily-newsletter' ); ?></span>
									<button type="button" class="button button-small adnl-smtp-preset" onclick="adnlApplyPreset('gmail')" data-host="smtp.gmail.com" data-port="465" data-enc="ssl">📧 Gmail</button>
									<button type="button" class="button button-small adnl-smtp-preset" onclick="adnlApplyPreset('brevo')" data-host="smtp-relay.brevo.com" data-port="587" data-enc="tls">🚀 Brevo</button>
									<button type="button" class="button button-small adnl-smtp-preset" onclick="adnlApplyPreset('sendgrid')" data-host="smtp.sendgrid.net" data-port="587" data-enc="tls">⚡ SendGrid</button>
									<button type="button" class="button button-small adnl-smtp-preset" onclick="adnlApplyPreset('mailgun')" data-host="smtp.mailgun.org" data-port="587" data-enc="tls">📨 Mailgun</button>
									<button type="button" class="button button-small adnl-smtp-preset" onclick="adnlApplyPreset('aws')" data-host="email-smtp.us-east-1.amazonaws.com" data-port="587" data-enc="tls">☁️ AWS SES</button>
								</div>
							</div>
							<div id="adnl-preset-hint" style="display:none; margin: 12px 0; padding: 12px 16px; border-radius: 6px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a; font-size: 13px; line-height: 1.5;"></div>
							<table class="form-table">
								<tr>
									<th scope="row"><?php esc_html_e( 'SMTP Host', 'auto-daily-newsletter' ); ?></th>
									<td>
										<input type="text" name="adnl_smtp_host" class="regular-text" placeholder="smtp.mailprovider.com" value="<?php echo esc_attr( get_option( 'adnl_smtp_host', '' ) ); ?>" />
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'SMTP Port', 'auto-daily-newsletter' ); ?></th>
									<td>
										<input type="number" name="adnl_smtp_port" class="small-text" value="<?php echo esc_attr( get_option( 'adnl_smtp_port', 587 ) ); ?>" />
										<p class="description"><?php esc_html_e( 'Standard ports: 587 (TLS), 465 (SSL), 25', 'auto-daily-newsletter' ); ?></p>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Encryption', 'auto-daily-newsletter' ); ?></th>
									<td>
										<select name="adnl_smtp_encryption">
											<option value="tls" <?php selected( get_option( 'adnl_smtp_encryption', 'tls' ), 'tls' ); ?>>TLS (Recommended)</option>
											<option value="ssl" <?php selected( get_option( 'adnl_smtp_encryption', 'tls' ), 'ssl' ); ?>>SSL</option>
											<option value="none" <?php selected( get_option( 'adnl_smtp_encryption', 'tls' ), 'none' ); ?>>None</option>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'SMTP Authentication', 'auto-daily-newsletter' ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="adnl_smtp_auth" value="1" <?php checked( get_option( 'adnl_smtp_auth', 1 ), 1 ); ?> />
											<?php esc_html_e( 'Yes, requires username and password', 'auto-daily-newsletter' ); ?>
										</label>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'SMTP Username', 'auto-daily-newsletter' ); ?></th>
									<td>
										<input type="text" name="adnl_smtp_user" class="regular-text" autocomplete="off" value="<?php echo esc_attr( get_option( 'adnl_smtp_user', '' ) ); ?>" />
									</td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'SMTP Password / API Key', 'auto-daily-newsletter' ); ?></th>
									<td>
										<input type="password" name="adnl_smtp_pass" class="regular-text" autocomplete="new-password" placeholder="<?php echo ! empty( get_option( 'adnl_smtp_pass' ) ) ? '••••••••••••••••' : ''; ?>" />
										<p class="description"><?php esc_html_e( 'Leave blank to preserve existing password.', 'auto-daily-newsletter' ); ?></p>
									</td>
								</tr>
							</table>
						</div>

						<h3 style="border-top: 1px solid #e2e8f0; padding-top: 20px;"><?php esc_html_e( 'Batching & Performance', 'auto-daily-newsletter' ); ?></h3>
						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Batch Size', 'auto-daily-newsletter' ); ?></th>
								<td>
									<input type="number" name="adnl_batch_size" min="1" max="500" class="small-text" value="<?php echo esc_attr( get_option( 'adnl_batch_size', 30 ) ); ?>" />
									<p class="description"><?php esc_html_e( 'Number of emails to send per batch to avoid server script timeouts.', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Batch Delay (seconds)', 'auto-daily-newsletter' ); ?></th>
								<td>
									<input type="number" name="adnl_batch_delay" min="0" max="10" class="small-text" value="<?php echo esc_attr( get_option( 'adnl_batch_delay', 1 ) ); ?>" />
									<p class="description"><?php esc_html_e( 'Pause between batches to avoid tripping provider rate limits.', 'auto-daily-newsletter' ); ?></p>
								</td>
							</tr>
						</table>

						<?php submit_button( __( 'Save Mailer Settings', 'auto-daily-newsletter' ), 'primary', 'submit_smtp' ); ?>
					</div>
				</div>
			</form>

		<!-- TAB 4: SUBSCRIBERS -->
		<?php elseif ( 'subscribers' === $active_tab ) : ?>
			<div class="adnl-box">
				<div class="adnl-box-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 10px;">
					<div>
						<h2><?php esc_html_e( 'Subscriber Management', 'auto-daily-newsletter' ); ?></h2>
						<p class="description"><?php esc_html_e( 'View, add, delete, import, and export subscribers.', 'auto-daily-newsletter' ); ?></p>
					</div>
					<div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
						<button type="button" class="button button-primary" id="adnl-btn-add-subscriber-modal">
							<span class="dashicons dashicons-plus-alt" style="margin-top: 2px;"></span> <?php esc_html_e( 'Add Subscriber', 'auto-daily-newsletter' ); ?>
						</button>
						<button type="button" class="button button-secondary" id="adnl-btn-import-subscribers-modal">
							<span class="dashicons dashicons-upload" style="margin-top: 2px;"></span> <?php esc_html_e( 'Import from CSV', 'auto-daily-newsletter' ); ?>
						</button>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=auto-daily-newsletter&action=adnl_export_csv' ), 'adnl_export_subscribers' ) ); ?>" class="button button-secondary">
							<span class="dashicons dashicons-download" style="margin-top: 2px;"></span> <?php esc_html_e( 'Export to CSV', 'auto-daily-newsletter' ); ?>
						</a>
					</div>
				</div>
				<div class="adnl-box-body">
					<?php
					$subscribers = $wpdb->get_results( "SELECT * FROM {$subscribers_table} ORDER BY id DESC LIMIT 200" );
					if ( ! empty( $subscribers ) ) :
						?>
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'ID', 'auto-daily-newsletter' ); ?></th>
									<th><?php esc_html_e( 'Email', 'auto-daily-newsletter' ); ?></th>
									<th><?php esc_html_e( 'Name', 'auto-daily-newsletter' ); ?></th>
									<th><?php esc_html_e( 'Status', 'auto-daily-newsletter' ); ?></th>
									<th><?php esc_html_e( 'Subscribed Date', 'auto-daily-newsletter' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'auto-daily-newsletter' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $subscribers as $sub ) : ?>
									<tr id="subscriber-row-<?php echo intval( $sub->id ); ?>">
										<td><?php echo intval( $sub->id ); ?></td>
										<td><strong><?php echo esc_html( $sub->email ); ?></strong></td>
										<td><?php echo esc_html( $sub->name ? $sub->name : '—' ); ?></td>
										<td>
											<?php if ( 'active' === $sub->status ) : ?>
												<span class="adnl-badge adnl-badge-success"><?php esc_html_e( 'Active', 'auto-daily-newsletter' ); ?></span>
											<?php else : ?>
												<span class="adnl-badge adnl-badge-gray"><?php esc_html_e( 'Unsubscribed', 'auto-daily-newsletter' ); ?></span>
											<?php endif; ?>
										</td>
										<td><?php echo esc_html( wp_date( 'M j, Y g:i A', strtotime( $sub->created_at ) ) ); ?></td>
										<td>
											<button type="button" class="button-link-delete adnl-btn-delete-subscriber" data-id="<?php echo intval( $sub->id ); ?>">
												<?php esc_html_e( 'Delete', 'auto-daily-newsletter' ); ?>
											</button>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p style="padding: 24px; text-align: center; color: #64748b;">
							<?php esc_html_e( 'No subscribers yet. Place the shortcode [daily_newsletter_form] on your site or add one manually!', 'auto-daily-newsletter' ); ?>
						</p>
					<?php endif; ?>
				</div>
			</div>

		<!-- TAB 5: LOGS -->
		<?php elseif ( 'logs' === $active_tab ) : ?>
			<div class="adnl-box">
				<div class="adnl-box-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
					<div>
						<h2><span class="dashicons dashicons-list-view" style="margin-right: 6px;"></span><?php esc_html_e( 'Newsletter Delivery & Schedule Audit Logs', 'auto-daily-newsletter' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Real-time audit trail of all past scheduled cron dispatches and manual test runs.', 'auto-daily-newsletter' ); ?></p>
					</div>
					<div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
						<button type="button" class="button button-primary" id="adnl-btn-manual-send-logs">
							<span class="dashicons dashicons-controls-play" style="margin-top: 2px;"></span> <?php esc_html_e( 'Run Scheduled Digest Now', 'auto-daily-newsletter' ); ?>
						</button>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=auto-daily-newsletter&action=adnl_clear_logs' ), 'adnl_clear_logs' ) ); ?>" class="button button-secondary" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to clear all delivery logs?', 'auto-daily-newsletter' ) ); ?>');">
							<span class="dashicons dashicons-trash" style="margin-top: 2px;"></span> <?php esc_html_e( 'Clear Logs', 'auto-daily-newsletter' ); ?>
						</a>
					</div>
				</div>
				<div class="adnl-box-body">
					<?php
					$logs = $wpdb->get_results( "SELECT * FROM {$logs_table} ORDER BY id DESC LIMIT 50" );
					if ( ! empty( $logs ) ) :
						?>
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Date / Time', 'auto-daily-newsletter' ); ?></th>
									<th><?php esc_html_e( 'Subject', 'auto-daily-newsletter' ); ?></th>
									<th><?php esc_html_e( 'Posts', 'auto-daily-newsletter' ); ?></th>
									<th><?php esc_html_e( 'Recipients', 'auto-daily-newsletter' ); ?></th>
									<th><?php esc_html_e( 'Status', 'auto-daily-newsletter' ); ?></th>
									<th><?php esc_html_e( 'Details', 'auto-daily-newsletter' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $logs as $log ) : ?>
									<tr>
										<td><?php echo esc_html( wp_date( 'M j, Y g:i A', strtotime( $log->created_at ) ) ); ?></td>
										<td><strong><?php echo esc_html( $log->subject ); ?></strong></td>
										<td><?php echo intval( $log->posts_count ); ?></td>
										<td><?php echo intval( $log->recipients_count ); ?></td>
										<td>
											<?php if ( 'success' === $log->status ) : ?>
												<span class="adnl-badge adnl-badge-success"><?php esc_html_e( 'Success', 'auto-daily-newsletter' ); ?></span>
											<?php elseif ( 'partial' === $log->status ) : ?>
												<span class="adnl-badge adnl-badge-warning"><?php esc_html_e( 'Partial', 'auto-daily-newsletter' ); ?></span>
											<?php elseif ( 'skipped' === $log->status ) : ?>
												<span class="adnl-badge adnl-badge-gray"><?php esc_html_e( 'Skipped', 'auto-daily-newsletter' ); ?></span>
											<?php else : ?>
												<span class="adnl-badge adnl-badge-danger"><?php esc_html_e( 'Failed', 'auto-daily-newsletter' ); ?></span>
											<?php endif; ?>
										</td>
										<td style="color: #475569; font-size: 12px;"><?php echo esc_html( $log->message ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p style="padding: 24px; text-align: center; color: #64748b;">
							<?php esc_html_e( 'No delivery logs recorded yet.', 'auto-daily-newsletter' ); ?>
						</p>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

	</div>
</div>

<!-- Modal: Preview Email -->
<div id="adnl-preview-modal" class="adnl-modal" style="display:none;">
	<div class="adnl-modal-dialog">
		<div class="adnl-modal-header">
			<h3><?php esc_html_e( 'Daily Newsletter Live Preview', 'auto-daily-newsletter' ); ?></h3>
			<button type="button" class="adnl-modal-close">&times;</button>
		</div>
		<div class="adnl-modal-body">
			<div id="adnl-preview-loading" style="text-align: center; padding: 40px;">
				<span class="spinner is-active" style="float:none;"></span> <?php esc_html_e( 'Building preview from latest posts...', 'auto-daily-newsletter' ); ?>
			</div>
			<iframe id="adnl-preview-iframe" style="width: 100%; height: 580px; border: 1px solid #cbd5e1; border-radius: 8px; display:none;"></iframe>
		</div>
	</div>
</div>

<!-- Modal: Add Subscriber -->
<div id="adnl-add-subscriber-modal" class="adnl-modal" style="display:none;">
	<div class="adnl-modal-dialog" style="max-width: 480px;">
		<div class="adnl-modal-header">
			<h3><?php esc_html_e( 'Add New Subscriber', 'auto-daily-newsletter' ); ?></h3>
			<button type="button" class="adnl-modal-close">&times;</button>
		</div>
		<div class="adnl-modal-body">
			<div id="adnl-add-sub-error" class="adnl-alert adnl-alert-danger" style="display:none; margin-bottom: 15px;"></div>
			<div class="adnl-form-group">
				<label for="adnl-new-sub-email"><?php esc_html_e( 'Email Address (Required)', 'auto-daily-newsletter' ); ?></label>
				<input type="email" id="adnl-new-sub-email" class="widefat" placeholder="reader@example.com" required />
			</div>
			<div class="adnl-form-group" style="margin-top: 15px;">
				<label for="adnl-new-sub-name"><?php esc_html_e( 'Name (Optional)', 'auto-daily-newsletter' ); ?></label>
				<input type="text" id="adnl-new-sub-name" class="widefat" placeholder="John Doe" />
			</div>
			<div style="margin-top: 20px; text-align: right;">
				<button type="button" class="button button-primary" id="adnl-btn-submit-add-sub">
					<?php esc_html_e( 'Add to Subscribers', 'auto-daily-newsletter' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal: Import Subscribers (CSV) -->
<div id="adnl-import-subscriber-modal" class="adnl-modal" style="display:none;">
	<div class="adnl-modal-dialog" style="max-width: 520px;">
		<div class="adnl-modal-header">
			<h3><?php esc_html_e( 'Import Subscribers from CSV', 'auto-daily-newsletter' ); ?></h3>
			<button type="button" class="adnl-modal-close">&times;</button>
		</div>
		<form method="post" action="" enctype="multipart/form-data" id="adnl-import-csv-form">
			<?php wp_nonce_field( 'adnl_import_subscribers_action', 'adnl_import_subscribers_nonce' ); ?>
			<input type="hidden" name="adnl_import_subscribers" value="1" />
			<input type="hidden" name="active_tab" value="subscribers" />

			<div class="adnl-modal-body">
				<p style="margin-top: 0; color: #475569; font-size: 13px; line-height: 1.5;">
					<?php esc_html_e( 'Upload a CSV file containing your subscriber emails. You can also include a Name column.', 'auto-daily-newsletter' ); ?>
				</p>

				<div style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 8px; padding: 22px; text-align: center; margin: 16px 0;">
					<span class="dashicons dashicons-upload" style="font-size: 36px; width: 36px; height: 36px; color: #64748b; margin-bottom: 8px;"></span>
					<div style="font-weight: 600; color: #0f172a; margin-bottom: 6px;">
						<?php esc_html_e( 'Choose a CSV file to upload', 'auto-daily-newsletter' ); ?>
					</div>
					<input type="file" name="adnl_csv_file" id="adnl-csv-file-input" accept=".csv,text/csv,application/vnd.ms-excel" required style="font-size: 13px; margin: 8px auto 0 auto; display: block;" />
				</div>

				<div style="margin: 16px 0;">
					<label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155; cursor: pointer;">
						<input type="checkbox" name="adnl_update_existing" value="1" checked />
						<span><?php esc_html_e( 'Update name and reactivate if subscriber already exists', 'auto-daily-newsletter' ); ?></span>
					</label>
				</div>

				<div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px 14px; font-size: 12px; color: #1e40af; display: flex; justify-content: space-between; align-items: center;">
					<span>
						💡 <strong><?php esc_html_e( 'Need a template?', 'auto-daily-newsletter' ); ?></strong> <?php esc_html_e( 'Download the ready-to-use CSV template:', 'auto-daily-newsletter' ); ?>
					</span>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=auto-daily-newsletter&action=adnl_download_sample_csv' ), 'adnl_download_sample_csv' ) ); ?>" class="button button-small" style="margin-left: 8px; white-space: nowrap;">
						<span class="dashicons dashicons-media-spreadsheet" style="font-size: 14px; width: 14px; height: 14px; margin-top: 3px;"></span> <?php esc_html_e( 'Sample CSV', 'auto-daily-newsletter' ); ?>
					</a>
				</div>
			</div>
			<div class="adnl-modal-footer" style="padding: 16px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
				<button type="button" class="button button-secondary adnl-modal-close"><?php esc_html_e( 'Cancel', 'auto-daily-newsletter' ); ?></button>
				<button type="submit" class="button button-primary" id="adnl-btn-submit-import">
					<span class="dashicons dashicons-upload" style="margin-top: 2px;"></span> <?php esc_html_e( 'Upload & Import', 'auto-daily-newsletter' ); ?>
				</button>
			</div>
		</form>
	</div>
</div>
<script type="text/javascript">
function adnlApplyPreset(type) {
	var hostInput = document.querySelector('input[name="adnl_smtp_host"]');
	var portInput = document.querySelector('input[name="adnl_smtp_port"]');
	var encSelect = document.querySelector('select[name="adnl_smtp_encryption"]');
	var authCheck = document.querySelector('input[name="adnl_smtp_auth"]');
	var userInput = document.querySelector('input[name="adnl_smtp_user"]');
	var hintDiv   = document.getElementById('adnl-preset-hint');

	if (!hostInput || !portInput || !encSelect) return;

	var presets = {
		'gmail': {
			host: 'smtp.gmail.com',
			port: 465,
			enc: 'ssl',
			auth: true,
			hint: '📧 <strong>Gmail Selected:</strong> Port <strong>465 (SSL)</strong> is set. Enter your full Gmail address as <strong>Username</strong>. For Password, you <em>must</em> use a 16-character <strong>Google App Password</strong> (generate at <a href="https://myaccount.google.com/apppasswords" target="_blank" style="text-decoration:underline;">myaccount.google.com/apppasswords</a>, NOT your personal password).'
		},
		'brevo': {
			host: 'smtp-relay.brevo.com',
			port: 587,
			enc: 'tls',
			auth: true,
			hint: '🚀 <strong>Brevo Selected:</strong> Enter your Brevo account email as <strong>Username</strong>, and your Brevo SMTP Master Key as <strong>Password</strong> (found in your Brevo account under SMTP & API).'
		},
		'sendgrid': {
			host: 'smtp.sendgrid.net',
			port: 587,
			enc: 'tls',
			auth: true,
			user: 'apikey',
			hint: '⚡ <strong>SendGrid Selected:</strong> Username is automatically set to <code>apikey</code>. Paste your SendGrid API key into the <strong>Password</strong> field.'
		},
		'mailgun': {
			host: 'smtp.mailgun.org',
			port: 587,
			enc: 'tls',
			auth: true,
			hint: '📨 <strong>Mailgun Selected:</strong> Enter your domain postmaster email (e.g. <code>postmaster@mg.yourdomain.com</code>) as <strong>Username</strong>, and your Mailgun SMTP password.'
		},
		'aws': {
			host: 'email-smtp.us-east-1.amazonaws.com',
			port: 587,
			enc: 'tls',
			auth: true,
			hint: '☁️ <strong>AWS SES Selected:</strong> Enter your AWS SES SMTP Username and SMTP Password (generated from the AWS SES Console under SMTP Settings).'
		}
	};

	var p = presets[type];
	if (!p) return;

	hostInput.value = p.host;
	portInput.value = p.port;
	encSelect.value = p.enc;
	if (authCheck) authCheck.checked = p.auth;
	if (p.user && userInput && !userInput.value) userInput.value = p.user;

	if (hintDiv) {
		hintDiv.innerHTML = p.hint;
		hintDiv.style.display = 'block';
	}

	[hostInput, portInput, encSelect].forEach(function(el) {
		el.style.transition = 'background 0.3s';
		el.style.backgroundColor = '#eff6ff';
		setTimeout(function() { el.style.backgroundColor = '#ffffff'; }, 600);
	});
}
</script>

