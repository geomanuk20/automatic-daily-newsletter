<?php
/**
 * Localhost Interactive Demo Server Runner
 */

require_once __DIR__ . '/mock-wp.php';

// Serve static assets if requested
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url( $request_uri, PHP_URL_PATH );

if ( preg_match( '/\.(css|js|png|jpg|jpeg|svg)$/', $path ) ) {
	$file_path = dirname( __DIR__ ) . $path;
	if ( file_exists( $file_path ) ) {
		$ext = pathinfo( $file_path, PATHINFO_EXTENSION );
		$mimes = array(
			'css' => 'text/css',
			'js'  => 'application/javascript',
			'png' => 'image/png',
			'jpg' => 'image/jpeg',
			'svg' => 'image/svg+xml',
		);
		header( 'Content-Type: ' . ( $mimes[$ext] ?? 'text/plain' ) );
		readfile( $file_path );
		exit;
	}
}

// Reset demo data trigger
if ( isset( $_GET['reset_demo'] ) ) {
	unlink( __DIR__ . '/demo_data.json' );
	header( 'Location: /demo/index.php' );
	exit;
}

// CSV Export trigger
if ( isset( $_GET['action'] ) && 'adnl_export_csv' === $_GET['action'] ) {
	$db_data = adnl_get_demo_data();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=subscribers-' . date( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'ID', 'Email', 'Name', 'Status', 'Subscribed Date' ) );
	foreach ( $db_data['subscribers'] as $s ) {
		fputcsv( $out, array( $s['id'], $s['email'], $s['name'], $s['status'], $s['created_at'] ) );
	}
	fclose( $out );
	exit;
}

// Download Sample CSV template
if ( isset( $_GET['action'] ) && 'adnl_download_sample_csv' === $_GET['action'] ) {
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=subscribers-sample-template.csv' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'Email', 'Name', 'Status' ) );
	fputcsv( $out, array( 'alex@example.com', 'Alex Morgan', 'active' ) );
	fputcsv( $out, array( 'sarah@example.com', 'Sarah Connor', 'active' ) );
	fputcsv( $out, array( 'reader@news.org', 'Daily Reader', 'active' ) );
	fclose( $out );
	exit;
}

// Clear logs in demo
if ( isset( $_GET['action'] ) && 'adnl_clear_logs' === $_GET['action'] ) {
	$db = adnl_get_demo_data();
	$db['logs'] = array();
	adnl_save_demo_data( $db );
	header( 'Location: /demo/index.php?tab=logs&logs_cleared=1' );
	exit;
}

// Handle CSV Import in Demo
if ( isset( $_POST['adnl_import_subscribers'] ) ) {
	$imported = 0;
	$skipped  = 0;
	$invalid  = 0;
	$total    = 0;

	if ( ! empty( $_FILES['adnl_csv_file']['tmp_name'] ) && is_uploaded_file( $_FILES['adnl_csv_file']['tmp_name'] ) ) {
		$db = adnl_get_demo_data();
		$handle = fopen( $_FILES['adnl_csv_file']['tmp_name'], 'r' );
		if ( $handle ) {
			$header = fgetcsv( $handle );
			while ( ( $row = fgetcsv( $handle ) ) !== false ) {
				$total++;
				$email = sanitize_email( $row[0] ?? '' );
				$name  = sanitize_text_field( $row[1] ?? '' );
				if ( ! is_email( $email ) ) {
					$invalid++;
					continue;
				}
				$exists = false;
				foreach ( $db['subscribers'] as $s ) {
					if ( strtolower( $s['email'] ) === strtolower( $email ) ) {
						$exists = true;
						break;
					}
				}
				if ( $exists ) {
					$skipped++;
				} else {
					$db['subscribers'][] = array(
						'id'         => count( $db['subscribers'] ) + 1,
						'email'      => $email,
						'name'       => $name,
						'status'     => 'active',
						'token'      => md5( $email . time() ),
						'created_at' => date( 'Y-m-d H:i:s' ),
					);
					$imported++;
				}
			}
			fclose( $handle );
			adnl_save_demo_data( $db );
		}
	}
	header( "Location: /demo/index.php?tab=subscribers&imported={$imported}&skipped={$skipped}&invalid={$invalid}&total={$total}" );
	exit;
}

// Unsubscribe action
if ( isset( $_GET['adnl_action'] ) && 'unsubscribe' === $_GET['adnl_action'] ) {
	$token = sanitize_text_field( $_GET['token'] ?? '' );
	$db = adnl_get_demo_data();
	foreach ( $db['subscribers'] as &$sub ) {
		if ( $sub['token'] === $token ) {
			$sub['status'] = 'unsubscribed';
		}
	}
	adnl_save_demo_data( $db );
	setcookie( 'adnl_subscribed', '', time() - 3600, '/' );
	include ADNL_PLUGIN_DIR . 'templates/unsubscribe-page.php';
	exit;
}

require_once ADNL_PLUGIN_DIR . 'includes/class-smtp-transport.php';

