# Auto Daily Newsletter - WordPress Plugin

An automated, modern daily news digest plugin for WordPress. It automatically queries the latest 5–10 published news articles, builds a responsive HTML newsletter, and delivers it to subscribers via your custom SMTP server or email API on a daily schedule.

---

## Architecture & Workflow

```
[ New WordPress News Posts ]
              ↓
  [ Daily WP-Cron / Server Cron ]
              ↓
  [ Post Collector (Latest 5–10) ]
              ↓
  [ Responsive HTML Template Builder ]
              ↓
  [ SMTP / PHPMailer / API Mailer ]
              ↓
  [ Active Subscribers (Batch Dispatch) ]
```

---

## Features

- **Automated Daily Scheduling**: Runs automatically once every 24 hours at your chosen time (e.g. `08:00 AM`). Compatible with WP-Cron and system server crons.
- **Smart Post Aggregation**:
  - Automatically fetches the latest 5–10 articles published within the last 24 hours.
  - Category and post-type filtering.
  - Fallback modes: Automatically send latest published posts if no new posts appeared in the last 24 hours, or skip delivery.
- **Random Subject Rotation & Dynamic News Tags**:
  - Support for multi-line subject templates with automatic random selection for each daily dispatch.
  - Dynamic news headline tags: `{top_story}`, `{random_story}`, `{posts_count}`, `{date}`, `{day}`, `{site_name}`.
  - Interactive admin UI with 1-click tag chips, 7-news random presets, and real-time preview simulation.
- **Cross-Client Responsive HTML Email**:
  - Bulletproof inline-styled email template tested for Gmail, Apple Mail, Outlook, and mobile clients.
  - Featured "Top Story" hero card + secondary story rows with thumbnails and reading time indicators.
  - Custom brand colors, site branding, and inbox preview preheader.
- **Deliverability & Transport (SMTP / API / wp_mail)**:
  - Built-in SMTP settings (Host, Port, SSL/TLS, Authentication, User, Pass).
  - Works with Gmail, SendGrid, Amazon SES, Brevo, Mailgun, Postmark, and custom host SMTP.
  - Batching system (e.g., 30 emails per batch with micro-delays) to prevent server timeouts and provider rate limits.
  - **RFC 8058 1-Click Unsubscribe headers** (`List-Unsubscribe` & `List-Unsubscribe-Post`) compliant with Google & Yahoo deliverability standards.
- **Subscriber Management**:
  - Embedded shortcode: `[daily_newsletter_form]` with instant AJAX subscription.
  - Secure, tokenized 1-click unsubscribe endpoint (no login required).
  - Admin management: Add, view, delete, and one-click export to CSV.
- **Admin Control Room**:
  - Live newsletter HTML preview modal.
  - "Send Test Email" diagnostic tool.
  - "Send Today's Digest Now" manual trigger.
  - Full audit delivery log table.

---

## Installation

### Method 1: Upload via WordPress Admin
1. Zip this plugin folder:
   ```bash
   zip -r auto-daily-newsletter.zip . -x "*.git*"
   ```
2. In your WordPress admin, navigate to **Plugins → Add New → Upload Plugin**.
3. Choose `auto-daily-newsletter.zip` and click **Install Now**.
4. Click **Activate Plugin**.

### Method 2: FTP / Direct Directory Copy
1. Upload the plugin files to your WordPress site directory:
   `/wp-content/plugins/auto-daily-newsletter/`
2. Go to **Plugins → Installed Plugins** in WordPress and click **Activate**.

---

## Shortcode Usage

To display a subscription box anywhere on your site, add the following shortcode:

```text
[daily_newsletter_form]
```

### Shortcode Parameters:
| Parameter | Default | Description |
| :--- | :--- | :--- |
| `title` | `"Subscribe to Our Daily Digest"` | Headline of the subscription card |
| `subtitle` | `"Get the top curated news..."` | Subtitle explanation |
| `button_text` | `"Subscribe"` | CTA button label |
| `show_name` | `"no"` | Set to `"yes"` to include a Name input |

**Example:**
```text
[daily_newsletter_form title="Daily Tech Digest" button_text="Join Now" show_name="yes"]
```

---

## Server Cron (Recommended for High Reliability)

While WordPress WP-Cron works out of the box, we recommend disabling WP-Cron execution on visitor page loads and running a real Linux cron job for precise daily timing:

1. Add this to your `wp-config.php`:
   ```php
   define( 'DISABLE_WP_CRON', true );
   ```
2. Add a system crontab entry (runs every 10 or 15 minutes):
   ```bash
   */15 * * * * curl -s https://example.com/wp-cron.php > /dev/null 2>&1
   ```

---

## Extensibility (Developer Hooks)

### 1. Intercept Delivery with Custom Transactional API (e.g. SendGrid / Brevo API)
```php
add_filter( 'adnl_send_email_via_api', function( $handled, $to_email, $subject, $html_body, $headers ) {
    // Dispatch via your custom API SDK (e.g. SendGrid, Mailgun, Brevo)
    // Return true on success, or false to fall back to wp_mail()
    return true; 
}, 10, 5 );
```

### 2. Customize Newsletter HTML
```php
add_filter( 'adnl_rendered_digest_html', function( $html, $posts ) {
    // Manipulate HTML before personalizing or sending
    return $html;
}, 10, 2 );
```

---

## License
GPLv2 or later.
