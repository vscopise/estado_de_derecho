<?php
/*
Plugin Name: WP Easy Paypal Payment Accept
Version: v4.9.5
Plugin URI: https://www.tipsandtricks-hq.com/wordpress-easy-paypal-payment-or-donation-accept-plugin-120
Author: Tips and Tricks HQ
Author URI: https://www.tipsandtricks-hq.com/
Description: Easy to use Wordpress plugin to accept paypal payment for a service or product or donation in one click. Can be used in the sidebar, posts and pages.
License: GPL2
*/

//Slug - wpapp

if (!defined('ABSPATH')){//Exit if accessed directly
    exit;
}

define('WP_PAYPAL_PAYMENT_ACCEPT_PLUGIN_VERSION', '4.9.5');
define('WP_PAYPAL_PAYMENT_ACCEPT_PLUGIN_URL', plugins_url('', __FILE__));

include_once('shortcode_view.php');
include_once('wpapp_admin_menu.php');
include_once('wpapp_paypal_utility.php');

function wp_pp_plugin_install() {
    // Some default options
    add_option('wp_pp_payment_email', get_bloginfo('admin_email'));
    add_option('paypal_payment_currency', 'USD');
    add_option('wp_pp_payment_subject', 'Plugin Service Payment');
    add_option('wp_pp_payment_item1', 'Basic Service - $10');
    add_option('wp_pp_payment_value1', '10');
    add_option('wp_pp_payment_item2', 'Gold Service - $20');
    add_option('wp_pp_payment_value2', '20');
    add_option('wp_pp_payment_item3', 'Platinum Service - $30');
    add_option('wp_pp_payment_value3', '30');
    add_option('wp_paypal_widget_title_name', 'Paypal Payment');
    add_option('payment_button_type', 'https://www.paypal.com/en_US/i/btn/btn_paynowCC_LG.gif');
    add_option('wp_pp_show_other_amount', '-1');
    add_option('wp_pp_show_ref_box', '1');
    add_option('wp_pp_ref_title', 'Your Email Address');
    add_option('wp_pp_return_url', home_url());
    add_option('wp_pp_cancel_url', home_url());
}

register_activation_hook(__FILE__, 'wp_pp_plugin_install');

add_shortcode('wp_paypal_payment_box_for_any_amount', 'wpapp_buy_now_any_amt_handler');

function wpapp_buy_now_any_amt_handler($args) {
    $output = wppp_render_paypal_button_with_other_amt($args);
    return $output;
}

add_shortcode('wp_paypal_payment_box', 'wpapp_buy_now_button_shortcode');

function wpapp_buy_now_button_shortcode($args) {
    ob_start();
    wppp_render_paypal_button_form($args);
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
}

