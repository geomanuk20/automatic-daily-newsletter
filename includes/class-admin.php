<?php
/**
 * Admin: Controller for WordPress admin dashboard, settings, and AJAX operations.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ADNL_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_admin_post_actions' ) );

		// AJAX endpoints for admin operations
		add_action( 'wp_ajax_adnl_send_test_email', array( $this, 'ajax_send_test_email' ) );
		add_action( 'wp_ajax_adnl_manual_send', array( $this, 'ajax_manual_send' ) );
		add_action( 'wp_ajax_adnl_preview_digest', array( $this, 'ajax_preview_digest' ) );
		add_action( 'wp_ajax_adnl_admin_add_subscriber', array( $this, 'ajax_admin_add_subscriber' ) );
		add_action( 'wp_ajax_adnl_admin_delete_subscriber', array( $this, 'ajax_admin_delete_subscriber' ) );
	}

	/**
	 * Register menu item in WordPress admin sidebar.
	 */
	public function register_admin_menu() {
		add_menu_page(
			__( 'Daily Newsletter', 'auto-daily-newsletter' ),
			__( 'Daily Newsletter', 'auto-daily-newsletter' ),
			'manage_options',
			'auto-daily-newsletter',
			array( $this, 'render_admin_page' ),
			'dashicons-email-alt2',
			30
		);
	}

	/**
	 * Enqueue admin scripts & stylesheets.
	 *
	 * @param string $hook_suffix
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( false === strpos( (string) $hook_suffix, 'auto-daily-newsletter' ) ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'adnl-admin-style',
			ADNL_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			ADNL_VERSION
		);

		wp_enqueue_script(
			'adnl-admin-script',
			ADNL_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			ADNL_VERSION,
			true
		);

		wp_localize_script(
			'adnl-admin-script',
			'adnl_admin_obj',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'adnl_admin_nonce' ),
				'strings'  => array(
					'confirm_manual_send'   => __( 'Are you sure you want to send today\'s digest to all active subscribers right now?', 'auto-daily-newsletter' ),
					'confirm_delete_sub'    => __( 'Are you sure you want to delete this subscriber?', 'auto-daily-newsletter' ),
					'sending'               => __( 'Sending...', 'auto-daily-newsletter' ),
					'success'               => __( 'Success!', 'auto-daily-newsletter' ),
					'error'                 => __( 'An error occurred.', 'auto-daily-newsletter' ),
				),
			)
		);
	}

	/**
	 * Handle admin POST submissions (Settings save, CSV export).
	 */
	public function handle_admin_post_actions() {
		// Handle CSV export
		if ( isset( $_GET['action'] ) && 'adnl_export_csv' === $_GET['action'] ) {
			ADNL_Subscriber_Manager::export_subscribers_csv();
		}

		// Handle Download Sample CSV Template
		if ( isset( $_GET['action'] ) && 'adnl_download_sample_csv' === $_GET['action'] ) {
			ADNL_Subscriber_Manager::download_sample_csv();
		}

		// Handle Clear Delivery Logs
		if ( isset( $_GET['action'] ) && 'adnl_clear_logs' === $_GET['action'] ) {
			if ( ! check_admin_referer( 'adnl_clear_logs' ) || ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'auto-daily-newsletter' ) );
			}
			global $wpdb;
			$logs_table = $wpdb->prefix . 'adnl_logs';
			$wpdb->query( "TRUNCATE TABLE {$logs_table}" );
			wp_safe_redirect( add_query_arg( array( 'page' => 'auto-daily-newsletter', 'tab' => 'logs', 'logs_cleared' => 1 ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// Handle CSV Import
		if ( isset( $_POST['adnl_import_subscribers'] ) ) {
			if ( ! check_admin_referer( 'adnl_import_subscribers_action', 'adnl_import_subscribers_nonce' ) || ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'auto-daily-newsletter' ) );
			}

			if ( empty( $_FILES['adnl_csv_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['adnl_csv_file']['tmp_name'] ) ) {
				wp_safe_redirect( add_query_arg( array( 'page' => 'auto-daily-newsletter', 'tab' => 'subscribers', 'import_error' => 'no_file' ), admin_url( 'admin.php' ) ) );
				exit;
			}

			$update_existing = ! empty( $_POST['adnl_update_existing'] );
			$result = ADNL_Subscriber_Manager::import_subscribers_from_csv( $_FILES['adnl_csv_file']['tmp_name'], $update_existing );

			if ( ! empty( $result['success'] ) ) {
				wp_safe_redirect( add_query_arg( array(
					'page'     => 'auto-daily-newsletter',
					'tab'      => 'subscribers',
					'imported' => intval( $result['imported'] ),
					'skipped'  => intval( $result['skipped'] ),
					'invalid'  => intval( $result['invalid'] ),
					'total'    => intval( $result['total'] ),
				), admin_url( 'admin.php' ) ) );
				exit;
			} else {
				wp_safe_redirect( add_query_arg( array(
					'page'         => 'auto-daily-newsletter',
					'tab'          => 'subscribers',
					'import_error' => urlencode( $result['message'] ?? 'import_failed' ),
				), admin_url( 'admin.php' ) ) );
				exit;
			}
		}

		// Handle Settings Save
		if ( isset( $_POST['adnl_save_settings_nonce'] ) ) {
			if ( ! check_admin_referer( 'adnl_save_settings', 'adnl_save_settings_nonce' ) || ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'auto-daily-newsletter' ) );
			}

			$active_tab = sanitize_text_field( $_POST['active_tab'] ?? '' );

			if ( 'settings' === $active_tab || 'schedule' === $active_tab || isset( $_POST['adnl_posts_count'] ) ) {
				// General & Schedule settings
				update_option( 'adnl_enabled', isset( $_POST['adnl_enabled'] ) ? 1 : 0 );
				update_option( 'adnl_selection_mode', sanitize_text_field( $_POST['adnl_selection_mode'] ?? 'auto' ) );
				
				$selected_posts = isset( $_POST['adnl_selected_post_ids'] ) && is_array( $_POST['adnl_selected_post_ids'] ) ? array_map( 'intval', $_POST['adnl_selected_post_ids'] ) : array();
				update_option( 'adnl_selected_post_ids', $selected_posts );

				update_option( 'adnl_posts_count', max( 1, min( 20, intval( $_POST['adnl_posts_count'] ?? 7 ) ) ) );
				update_option( 'adnl_lookback_hours', intval( $_POST['adnl_lookback_hours'] ?? 24 ) );
				update_option( 'adnl_fallback_behavior', sanitize_text_field( $_POST['adnl_fallback_behavior'] ?? 'latest' ) );
				
				$schedule_time = sanitize_text_field( $_POST['adnl_schedule_time'] ?? '08:00' );
				$old_time      = get_option( 'adnl_schedule_time' );
				update_option( 'adnl_schedule_time', $schedule_time );

				$timezone      = sanitize_text_field( $_POST['adnl_timezone'] ?? '' );
				$old_tz        = get_option( 'adnl_timezone' );
				update_option( 'adnl_timezone', $timezone );

				if ( $schedule_time !== $old_time || $timezone !== $old_tz ) {
					delete_option( 'adnl_last_dispatched_key' );
					delete_option( 'adnl_last_dispatched_date' );
					delete_transient( 'adnl_cron_lock' );
					ADNL_Cron::reschedule();
				}

				update_option( 'adnl_email_subject', sanitize_textarea_field( $_POST['adnl_email_subject'] ?? "[Daily Digest] Today's Top Stories - {date}" ) );
				update_option( 'adnl_preheader_text', sanitize_text_field( $_POST['adnl_preheader_text'] ?? '' ) );
				update_option( 'adnl_header_title', sanitize_text_field( $_POST['adnl_header_title'] ?? '' ) );
				update_option( 'adnl_site_logo', esc_url_raw( $_POST['adnl_site_logo'] ?? '' ) );
				update_option( 'adnl_logo_height', intval( $_POST['adnl_logo_height'] ?? 70 ) );
				update_option( 'adnl_primary_color', sanitize_hex_color( $_POST['adnl_primary_color'] ?? '#e11d48' ) );
				update_option( 'adnl_footer_text', sanitize_textarea_field( $_POST['adnl_footer_text'] ?? '' ) );
				update_option( 'adnl_footer_copyright', sanitize_text_field( $_POST['adnl_footer_copyright'] ?? '' ) );
				update_option( 'adnl_footer_bg_color', sanitize_hex_color( $_POST['adnl_footer_bg_color'] ?? '#f8fafc' ) );
				update_option( 'adnl_show_primary_tip', isset( $_POST['adnl_show_primary_tip'] ) ? 1 : 0 );

				// Bottom-Left Popup settings
				update_option( 'adnl_popup_enabled', isset( $_POST['adnl_popup_enabled'] ) ? 1 : 0 );
				update_option( 'adnl_popup_show_logo', isset( $_POST['adnl_popup_show_logo'] ) ? 1 : 0 );
				update_option( 'adnl_popup_title', sanitize_text_field( wp_unslash( $_POST['adnl_popup_title'] ?? 'HI THERE!' ) ) );
				update_option( 'adnl_popup_message', sanitize_textarea_field( html_entity_decode( wp_unslash( $_POST['adnl_popup_message'] ?? '' ), ENT_QUOTES, 'UTF-8' ) ) );
				update_option( 'adnl_popup_button', sanitize_text_field( wp_unslash( $_POST['adnl_popup_button'] ?? 'SUBSCRIBE' ) ) );
				update_option( 'adnl_popup_btn_color', sanitize_hex_color( $_POST['adnl_popup_btn_color'] ?? '#f43f5e' ) );
				update_option( 'adnl_popup_placeholder', sanitize_text_field( $_POST['adnl_popup_placeholder'] ?? 'Email' ) );
				update_option( 'adnl_popup_image', esc_url_raw( $_POST['adnl_popup_image'] ?? '' ) );
				update_option( 'adnl_popup_delay', intval( $_POST['adnl_popup_delay'] ?? 3 ) );
				update_option( 'adnl_popup_frequency', intval( $_POST['adnl_popup_frequency'] ?? 30 ) );
				update_option( 'adnl_popup_logo_height', intval( $_POST['adnl_popup_logo_height'] ?? 55 ) );
				update_option( 'adnl_popup_position', sanitize_text_field( $_POST['adnl_popup_position'] ?? 'bottom-left' ) );

				// Categories
				$categories = isset( $_POST['adnl_categories'] ) && is_array( $_POST['adnl_categories'] ) ? array_map( 'intval', $_POST['adnl_categories'] ) : array();
				update_option( 'adnl_categories', $categories );
			} elseif ( 'smtp' === $active_tab || isset( $_POST['adnl_smtp_host'] ) ) {
				// Sender settings
				update_option( 'adnl_from_name', sanitize_text_field( $_POST['adnl_from_name'] ?? get_bloginfo( 'name' ) ) );
				update_option( 'adnl_from_email', sanitize_email( $_POST['adnl_from_email'] ?? get_bloginfo( 'admin_email' ) ) );

				// Mailer / SMTP settings
				update_option( 'adnl_mailer_type', sanitize_text_field( $_POST['adnl_mailer_type'] ?? 'smtp' ) );
				update_option( 'adnl_smtp_host', sanitize_text_field( $_POST['adnl_smtp_host'] ?? '' ) );
				update_option( 'adnl_smtp_port', intval( $_POST['adnl_smtp_port'] ?? 587 ) );
				update_option( 'adnl_smtp_encryption', sanitize_text_field( $_POST['adnl_smtp_encryption'] ?? 'tls' ) );
				update_option( 'adnl_smtp_auth', isset( $_POST['adnl_smtp_auth'] ) ? 1 : 0 );
				update_option( 'adnl_smtp_user', sanitize_text_field( $_POST['adnl_smtp_user'] ?? '' ) );
				
				// Only update password if provided
				if ( ! empty( $_POST['adnl_smtp_pass'] ) ) {
					update_option( 'adnl_smtp_pass', sanitize_text_field( $_POST['adnl_smtp_pass'] ) );
				}

				// Batching settings
				update_option( 'adnl_batch_size', max( 1, intval( $_POST['adnl_batch_size'] ?? 30 ) ) );
				update_option( 'adnl_batch_delay', max( 0, intval( $_POST['adnl_batch_delay'] ?? 1 ) ) );
			}

			// Automatically purge all page caches so frontend updates immediately
			if ( has_action( 'litespeed_purge_all' ) ) {
				do_action( 'litespeed_purge_all' );
			}
			if ( class_exists( '\LiteSpeed\Purge' ) ) {
				\LiteSpeed\Purge::purge_all();
			}
			if ( function_exists( 'wp_cache_flush' ) ) {
				wp_cache_flush();
			}
			if ( function_exists( 'rocket_clean_domain' ) ) {
				rocket_clean_domain();
			}
			if ( function_exists( 'w3tc_flush_all' ) ) {
				w3tc_flush_all();
			}

			wp_safe_redirect( add_query_arg( array( 'page' => 'auto-daily-newsletter', 'updated' => 'true', 'tab' => sanitize_text_field( $_POST['active_tab'] ?? 'dashboard' ) ), admin_url( 'admin.php' ) ) );
			exit;
		}
	}

	/**
	 * Render main admin interface.
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'auto-daily-newsletter' ) );
		}

		global $wpdb;
		$subscribers_table = $wpdb->prefix . 'adnl_subscribers';
		$logs_table        = $wpdb->prefix . 'adnl_logs';

		// Proactively check and execute if scheduled digest is due right now
		if ( class_exists( 'ADNL_Cron' ) ) {
			$cron_check = new ADNL_Cron();
			$cron_check->maybe_trigger_due_digest();
		}

		// Gather stats
		$total_subscribers = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$subscribers_table} WHERE status = 'active'" );
		$unsubscribed_count= (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$subscribers_table} WHERE status = 'unsubscribed'" );
		$total_logs        = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$logs_table}" );
		
		$tz = ADNL_Cron::get_timezone();
		$next_run_ts = wp_next_scheduled( ADNL_Cron::CRON_HOOK );
		if ( ! $next_run_ts && (bool) get_option( 'adnl_enabled', 1 ) ) {
			ADNL_Cron::reschedule();
			$next_run_ts = wp_next_scheduled( ADNL_Cron::CRON_HOOK );
		}
		if ( $next_run_ts ) {
			$dt = new DateTime( '@' . $next_run_ts );
			$dt->setTimezone( $tz );
			$next_run_formatted = $dt->format( 'M j, Y - g:i A' );
		} else {
			$next_run_formatted = __( 'Not Scheduled', 'auto-daily-newsletter' );
		}

		$last_run_ts = get_option( 'adnl_last_run_timestamp' );
		if ( $last_run_ts ) {
			$dt = new DateTime( '@' . $last_run_ts );
			$dt->setTimezone( $tz );
			$last_run_formatted = $dt->format( 'M j, Y - g:i A' ) . ' (' . $dt->format( 'T' ) . ')';
		} else {
			$last_run_formatted = __( 'Never', 'auto-daily-newsletter' );
		}

		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';

		include ADNL_PLUGIN_DIR . 'templates/admin-page.php';
	}

	/**
	 * AJAX: Send test email.
	 */
	public function ajax_send_test_email() {
		check_ajax_referer( 'adnl_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'auto-daily-newsletter' ) ) );
		}

		$recipient = isset( $_POST['test_email'] ) ? sanitize_email( wp_unslash( $_POST['test_email'] ) ) : '';
		if ( ! is_email( $recipient ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid recipient email address.', 'auto-daily-newsletter' ) ) );
		}

		$mailer = new ADNL_Mailer();
		$result = $mailer->send_test_email( $recipient );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => sprintf( __( 'Test email sent successfully to %s! Check your inbox (and spam folder).', 'auto-daily-newsletter' ), esc_html( $recipient ) ) ) );
	}

	/**
	 * AJAX: Manually trigger daily digest dispatch now.
	 */
	public function ajax_manual_send() {
		check_ajax_referer( 'adnl_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'auto-daily-newsletter' ) ) );
		}

		$cron   = new ADNL_Cron();
		$result = $cron->execute_daily_digest( true );

		if ( 'failed' === $result['status'] ) {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}

		wp_send_json_success( array( 'message' => $result['message'] ) );
	}

	/**
	 * AJAX: Preview newsletter HTML.
	 */
	public function ajax_preview_digest() {
		check_ajax_referer( 'adnl_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'auto-daily-newsletter' ) ) );
		}

		$post_collector   = new ADNL_Post_Collector();
		$template_builder = new ADNL_Template_Builder();

		$overrides = array();
		if ( isset( $_POST['logo_height'] ) ) {
			$overrides['logo_height'] = intval( $_POST['logo_height'] );
		}
		if ( ! empty( $_POST['site_logo'] ) ) {
			$overrides['site_logo'] = sanitize_text_field( $_POST['site_logo'] );
		}
		if ( ! empty( $_POST['header_title'] ) ) {
			$overrides['header_title'] = sanitize_text_field( $_POST['header_title'] );
		}

		$posts = $post_collector->get_latest_news_posts();
		$html  = $template_builder->build_digest_html( $posts, $overrides );

		$mock_subscriber = (object) array(
			'email' => 'subscriber@example.com',
			'name'  => 'Sample Subscriber',
			'token' => 'sample-preview-token',
		);

		$personalized = $template_builder->personalize_html( $html, $mock_subscriber );
		$subject      = ADNL_Template_Builder::generate_subject( get_option( 'adnl_email_subject' ), $posts );

		wp_send_json_success( array(
			'html'       => $personalized,
			'post_count' => count( $posts ),
			'subject'    => $subject,
		) );
	}

	/**
	 * AJAX: Add subscriber manually from admin.
	 */
	public function ajax_admin_add_subscriber() {
		check_ajax_referer( 'adnl_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'auto-daily-newsletter' ) ) );
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';

		$mgr    = new ADNL_Subscriber_Manager();
		$result = $mgr->add_subscriber( $email, $name, 'active' );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Subscriber added successfully.', 'auto-daily-newsletter' ) ) );
	}

	/**
	 * AJAX: Delete subscriber from admin.
	 */
	public function ajax_admin_delete_subscriber() {
		check_ajax_referer( 'adnl_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'auto-daily-newsletter' ) ) );
		}

		$id  = isset( $_POST['subscriber_id'] ) ? intval( $_POST['subscriber_id'] ) : 0;
		$mgr = new ADNL_Subscriber_Manager();

		if ( $mgr->delete_subscriber( $id ) ) {
			wp_send_json_success( array( 'message' => __( 'Subscriber removed.', 'auto-daily-newsletter' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete subscriber.', 'auto-daily-newsletter' ) ) );
		}
	}
}
