<?php
/**
 * Mailer: Dispatches emails via WordPress wp_mail or custom SMTP/API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ADNL_Mailer {

	public $last_mail_error    = '';
	public $current_plain_body = '';

	public function __construct() {
		// Hook into PHPMailer when custom SMTP is enabled
		if ( 'smtp' === get_option( 'adnl_mailer_type', 'smtp' ) ) {
			add_action( 'phpmailer_init', array( $this, 'configure_phpmailer_smtp' ) );
		}
		add_action( 'wp_mail_failed', array( $this, 'capture_wp_mail_failed' ) );
	}

	/**
	 * Capture wp_mail failure message for detailed reporting.
	 *
	 * @param WP_Error $wp_error
	 */
	public function capture_wp_mail_failed( $wp_error ) {
		if ( is_wp_error( $wp_error ) ) {
			$this->last_mail_error = $wp_error->get_error_message();
		}
	}

	/**
	 * Configure PHPMailer for custom SMTP transmission.
	 *
	 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer
	 */
	public function configure_phpmailer_smtp( $phpmailer ) {
		// Set plain-text alternative if available
		if ( ! empty( $this->current_plain_body ) ) {
			$phpmailer->AltBody = $this->current_plain_body;
		}

		$host       = get_option( 'adnl_smtp_host', '' );
		$port       = intval( get_option( 'adnl_smtp_port', 587 ) );
		$encryption = get_option( 'adnl_smtp_encryption', 'tls' );
		$auth       = (bool) get_option( 'adnl_smtp_auth', 1 );
		$user       = get_option( 'adnl_smtp_user', '' );
		$pass       = get_option( 'adnl_smtp_pass', '' );

		if ( empty( $host ) ) {
			return;
		}

		// Clean pass (remove spaces from Google App Passwords)
		$pass = str_replace( ' ', '', $pass );

		$phpmailer->isSMTP();
		$phpmailer->Host       = $host;
		$phpmailer->Port       = $port;
		$phpmailer->SMTPAuth   = $auth;
		$phpmailer->Username   = $user;
		$phpmailer->Password   = $pass;
		$phpmailer->Timeout    = 15;

		if ( 'tls' === $encryption ) {
			$phpmailer->SMTPSecure = 'tls';
		} elseif ( 'ssl' === $encryption ) {
			$phpmailer->SMTPSecure = 'ssl';
		} else {
			$phpmailer->SMTPSecure = '';
			$phpmailer->SMTPAutoTLS = false;
		}

		// Allow self-signed or internal CA certs
		$phpmailer->SMTPOptions = array(
			'ssl' => array(
				'verify_peer'       => false,
				'verify_peer_name'  => false,
				'allow_self_signed' => true,
			),
		);

		// Configure from header
		$from_name  = get_option( 'adnl_from_name', get_bloginfo( 'name' ) );
		$from_email = get_option( 'adnl_from_email', get_bloginfo( 'admin_email' ) );

		if ( ! empty( $from_email ) && is_email( $from_email ) ) {
			$phpmailer->setFrom( $from_email, $from_name, false );
		}
	}

	/**
	 * Send newsletter digest to a list of subscribers in batches.
	 *
	 * @param array  $subscribers Array of subscriber objects.
	 * @param array  $posts       Array of formatted post data.
	 * @param string $subject     Email subject line.
	 * @return array Execution report.
	 */
	public function send_digest_to_subscribers( $subscribers, $posts, $subject ) {
		$template_builder = new ADNL_Template_Builder();
		$base_html        = $template_builder->build_digest_html( $posts );
		$base_plain       = $template_builder->build_digest_plain_text( $posts );

		$batch_size  = intval( get_option( 'adnl_batch_size', 30 ) );
		$batch_delay = intval( get_option( 'adnl_batch_delay', 1 ) );

		$sent_count   = 0;
		$failed_count = 0;
		$errors       = array();

		// Split into manageable batches
		$batches = array_chunk( $subscribers, max( 1, $batch_size ) );

		foreach ( $batches as $batch_index => $batch ) {
			foreach ( $batch as $subscriber ) {
				$personalized_html  = $template_builder->personalize_html( $base_html, $subscriber );
				$personalized_plain = $template_builder->personalize_plain_text( $base_plain, $subscriber );

				$result = $this->send_single_email(
					$subscriber->email,
					$subject,
					$personalized_html,
					$subscriber->token ?? '',
					$personalized_plain
				);

				if ( ! empty( $result['success'] ) ) {
					$sent_count++;
				} else {
					$failed_count++;
					$err_text = ! empty( $result['message'] ) ? $result['message'] : 'Mail delivery error';
					$errors[] = sprintf( '%s (%s)', sanitize_email( $subscriber->email ), $err_text );
				}
			}

			// Delay between batches to respect rate limits
			if ( $batch_delay > 0 && $batch_index < count( $batches ) - 1 ) {
				sleep( $batch_delay );
			}
		}

		$status = 'success';
		if ( 0 === $sent_count && $failed_count > 0 ) {
			$status = 'failed';
		} elseif ( $failed_count > 0 ) {
			$status = 'partial';
		}

		$message = sprintf(
			__( 'Sent to %d subscriber(s). Failures: %d.', 'auto-daily-newsletter' ),
			$sent_count,
			$failed_count
		);

		if ( ! empty( $errors ) ) {
			$message .= ' ' . implode( '; ', array_slice( $errors, 0, 3 ) );
		}

		return array(
			'status'       => $status,
			'sent_count'   => $sent_count,
			'failed_count' => $failed_count,
			'message'      => $message,
		);
	}

	/**
	 * Send single email via configured mailer (SMTP socket or wp_mail).
	 *
	 * @param string $to
	 * @param string $subject
	 * @param string $html_body
	 * @param string $token
	 * @param string $plain_body
	 * @return array
	 */
	public function send_single_email( $to, $subject, $html_body, $token = '', $plain_body = '' ) {
		$mailer_type = get_option( 'adnl_mailer_type', 'smtp' );
		$from_name   = get_option( 'adnl_from_name', get_bloginfo( 'name' ) );
		$from_email  = get_option( 'adnl_from_email', get_bloginfo( 'admin_email' ) );

		$smtp_host = get_option( 'adnl_smtp_host', '' );
		$smtp_user = get_option( 'adnl_smtp_user', '' );

		// If Gmail, align From address to match authenticated user to avoid 553 Sender Rejected
		if ( ( strpos( $smtp_user, '@gmail.com' ) !== false || strpos( $smtp_host, 'gmail.com' ) !== false ) && ! empty( $smtp_user ) ) {
			$from_email = $smtp_user;
		}

		$unsub_url = '';
		if ( ! empty( $token ) ) {
			$unsub_url = add_query_arg( array( 'adnl_action' => 'unsubscribe', 'token' => $token ), home_url( '/' ) );
		}

		// Store plain body for PHPMailer AltBody hook
		$this->current_plain_body = $plain_body;

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', esc_html( $from_name ), sanitize_email( $from_email ) ),
			sprintf( 'Reply-To: %s <%s>', esc_html( $from_name ), sanitize_email( $from_email ) ),
		);
		if ( ! empty( $unsub_url ) ) {
			$headers[] = sprintf( 'List-Unsubscribe: <%s>', esc_url_raw( $unsub_url ) );
			$headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
		}

		$smtp_res = null;

		// 1. If custom SMTP is selected and host is provided, try direct socket
		if ( 'smtp' === $mailer_type && ! empty( $smtp_host ) ) {
			$port       = intval( get_option( 'adnl_smtp_port', 587 ) );
			$encryption = get_option( 'adnl_smtp_encryption', 'tls' );

			// Auto-protect Gmail on Hostinger (switch 587 to 465 SSL)
			if ( strpos( $smtp_host, 'gmail.com' ) !== false && 587 === $port ) {
				$port       = 465;
				$encryption = 'ssl';
			}

			$config = array(
				'host'            => $smtp_host,
				'port'            => $port,
				'encryption'      => $encryption,
				'auth'            => get_option( 'adnl_smtp_auth', 1 ),
				'username'        => $smtp_user,
				'password'        => get_option( 'adnl_smtp_pass', '' ),
				'from_name'       => $from_name,
				'from_email'      => $from_email,
				'reply_to'        => $from_email,
				'unsubscribe_url' => $unsub_url,
				'plain_body'      => $plain_body,
			);
			$smtp_res = ADNL_SMTP_Transport::send( $to, $subject, $html_body, $config );
			if ( ! empty( $smtp_res['success'] ) ) {
				return $smtp_res;
			}
		}

		// 2. Redundant Delivery via WordPress core wp_mail()
		$this->last_mail_error = '';
		$sent = wp_mail( $to, $subject, $html_body, $headers );
		if ( $sent ) {
			return array(
				'success' => true,
				'message' => 'Email delivered successfully via wp_mail.',
			);
		}

		// 3. Ultimate Fallback: Try native PHP mail() if custom SMTP failed
		if ( 'smtp' === $mailer_type ) {
			remove_action( 'phpmailer_init', array( $this, 'configure_phpmailer_smtp' ) );
			$native_sent = wp_mail( $to, $subject, $html_body, $headers );
			add_action( 'phpmailer_init', array( $this, 'configure_phpmailer_smtp' ) );

			if ( $native_sent ) {
				return array(
					'success' => true,
					'message' => 'Email delivered successfully via server default mail.',
				);
			}
		}

		$err_text = ! empty( $this->last_mail_error ) ? $this->last_mail_error : ( ! empty( $smtp_res['message'] ) ? $smtp_res['message'] : 'Mail delivery failed. Please check your SMTP credentials or mail configuration.' );
		return array(
			'success' => false,
			'message' => $err_text,
		);
	}

	/**
	 * Send test email for diagnostics.
	 *
	 * @param string $recipient_email
	 * @return bool|WP_Error
	 */
	public function send_test_email( $recipient_email ) {
		if ( ! is_email( $recipient_email ) ) {
			return new WP_Error( 'invalid_email', __( 'Invalid test email address.', 'auto-daily-newsletter' ) );
		}

		$post_collector   = new ADNL_Post_Collector();
		$template_builder = new ADNL_Template_Builder();

		$posts      = $post_collector->get_latest_news_posts();
		$html       = $template_builder->build_digest_html( $posts );
		$plain_text = $template_builder->build_digest_plain_text( $posts );

		// Mock subscriber object for test preview
		$mock_subscriber = (object) array(
			'email' => $recipient_email,
			'name'  => 'Test Recipient',
			'token' => 'sample-test-token-12345',
		);

		$personalized_html  = $template_builder->personalize_html( $html, $mock_subscriber );
		$personalized_plain = $template_builder->personalize_plain_text( $plain_text, $mock_subscriber );

		// Spam-safe subject line (avoids promotional filter flags)
		$subject = sprintf( '%s - Daily Newsletter Preview', get_bloginfo( 'name' ) );

		$result = $this->send_single_email(
			$recipient_email,
			$subject,
			$personalized_html,
			'sample-test-token-12345',
			$personalized_plain
		);

		if ( ! $result['success'] ) {
			return new WP_Error( 'mail_send_error', $result['message'] );
		}

		return true;
	}
}
