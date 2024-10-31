<?php

namespace Elementor_Pay_Addons\Core;

use Pelago\Emogrifier\CssInliner;
use Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter;
use Pelago\Emogrifier\HtmlProcessor\HtmlPruner;
use Elementor_Pay_Addons\Shared\Logger;
use Exception;

if (!defined('ABSPATH')) {
  exit;
}

class Mailer
{
  private static $instance = null;

  protected $placeholders = array();

  public function __construct($emailId)
  {
    $this->id = $emailId;
    $this->placeholders = array(
      '{site_title}'   => $this->get_blogname(),
      '{site_address}' => home_url(),
      '{site_url}'     => home_url(),
      '{header_text}'  => $this->get_email_option('headerText')
    );
  }

  public function get_blogname()
  {
    return wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
  }

  /**
   * Format email string.
   *
   * @param mixed $string Text to replace placeholders in.
   * @return string
   */
  public function format_string($string)
  {
    $find    = array_keys($this->placeholders);
    $replace = array_values($this->placeholders);
    return apply_filters('elementor_pay_addons/email/format_string', str_replace($find, $replace, $string), $this);
  }

  /**
   * Get email subject.
   *
   * @return string
   */
  public function get_subject()
  {
    return apply_filters('elementor_pay_addons/email/subject_' . $this->id, $this->format_string($this->get_email_option('subject')));
  }

  /**
   * Get valid recipients.
   *
   * @return string
   */
  public function get_recipient()
  {
    $recipient = $this->get_email_option('recipients') ?: get_option('admin_email');
    $recipient  = apply_filters('elementor_pay_addons/email/recipient_' . $this->id, $recipient);
    $recipient = $this->format_string($recipient);
    $recipients = array_map('trim', explode(',', $recipient));
    $recipients = array_filter($recipients, 'is_email');
    return implode(', ', $recipients);
  }

  /**
   * Get email headers.
   *
   * @return string
   */
  public function get_headers()
  {
    $header = 'Content-Type: ' . $this->get_content_type() . "\r\n";

    if ($this->get_from_address() && $this->get_from_name()) {
      $header .= 'Reply-to: ' . $this->get_from_name() . ' <' . $this->get_from_address() . ">\r\n";
    }

    return apply_filters('elementor_pay_addons/email/headers', $header, $this->id, $this->object, $this);
  }

  /**
   * Get email content type.
   *
   * @param string $default_content_type Default wp_mail() content type.
   * @return string
   */
  public function get_content_type($default_content_type = '')
  {
    return 'text/html';
  }

  /**
   * Depracted: Checks if this email is customer focussed.
   *
   * @return bool
   */
  public function is_customer_email()
  {
    return $this->get_email_option('recipients') == 'customer';
  }

  public function is_enabled()
  {
    return $this->get_email_option('enabled');
  }

  /**
   * Get the from name for outgoing emails.
   *
   * @param string $from_name Default wp_mail() name associated with the "from" email address.
   * @return string
   */
  public function get_from_name($from_name = '')
  {
    $from_name = apply_filters('elementor_pay_addons/email/from_name', get_option('epa_email_sender_from_name'), $this, $from_name);
    return wp_specialchars_decode(esc_html($from_name), ENT_QUOTES);
  }

  /**
   * Get the from address for outgoing emails.
   *
   * @param string $from_email Default wp_mail() email address to send from.
   * @return string
   */
  public function get_from_address($from_email = '')
  {
    $from_email = apply_filters('elementor_pay_addons/email/from_address', get_option('epa_email_sender_from_email'), $this, $from_email);
    return sanitize_email($from_email);
  }

  /**
   * Get the email content in HTML format.
   *
   * @return string
   */
  public function get_content_html()
  {
    $email_html_content = self::get_email_template_by_name('elementor_pay_addons/email/default_theme');

    $content = self::get_email_by_id($this->id)['htmlContent'];

    $email_html_content = str_replace('{{__email_body__}}', $content, $email_html_content);

    return apply_filters('elementor_pay_addons/email/html_' . $this->id, $this->format_string($email_html_content));
  }

  /**
   * Apply inline styles to dynamic content.
   *
   */
  public function style_inline($content)
  {
    $css = self::get_email_template_by_name('elementor_pay_addons/email/default_theme_styles');

    $css = $this->style_inline_replace($css);
    if (class_exists('DOMDocument')) {
      try {
        $css_inliner = CssInliner::fromHtml( $content )->inlineCss( $css );

        $dom_document = $css_inliner->getDomDocument();

        HtmlPruner::fromDomDocument($dom_document)->removeElementsWithDisplayNone();
        $content = CssToAttributeConverter::fromDomDocument($dom_document)
        ->convertCssToVisualAttributes()
        ->render();
      } catch (Exception $e) {
        Logger::error($e->getMessage(), array('source' => 'emogrifier'));
      }
    } else {
      $content = '<style type="text/css">' . $css . '</style>' . $content;
    }

    return $content;
  }

