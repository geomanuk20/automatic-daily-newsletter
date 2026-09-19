<?php
/**
 * Template Builder: Renders and personalizes the HTML email newsletter.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ADNL_Template_Builder {

	/**
	 * Build dynamic email subject with support for random template selection and news tags.
	 *
	 * @param string|null $subject_template Optional template or multi-line template string.
	 * @param array       $posts            Array of news posts.
	 * @return string Formatted email subject.
	 */
	public static function generate_subject( $subject_template = null, $posts = array() ) {
		if ( null === $subject_template || '' === trim( (string) $subject_template ) ) {
			$subject_template = get_option( 'adnl_email_subject', "[Daily Digest] Today's Top Stories - {date}" );
		}

		// Support multi-line subjects or pipe-separated subjects for random selection
		$lines = preg_split( '/[\r\n|]+/', (string) $subject_template );
		$valid_lines = array();
		if ( is_array( $lines ) ) {
			foreach ( $lines as $line ) {
				$line = trim( $line );
				if ( '' !== $line ) {
					$valid_lines[] = $line;
				}
			}
		}

		$selected_template = ! empty( $valid_lines ) ? $valid_lines[ array_rand( $valid_lines ) ] : "[Daily Digest] Today's Top Stories - {date}";

		// Extract post data
		$post_count   = is_array( $posts ) ? count( $posts ) : intval( get_option( 'adnl_posts_count', 7 ) );
		$top_story    = '';
		$random_story = '';

		if ( ! empty( $posts ) && is_array( $posts ) ) {
			$post_count = count( $posts );

			// First / top story title
			$first = reset( $posts );
			$top_story = isset( $first['title'] ) ? ( function_exists( 'wp_strip_all_tags' ) ? wp_strip_all_tags( $first['title'] ) : strip_tags( $first['title'] ) ) : '';

			// Random story title picked from today's news posts
			$rand_key     = array_rand( $posts );
			$random_story = isset( $posts[ $rand_key ]['title'] ) ? ( function_exists( 'wp_strip_all_tags' ) ? wp_strip_all_tags( $posts[ $rand_key ]['title'] ) : strip_tags( $posts[ $rand_key ]['title'] ) ) : $top_story;
		}

		$current_date = function_exists( 'wp_date' ) ? wp_date( get_option( 'date_format', 'F j, Y' ) ) : date( 'F j, Y' );
		$day          = function_exists( 'wp_date' ) ? wp_date( 'l' ) : date( 'l' );
		$month        = function_exists( 'wp_date' ) ? wp_date( 'F' ) : date( 'F' );
		$year         = function_exists( 'wp_date' ) ? wp_date( 'Y' ) : date( 'Y' );
		$site_name    = function_exists( 'get_bloginfo' ) ? get_bloginfo( 'name' ) : ( get_option( 'blogname', 'Daily News' ) );

		$replacements = array(
			'{date}'         => $current_date,
			'{site_name}'    => $site_name,
			'{posts_count}'  => (string) $post_count,
			'{top_story}'    => $top_story,
			'{first_story}'  => $top_story,
			'{headline}'     => $top_story,
			'{random_story}' => $random_story,
			'{random_news}'  => $random_story,
			'{day}'          => $day,
			'{month}'        => $month,
			'{year}'         => $year,
		);

		$subject = str_replace( array_keys( $replacements ), array_values( $replacements ), $selected_template );

		return trim( preg_replace( '/\s+/', ' ', $subject ) );
	}

	/**
	 * Build base HTML newsletter from posts data.
	 *
	 * @param array $posts Formatted posts array.
	 * @return string Rendered HTML content.
	 */
	public function build_digest_html( $posts, $custom_overrides = array() ) {
		$site_name      = get_bloginfo( 'name' );
		$site_url       = home_url();
		$site_logo      = isset( $custom_overrides['site_logo'] ) ? $custom_overrides['site_logo'] : get_option( 'adnl_site_logo', '' );
		$logo_height    = isset( $custom_overrides['logo_height'] ) ? intval( $custom_overrides['logo_height'] ) : intval( get_option( 'adnl_logo_height', 70 ) );
		$current_date   = wp_date( get_option( 'date_format', 'F j, Y' ) );
		$preheader_text = get_option( 'adnl_preheader_text', "Here are today's top stories and news updates." );
		$primary_color  = get_option( 'adnl_primary_color', '#2563eb' );
		
		$raw_title     = isset( $custom_overrides['header_title'] ) ? $custom_overrides['header_title'] : get_option( 'adnl_header_title', $site_name . ' Newsletter' );
		$header_title  = ! empty( $raw_title ) ? str_replace( '{site_name}', $site_name, $raw_title ) : $site_name . ' Newsletter';

		$raw_footer_text  = get_option( 'adnl_footer_text', 'You received this email because you subscribed to daily news updates on {site_name}.' );
		$footer_text      = ! empty( $raw_footer_text ) ? str_replace( '{site_name}', $site_name, $raw_footer_text ) : '';

		$current_year     = wp_date( 'Y' );
		$raw_copyright    = get_option( 'adnl_footer_copyright', '© {year} {site_name}. All rights reserved.' );
		$footer_copyright = str_replace( array( '{year}', '{site_name}' ), array( $current_year, $site_name ), $raw_copyright );
		$footer_bg_color  = get_option( 'adnl_footer_bg_color', '#f8fafc' );
		$show_primary_tip = (bool) get_option( 'adnl_show_primary_tip', 1 );

		ob_start();
		include ADNL_PLUGIN_DIR . 'templates/email-digest.php';
		$html = ob_get_clean();

		return apply_filters( 'adnl_rendered_digest_html', $html, $posts );
	}

	/**
	 * Build clean plain-text alternative of the digest for RFC MIME multipart/alternative.
	 *
	 * @param array $posts Formatted posts array.
	 * @param array $custom_overrides
	 * @return string
	 */
	public function build_digest_plain_text( $posts, $custom_overrides = array() ) {
		$site_name    = get_bloginfo( 'name' );
		$site_url     = home_url();
		$current_date = wp_date( get_option( 'date_format', 'l, F j, Y' ) );

		$raw_title    = isset( $custom_overrides['header_title'] ) ? $custom_overrides['header_title'] : get_option( 'adnl_header_title', $site_name . ' Newsletter' );
		$header_title = ! empty( $raw_title ) ? str_replace( '{site_name}', $site_name, $raw_title ) : $site_name . ' Newsletter';

		$lines   = array();
		$lines[] = strtoupper( $header_title );
		$lines[] = str_repeat( '=', strlen( $header_title ) );
		$lines[] = $current_date;
		$lines[] = '';

		if ( ! empty( $posts ) ) {
			$lines[] = "TODAY'S HIGHLIGHTS (" . count( $posts ) . ' Stories)';
			$lines[] = str_repeat( '-', 30 );
			$lines[] = '';

			foreach ( $posts as $i => $post ) {
				$num     = $i + 1;
				$title   = ! empty( $post['title'] ) ? html_entity_decode( $post['title'], ENT_QUOTES, 'UTF-8' ) : '';
				$raw_exc = ! empty( $post['excerpt'] ) ? ( function_exists( 'wp_strip_all_tags' ) ? wp_strip_all_tags( $post['excerpt'] ) : strip_tags( $post['excerpt'] ) ) : '';
				$excerpt = html_entity_decode( $raw_exc, ENT_QUOTES, 'UTF-8' );
				$cat     = ! empty( $post['category'] ) ? strtoupper( $post['category'] ) : '';
				$link    = ! empty( $post['permalink'] ) ? $post['permalink'] : '';

				$prefix = ( 0 === $i ) ? '★ FEATURED: ' : "{$num}. ";
				$lines[] = $prefix . $title;
				if ( ! empty( $cat ) ) {
					$lines[] = '[' . $cat . ']';
				}
				if ( ! empty( $excerpt ) ) {
					$lines[] = $excerpt;
				}
				if ( ! empty( $link ) ) {
					$lines[] = 'Read story: ' . $link;
				}
				$lines[] = '';
			}
		}

		$lines[] = str_repeat( '-', 30 );
		$lines[] = 'Tip: To ensure our daily updates arrive in your Primary inbox, please drag this email to your "Primary" tab or add us to your contacts.';
		$lines[] = '';
		$lines[] = 'Unsubscribe: {{UNSUBSCRIBE_URL}}';
		$lines[] = 'Visit our website: ' . $site_url;
		$lines[] = '© ' . wp_date( 'Y' ) . ' ' . $site_name . '. All rights reserved.';

		$plain = implode( "\n", $lines );
		return apply_filters( 'adnl_rendered_digest_plain_text', $plain, $posts );
	}

	/**
	 * Personalize plain text content for a specific subscriber.
	 *
	 * @param string $base_plain
	 * @param object $subscriber
	 * @return string
	 */
	public function personalize_plain_text( $base_plain, $subscriber ) {
		$token           = ! empty( $subscriber->token ) ? $subscriber->token : '';
		$unsubscribe_url = add_query_arg(
			array(
				'adnl_action' => 'unsubscribe',
				'token'       => $token,
			),
			home_url( '/' )
		);

		$subscriber_name  = ! empty( $subscriber->name ) ? $subscriber->name : __( 'Subscriber', 'auto-daily-newsletter' );
		$subscriber_email = ! empty( $subscriber->email ) ? $subscriber->email : '';

		$replacements = array(
			'{{UNSUBSCRIBE_URL}}' => function_exists( 'esc_url_raw' ) ? esc_url_raw( $unsubscribe_url ) : esc_url( $unsubscribe_url ),
			'{{SUBSCRIBER_NAME}}' => $subscriber_name,
			'{{SUBSCRIBER_EMAIL}}'=> $subscriber_email,
			'{{SITE_NAME}}'       => get_bloginfo( 'name' ),
			'{{SITE_URL}}'        => home_url(),
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $base_plain );
	}

	/**
	 * Personalize template HTML for a specific subscriber.
	 *
	 * @param string $base_html
	 * @param object $subscriber
	 * @return string
	 */
	public function personalize_html( $base_html, $subscriber ) {
		$token           = ! empty( $subscriber->token ) ? $subscriber->token : '';
		$unsubscribe_url = add_query_arg(
			array(
				'adnl_action' => 'unsubscribe',
				'token'       => $token,
			),
			home_url( '/' )
		);

		$subscriber_name = ! empty( $subscriber->name ) ? $subscriber->name : __( 'Subscriber', 'auto-daily-newsletter' );
		$subscriber_email = ! empty( $subscriber->email ) ? $subscriber->email : '';

		$replacements = array(
			'{{UNSUBSCRIBE_URL}}' => esc_url( $unsubscribe_url ),
			'{{SUBSCRIBER_NAME}}' => esc_html( $subscriber_name ),
			'{{SUBSCRIBER_EMAIL}}'=> esc_html( $subscriber_email ),
			'{{SITE_NAME}}'       => esc_html( get_bloginfo( 'name' ) ),
			'{{SITE_URL}}'        => esc_url( home_url() ),
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $base_html );
	}
}