function Paypal_payment_accept() {
    $paypal_email = get_option('wp_pp_payment_email');
    $payment_currency = get_option('paypal_payment_currency');
    $paypal_subject = get_option('wp_pp_payment_subject');

    $itemName1 = get_option('wp_pp_payment_item1');
    $value1 = get_option('wp_pp_payment_value1');
    $itemName2 = get_option('wp_pp_payment_item2');
    $value2 = get_option('wp_pp_payment_value2');
    $itemName3 = get_option('wp_pp_payment_item3');
    $value3 = get_option('wp_pp_payment_value3');
    $itemName4 = get_option('wp_pp_payment_item4');
    $value4 = get_option('wp_pp_payment_value4');
    $itemName5 = get_option('wp_pp_payment_item5');
    $value5 = get_option('wp_pp_payment_value5');
    $itemName6 = get_option('wp_pp_payment_item6');
    $value6 = get_option('wp_pp_payment_value6');
    $payment_button = get_option('payment_button_type');
    $wp_pp_show_other_amount = get_option('wp_pp_show_other_amount');
    $wp_pp_show_ref_box = get_option('wp_pp_show_ref_box');
    $wp_pp_ref_title = get_option('wp_pp_ref_title');
    $wp_pp_return_url = get_option('wp_pp_return_url');
    $wp_pp_cancel_url = get_option('wp_pp_cancel_url');

    /* === Paypal form === */
    $output = '';
    $output .= '<div id="accept_paypal_payment_form">';
    $output .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" class="wp_accept_pp_button_form_classic">';
    $output .= '<input type="hidden" name="cmd" value="_xclick" />';
    $output .= '<input type="hidden" name="business" value="'.esc_attr($paypal_email).'" />';
    $output .= '<input type="hidden" name="item_name" value="'.esc_attr($paypal_subject).'" />';
    $output .= '<input type="hidden" name="currency_code" value="'.esc_attr($payment_currency).'" />';
    $output .= '<div class="wpapp_payment_subject"><span class="payment_subject"><strong>'.esc_attr($paypal_subject).'</strong></span></div>';
    $output .= '<select id="amount" name="amount" class="">';
    $output .= '<option value="'.esc_attr($value1).'">'.esc_attr($itemName1).'</option>';
    if (!empty($value2)) {
        $output .= '<option value="'.esc_attr($value2).'">'.esc_attr($itemName2).'</option>';
    }
    if (!empty($value3)) {
        $output .= '<option value="'.esc_attr($value3).'">'.esc_attr($itemName3).'</option>';
    }
    if (!empty($value4)) {
        $output .= '<option value="'.esc_attr($value4).'">'.esc_attr($itemName4).'</option>';
    }
    if (!empty($value5)) {
        $output .= '<option value="'.esc_attr($value5).'">'.esc_attr($itemName5).'</option>';
    }
    if (!empty($value6)) {
        $output .= '<option value="'.esc_attr($value6).'">'.esc_attr($itemName6).'</option>';
    }

    $output .= '</select>';

    // Show other amount text box
    if ($wp_pp_show_other_amount == '1') {
        $output .= '<div class="wpapp_other_amount_label"><strong>Other Amount:</strong></div>';
        $output .= '<div class="wpapp_other_amount_input"><input type="number" min="1" step="any" name="other_amount" title="Other Amount" value="" class="wpapp_other_amt_input" style="max-width:80px;" /></div>';
    }

    // Show the reference text box
    if ($wp_pp_show_ref_box == '1') {
        $output .= '<div class="wpapp_ref_title_label"><strong>'.esc_attr($wp_pp_ref_title).':</strong></div>';
        $output .= '<input type="hidden" name="on0" value="'.apply_filters('wp_pp_button_reference_name','Reference').'" />';
        $output .= '<div class="wpapp_ref_value"><input type="text" name="os0" maxlength="60" value="'.apply_filters('wp_pp_button_reference_value','').'" class="wp_pp_button_reference" /></div>';
    }

    $output .= '<input type="hidden" name="no_shipping" value="0" /><input type="hidden" name="no_note" value="1" /><input type="hidden" name="bn" value="TipsandTricks_SP" />';
    
    if (!empty($wp_pp_return_url)) {
        $output .= '<input type="hidden" name="return" value="' . esc_url($wp_pp_return_url) . '" />';
    } else {
        $output .='<input type="hidden" name="return" value="' . home_url() . '" />';
    }

    if (!empty($wp_pp_cancel_url)) {
        $output .= '<input type="hidden" name="cancel_return" value="' . esc_url($wp_pp_cancel_url) . '" />';
    }
    
    $output .= '<div class="wpapp_payment_button">';
    $output .= '<input type="image" src="'.esc_url($payment_button).'" name="submit" alt="Make payments with payPal - it\'s fast, free and secure!" />';
    $output .= '</div>';
    
    $output .= '</form>';
    $output .= '</div>';
    $output .= <<<EOT
<script type="text/javascript">
jQuery(document).ready(function($) {
    $('.wp_accept_pp_button_form_classic').submit(function(e){
        var form_obj = $(this);
        var other_amt = form_obj.find('input[name=other_amount]').val();
        if (!isNaN(other_amt) && other_amt.length > 0){
            options_val = other_amt;
            //insert the amount field in the form with the custom amount
            $('<input>').attr({
                type: 'hidden',
                id: 'amount',
                name: 'amount',
                value: options_val
            }).appendTo(form_obj);
        }		
        return;
    });
});
</script>
EOT;
    /* = end of paypal form = */
    return $output;
}

function wp_ppp_process($content) {
    if (strpos($content, "<!-- wp_paypal_payment -->") !== FALSE) {
        $content = preg_replace('/<p>\s*<!--(.*)-->\s*<\/p>/i', "<!--$1-->", $content);
        $content = str_replace('<!-- wp_paypal_payment -->', Paypal_payment_accept(), $content);
    }
    return $content;
}

function show_wp_paypal_payment_widget($args) {
    extract($args);

    $wp_paypal_payment_widget_title_name_value = get_option('wp_paypal_widget_title_name');
    echo $before_widget;
    echo $before_title . $wp_paypal_payment_widget_title_name_value . $after_title;
    echo Paypal_payment_accept();
    echo $after_widget;
}

function wp_paypal_payment_widget_control() {
    ?>
    <p>
    <? _e("Set the Plugin Settings from the Settings menu"); ?>
    </p>
    <?php
}

function wp_paypal_payment_init() {
    wp_register_style('wpapp-styles', WP_PAYPAL_PAYMENT_ACCEPT_PLUGIN_URL . '/wpapp-styles.css');
    wp_enqueue_style('wpapp-styles');

    //Widget code
    $widget_options = array('classname' => 'widget_wp_paypal_payment', 'description' => __("Display WP Paypal Payment."));
    wp_register_sidebar_widget('wp_paypal_payment_widgets', __('WP Paypal Payment'), 'show_wp_paypal_payment_widget', $widget_options);
    wp_register_widget_control('wp_paypal_payment_widgets', __('WP Paypal Payment'), 'wp_paypal_payment_widget_control');
    
    //Listen for IPN and validate it
    if (isset($_REQUEST['wpapp_paypal_ipn']) && $_REQUEST['wpapp_paypal_ipn'] == "process") {
        wpapp_validate_paypl_ipn();
        exit;
    }
}

function wpapp_shortcode_plugin_enqueue_jquery() {
    wp_enqueue_script('jquery');
}

add_filter('the_content', 'wp_ppp_process');
add_shortcode('wp_paypal_payment', 'Paypal_payment_accept');
if (!is_admin()) {
    add_filter('widget_text', 'do_shortcode');
}

add_action('init', 'wpapp_shortcode_plugin_enqueue_jquery');
add_action('init', 'wp_paypal_payment_init');