  public function style_inline_replace($css)
  {
    $bg = get_option('epa_email_style_background_color');
    $body = get_option('epa_email_style_body_background_color');
    $base = get_option('epa_email_style_base_color');
    $base_text = epa_light_or_dark($base, '#202020', '#ffffff');
    $text = get_option('epa_email_style_text_color');
    // Pick a contrasting color for links.
    $link_color = epa_hex_is_light($base) ? $base : $base_text;

    if (epa_hex_is_light($body)) {
      $link_color = epa_hex_is_light($base) ? $base_text : $base;
    }

    $bg_darker_10    = epa_hex_darker($bg, 10);
    $body_darker_10  = epa_hex_darker($body, 10);
    $base_lighter_20 = epa_hex_lighter($base, 20);
    $base_lighter_40 = epa_hex_lighter($base, 40);
    $text_lighter_20 = epa_hex_lighter($text, 20);
    $text_lighter_40 = epa_hex_lighter($text, 40);

    $placeholders = array();
    $placeholders['#bg#'] = $bg;
    $placeholders['#body#'] = $body;
    $placeholders['#base#'] = $base;
    $placeholders['#base_text#'] = $base_text;
    $placeholders['#text#'] = $text;
    $placeholders['#link_color#'] = $link_color;
    $placeholders['#bg_darker_10#'] = $bg_darker_10;
    $placeholders['#body_darker_10#'] = $body_darker_10;
    $placeholders['#base_lighter_20#'] = $base_lighter_20;
    $placeholders['#base_lighter_40#'] = $base_lighter_40;
    $placeholders['#text_lighter_20#'] = $text_lighter_20;
    $placeholders['#text_lighter_40#'] = $text_lighter_40;

    $find    = array_keys($placeholders);
    $replace = array_values($placeholders);
    return apply_filters('elementor_pay_addons/email/format_style', str_replace($find, $replace, $css), $this);
  }

  /**
   * Send an email.
   *
   * @param string $to Email to.
   * @param string $subject Email subject.
   * @param string $message Email message.
   * @param string $headers Email headers.
   * @param array  $attachments Email attachments.
   * @return bool success
   */
  protected function send($to, $subject, $message, $headers, $attachments = array())
  {
    add_filter('wp_mail_from', array($this, 'get_from_address'));
    add_filter('wp_mail_from_name', array($this, 'get_from_name'));
    add_filter('wp_mail_content_type', array($this, 'get_content_type'));

    $message              = apply_filters('elementor_pay_addons/email/content', $this->style_inline($message));
    $mail_callback        = apply_filters('elementor_pay_addons/email/callback', 'wp_mail', $this);
    $mail_callback_params = apply_filters('elementor_pay_addons/email/callback_params', array($to, $subject, $message, $headers, $attachments), $this);
    $return               = $mail_callback(...$mail_callback_params);

    if ($return == true) {
      Logger::info("Email $this->id was sent successfully, recipient: $to");
    } else {
      Logger::error("Email $this->id could not be sent, recipient: $to");
      Logger::error($message);
    }

    remove_filter('wp_mail_from', array($this, 'get_from_address'));
    remove_filter('wp_mail_from_name', array($this, 'get_from_name'));
    remove_filter('wp_mail_content_type', array($this, 'get_content_type'));

    return $return;
  }

  public function trigger()
  {
    $recipients = $this->get_recipient();
    // remove in future when there is no `customer` hardcode
    if ($this->is_customer_email()) {
      $recipients = $this->placeholders['{customer.email}'];
    }
    if ($this->is_enabled() && $recipients) {
      $this->send($recipients, $this->get_subject(), $this->get_content_html(), $this->get_headers());
    }
  }

  public function set_placeholders($placeholders = array())
  {
    foreach($placeholders as $key => $value)
    {
        unset($placeholders[$key]);
        $placeholders['{' . $key . '}'] = $value;
    }
    $this->placeholders = array_merge(
      $this->placeholders,
      $placeholders
    );
    return $this;
  }

  public function get_email_option($key, $empty_value = null)
  {
    $email = self::get_email_by_id($this->id);
    if ($email) {
      return $email[$key];
    }
    return $empty_value;
  }

  static function get_email_by_id($id)
  {
    $emails = get_option('epa_email_list');
    $email = array_filter($emails, function ($ar) use ($id) {
      return $ar['id'] == $id;
    });

    if (count($email) > 0) {
      $found = array_pop(array_reverse($email));
      if (self::is_custom_template_enabled()) {
        $templatePath = self::get_email_template_path_from_theme($id);
        if (file_exists($templatePath)) {
          $found['htmlContent'] = self::get_email_template_by_name($id);
        }
      }
      return $found;
    }
    return null;
  }