// AJAX router
if ( isset( $_POST['action'] ) ) {
	$action = sanitize_text_field( $_POST['action'] );

	if ( 'adnl_send_test_email' === $action ) {
		$email = sanitize_email( $_POST['test_email'] ?? '' );
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'Invalid email address.' ) );
		}

		$posts = adnl_get_mock_news_posts();
		$site_name      = get_bloginfo( 'name' );
		$site_url       = home_url();
		$current_date   = wp_date( get_option( 'date_format', 'F j, Y' ) );
		$preheader_text = get_option( 'adnl_preheader_text', "Here are today's top stories and news updates." );
		$primary_color  = get_option( 'adnl_primary_color', '#2563eb' );
		$show_primary_tip = (bool) get_option( 'adnl_show_primary_tip', 1 );

		ob_start();
		include ADNL_PLUGIN_DIR . 'templates/email-digest.php';
		$html = ob_get_clean();

		$unsub_url = 'http://127.0.0.1:8000/demo/index.php?adnl_action=unsubscribe&token=test_token';

		$html = str_replace(
			array( '{{UNSUBSCRIBE_URL}}', '{{SUBSCRIBER_NAME}}', '{{SITE_NAME}}' ),
			array( $unsub_url, 'Test Recipient', $site_name ),
			$html
		);

		$builder    = new ADNL_Template_Builder();
		$plain_body = $builder->build_digest_plain_text( $posts );
		$plain_body = str_replace(
			array( '{{UNSUBSCRIBE_URL}}', '{{SUBSCRIBER_NAME}}', '{{SITE_NAME}}' ),
			array( $unsub_url, 'Test Recipient', $site_name ),
			$plain_body
		);

		$subject = sprintf( '%s - Daily Newsletter Preview', $site_name );

		$smtp_host = get_option( 'adnl_smtp_host' );
		$smtp_user = get_option( 'adnl_smtp_user' );
		$smtp_pass = get_option( 'adnl_smtp_pass' );

		if ( empty( $smtp_host ) || empty( $smtp_pass ) ) {
			wp_send_json_error( array(
				'message' => 'SMTP is not configured yet! Please go to the "SMTP & Delivery" tab and enter your SMTP Host, Username, and Password (or Gmail App Password).',
			) );
		}

		$config = array(
			'host'            => $smtp_host,
			'port'            => get_option( 'adnl_smtp_port', 587 ),
			'encryption'      => get_option( 'adnl_smtp_encryption', 'tls' ),
			'auth'            => get_option( 'adnl_smtp_auth', 1 ),
			'username'        => $smtp_user,
			'password'        => $smtp_pass,
			'from_name'       => get_option( 'adnl_from_name', $site_name ),
			'from_email'      => get_option( 'adnl_from_email', 'newsletter@example.com' ),
			'reply_to'        => get_option( 'adnl_from_email', 'newsletter@example.com' ),
			'unsubscribe_url' => $unsub_url,
			'plain_body'      => $plain_body,
		);

		$send_result = ADNL_SMTP_Transport::send( $email, $subject, $html, $config );

		global $wpdb;
		$wpdb->insert( 'adnl_logs', array(
			'subject'          => $subject,
			'posts_count'      => count( $posts ),
			'recipients_count' => 1,
			'status'           => $send_result['success'] ? 'success' : 'failed',
			'message'          => $send_result['message'],
			'created_at'       => date( 'Y-m-d H:i:s' ),
		) );

		if ( $send_result['success'] ) {
			wp_send_json_success( array(
				'message' => 'Real test email sent to ' . $email . ' via ' . $smtp_host . '! Please check your inbox (and Spam folder).',
			) );
		} else {
			wp_send_json_error( array(
				'message' => 'SMTP Delivery Failed: ' . $send_result['message'] . '. Please verify your SMTP credentials in the "SMTP & Delivery" tab.',
			) );
		}
	}

	if ( 'adnl_manual_send' === $action ) {
		$posts = adnl_get_mock_news_posts();
		$count_posts = intval( get_option( 'adnl_posts_count', 7 ) );
		$posts = array_slice( $posts, 0, $count_posts );

		$db = adnl_get_demo_data();
		$active_subs = array();
		foreach ( $db['subscribers'] as $sub ) {
			if ( 'active' === $sub['status'] ) $active_subs[] = $sub;
		}

		if ( empty( $active_subs ) ) {
			wp_send_json_error( array( 'message' => 'No active subscribers to send to.' ) );
		}

		$subject = ADNL_Template_Builder::generate_subject( get_option( 'adnl_email_subject' ), $posts );

		$smtp_host = get_option( 'adnl_smtp_host' );
		$smtp_pass = get_option( 'adnl_smtp_pass' );

		if ( empty( $smtp_host ) || empty( $smtp_pass ) ) {
			wp_send_json_error( array(
				'message' => 'SMTP is not configured yet! Please enter your SMTP Host, Username, and Password in the "SMTP & Delivery" tab before sending to subscribers.',
			) );
		}

		$config = array(
			'host'       => $smtp_host,
			'port'       => get_option( 'adnl_smtp_port', 587 ),
			'encryption' => get_option( 'adnl_smtp_encryption', 'tls' ),
			'auth'       => get_option( 'adnl_smtp_auth', 1 ),
			'username'   => get_option( 'adnl_smtp_user' ),
			'password'   => $smtp_pass,
			'from_name'  => get_option( 'adnl_from_name', get_bloginfo( 'name' ) ),
			'from_email' => get_option( 'adnl_from_email', 'newsletter@example.com' ),
		);

		$site_name      = get_bloginfo( 'name' );
		$site_url       = home_url();
		$current_date   = wp_date( get_option( 'date_format', 'F j, Y' ) );
		$preheader_text = get_option( 'adnl_preheader_text', "Here are today's top stories and news updates." );
		$primary_color  = get_option( 'adnl_primary_color', '#2563eb' );

		ob_start();
		include ADNL_PLUGIN_DIR . 'templates/email-digest.php';
		$base_html = ob_get_clean();

		$sent_count = 0;
		$failed_count = 0;
		$last_error = '';

		foreach ( $active_subs as $sub ) {
			$unsub_url = 'http://127.0.0.1:8000/demo/index.php?adnl_action=unsubscribe&token=' . $sub['token'];
			$html = str_replace(
				array( '{{UNSUBSCRIBE_URL}}', '{{SUBSCRIBER_NAME}}', '{{SITE_NAME}}' ),
				array( $unsub_url, $sub['name'] ? $sub['name'] : 'Subscriber', $site_name ),
				$base_html
			);

			$res = ADNL_SMTP_Transport::send( $sub['email'], $subject, $html, $config );
			if ( $res['success'] ) {
				$sent_count++;
			} else {
				$failed_count++;
				$last_error = $res['message'];
			}
		}

		global $wpdb;
		$status = ( $sent_count > 0 && 0 === $failed_count ) ? 'success' : ( $sent_count > 0 ? 'partial' : 'failed' );
		$wpdb->insert( 'adnl_logs', array(
			'subject'          => $subject,
			'posts_count'      => count( $posts ),
			'recipients_count' => $sent_count,
			'status'           => $status,
			'message'          => sprintf( 'Sent to %d subscribers. Failures: %d. %s', $sent_count, $failed_count, $last_error ),
			'created_at'       => date( 'Y-m-d H:i:s' ),
		) );

		update_option( 'adnl_last_run_timestamp', time() );

		if ( $sent_count > 0 ) {
			wp_send_json_success( array(
				'message' => sprintf( 'Daily digest sent via SMTP to %d subscribers! (Failures: %d)', $sent_count, $failed_count ),
			) );
		} else {
			wp_send_json_error( array(
				'message' => 'Failed to send emails via SMTP: ' . $last_error,
			) );
		}
	}

	if ( 'adnl_preview_digest' === $action ) {
		$posts = adnl_get_mock_news_posts();
		$count_posts = intval( get_option( 'adnl_posts_count', 7 ) );
		$posts = array_slice( $posts, 0, $count_posts );

		$site_name      = get_bloginfo( 'name' );
		$site_url       = home_url();
		$current_date   = wp_date( get_option( 'date_format', 'F j, Y' ) );
		$preheader_text = get_option( 'adnl_preheader_text', "Here are today's top stories and news updates." );
		$site_logo      = ! empty( $_POST['site_logo'] ) ? sanitize_text_field( $_POST['site_logo'] ) : get_option( 'adnl_site_logo', '' );
		$logo_height    = isset( $_POST['logo_height'] ) ? intval( $_POST['logo_height'] ) : intval( get_option( 'adnl_logo_height', 70 ) );
		$raw_title      = ! empty( $_POST['header_title'] ) ? sanitize_text_field( $_POST['header_title'] ) : get_option( 'adnl_header_title', '' );
		$header_title   = ! empty( $raw_title ) ? str_replace( '{site_name}', $site_name, $raw_title ) : $site_name . ' Newsletter';
		$raw_footer_text  = get_option( 'adnl_footer_text', 'You received this email because you subscribed to daily news updates on {site_name}.' );
		$footer_text      = ! empty( $raw_footer_text ) ? str_replace( '{site_name}', $site_name, $raw_footer_text ) : '';
		$current_year     = wp_date( 'Y' );
		$raw_copyright    = get_option( 'adnl_footer_copyright', '© {year} {site_name}. All rights reserved.' );
		$footer_copyright = str_replace( array( '{year}', '{site_name}' ), array( $current_year, $site_name ), $raw_copyright );
		$footer_bg_color  = get_option( 'adnl_footer_bg_color', '#f8fafc' );

		ob_start();
		include ADNL_PLUGIN_DIR . 'templates/email-digest.php';
		$html = ob_get_clean();

		$html = str_replace(
			array( '{{UNSUBSCRIBE_URL}}', '{{SUBSCRIBER_NAME}}', '{{SITE_NAME}}' ),
			array( 'http://127.0.0.1:8000/demo/index.php?adnl_action=unsubscribe&token=token_alex_847193758291', 'Alex Chen', $site_name ),
			$html
		);

		$subject = ADNL_Template_Builder::generate_subject( get_option( 'adnl_email_subject' ), $posts );
		wp_send_json_success( array( 'html' => $html, 'post_count' => count( $posts ), 'subject' => $subject ) );
	}

	if ( 'adnl_admin_add_subscriber' === $action ) {
		$email = sanitize_email( $_POST['email'] ?? '' );
		$name  = sanitize_text_field( $_POST['name'] ?? '' );
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'Invalid email address.' ) );
		}

		global $wpdb;
		$token = 'token_' . bin2hex( random_bytes( 10 ) );
		$wpdb->insert( 'adnl_subscribers', array(
			'email'      => $email,
			'name'       => $name,
			'token'      => $token,
			'status'     => 'active',
			'created_at' => date( 'Y-m-d H:i:s' ),
		) );

		wp_send_json_success( array( 'message' => 'Subscriber added.' ) );
	}

	if ( 'adnl_admin_delete_subscriber' === $action ) {
		$id = intval( $_POST['subscriber_id'] ?? 0 );
		global $wpdb;
		$wpdb->delete( 'adnl_subscribers', array( 'id' => $id ) );
		wp_send_json_success( array( 'message' => 'Subscriber deleted.' ) );
	}

	if ( 'adnl_subscribe' === $action ) {
		$email = sanitize_email( $_POST['email'] ?? '' );
		$name  = sanitize_text_field( $_POST['name'] ?? '' );
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'Please enter a valid email address.' ) );
		}

		global $wpdb;
		$db_data = adnl_get_demo_data();
		foreach ( $db_data['subscribers'] as $s ) {
			if ( strtolower( $s['email'] ) === strtolower( $email ) && 'active' === $s['status'] ) {
				setcookie( 'adnl_subscribed', '1', time() + ( 365 * 86400 ), '/' );
				wp_send_json_error( array(
					'code'    => 'already_subscribed',
					'message' => sprintf( '%s is already subscribed to our newsletter!', $email ),
				) );
			}
		}

		$token = 'token_' . bin2hex( random_bytes( 10 ) );
		$wpdb->insert( 'adnl_subscribers', array(
			'email'      => $email,
			'name'       => $name,
			'token'      => $token,
			'status'     => 'active',
			'created_at' => date( 'Y-m-d H:i:s' ),
		) );

		setcookie( 'adnl_subscribed', '1', time() + ( 365 * 86400 ), '/' );
		wp_send_json_success( array(
			'message' => 'Thank you! You have successfully subscribed. You will receive our daily digest at the scheduled dispatch time.',
		) );
	}
}

