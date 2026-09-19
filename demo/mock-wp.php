<?php
/**
 * Mock WordPress Environment for Localhost Demo
 * Provides standard WordPress APIs and persists mock DB state to JSON.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

define( 'WPINC', true );
define( 'ADNL_VERSION', '1.2.0' );
define( 'ADNL_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'ADNL_PLUGIN_URL', '/' );
define( 'ADNL_PLUGIN_BASENAME', 'auto-daily-newsletter/auto-daily-newsletter.php' );

// WordPress Database return type constants
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}
if ( ! defined( 'object' ) ) {
	define( 'object', 'OBJECT' );
}
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'ARRAY_N' ) ) {
	define( 'ARRAY_N', 'ARRAY_N' );
}

// Mock Data Storage File
$data_file = __DIR__ . '/demo_data.json';

if ( ! file_exists( $data_file ) ) {
	$initial_data = array(
		'options' => array(
			'adnl_enabled'             => 1,
			'adnl_posts_count'         => 7,
			'adnl_lookback_hours'      => 24,
			'adnl_fallback_behavior'   => 'latest',
			'adnl_post_types'          => array( 'post' ),
			'adnl_categories'          => array( 1, 2 ),
			'adnl_schedule_time'       => '08:00',
			'adnl_email_subject'       => "[Daily Digest] Today's Top Stories - {date}",
			'adnl_preheader_text'      => "Here are today's top stories and news updates.",
			'adnl_primary_color'       => '#2563eb',
			'adnl_from_name'           => 'Global News Daily',
			'adnl_from_email'          => 'newsletter@globalnews.com',
			'adnl_mailer_type'         => 'smtp',
			'adnl_smtp_host'           => 'smtp.mailgun.org',
			'adnl_smtp_port'           => 587,
			'adnl_smtp_encryption'     => 'tls',
			'adnl_smtp_auth'           => 1,
			'adnl_smtp_user'           => 'postmaster@globalnews.com',
			'adnl_smtp_pass'           => 'secretpassword123',
			'adnl_batch_size'          => 30,
			'adnl_batch_delay'         => 1,
			'adnl_last_run_timestamp'  => time() - 3600 * 5,
			'blogname'                 => 'Global News Daily',
			'admin_email'              => 'admin@globalnews.com',
			'date_format'              => 'F j, Y',
		),
		'subscribers' => array(
			array(
				'id'         => 1,
				'email'      => 'alex.chen@example.com',
				'name'       => 'Alex Chen',
				'token'      => 'token_alex_847193758291',
				'status'     => 'active',
				'created_at' => date( 'Y-m-d H:i:s', time() - 86400 * 5 ),
			),
			array(
				'id'         => 2,
				'email'      => 'sarah.miller@example.com',
				'name'       => 'Sarah Miller',
				'token'      => 'token_sarah_193857204918',
				'status'     => 'active',
				'created_at' => date( 'Y-m-d H:i:s', time() - 86400 * 3 ),
			),
			array(
				'id'         => 3,
				'email'      => 'david.kim@example.com',
				'name'       => 'David Kim',
				'token'      => 'token_david_384729105823',
				'status'     => 'active',
				'created_at' => date( 'Y-m-d H:i:s', time() - 86400 * 2 ),
			),
			array(
				'id'         => 4,
				'email'      => 'emma.watson@example.com',
				'name'       => 'Emma Watson',
				'token'      => 'token_emma_958372019482',
				'status'     => 'active',
				'created_at' => date( 'Y-m-d H:i:s', time() - 86400 * 1 ),
			),
			array(
				'id'         => 5,
				'email'      => 'james.wilson@example.com',
				'name'       => 'James Wilson',
				'token'      => 'token_james_729401847291',
				'status'     => 'unsubscribed',
				'created_at' => date( 'Y-m-d H:i:s', time() - 86400 * 10 ),
			),
		),
		'logs' => array(
			array(
				'id'               => 1,
				'subject'          => "[Daily Digest] Today's Top Stories - " . date( 'F j, Y', time() - 86400 ),
				'posts_count'      => 7,
				'recipients_count' => 4,
				'status'           => 'success',
				'message'          => 'Sent to 4 subscribers. Failures: 0.',
				'created_at'       => date( 'Y-m-d H:i:s', time() - 86400 ),
			),
			array(
				'id'               => 2,
				'subject'          => "[Daily Digest] Today's Top Stories - " . date( 'F j, Y', time() - 86400 * 2 ),
				'posts_count'      => 6,
				'recipients_count' => 4,
				'status'           => 'success',
				'message'          => 'Sent to 4 subscribers. Failures: 0.',
				'created_at'       => date( 'Y-m-d H:i:s', time() - 86400 * 2 ),
			),
		),
	);
	file_put_contents( $data_file, json_encode( $initial_data, JSON_PRETTY_PRINT ) );
}

function adnl_get_demo_data() {
	global $data_file;
	return json_decode( file_get_contents( $data_file ), true );
}

function adnl_save_demo_data( $data ) {
	global $data_file;
	file_put_contents( $data_file, json_encode( $data, JSON_PRETTY_PRINT ) );
}

// WordPress Helper functions shim
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $url ) { return filter_var( $url, FILTER_SANITIZE_URL ); }
function esc_js( $text ) { return addcslashes( (string) $text, "\\\'\"&\n\r<>" ); }
function esc_textarea( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function sanitize_text_field( $str ) { return strip_tags( trim( (string) $str ) ); }
function sanitize_email( $email ) { return filter_var( trim( (string) $email ), FILTER_SANITIZE_EMAIL ); }
function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ); }
function sanitize_hex_color( $color ) { return preg_match( '/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $color ) ? $color : '#2563eb'; }
function sanitize_textarea_field( $str ) { return htmlspecialchars( trim( (string) $str ) ); }
function is_email( $email ) { return filter_var( $email, FILTER_VALIDATE_EMAIL ); }
function wp_unslash( $val ) { return $val; }
function __( $text, $domain = '' ) { return $text; }
function _e( $text, $domain = '' ) { echo $text; }
function esc_html__( $text, $domain = '' ) { return esc_html( $text ); }
function esc_html_e( $text, $domain = '' ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = '' ) { echo esc_attr( $text ); }
function esc_attr__( $text, $domain = '' ) { return esc_attr( $text ); }
function home_url( $path = '' ) { return 'http://127.0.0.1:8000' . $path; }
function site_url( $path = '' ) { return home_url( $path ); }
function admin_url( $path = '' ) { return 'http://127.0.0.1:8000/demo/index.php?' . ltrim( $path, '?' ); }
function get_bloginfo( $show = 'name' ) { return get_option( 'blogname', 'ManoramaOnline' ); }
function wp_date( $format, $timestamp = null ) { return date( $format, $timestamp ?? time() ); }
function wp_timezone_string() {
	$tz = get_option( 'adnl_timezone', '' );
	if ( ! empty( $tz ) ) return $tz;
	$sys = @date_default_timezone_get();
	return ( $sys && 'UTC' !== $sys ) ? $sys : 'Asia/Kolkata';
}
function wp_timezone() { return new DateTimeZone( wp_timezone_string() ); }
function checked( $checked, $current = true, $echo = true ) {
	$result = ( (string) $checked === (string) $current ) ? " checked='checked'" : '';
	if ( $echo ) echo $result;
	return $result;
}
function selected( $selected, $current = true, $echo = true ) {
	$result = ( (string) $selected === (string) $current ) ? " selected='selected'" : '';
	if ( $echo ) echo $result;
	return $result;
}
function current_time( $type = 'timestamp' ) { return 'mysql' === $type ? date( 'Y-m-d H:i:s' ) : time(); }
function wp_get_current_user() { return (object) array( 'user_email' => 'editor@globalnews.com' ); }
function current_user_can( $capability ) { return true; }
function wp_create_nonce( $action ) { return 'demo_nonce_' . md5( $action ); }
function check_ajax_referer( $action, $query_arg = false, $die = true ) { return true; }
function check_admin_referer( $action = -1, $query_arg = '_wpnonce' ) { return true; }
function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) {
	$html = '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( wp_create_nonce( $action ) ) . '" />';
	if ( $echo ) echo $html;
	return $html;
}
function wp_nonce_url( $actionurl, $action = -1, $name = '_wpnonce' ) {
	return $actionurl . '&' . $name . '=' . wp_create_nonce( $action );
}
function submit_button( $text = 'Save Changes', $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null ) {
	echo '<p class="submit"><input type="submit" name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '" class="button button-' . esc_attr( $type ) . '" value="' . esc_attr( $text ) . '"></p>';
}

function get_option( $key, $default = false ) {
	$data = adnl_get_demo_data();
	return $data['options'][$key] ?? $default;
}

function update_option( $key, $value ) {
	$data = adnl_get_demo_data();
	$data['options'][$key] = $value;
	adnl_save_demo_data( $data );
	return true;
}

function delete_option( $key ) {
	$data = adnl_get_demo_data();
	unset( $data['options'][$key] );
	adnl_save_demo_data( $data );
	return true;
}

function wp_next_scheduled( $hook ) {
	return strtotime( 'tomorrow 08:00:00' );
}

function get_categories( $args = array() ) {
	return array(
		(object) array( 'term_id' => 1, 'name' => 'Technology & AI', 'count' => 14 ),
		(object) array( 'term_id' => 2, 'name' => 'Global Politics', 'count' => 22 ),
		(object) array( 'term_id' => 3, 'name' => 'Markets & Business', 'count' => 19 ),
		(object) array( 'term_id' => 4, 'name' => 'Science & Space', 'count' => 11 ),
		(object) array( 'term_id' => 5, 'name' => 'Energy & Climate', 'count' => 8 ),
	);
}

function apply_filters( $tag, $value, ...$args ) {
	return $value;
}

function add_query_arg( $args, $url ) {
	$query = http_build_query( $args );
	return rtrim( $url, '/' ) . '/demo/index.php?' . $query;
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return esc_url( $url );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string, $remove_breaks = false ) {
		$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
		$string = strip_tags( $string );
		if ( $remove_breaks ) {
			$string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
		}
		return trim( $string );
	}
}

function wp_send_json_success( $data = null ) {
	header( 'Content-Type: application/json' );
	echo json_encode( array( 'success' => true, 'data' => $data ) );
	exit;
}

function wp_send_json_error( $data = null ) {
	header( 'Content-Type: application/json' );
	echo json_encode( array( 'success' => false, 'data' => $data ) );
	exit;
}

// Sample Curated News Posts for the demo
function adnl_get_mock_news_posts() {
	return array(
		array(
			'id'            => 101,
			'title'         => 'Next-Generation Quantum Chips Achieve Breakthrough in Real-Time Quantum Processing',
			'permalink'     => 'http://127.0.0.1:8000/demo/index.php?view=article&id=101',
			'excerpt'       => 'Researchers reveal a scalable 1,000-qubit architecture running at ambient room temperatures, paving the way for commercial deployments across cryptography and aerospace.',
			'date'          => date( 'M j, Y' ),
			'is_today'      => true,
			'post_date_raw' => date( 'Y-m-d' ),
			'author'        => 'Elena Rostova',
			'category'      => 'Technology & AI',
			'thumbnail_url' => 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?auto=format&fit=crop&w=1000&q=80',
			'read_time'     => '4 min read',
		),
		array(
			'id'            => 102,
			'title'         => 'Global Green Energy Grid Links Cross-Continental Solar Corridors',
			'permalink'     => 'http://127.0.0.1:8000/demo/index.php?view=article&id=102',
			'excerpt'       => 'An international consortium completes the submarine high-voltage transmission line delivering uninterrupted renewable solar power between continents.',
			'date'          => date( 'M j, Y' ),
			'is_today'      => true,
			'post_date_raw' => date( 'Y-m-d' ),
			'author'        => 'Marcus Vance',
			'category'      => 'Energy & Climate',
			'thumbnail_url' => 'https://images.unsplash.com/photo-1509391365360-2e959784a276?auto=format&fit=crop&w=400&q=80',
			'read_time'     => '3 min read',
		),
		array(
			'id'            => 103,
			'title'         => 'Central Banks Announce Synchronized Liquidity Framework for Digital Assets',
			'permalink'     => 'http://127.0.0.1:8000/demo/index.php?view=article&id=103',
			'excerpt'       => 'Financial regulators from seven leading economies agree on interoperable standards to streamline wholesale cross-border settlements.',
			'date'          => date( 'M j, Y' ),
			'is_today'      => true,
			'post_date_raw' => date( 'Y-m-d' ),
			'author'        => 'Sophia Sterling',
			'category'      => 'Markets & Business',
			'thumbnail_url' => 'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?auto=format&fit=crop&w=400&q=80',
			'read_time'     => '5 min read',
		),
		array(
			'id'            => 104,
			'title'         => 'Autonomous Deep-Sea Rovers Discover Hydrothermal Ecosystems in Kermadec Trench',
			'permalink'     => 'http://127.0.0.1:8000/demo/index.php?view=article&id=104',
			'excerpt'       => 'Marine biologists catalog over 40 previously unrecorded deep-sea species thriving under extreme oceanic pressures.',
			'date'          => date( 'M j, Y' ),
			'is_today'      => true,
			'post_date_raw' => date( 'Y-m-d' ),
			'author'        => 'Dr. Kenneth Thorne',
			'category'      => 'Science & Space',
			'thumbnail_url' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=400&q=80',
			'read_time'     => '3 min read',
		),
		array(
			'id'            => 105,
			'title'         => 'Open-Source Foundation Releases Multimodal Agent Operating System',
			'permalink'     => 'http://127.0.0.1:8000/demo/index.php?view=article&id=105',
			'excerpt'       => 'A lightweight framework allows autonomous AI agents to execute local desktop workflows, schedule background tasks, and collaborate securely.',
			'date'          => date( 'M j, Y' ),
			'author'        => 'Geo manu k',
			'category'      => 'Technology & AI',
			'thumbnail_url' => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&w=400&q=80',
			'read_time'     => '6 min read',
		),
		array(
			'id'            => 106,
			'title'         => 'Space Telescope Identifies Atmosphere Rich in Water Vapor on Exoplanet K2-18d',
			'permalink'     => 'http://127.0.0.1:8000/demo/index.php?view=article&id=106',
			'excerpt'       => 'Spectroscopic data reveals strong biosignature candidates in the habitable zone of a red dwarf star 120 light years away.',
			'date'          => date( 'M j, Y' ),
			'author'        => 'Sarah Jenkins',
			'category'      => 'Science & Space',
			'thumbnail_url' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=400&q=80',
			'read_time'     => '4 min read',
		),
		array(
			'id'            => 107,
			'title'         => 'Global Manufacturing Survey Signals Rebound in High-Tech Industrial Output',
			'permalink'     => 'http://127.0.0.1:8000/demo/index.php?view=article&id=107',
			'excerpt'       => 'Supply chains stabilize as automated factories report double-digit capacity expansion throughout the third quarter.',
			'date'          => date( 'M j, Y' ),
			'author'        => 'Liam Gallagher',
			'category'      => 'Markets & Business',
			'thumbnail_url' => 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=400&q=80',
			'read_time'     => '2 min read',
		),
	);
}

// Mock wpdb
class Mock_WPDB {
	public $prefix = 'wp_';

	public function get_charset_collate() {
		return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
	}

	public function prepare( $query, ...$args ) {
		foreach ( $args as $a ) {
			$escaped = "'" . addslashes( (string) $a ) . "'";
			$query = preg_replace( '/%[sfd]/', $escaped, $query, 1 );
		}
		return $query;
	}

	public function get_results( $query, $output = OBJECT ) {
		$data = adnl_get_demo_data();
		if ( strpos( $query, 'adnl_subscribers' ) !== false ) {
			$subs = $data['subscribers'];
			if ( $output === ARRAY_A ) return $subs;
			return array_map( function( $item ) { return (object) $item; }, $subs );
		}
		if ( strpos( $query, 'adnl_logs' ) !== false ) {
			$logs = $data['logs'];
			if ( $output === ARRAY_A ) return $logs;
			return array_map( function( $item ) { return (object) $item; }, $logs );
		}
		return array();
	}

	public function get_var( $query ) {
		$data = adnl_get_demo_data();
		if ( strpos( $query, 'SHOW TABLES' ) !== false ) {
			return 'wp_adnl_subscribers';
		}
		if ( strpos( $query, 'adnl_subscribers' ) !== false && strpos( $query, "status = 'active'" ) !== false ) {
			$count = 0;
			foreach ( $data['subscribers'] as $s ) {
				if ( 'active' === $s['status'] ) $count++;
			}
			return $count;
		}
		if ( strpos( $query, 'adnl_subscribers' ) !== false && strpos( $query, "status = 'unsubscribed'" ) !== false ) {
			$count = 0;
			foreach ( $data['subscribers'] as $s ) {
				if ( 'unsubscribed' === $s['status'] ) $count++;
			}
			return $count;
		}
		if ( strpos( $query, 'adnl_logs' ) !== false ) {
			return count( $data['logs'] );
		}
		return 0;
	}

	public function get_row( $query, $output = OBJECT ) {
		$db_data = adnl_get_demo_data();
		if ( strpos( $query, 'adnl_subscribers' ) !== false ) {
			if ( preg_match( "/email = '([^']+)'/i", $query, $matches ) ) {
				$target_email = strtolower( trim( $matches[1] ) );
				foreach ( $db_data['subscribers'] as $s ) {
					if ( strtolower( $s['email'] ) === $target_email ) {
						return ( $output === ARRAY_A ) ? $s : (object) $s;
					}
				}
			}
		}
		return null;
	}

	public function insert( $table, $data_row ) {
		$db_data = adnl_get_demo_data();
		if ( strpos( $table, 'adnl_subscribers' ) !== false ) {
			$data_row['id'] = count( $db_data['subscribers'] ) + 1;
			$db_data['subscribers'][] = $data_row;
			adnl_save_demo_data( $db_data );
			return true;
		}
		if ( strpos( $table, 'adnl_logs' ) !== false ) {
			$data_row['id'] = count( $db_data['logs'] ) + 1;
			array_unshift( $db_data['logs'], $data_row );
			adnl_save_demo_data( $db_data );
			return true;
		}
		return false;
	}

	public function update( $table, $data_row, $where ) {
		$db_data = adnl_get_demo_data();
		if ( strpos( $table, 'adnl_subscribers' ) !== false ) {
			foreach ( $db_data['subscribers'] as &$s ) {
				$match = true;
				foreach ( $where as $k => $v ) {
					if ( (string) $s[$k] !== (string) $v ) { $match = false; break; }
				}
				if ( $match ) {
					foreach ( $data_row as $k => $v ) { $s[$k] = $v; }
				}
			}
			adnl_save_demo_data( $db_data );
			return true;
		}
		return false;
	}

	public function delete( $table, $where ) {
		$db_data = adnl_get_demo_data();
		if ( strpos( $table, 'adnl_subscribers' ) !== false ) {
			$new_subs = array();
			foreach ( $db_data['subscribers'] as $s ) {
				$match = true;
				foreach ( $where as $k => $v ) {
					if ( (string) $s[$k] === (string) $v ) { $match = false; break; }
				}
				if ( ! $match ) { $new_subs[] = $s; }
			}
			$db_data['subscribers'] = $new_subs;
			adnl_save_demo_data( $db_data );
			return true;
		}
		return false;
	}
}

global $wpdb;
$wpdb = new Mock_WPDB();

if ( file_exists( ADNL_PLUGIN_DIR . 'includes/class-template-builder.php' ) ) {
	require_once ADNL_PLUGIN_DIR . 'includes/class-template-builder.php';
}
if ( file_exists( ADNL_PLUGIN_DIR . 'includes/class-cron.php' ) ) {
	require_once ADNL_PLUGIN_DIR . 'includes/class-cron.php';
}
