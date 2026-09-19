<?php
/**
 * Plugin Name:       Auto Daily Newsletter
 * Plugin URI:        https://github.com/geomanuk20/newsletter-plugin-
 * Description:       Automates daily email newsletter digests by aggregating the latest 5–10 WordPress news posts and sending them to subscribers via configurable SMTP or API.
 * Version:           1.2.0
 * Author:            Geo manu k
 * Author URI:        https://github.com/geomanuk20
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       auto-daily-newsletter
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'ADNL_VERSION', '1.2.0' );
define( 'ADNL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ADNL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ADNL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load core plugin class dependencies.
 */
require_once ADNL_PLUGIN_DIR . 'includes/class-activator.php';
require_once ADNL_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once ADNL_PLUGIN_DIR . 'includes/class-subscriber-manager.php';
require_once ADNL_PLUGIN_DIR . 'includes/class-post-collector.php';
require_once ADNL_PLUGIN_DIR . 'includes/class-template-builder.php';
require_once ADNL_PLUGIN_DIR . 'includes/class-smtp-transport.php';
require_once ADNL_PLUGIN_DIR . 'includes/class-mailer.php';
require_once ADNL_PLUGIN_DIR . 'includes/class-cron.php';
require_once ADNL_PLUGIN_DIR . 'includes/class-admin.php';

/**
 * Main plugin orchestrator class.
 */
final class Auto_Daily_Newsletter {

	/**
	 * Singleton instance.
	 *
	 * @var Auto_Daily_Newsletter|null
	 */
	private static $instance = null;

	/**
	 * Component instances.
	 */
	public $subscribers;
	public $post_collector;
	public $template_builder;
	public $mailer;
	public $cron;
	public $admin;

	/**
	 * Get singleton instance.
	 *
	 * @return Auto_Daily_Newsletter
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_components();
		$this->register_hooks();
	}

	/**
	 * Initialize plugin components.
	 */
	private function init_components() {
		$this->subscribers      = new ADNL_Subscriber_Manager();
		$this->post_collector   = new ADNL_Post_Collector();
		$this->template_builder = new ADNL_Template_Builder();
		$this->mailer           = new ADNL_Mailer();
		$this->cron             = new ADNL_Cron();

		if ( is_admin() ) {
			$this->admin = new ADNL_Admin();
		}
	}

	/**
	 * Register general hooks and assets.
	 */
	private function register_hooks() {
		add_action( 'init', array( $this, 'handle_unsubscribe_request' ) );
		add_action( 'init', array( $this, 'check_database_tables' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_shortcode( 'daily_newsletter_form', array( $this->subscribers, 'render_subscribe_form_shortcode' ) );
		add_shortcode( 'daily_newsletter_popup', array( $this->subscribers, 'render_popup_shortcode' ) );
		add_action( 'wp_footer', array( $this->subscribers, 'render_popup_widget' ), 999 );

		// Register settings link on plugins list page
		add_filter( 'plugin_action_links_' . ADNL_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
	}

	/**
	 * Self-healing database check: ensure custom tables exist on the active site.
	 */
	public function check_database_tables() {
		global $wpdb;
		$subscribers_table = $wpdb->prefix . 'adnl_subscribers';
		if ( get_option( 'adnl_db_installed' ) !== ADNL_VERSION || $wpdb->get_var( "SHOW TABLES LIKE '$subscribers_table'" ) !== $subscribers_table ) {
			require_once ADNL_PLUGIN_DIR . 'includes/class-activator.php';
			ADNL_Activator::create_tables();
			update_option( 'adnl_db_installed', ADNL_VERSION );
		}
	}

	/**
	 * Enqueue frontend scripts & styles for subscribe form.
	 */
	public function enqueue_frontend_assets() {
		wp_enqueue_style(
			'adnl-frontend-style',
			ADNL_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			ADNL_VERSION
		);

		wp_enqueue_script(
			'adnl-frontend-script',
			ADNL_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			ADNL_VERSION,
			true
		);

		wp_localize_script(
			'adnl-frontend-script',
			'adnl_ajax_obj',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'adnl_subscribe_nonce' ),
				'is_admin' => current_user_can( 'manage_options' ) ? 1 : 0,
			)
		);
	}

	/**
	 * Handle 1-click unsubscribe links: ?adnl_action=unsubscribe&token=xxx
	 */
	public function handle_unsubscribe_request() {
		if ( isset( $_GET['adnl_action'] ) && 'unsubscribe' === sanitize_text_field( wp_unslash( $_GET['adnl_action'] ) ) ) {
			$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
			if ( empty( $token ) ) {
				wp_die( esc_html__( 'Invalid unsubscribe link.', 'auto-daily-newsletter' ), esc_html__( 'Unsubscribe Error', 'auto-daily-newsletter' ), array( 'response' => 400 ) );
			}

			$result = $this->subscribers->unsubscribe_by_token( $token );

			// Clear subscriber cookie so popup shows again for unsubscribed user
			if ( ! headers_sent() ) {
				setcookie( 'adnl_subscribed', '', time() - 3600, '/' );
			}

			// Render a clean, standalone unsubscribe confirmation page
			include ADNL_PLUGIN_DIR . 'templates/unsubscribe-page.php';
			exit;
		}
	}

	/**
	 * Add custom settings link to Plugins screen.
	 *
	 * @param array $links
	 * @return array
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=auto-daily-newsletter' ) ),
			esc_html__( 'Settings', 'auto-daily-newsletter' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}
}

/**
 * Activation callback.
 */
function adnl_activate_plugin() {
	ADNL_Activator::activate();
}
register_activation_hook( __FILE__, 'adnl_activate_plugin' );

/**
 * Deactivation callback.
 */
function adnl_deactivate_plugin() {
	ADNL_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'adnl_deactivate_plugin' );

/**
 * Bootstrap plugin execution.
 */
function adnl_run_plugin() {
	return Auto_Daily_Newsletter::get_instance();
}
add_action( 'plugins_loaded', 'adnl_run_plugin' );
