<?php
/**
 * Cron Manager: Schedules and executes the daily newsletter digest.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ADNL_Cron {

	const CRON_HOOK = 'adnl_daily_newsletter_cron';

	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'register_cron_schedules' ) );
		add_action( self::CRON_HOOK, array( $this, 'execute_daily_digest' ) );

		// Auto-register cron schedule if missing and automation is enabled
		if ( (bool) get_option( 'adnl_enabled', 1 ) && ! wp_next_scheduled( self::CRON_HOOK ) ) {
			self::reschedule();
		}

		// Fail-safe check: automatically execute digest if scheduled time arrived and cron was delayed
		add_action( 'init', array( $this, 'maybe_trigger_due_digest' ) );

		// Background AJAX ping (bypasses LiteSpeed and static page caching)
		add_action( 'wp_ajax_adnl_cron_ping', array( $this, 'ajax_cron_ping' ) );
		add_action( 'wp_ajax_nopriv_adnl_cron_ping', array( $this, 'ajax_cron_ping' ) );
	}

	/**
	 * AJAX endpoint: triggers due scheduled digest even when pages are cached by LiteSpeed.
	 */
	public function ajax_cron_ping() {
		$this->maybe_trigger_due_digest( true );
		wp_send_json_success( array( 'checked' => true ) );
	}

	/**
	 * Register daily cron schedule interval.
	 *
	 * @param array $schedules
	 * @return array
	 */
	public function register_cron_schedules( $schedules ) {
		if ( ! isset( $schedules['adnl_daily'] ) ) {
			$schedules['adnl_daily'] = array(
				'interval' => DAY_IN_SECONDS,
				'display'  => __( 'Once Daily (Auto Daily Newsletter)', 'auto-daily-newsletter' ),
			);
		}
		return $schedules;
	}

	/**
	 * Get the configured timezone for newsletter schedules.
	 *
	 * @return DateTimeZone
	 */
	public static function get_timezone() {
		$tz_string = get_option( 'adnl_timezone', '' );
		if ( ! empty( $tz_string ) ) {
			try {
				return new DateTimeZone( $tz_string );
			} catch ( \Exception $e ) {
				// Fall back to WP timezone
			}
		}
		$wp_tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
		if ( 'UTC' === $wp_tz->getName() ) {
			try {
				return new DateTimeZone( 'Asia/Kolkata' );
			} catch ( \Exception $e ) {}
		}
		return $wp_tz;
	}

	/**
	 * Get human-readable timezone string (e.g. "Asia/Kolkata").
	 *
	 * @return string
	 */
	public static function get_timezone_string() {
		$tz = self::get_timezone();
		return $tz->getName();
	}

	/**
	 * Calculate timestamp for next run based on user configured time and chosen timezone.
	 *
	 * @return int Unix UTC timestamp for WordPress cron
	 */
	public static function get_next_run_timestamp() {
		$time_str = get_option( 'adnl_schedule_time', '08:00' );
		list( $hour, $minute ) = array_map( 'intval', explode( ':', $time_str ) );

		// Use configured newsletter timezone (e.g. Asia/Kolkata or WP timezone)
		$timezone = self::get_timezone();
		$now      = new DateTime( 'now', $timezone );
		$target   = clone $now;
		$target->setTime( $hour, $minute, 0 );

		// If time has already passed today, schedule for tomorrow
		if ( $target <= $now ) {
			$target->modify( '+1 day' );
		}

		return $target->getTimestamp();
	}

	/**
	 * Reschedule cron whenever schedule time changes.
	 */
	public static function reschedule() {
		wp_clear_scheduled_hook( self::CRON_HOOK );

		$next_run = self::get_next_run_timestamp();
		wp_schedule_event( $next_run, 'daily', self::CRON_HOOK );
	}

	/**
	 * Fail-safe check: automatically triggers daily digest if scheduled time has passed
	 * and WordPress built-in cron was delayed or stalled (e.g. by LiteSpeed page caching).
	 */
	public function maybe_trigger_due_digest( $from_ajax = false ) {
		// Only run on standard page requests or our explicit cron ping
		if ( ! $from_ajax && ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) ) {
			return;
		}

		$is_enabled = (bool) get_option( 'adnl_enabled', 1 );
		if ( ! $is_enabled ) {
			return;
		}

		// Prevent multiple concurrent executions with a lock transient
		if ( get_transient( 'adnl_cron_lock' ) ) {
			return;
		}

		$time_str = get_option( 'adnl_schedule_time', '08:00' );
		list( $hour, $minute ) = array_map( 'intval', explode( ':', $time_str ) );

		$timezone = self::get_timezone();
		$now      = new DateTime( 'now', $timezone );
		$target   = clone $now;
		$target->setTime( $hour, $minute, 0 );

		// Only trigger if current time is at or past today's scheduled time
		if ( $now < $target ) {
			return;
		}

		// Check if this specific scheduled time has already been dispatched today
		$dispatch_key  = $now->format( 'Y-m-d' ) . '_' . $time_str;
		$last_dispatch = get_option( 'adnl_last_dispatched_key', '' );
		if ( $last_dispatch === $dispatch_key ) {
			return;
		}

		// Set a 5-minute mutex lock
		set_transient( 'adnl_cron_lock', time(), 300 );

		// Mark this specific scheduled time as dispatched to prevent duplicate sends
		update_option( 'adnl_last_dispatched_key', $dispatch_key );
		update_option( 'adnl_last_dispatched_date', $now->format( 'Y-m-d' ) );

		// Execute digest
		$this->execute_daily_digest( false );

		// Reschedule cron event for next occurrence
		self::reschedule();

		delete_transient( 'adnl_cron_lock' );
	}

	/**
	 * Main execution routine: collects posts, builds HTML, and emails subscribers.
	 *
	 * @param bool $is_manual Whether this run was triggered manually from admin dashboard.
	 * @return array Status report of execution.
	 */
	public function execute_daily_digest( $is_manual = false ) {
		// Prevent script timeout during batch email sending
		if ( ! ini_get( 'safe_mode' ) ) {
			@set_time_limit( 600 );
		}

		$is_enabled = (bool) get_option( 'adnl_enabled', 1 );
		if ( ! $is_enabled && ! $is_manual ) {
			$this->log_execution(
				__( '[Skipped] Daily Digest (Disabled)', 'auto-daily-newsletter' ),
				0,
				0,
				'skipped',
				__( 'Scheduled execution skipped: Automated Daily Digest is turned OFF in plugin settings.', 'auto-daily-newsletter' )
			);
			return array(
				'status'  => 'skipped',
				'message' => __( 'Automated digest is currently disabled in plugin settings.', 'auto-daily-newsletter' ),
			);
		}

		$post_collector   = new ADNL_Post_Collector();
		$template_builder = new ADNL_Template_Builder();
		$mailer           = new ADNL_Mailer();
		$subscriber_mgr   = new ADNL_Subscriber_Manager();

		// 1. Gather posts
		$posts_data = $post_collector->get_latest_news_posts();
		$post_count = count( $posts_data );

		if ( 0 === $post_count ) {
			$this->log_execution(
				get_option( 'adnl_email_subject', "[Daily Digest] Today's Top Stories - {date}" ),
				0,
				0,
				'skipped',
				__( 'No published news articles found matching your criteria (lookback window or selected categories).', 'auto-daily-newsletter' )
			);
			return array(
				'status'  => 'failed',
				'message' => __( 'No published news posts found to include in the digest. Please ensure you have published news posts on your site.', 'auto-daily-newsletter' ),
			);
		}

		// 2. Fetch active subscribers
		$subscribers = $subscriber_mgr->get_active_subscribers();
		$sub_count   = count( $subscribers );

		if ( 0 === $sub_count ) {
			$this->log_execution(
				get_option( 'adnl_email_subject', "[Daily Digest] Today's Top Stories - {date}" ),
				$post_count,
				0,
				'failed',
				__( 'Execution aborted: No active subscribers found in subscriber list.', 'auto-daily-newsletter' )
			);
			return array(
				'status'  => 'failed',
				'message' => __( 'No active subscribers found. Please add or import subscribers in the "Subscribers" tab before sending.', 'auto-daily-newsletter' ),
			);
		}

		// 3. Verify SMTP setup if custom SMTP mode is active
		$mailer_type = get_option( 'adnl_mailer_type', 'smtp' );
		$smtp_host   = get_option( 'adnl_smtp_host', '' );
		if ( 'smtp' === $mailer_type && empty( $smtp_host ) ) {
			// If SMTP host is not yet configured, gracefully fall back to WordPress built-in mailer
			update_option( 'adnl_mailer_type', 'wp_mail' );
			$mailer_type = 'wp_mail';
		}

		// 4. Prepare email subject (supports random subject lines & dynamic news tags)
		$subject = ADNL_Template_Builder::generate_subject( get_option( 'adnl_email_subject' ), $posts_data );

		// 5. Send digest in batches
		$send_result = $mailer->send_digest_to_subscribers( $subscribers, $posts_data, $subject );

		// 6. Record logs
		$this->log_execution(
			$subject,
			$post_count,
			$send_result['sent_count'],
			$send_result['status'],
			$send_result['message']
		);

		// Update last sent timestamp as standard Unix UTC timestamp
		update_option( 'adnl_last_run_timestamp', time() );

		// If this was an automated scheduled run, mark today's date and key as dispatched
		if ( ! $is_manual ) {
			$tz = self::get_timezone();
			$now_local = new DateTime( 'now', $tz );
			$time_str  = get_option( 'adnl_schedule_time', '08:00' );
			update_option( 'adnl_last_dispatched_key', $now_local->format( 'Y-m-d' ) . '_' . $time_str );
			update_option( 'adnl_last_dispatched_date', $now_local->format( 'Y-m-d' ) );
		}

		return $send_result;
	}

	/**
	 * Insert execution record into database logs.
	 *
	 * @param string $subject
	 * @param int    $posts_count
	 * @param int    $recipients_count
	 * @param string $status
	 * @param string $message
	 */
	public function log_execution( $subject, $posts_count, $recipients_count, $status, $message = '' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'adnl_logs';

		// Ensure table exists (self-healing)
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			require_once ADNL_PLUGIN_DIR . 'includes/class-activator.php';
			ADNL_Activator::create_tables();
		}

		$wpdb->insert(
			$table_name,
			array(
				'subject'          => sanitize_text_field( $subject ),
				'posts_count'      => intval( $posts_count ),
				'recipients_count' => intval( $recipients_count ),
				'status'           => sanitize_text_field( $status ),
				'message'          => sanitize_textarea_field( $message ),
				'created_at'       => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s' )
		);
	}
}