  static function update_email_by_id($emailId, $email_data)
  {
    $emails = get_option('epa_email_list');
    $currentEmailKey = array_search($emailId, array_column($emails, 'id'));
    $currentEmail = $emails[$currentEmailKey];
    if (!empty($currentEmail) && !empty($email_data['htmlContent'])) {
      foreach ($currentEmail as $key => $value) {
        if (array_key_exists($key, $email_data)) {
          $emails[$currentEmailKey][$key] = $email_data[$key];
        }
      }
      update_option('epa_email_list', $emails);

      // update custom template file if enable
      if (self::is_custom_template_enabled()) {
        $file = self::get_email_template_path_from_theme($emailId);
        $content = $email_data['htmlContent'];
        if (file_exists($file)) {
          if (is_writeable($file)) { // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_is_writeable
            $f = fopen($file, 'w+'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen

            if (false !== $f) {
              fwrite($f, $content); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
              fclose($f); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
              $saved = true;
            }
          }

          if (!$saved) {
            Logger::error("Could not write to template file $file");
            return false;
          }
        }
      }
      return true;
    }
    return false;
  }

  static function get_email_setting()
  {
    return array(
      'fromName' => get_option('epa_email_sender_from_name'),
      'fromEmail' => get_option('epa_email_sender_from_email'),
      'backgroundColor' => get_option('epa_email_style_background_color'),
      'bodyBackgroundColor' => get_option('epa_email_style_body_background_color'),
      'baseColor' => get_option('epa_email_style_base_color'),
      'textColor' => get_option('epa_email_style_text_color'),
      'enableCustom' => boolval(get_option('epa_email_enable_custom_template', false)),
    );
  }

  static function update_email_setting($setting)
  {
    !empty($setting["fromName"]) && update_option('epa_email_sender_from_name', $setting["fromName"]);
    !empty($setting["fromEmail"]) && update_option('epa_email_sender_from_email', $setting["fromEmail"]);
    !empty($setting["backgroundColor"]) && update_option('epa_email_style_background_color', $setting["backgroundColor"]);
    !empty($setting["bodyBackgroundColor"]) && update_option('epa_email_style_body_background_color', $setting["bodyBackgroundColor"]);
    !empty($setting["baseColor"]) && update_option('epa_email_style_base_color', $setting["baseColor"]);
    !empty($setting["textColor"]) && update_option('epa_email_style_text_color', $setting["textColor"]);
    isset($setting["enableCustom"]) && update_option('epa_email_enable_custom_template', $setting["enableCustom"]);
    return true;
  }

  static function get_email_template_path_from_theme($name)
  {
    return get_stylesheet_directory() . '/' . EPA_PLUGIN_NAME . '/emails/' . $name . '.html';
  }

  static function get_email_template_path($name)
  {
    if (self::is_custom_template_enabled()) {
      $file_path = self::get_email_template_path_from_theme($name);
      if (file_exists($file_path)) {
        return $file_path;
      }
    }
    return EPA_ADDONS_EMAILS_TEMPLATE_PATH . $name . '.html';
  }

  static function get_email_template_by_name($name)
  {
    $templatePath = self::get_email_template_path($name);
    if (file_exists($templatePath)) {
      return file_get_contents($templatePath);
    }
    return "";
  }

  static function is_custom_template_enabled()
  {
    return get_option('epa_email_enable_custom_template');
  }

  public static function get_emails()
  {
    if (self::$instance == null) {
      self::$instance = array(
        'epa_email_pay_success' => new self('epa_email_pay_success'),
        'epa_email_pay_fail' => new self('epa_email_pay_fail'),
        'epa_email_invoice' => new self('epa_email_invoice'),
      );
    }
    return self::$instance;
  }

  static function init_emails()
  {
    // Init emails
    if (!empty(get_option('epa_email_list'))) {
      return;
    }

    Logger::info('init_emails');

    $current_user = wp_get_current_user();
    update_option('epa_email_enable_custom_template', false);
    update_option('epa_email_sender_from_name', $current_user->display_name);
    update_option('epa_email_sender_from_email', $current_user->user_email);
    update_option('epa_email_style_background_color', '#f7f7f7');
    update_option('epa_email_style_body_background_color', '#ffffff');
    update_option('epa_email_style_base_color', '#4a90e2');
    update_option('epa_email_style_text_color', '#3c3c3c');

    update_option('epa_email_list', array(
      array(
        "id" => "epa_email_pay_fail",
        "name" => "Payment failed",
        "enabled" => false,
        "recipients" => $current_user->user_email,
        "subject" => "Payment Failed from {customer.email}",
        "headerText" => "Ops, Payment Failed",
        "description" => "Payment failed notification is sent to chosen recipient(s) when payment failed.",
        "type" => "html",
        "htmlContent" => self::get_email_template_by_name('epa_email_pay_fail')
      ),
      array(
        "id" => "epa_email_pay_success",
        "name" => "Payment success",
        "enabled" => false,
        "recipients" => $current_user->user_email,
        "subject" => "W00t! You received a payment from {customer.email}",
        "headerText" => "You recevied a payment!",
        "description" => "Payment successfully notification is sent to chosen recipient(s).",
        "type" => "html",
        "htmlContent" => self::get_email_template_by_name('epa_email_pay_success')
      ),
      array(
        "id" => "epa_email_invoice",
        "name" => "Customer invoice",
        "enabled" => false,
        "recipients" => "{customer.email}",
        "subject" => "Customer Invoice",
        "headerText" => "Thanks for shopping with us!",
        "description" => "Customer invoice emails can be sent to customers containing their receipt url.",
        "type" => "html",
        "htmlContent" => self::get_email_template_by_name('epa_email_invoice')
      ),
    ));
  }
}

// Copy from WooCommerce
if (!function_exists('epa_rgb_from_hex')) {

  /**
   * Convert RGB to HEX.
   *
   * @param mixed $color Color.
   *
   * @return array
   */
  function epa_rgb_from_hex($color)
  {
    $color = str_replace('#', '', $color);
    // Convert shorthand colors to full format, e.g. "FFF" -> "FFFFFF".
    $color = preg_replace('~^(.)(.)(.)$~', '$1$1$2$2$3$3', $color);

    $rgb      = array();
    $rgb['R'] = hexdec($color[0] . $color[1]);
    $rgb['G'] = hexdec($color[2] . $color[3]);
    $rgb['B'] = hexdec($color[4] . $color[5]);

    return $rgb;
  }
}

if (!function_exists('epa_hex_darker')) {

  /**
   * Make HEX color darker.
   *
   * @param mixed $color  Color.
   * @param int   $factor Darker factor.
   *                      Defaults to 30.
   * @return string
   */
  function epa_hex_darker($color, $factor = 30)
  {
    $base  = epa_rgb_from_hex($color);
    $color = '#';

    foreach ($base as $k => $v) {
      $amount      = $v / 100;
      $amount      = round($amount * $factor);
      $new_decimal = $v - $amount;

      $new_hex_component = dechex($new_decimal);
      if (strlen($new_hex_component) < 2) {
        $new_hex_component = '0' . $new_hex_component;
      }
      $color .= $new_hex_component;
    }

    return $color;
  }
}

if (!function_exists('epa_hex_lighter')) {

  /**
   * Make HEX color lighter.
   *
   * @param mixed $color  Color.
   * @param int   $factor Lighter factor.
   *                      Defaults to 30.
   * @return string
   */
  function epa_hex_lighter($color, $factor = 30)
  {
    $base  = epa_rgb_from_hex($color);
    $color = '#';

    foreach ($base as $k => $v) {
      $amount      = 255 - $v;
      $amount      = $amount / 100;
      $amount      = round($amount * $factor);
      $new_decimal = $v + $amount;

      $new_hex_component = dechex($new_decimal);
      if (strlen($new_hex_component) < 2) {
        $new_hex_component = '0' . $new_hex_component;
      }
      $color .= $new_hex_component;
    }

    return $color;
  }
}

if (!function_exists('epa_hex_is_light')) {

  /**
   * Determine whether a hex color is light.
   *
   * @param mixed $color Color.
   * @return bool  True if a light color.
   */
  function epa_hex_is_light($color)
  {
    $hex = str_replace('#', '', $color);

    $c_r = hexdec(substr($hex, 0, 2));
    $c_g = hexdec(substr($hex, 2, 2));
    $c_b = hexdec(substr($hex, 4, 2));

    $brightness = (($c_r * 299) + ($c_g * 587) + ($c_b * 114)) / 1000;

    return $brightness > 155;
  }
}

if (!function_exists('epa_light_or_dark')) {

  /**
   * Detect if we should use a light or dark color on a background color.
   *
   * @param mixed  $color Color.
   * @param string $dark  Darkest reference.
   *                      Defaults to '#000000'.
   * @param string $light Lightest reference.
   *                      Defaults to '#FFFFFF'.
   * @return string
   */
  function epa_light_or_dark($color, $dark = '#000000', $light = '#FFFFFF')
  {
    return epa_hex_is_light($color) ? $dark : $light;
  }
}

if (!function_exists('epa_format_hex')) {

  /**
   * Format string as hex.
   *
   * @param string $hex HEX color.
   * @return string|null
   */
  function epa_format_hex($hex)
  {
    $hex = trim(str_replace('#', '', $hex));

    if (strlen($hex) === 3) {
      $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    return $hex ? '#' . $hex : null;
  }
}