// Handle Settings Form POST
if ( isset( $_POST['adnl_save_settings_nonce'] ) ) {
	$active_tab = sanitize_text_field( $_POST['active_tab'] ?? '' );

	if ( 'settings' === $active_tab || 'schedule' === $active_tab || isset( $_POST['adnl_posts_count'] ) ) {
		update_option( 'adnl_enabled', isset( $_POST['adnl_enabled'] ) ? 1 : 0 );
		update_option( 'adnl_selection_mode', sanitize_text_field( $_POST['adnl_selection_mode'] ?? 'auto' ) );
		$sel_ids = isset( $_POST['adnl_selected_post_ids'] ) && is_array( $_POST['adnl_selected_post_ids'] ) ? array_map( 'intval', $_POST['adnl_selected_post_ids'] ) : array();
		update_option( 'adnl_selected_post_ids', $sel_ids );
		update_option( 'adnl_posts_count', intval( $_POST['adnl_posts_count'] ?? 7 ) );
		update_option( 'adnl_lookback_hours', intval( $_POST['adnl_lookback_hours'] ?? 24 ) );
		update_option( 'adnl_fallback_behavior', sanitize_text_field( $_POST['adnl_fallback_behavior'] ?? 'latest' ) );
		update_option( 'adnl_schedule_time', sanitize_text_field( $_POST['adnl_schedule_time'] ?? '08:00' ) );
		update_option( 'adnl_timezone', sanitize_text_field( $_POST['adnl_timezone'] ?? '' ) );
		update_option( 'adnl_email_subject', sanitize_textarea_field( $_POST['adnl_email_subject'] ?? "[Daily Digest] Today's Top Stories - {date}" ) );
		update_option( 'adnl_preheader_text', sanitize_text_field( $_POST['adnl_preheader_text'] ?? '' ) );
		update_option( 'adnl_header_title', sanitize_text_field( $_POST['adnl_header_title'] ?? '' ) );
		update_option( 'adnl_site_logo', sanitize_text_field( $_POST['adnl_site_logo'] ?? '' ) );
		update_option( 'adnl_logo_height', intval( $_POST['adnl_logo_height'] ?? 70 ) );
		update_option( 'adnl_primary_color', sanitize_hex_color( $_POST['adnl_primary_color'] ?? '#e11d48' ) );
		update_option( 'adnl_footer_text', sanitize_text_field( $_POST['adnl_footer_text'] ?? '' ) );
		update_option( 'adnl_footer_copyright', sanitize_text_field( $_POST['adnl_footer_copyright'] ?? '' ) );
		update_option( 'adnl_footer_bg_color', sanitize_hex_color( $_POST['adnl_footer_bg_color'] ?? '#f8fafc' ) );
		update_option( 'adnl_popup_enabled', isset( $_POST['adnl_popup_enabled'] ) ? 1 : 0 );
		update_option( 'adnl_popup_show_logo', isset( $_POST['adnl_popup_show_logo'] ) ? 1 : 0 );
		update_option( 'adnl_popup_title', sanitize_text_field( wp_unslash( $_POST['adnl_popup_title'] ?? 'HI THERE!' ) ) );
		update_option( 'adnl_popup_message', sanitize_textarea_field( html_entity_decode( wp_unslash( $_POST['adnl_popup_message'] ?? '' ), ENT_QUOTES, 'UTF-8' ) ) );
		update_option( 'adnl_popup_button', sanitize_text_field( wp_unslash( $_POST['adnl_popup_button'] ?? 'SUBSCRIBE' ) ) );
		update_option( 'adnl_popup_btn_color', sanitize_hex_color( $_POST['adnl_popup_btn_color'] ?? '#f43f5e' ) );
		update_option( 'adnl_popup_placeholder', sanitize_text_field( $_POST['adnl_popup_placeholder'] ?? 'Email' ) );
		update_option( 'adnl_popup_image', sanitize_text_field( $_POST['adnl_popup_image'] ?? '' ) );
		update_option( 'adnl_popup_delay', intval( $_POST['adnl_popup_delay'] ?? 3 ) );
		update_option( 'adnl_popup_frequency', intval( $_POST['adnl_popup_frequency'] ?? 30 ) );
		update_option( 'adnl_popup_logo_height', intval( $_POST['adnl_popup_logo_height'] ?? 55 ) );
		update_option( 'adnl_popup_position', sanitize_text_field( $_POST['adnl_popup_position'] ?? 'bottom-left' ) );
	} elseif ( 'smtp' === $active_tab || isset( $_POST['adnl_smtp_host'] ) ) {
		update_option( 'adnl_from_name', sanitize_text_field( $_POST['adnl_from_name'] ?? 'Global News Daily' ) );
		update_option( 'adnl_from_email', sanitize_email( $_POST['adnl_from_email'] ?? 'newsletter@globalnews.com' ) );
		update_option( 'adnl_mailer_type', sanitize_text_field( $_POST['adnl_mailer_type'] ?? 'smtp' ) );
		update_option( 'adnl_smtp_host', sanitize_text_field( $_POST['adnl_smtp_host'] ?? '' ) );
		update_option( 'adnl_smtp_port', intval( $_POST['adnl_smtp_port'] ?? 587 ) );
		update_option( 'adnl_smtp_encryption', sanitize_text_field( $_POST['adnl_smtp_encryption'] ?? 'tls' ) );
		update_option( 'adnl_smtp_auth', isset( $_POST['adnl_smtp_auth'] ) ? 1 : 0 );
		update_option( 'adnl_smtp_user', sanitize_text_field( $_POST['adnl_smtp_user'] ?? '' ) );
		if ( ! empty( $_POST['adnl_smtp_pass'] ) ) {
			update_option( 'adnl_smtp_pass', sanitize_text_field( $_POST['adnl_smtp_pass'] ) );
		}
		update_option( 'adnl_batch_size', intval( $_POST['adnl_batch_size'] ?? 30 ) );
		update_option( 'adnl_batch_delay', intval( $_POST['adnl_batch_delay'] ?? 1 ) );
	}

	header( 'Location: /demo/index.php?tab=' . sanitize_text_field( $_POST['active_tab'] ?? 'dashboard' ) . '&saved=1' );
	exit;
}

$view = $_GET['view'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Auto Daily Newsletter - Localhost Interactive Demo</title>
	<!-- Standard WordPress Dashicons & Common styles -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dashicons/0.9.0/css/dashicons.min.css">
	<link rel="stylesheet" href="/assets/css/admin.css">
	<link rel="stylesheet" href="/assets/css/frontend.css">
	<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
	<script>
		var adnl_admin_obj = {
			ajax_url: '/demo/index.php',
			nonce: 'demo_nonce_adnl_admin_nonce',
			strings: {
				confirm_manual_send: "Are you sure you want to send today's digest to all active subscribers right now?",
				confirm_delete_sub: "Are you sure you want to delete this subscriber?",
				sending: "Sending...",
				success: "Success!",
				error: "An error occurred."
			}
		};
		var adnl_ajax_obj = {
			ajax_url: '/demo/index.php',
			nonce: 'demo_nonce_adnl_subscribe_nonce'
		};
	</script>
	<script src="/assets/js/admin.js"></script>
	<script src="/assets/js/frontend.js"></script>
	<style>
		/* Demo Top Switcher Bar */
		.demo-bar {
			background: #0f172a;
			color: #ffffff;
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 10px 24px;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			font-size: 13px;
			border-bottom: 2px solid #2563eb;
			position: sticky;
			top: 0;
			z-index: 99999;
		}
		.demo-brand {
			font-weight: 700;
			display: flex;
			align-items: center;
			gap: 8px;
			letter-spacing: 0.5px;
		}
		.demo-brand span.tag {
			background: #2563eb;
			color: #fff;
			font-size: 10px;
			padding: 2px 6px;
			border-radius: 4px;
		}
		.demo-nav {
			display: flex;
			gap: 8px;
		}
		.demo-nav a {
			color: #cbd5e1;
			text-decoration: none;
			padding: 6px 14px;
			border-radius: 6px;
			font-weight: 500;
			transition: all 0.2s;
		}
		.demo-nav a:hover, .demo-nav a.active {
			background: rgba(255,255,255,0.15);
			color: #ffffff;
		}
		.demo-nav a.active {
			background: #2563eb;
		}
		.demo-reset-btn {
			color: #94a3b8;
			text-decoration: underline;
			font-size: 12px;
		}

		/* Mock WordPress Admin Shell */
		body {
			margin: 0;
			padding: 0;
			background: #f0f0f1;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
		}
		.wp-admin-shell {
			display: flex;
			min-height: calc(100vh - 46px);
		}
		.wp-sidebar {
			width: 170px;
			background: #1d2327;
			color: #ffffff;
			flex-shrink: 0;
			padding-top: 15px;
		}
		.wp-sidebar-item {
			display: flex;
			align-items: center;
			gap: 8px;
			padding: 10px 16px;
			color: #c3c4c7;
			font-size: 13px;
			text-decoration: none;
		}
		.wp-sidebar-item:hover, .wp-sidebar-item.active {
			background: #2271b1;
			color: #ffffff;
		}
		.wp-main-content {
			flex: 1;
			padding: 20px 30px;
			background: #f0f0f1;
		}

		/* Form element resets */
		.regular-text { width: 25em; padding: 6px 10px; border: 1px solid #8c8f94; border-radius: 4px; }
		.large-text { width: 95%; max-width: 600px; padding: 6px 10px; border: 1px solid #8c8f94; border-radius: 4px; }
		.small-text { width: 70px; padding: 6px 8px; border: 1px solid #8c8f94; border-radius: 4px; }
		.form-table th { width: 220px; text-align: left; padding: 16px 10px 16px 0; vertical-align: top; font-weight: 600; color: #1d2327; font-size: 14px; }
		.form-table td { padding: 12px 10px; vertical-align: middle; }
		.button { cursor: pointer; display: inline-flex; align-items: center; padding: 6px 14px; border-radius: 4px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1px solid #8c8f94; }
		.button-primary { background: #2271b1; border-color: #2271b1; color: #ffffff; }
		.button-primary:hover { background: #135e96; }
		.button-secondary { background: #f6f7f7; color: #2271b1; border-color: #2271b1; }
		.widefat { width: 100%; border-collapse: collapse; background: #ffffff; border: 1px solid #c3c4c7; }
		.widefat th { text-align: left; padding: 10px 12px; font-weight: 600; border-bottom: 1px solid #c3c4c7; background: #f6f7f7; }
		.widefat td { padding: 10px 12px; border-bottom: 1px solid #f0f0f1; font-size: 13px; }
		.widefat.striped tbody tr:nth-child(odd) { background-color: #fbfbfb; }
		.button-link-delete { color: #b32d2e; background: none; border: none; cursor: pointer; padding: 0; text-decoration: underline; font-size: 13px; }
		.nav-tab-wrapper { border-bottom: 1px solid #c3c4c7; display: flex; gap: 4px; }
		.nav-tab { background: #e5e5e5; color: #50575e; text-decoration: none; padding: 8px 14px; font-size: 14px; font-weight: 600; border-radius: 4px 4px 0 0; border: 1px solid #c3c4c7; border-bottom: none; }
		.nav-tab-active { background: #f0f0f1; color: #000000; border-bottom: 1px solid #f0f0f1; margin-bottom: -1px; }

		/* Frontend preview page */
		.frontend-shell {
			max-width: 900px;
			margin: 40px auto;
			padding: 0 20px;
		}
		.frontend-nav {
			display: flex;
			justify-content: space-between;
			align-items: center;
			border-bottom: 2px solid #0f172a;
			padding-bottom: 16px;
			margin-bottom: 30px;
		}
		.frontend-site-title {
			font-size: 26px;
			font-weight: 800;
			color: #0f172a;
		}
		.article-card {
			background: #ffffff;
			border-radius: 12px;
			padding: 30px;
			box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
			margin-bottom: 30px;
		}
	</style>
</head>
<body>

	<!-- Top Demo Switcher Toolbar -->
	<header class="demo-bar">
		<div class="demo-brand">
			⚡ AUTO DAILY NEWSLETTER <span class="tag">LOCAL DEMO</span>
		</div>
		<nav class="demo-nav">
			<a href="/demo/index.php?view=admin" class="<?php echo 'admin' === $view ? 'active' : ''; ?>">
				⚙️ WP Admin Dashboard
			</a>
			<a href="/demo/index.php?view=frontend" class="<?php echo 'frontend' === $view ? 'active' : ''; ?>">
				🌐 Frontend Site & Subscribe Form
			</a>
			<a href="/demo/index.php?view=email" class="<?php echo 'email' === $view ? 'active' : ''; ?>">
				✉️ Standalone Email Preview
			</a>
			<a href="/demo/index.php?adnl_action=unsubscribe&token=token_alex_847193758291" target="_blank">
				🔕 1-Click Unsubscribe Flow &rarr;
			</a>
		</nav>
		<div>
			<?php if ( 'frontend' === $view ) : ?>
				<span style="color:#94a3b8; font-size: 11px; margin-right: 4px;">Simulate:</span>
				<button type="button" onclick="localStorage.setItem('adnl_user_subscribed','1'); document.cookie='adnl_subscribed=1; path=/;'; location.reload();" style="background:#059669; color:#fff; border:none; padding:3px 8px; border-radius:4px; font-size:11px; cursor:pointer; font-weight:600; margin-right: 4px;">✓ Subscribed (Never Shows)</button>
				<button type="button" onclick="localStorage.removeItem('adnl_user_subscribed'); sessionStorage.removeItem('adnl_popup_dismissed_time'); document.cookie='adnl_subscribed=; path=/; max-age=0;'; location.reload();" style="background:#e11d48; color:#fff; border:none; padding:3px 8px; border-radius:4px; font-size:11px; cursor:pointer; font-weight:600; margin-right: 12px;">✕ Unsubscribed / New (Shows Up)</button>
			<?php endif; ?>
			<a href="/demo/index.php?reset_demo=1" class="demo-reset-btn" onclick="return confirm('Reset all demo data back to default initial state?');">
				Reset Sample Data
			</a>
		</div>
	</header>

	<?php if ( 'admin' === $view ) : ?>
		<!-- VIEW 1: WORDPRESS ADMIN DEMO -->
		<div class="wp-admin-shell">
			<div class="wp-sidebar">
				<div class="wp-sidebar-item"><span class="dashicons dashicons-dashboard"></span> Dashboard</div>
				<div class="wp-sidebar-item"><span class="dashicons dashicons-admin-post"></span> Posts (7)</div>
				<div class="wp-sidebar-item"><span class="dashicons dashicons-admin-media"></span> Media</div>
				<div class="wp-sidebar-item"><span class="dashicons dashicons-admin-page"></span> Pages</div>
				<div class="wp-sidebar-item active"><span class="dashicons dashicons-email-alt2"></span> Daily Newsletter</div>
				<div class="wp-sidebar-item"><span class="dashicons dashicons-admin-plugins"></span> Plugins</div>
				<div class="wp-sidebar-item"><span class="dashicons dashicons-admin-settings"></span> Settings</div>
			</div>
			<div class="wp-main-content">
				<?php
				// Compute variables needed for admin-page.php
				$db = adnl_get_demo_data();
				$total_subscribers = 0;
				foreach ( $db['subscribers'] as $s ) {
					if ( 'active' === $s['status'] ) $total_subscribers++;
				}
				$total_logs = count( $db['logs'] );
				$next_run_formatted = date( 'M j, Y - 8:00 AM', strtotime( 'tomorrow' ) );
				$last_run_ts = get_option( 'adnl_last_run_timestamp' );
				$last_run_formatted = $last_run_ts ? date( 'M j, Y - g:i A', $last_run_ts ) : 'Never';
				$subscribers_table = 'wp_adnl_subscribers';
				$logs_table = 'wp_adnl_logs';

				if ( isset( $_GET['saved'] ) ) {
					echo '<div class="adnl-alert adnl-alert-success" style="margin-bottom:18px;">Settings saved successfully!</div>';
				}

				include ADNL_PLUGIN_DIR . 'templates/admin-page.php';
				?>
			</div>
		</div>

	<?php elseif ( 'frontend' === $view ) : ?>
		<!-- VIEW 2: FRONTEND NEWS SITE DEMO -->
		<?php $site_name = get_bloginfo( 'name' ); ?>
		<div class="frontend-shell" style="max-width: 960px; margin: 30px auto; padding: 0 20px;">

			<!-- Demo Instructions Banner -->
			<div style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%); color: #ffffff; border-radius: 12px; padding: 22px 26px; margin-bottom: 28px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);">
				<div style="display: flex; align-items: flex-start; gap: 16px;">
					<div style="font-size: 32px; line-height: 1;">📰</div>
					<div>
						<h2 style="margin: 0 0 6px 0; font-size: 18px; font-weight: 700; color: #ffffff;">
							Frontend Website Demo &bull; Subscription Form Showcase
						</h2>
						<p style="margin: 0 0 10px 0; font-size: 13px; color: #cbd5e1; line-height: 1.6;">
							This simulates your live public WordPress website where visitors read news articles. The subscription forms below are generated using your plugin's shortcode: <code style="background: rgba(255,255,255,0.15); padding: 2px 6px; border-radius: 4px; color: #93c5fd;">[daily_newsletter_form]</code>.
						</p>
						<div style="background: rgba(255, 255, 255, 0.08); border-left: 3px solid #38bdf8; padding: 8px 12px; border-radius: 4px; font-size: 12px; color: #e0f2fe;">
							👉 <strong>Try it live:</strong> Type your name &amp; email into the form below and click <strong>Subscribe Now</strong>. A real welcome email with today's top stories will be dispatched immediately to your inbox!
						</div>
					</div>
				</div>
			</div>

			<!-- Newspaper Header -->
			<div style="background: #ffffff; border-radius: 10px; padding: 20px 24px; border: 1px solid #e2e8f0; margin-bottom: 24px; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
				<div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0f172a; padding-bottom: 14px; margin-bottom: 12px;">
					<div style="display: flex; align-items: center; gap: 10px;">
						<span style="background: #e11d48; color: #ffffff; font-size: 16px; font-weight: 900; padding: 3px 8px; border-radius: 4px; font-family: sans-serif;">M</span>
						<span style="font-size: 24px; font-weight: 800; color: #004b87; font-family: sans-serif; letter-spacing: -0.5px;">
							<?php echo esc_html( $site_name ); ?><span style="color: #00a0e9;">ONLINE</span>
						</span>
					</div>
					<div style="color: #64748b; font-size: 13px; font-weight: 500;">
						<?php echo date( 'l, F j, Y' ); ?> &bull; Edition #2,841
					</div>
				</div>
				<!-- News Categories Bar -->
				<div style="display: flex; gap: 18px; font-size: 13px; font-weight: 600; color: #475569; overflow-x: auto;">
					<a href="#" style="color: #e11d48; text-decoration: none;">Top News</a>
					<a href="#" style="color: #475569; text-decoration: none;">Kerala</a>
					<a href="#" style="color: #475569; text-decoration: none;">India</a>
					<a href="#" style="color: #475569; text-decoration: none;">World</a>
					<a href="#" style="color: #475569; text-decoration: none;">Tech &amp; AI</a>
					<a href="#" style="color: #475569; text-decoration: none;">Sports</a>
					<a href="#" style="color: #475569; text-decoration: none;">Entertainment</a>
				</div>
			</div>

			<!-- Main 2-Column Content Layout -->
			<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
				
				<!-- Left Column: Main News Article -->
				<div>
					<div class="article-card" style="background: #ffffff; border-radius: 10px; padding: 28px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
						<span style="background: #eff6ff; color: #2563eb; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">
							Technology &amp; Breakthroughs
						</span>
						<h1 style="font-size: 26px; line-height: 1.3; font-weight: 800; color: #0f172a; margin: 12px 0 8px 0; font-family: Georgia, serif;">
							Next-Generation Quantum Chips Achieve Breakthrough in Real-Time Quantum Processing
						</h1>
						<div style="color: #64748b; font-size: 12px; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
							<span>By Elena Rostova</span>
							<span>&bull;</span>
							<span>Published today at 07:45 AM</span>
							<span>&bull;</span>
							<span>4 min read</span>
						</div>
						<img src="https://images.unsplash.com/photo-1635070041078-e363dbe005cb?auto=format&fit=crop&w=1000&q=80" style="width: 100%; border-radius: 8px; max-height: 320px; object-fit: cover; margin-bottom: 18px;" alt="Quantum Chip">
						<p style="font-size: 15px; line-height: 1.7; color: #334155; margin-bottom: 14px;">
							In what researchers are describing as a watershed moment for computing architecture, an international consortium of engineers has achieved sustainable quantum coherence at ambient room temperatures.
						</p>
						<p style="font-size: 15px; line-height: 1.7; color: #334155; margin-bottom: 14px;">
							The scalable 1,000-qubit processor operated without high-vacuum cryo-cooling during a continuous 48-hour benchmarking cycle. The prototype demonstrated 99.8% two-qubit gate fidelities across all testing clusters.
						</p>
					</div>

					</div>
				</div>

				<!-- Right Column: Sidebar Widgets -->
				<div>
					<!-- Sidebar Widget 1: Trending News -->
					<div style="background: #ffffff; border-radius: 10px; padding: 20px; border: 1px solid #e2e8f0; margin-bottom: 24px;">
						<h3 style="margin: 0 0 14px 0; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; color: #0f172a; border-bottom: 2px solid #e11d48; padding-bottom: 6px;">
							Trending News
						</h3>
						<div style="display: flex; flex-direction: column; gap: 14px;">
							<div>
								<span style="font-size: 11px; color: #e11d48; font-weight: 700;">BUSINESS</span>
								<h4 style="margin: 3px 0 0 0; font-size: 13px; font-weight: 600; line-height: 1.4; color: #1e293b;">
									Global Clean Energy Investments Surpass $2 Trillion
								</h4>
							</div>
							<div>
								<span style="font-size: 11px; color: #e11d48; font-weight: 700;">SPACE</span>
								<h4 style="margin: 3px 0 0 0; font-size: 13px; font-weight: 600; line-height: 1.4; color: #1e293b;">
									Orbital Observatory Detects Water Vapor in Habitable Zone
								</h4>
							</div>
							<div>
								<span style="font-size: 11px; color: #e11d48; font-weight: 700;">AI & TECH</span>
								<h4 style="margin: 3px 0 0 0; font-size: 13px; font-weight: 600; line-height: 1.4; color: #1e293b;">
									Autonomous Systems Demonstrate Breakthrough in Real-Time Logistics
								</h4>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>

		<!-- Popup Subscription Widget (Frontend View) -->
		<?php include ADNL_PLUGIN_DIR . 'templates/popup-widget.php'; ?>

	<?php elseif ( 'email' === $view ) : ?>
		$posts = adnl_get_mock_news_posts();
		$site_name      = get_bloginfo( 'name' );
		$site_url       = home_url();
		$current_date   = wp_date( get_option( 'date_format', 'F j, Y' ) );
		$preheader_text = get_option( 'adnl_preheader_text', "Here are today's top stories and news updates." );
		$primary_color  = get_option( 'adnl_primary_color', '#e11d48' );
		$site_logo      = get_option( 'adnl_site_logo', '' );
		$logo_height    = intval( get_option( 'adnl_logo_height', 70 ) );
		$raw_title      = get_option( 'adnl_header_title', $site_name . ' Newsletter' );
		$header_title   = ! empty( $raw_title ) ? str_replace( '{site_name}', $site_name, $raw_title ) : $site_name . ' Newsletter';
		$raw_footer_text  = get_option( 'adnl_footer_text', 'You received this email because you subscribed to daily news updates on {site_name}.' );
		$footer_text      = ! empty( $raw_footer_text ) ? str_replace( '{site_name}', $site_name, $raw_footer_text ) : '';
		$current_year     = wp_date( 'Y' );
		$raw_copyright    = get_option( 'adnl_footer_copyright', '© {year} {site_name}. All rights reserved.' );
		$footer_copyright = str_replace( array( '{year}', '{site_name}' ), array( $current_year, $site_name ), $raw_copyright );
		$footer_bg_color  = get_option( 'adnl_footer_bg_color', '#f8fafc' );

		ob_start();
		include ADNL_PLUGIN_DIR . 'templates/email-digest.php';
		$html = ob_get_clean();

		$html = str_replace(
			array( '{{UNSUBSCRIBE_URL}}', '{{SUBSCRIBER_NAME}}', '{{SITE_NAME}}' ),
			array( '/demo/index.php?adnl_action=unsubscribe&token=token_alex_847193758291', 'Alex Chen', $site_name ),
			$html
		);

		echo $html;
		?>
	<?php endif; ?>

</body>
</html>
