<?php

// Displays PayPal Payment Accept Options menu
function paypal_payment_add_option_pages() {
    if (function_exists('add_options_page')) {
        add_options_page('WP Paypal Payment Accept', 'WP PayPal Payment', 'manage_options', __FILE__, 'paypal_payment_options_page');
    }
}
// Insert the paypal_payment_add_option_pages in the 'admin_menu'
add_action('admin_menu', 'paypal_payment_add_option_pages');

function paypal_payment_options_page() {

    if(!current_user_can('manage_options')){
        wp_die('You do not have permission to access this settings page.');
    }
    
    if (isset($_POST['info_update'])) {
    	$nonce = $_REQUEST['_wpnonce'];
        if ( !wp_verify_nonce($nonce, 'wp_accept_pp_payment_settings_update')){
                wp_die('Error! Nonce Security Check Failed! Go back to settings menu and save the settings again.');
        }

        $value1 = filter_input(INPUT_POST, 'wp_pp_payment_value1', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $value2 = filter_input(INPUT_POST, 'wp_pp_payment_value2', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $value3 = filter_input(INPUT_POST, 'wp_pp_payment_value3', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $value4 = filter_input(INPUT_POST, 'wp_pp_payment_value4', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $value5 = filter_input(INPUT_POST, 'wp_pp_payment_value5', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $value6 = filter_input(INPUT_POST, 'wp_pp_payment_value6', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        
        update_option('wp_paypal_widget_title_name', sanitize_text_field(stripslashes($_POST["wp_paypal_widget_title_name"])));
        update_option('wp_pp_payment_email', sanitize_email($_POST["wp_pp_payment_email"]));
        update_option('paypal_payment_currency', sanitize_text_field($_POST["paypal_payment_currency"]));
        update_option('wp_pp_payment_subject', sanitize_text_field(stripslashes($_POST["wp_pp_payment_subject"])));
        update_option('wp_pp_payment_item1', sanitize_text_field(stripslashes($_POST["wp_pp_payment_item1"])));
        update_option('wp_pp_payment_value1', $value1);
        update_option('wp_pp_payment_item2', sanitize_text_field(stripslashes($_POST["wp_pp_payment_item2"])));
        update_option('wp_pp_payment_value2', $value2);
        update_option('wp_pp_payment_item3', sanitize_text_field(stripslashes($_POST["wp_pp_payment_item3"])));
        update_option('wp_pp_payment_value3', $value3);
        update_option('wp_pp_payment_item4', sanitize_text_field(stripslashes($_POST["wp_pp_payment_item4"])));
        update_option('wp_pp_payment_value4', $value4);
        update_option('wp_pp_payment_item5', sanitize_text_field(stripslashes($_POST["wp_pp_payment_item5"])));
        update_option('wp_pp_payment_value5', $value5);
        update_option('wp_pp_payment_item6', sanitize_text_field(stripslashes($_POST["wp_pp_payment_item6"])));
        update_option('wp_pp_payment_value6', $value6);
        update_option('payment_button_type', sanitize_text_field($_POST["payment_button_type"]));
        update_option('wp_pp_show_other_amount', isset($_POST['wp_pp_show_other_amount']) ? '1' : '-1' );
        update_option('wp_pp_show_ref_box', isset($_POST['wp_pp_show_ref_box']) ? '1' : '-1' );
        update_option('wp_pp_ref_title', sanitize_text_field(stripslashes($_POST["wp_pp_ref_title"])));
        update_option('wp_pp_return_url', esc_url_raw(sanitize_text_field($_POST["wp_pp_return_url"])));
        
        $cancel_url = esc_url_raw(sanitize_text_field($_POST["wp_pp_cancel_url"]));
        if(empty($cancel_url)){
            $cancel_url = home_url();
        }
        update_option('wp_pp_cancel_url', $cancel_url);

        echo '<div id="message" class="updated fade"><p><strong>';
        echo 'Options Updated!';
        echo '</strong></p></div>';
    }

    $paypal_payment_currency = stripslashes(get_option('paypal_payment_currency'));
    $payment_button_type = stripslashes(get_option('payment_button_type'));
    ?>

    <div class=wrap>
    <h2>Accept Paypal Payment Settings v<?php echo WP_PAYPAL_PAYMENT_ACCEPT_PLUGIN_VERSION; ?></h2>

    <div id="poststuff"><div id="post-body">

        <div style="background: none repeat scroll 0 0 #ECECEC;border: 1px solid #CFCFCF;color: #363636;margin: 10px 0 15px;padding:15px;text-shadow: 1px 1px #FFFFFF;">
            For usage documentation and updates, please visit the plugin page at the following URL:<br />
            <a href="https://www.tipsandtricks-hq.com/wordpress-easy-paypal-payment-or-donation-accept-plugin-120" target="_blank">https://www.tipsandtricks-hq.com/wordpress-easy-paypal-payment-or-donation-accept-plugin-120</a>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('wp_accept_pp_payment_settings_update'); ?>
            
            <input type="hidden" name="info_update" id="info_update" value="true" />

            <div class="postbox">
            <h3 class="hndle"><label for="title">Plugin Usage</label></h3>
            <div class="inside">      
                <p>There are a few different ways you can use this plugin:</p>
                <ol>
                    <li>Configure the options below and then add the shortcode <strong>[wp_paypal_payment]</strong> to a post or page (where you want the payment button)</li>
                    <li>Use the shortcode with custom parameter options to add multiple different payment widgets with different configuration.
                        <a href="https://www.tipsandtricks-hq.com/wordpress-easy-paypal-payment-or-donation-accept-plugin-120#shortcode_with_custom_parameters" target="_blank">View shortcode documentation</a></li>
                    <li>Call the function from a template file: <strong>&lt;?php echo Paypal_payment_accept(); ?&gt;</strong></li>
                    <li>Use the <strong>WP Paypal Payment</strong> Widget from the Widgets menu</li>                    
                </ol>
            </div></div>

            <div class="postbox">
            <h3 class="hndle"><label for="title">WP Paypal Payment or Donation Accept Plugin Options</label></h3>
            <div class="inside">

                <table class="form-table">

                    <tr valign="top"><td width="25%" align="left">
                            <strong>WP Paypal Payment Widget Title:</strong>
                        </td><td align="left">
                            <input name="wp_paypal_widget_title_name" type="text" size="30" value="<?php echo esc_attr(get_option('wp_paypal_widget_title_name')); ?>"/>
                            <br /><i>This will be the title of the Widget on the Sidebar if you use it.</i><br />
                        </td></tr>
                    
                    <tr valign="top"><td width="25%" align="left">
                            <strong>Paypal Email address:</strong>
                        </td><td align="left">
                            <input name="wp_pp_payment_email" type="text" size="35" value="<?php echo esc_attr(get_option('wp_pp_payment_email')); ?>"/>
                            <br /><i>This is the Paypal Email address where the payments will go</i><br />
                        </td></tr>

                    <tr valign="top"><td width="25%" align="left">
                            <strong>Choose Payment Currency: </strong>
                        </td><td align="left">
                            <select id="paypal_payment_currency" name="paypal_payment_currency">
    <?php _e('<option value="USD"') ?><?php if ($paypal_payment_currency == "USD") echo " selected " ?><?php _e('>US Dollar</option>') ?>
    <?php _e('<option value="GBP"') ?><?php if ($paypal_payment_currency == "GBP") echo " selected " ?><?php _e('>Pound Sterling</option>') ?>
    <?php _e('<option value="EUR"') ?><?php if ($paypal_payment_currency == "EUR") echo " selected " ?><?php _e('>Euro</option>') ?>
    <?php _e('<option value="AUD"') ?><?php if ($paypal_payment_currency == "AUD") echo " selected " ?><?php _e('>Australian Dollar</option>') ?>
    <?php _e('<option value="CAD"') ?><?php if ($paypal_payment_currency == "CAD") echo " selected " ?><?php _e('>Canadian Dollar</option>') ?>
    <?php _e('<option value="NZD"') ?><?php if ($paypal_payment_currency == "NZD") echo " selected " ?><?php _e('>New Zealand Dollar</option>') ?>
    <?php _e('<option value="HKD"') ?><?php if ($paypal_payment_currency == "HKD") echo " selected " ?><?php _e('>Hong Kong Dollar</option>') ?>
                            </select>
                            <br /><i>This is the currency for your visitors to make Payments or Donations in.</i><br />
                        </td></tr>

                    <tr valign="top"><td width="25%" align="left">
                            <strong>Payment Subject:</strong>
                        </td><td align="left">
                            <input name="wp_pp_payment_subject" type="text" size="35" value="<?php echo esc_attr(get_option('wp_pp_payment_subject')); ?>"/>
                            <br /><i>Enter the Product or service name or the reason for the payment here. The visitors will see this text</i><br />
                        </td></tr>

                    <tr valign="top"><td width="25%" align="left">
                            <strong>Payment Option 1:</strong>
                        </td><td align="left">
                            <input name="wp_pp_payment_item1" type="text" size="25" value="<?php echo esc_attr(get_option('wp_pp_payment_item1')); ?>"/>
                            <strong>Price :</strong>
                            <input name="wp_pp_payment_value1" type="text" size="10" value="<?php echo esc_attr(get_option('wp_pp_payment_value1')); ?>"/>
                            <br />
                        </td></tr>

                    <tr valign="top"><td width="25%" align="left">
                            <strong>Payment Option 2:</strong>
                        </td><td align="left">
                            <input name="wp_pp_payment_item2" type="text" size="25" value="<?php echo esc_attr(get_option('wp_pp_payment_item2')); ?>"/>
                            <strong>Price :</strong>
                            <input name="wp_pp_payment_value2" type="text" size="10" value="<?php echo esc_attr(get_option('wp_pp_payment_value2')); ?>"/>
                            <br />
                        </td></tr>

                    <tr valign="top"><td width="25%" align="left">
                            <strong>Payment Option 3:</strong>
                        </td><td align="left">
                            <input name="wp_pp_payment_item3" type="text" size="25" value="<?php echo esc_attr(get_option('wp_pp_payment_item3')); ?>"/>
                            <strong>Price :</strong>
                            <input name="wp_pp_payment_value3" type="text" size="10" value="<?php echo esc_attr(get_option('wp_pp_payment_value3')); ?>"/>
                            <br />
                        </td></tr>

                    <tr valign="top"><td width="25%" align="left">
                            <strong>Payment Option 4:</strong>
                        </td><td align="left">
                            <input name="wp_pp_payment_item4" type="text" size="25" value="<?php echo esc_attr(get_option('wp_pp_payment_item4')); ?>"/>
                            <strong>Price :</strong>
                            <input name="wp_pp_payment_value4" type="text" size="10" value="<?php echo esc_attr(get_option('wp_pp_payment_value4')); ?>"/>
                            <br />
                        </td></tr>

                    <tr valign="top"><td width="25%" align="left">
                            <strong>Payment Option 5:</strong>
                        </td><td align="left">
                            <input name="wp_pp_payment_item5" type="text" size="25" value="<?php echo esc_attr(get_option('wp_pp_payment_item5')); ?>"/>
                            <strong>Price :</strong>
                            <input name="wp_pp_payment_value5" type="text" size="10" value="<?php echo esc_attr(get_option('wp_pp_payment_value5')); ?>"/>
                            <br />
                        </td></tr>

                    <tr valign="top"><td width="25%" align="left">
                            <strong>Payment Option 6:</strong>
                        </td><td align="left">
                            <input name="wp_pp_payment_item6" type="text" size="25" value="<?php echo esc_attr(get_option('wp_pp_payment_item6')); ?>"/>
                            <strong>Price :</strong>
                            <input name="wp_pp_payment_value6" type="text" size="10" value="<?php echo esc_attr(get_option('wp_pp_payment_value6')); ?>"/>
                            <br /><i>Enter the name of the service or product and the price. eg. Enter "Basic service - $10" in the Payment Option text box and "10.00" in the price text box to accept a payment of $10 for "Basic service". Leave the Payment Option and Price fields empty if u don't want to use that option. For example, if you have 3 price options then fill in the top 3 and leave the rest empty.</i>
                        </td></tr>
                    
                    <tr valign="top"><td width="25%" align="left">
                            <strong>Show Other Amount:</strong>
                        </td><td align="left">
                            <input name="wp_pp_show_other_amount" type="checkbox"<?php if (get_option('wp_pp_show_other_amount') != '-1') echo ' checked="checked"'; ?> value="1"/>
                            <i> Tick this checkbox if you want to show ohter amount text box to your visitors so they can enter custom amount.</i>
                        </td></tr>
                   
                    <tr valign="top"><td width="25%" align="left">
                            <strong>Show Reference Text Box:</strong>
                        </td><td align="left">
                            <input name="wp_pp_show_ref_box" type="checkbox"<?php if (get_option('wp_pp_show_ref_box') != '-1') echo ' checked="checked"'; ?> value="1"/>
                            <i> Tick this checkbox if you want your visitors to be able to enter a reference text like email or web address.</i>
                        </td></tr>

                    <tr valign="top"><td width="25%" align="left">
                            <strong>Reference Text Box Title:</strong>
                        </td><td align="left">
                            <input name="wp_pp_ref_title" type="text" size="35" value="<?php echo esc_attr(get_option('wp_pp_ref_title')); ?>"/>
                            <br /><i>Enter a title for the Reference text box (ie. Your Web Address). The visitors will see this text.</i><br />
                        </td></tr>

                    <tr valign="top">
                        <td width="25%" align="left">
                            <strong>Return URL from PayPal:</strong>
                        </td><td align="left">
                            <input name="wp_pp_return_url" type="text" size="60" value="<?php echo esc_url(get_option('wp_pp_return_url')); ?>"/>
                            <br /><i>Enter a return URL (could be a Thank You page). PayPal will redirect visitors to this page after Payment.</i><br />
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <td width="25%" align="left">
                            <strong>Cancel URL from PayPal:</strong>
                        </td><td align="left">
                            <input name="wp_pp_cancel_url" type="text" size="60" value="<?php echo esc_url(get_option('wp_pp_cancel_url')); ?>"/>
                            <br /><i>Enter a cancel URL. PayPal will redirect visitors to this page if they click on the cancel link.</i><br />
                        </td>
                    </tr>
                    
                </table>

                <br /><br />
                <strong>Choose a Submit Button Type :</strong>
                <br /><i>This is the button the visitors will click on to make Payments or Donations.</i><br />
                <table style="width:50%; border-spacing:0; padding:0; text-align:center;">
                    <tr>
                        <td>
    <?php _e('<input type="radio" name="payment_button_type" value="https://www.paypal.com/en_US/i/btn/btn_paynowCC_LG.gif"') ?>
    <?php if ($payment_button_type == "https://www.paypal.com/en_US/i/btn/btn_paynowCC_LG.gif") echo " checked " ?>
    <?php _e('/>') ?>
                        </td>
                        <td>
    <?php _e('<input type="radio" name="payment_button_type" value="https://www.paypal.com/en_US/i/btn/x-click-but11.gif"') ?>
    <?php if ($payment_button_type == "https://www.paypal.com/en_US/i/btn/x-click-but11.gif") echo " checked " ?>
    <?php _e('/>') ?>
                        </td>
                    </tr>
                    <tr>
                        <td><img border="0" src="https://www.paypal.com/en_US/i/btn/btn_paynowCC_LG.gif" alt="" /></td>
                        <td><img border="0" src="https://www.paypal.com/en_US/i/btn/x-click-but11.gif" alt="" /></td>
                    </tr>
                </table>

            </div></div><!-- end of postbox -->
            
            <div class="submit">
                <input type="submit" class="button-primary" name="info_update" value="<?php _e('Update options'); ?> &raquo;" />
            </div>
        </form>

        <div style="background: none repeat scroll 0 0 #FFF6D5;border: 1px solid #D1B655;color: #3F2502;margin: 10px 0;padding: 5px 5px 5px 10px;text-shadow: 1px 1px #FFFFFF;">	
            <p>If you need a feature rich and supported plugin for accepting PayPal payment then check out our <a href="https://www.tipsandtricks-hq.com/wordpress-estore-plugin-complete-solution-to-sell-digital-products-from-your-wordpress-blog-securely-1059" target="_blank">WP eStore Plugin</a> (You will love the WP eStore Plugin).
            </p>
        </div>

    </div></div> <!-- end of .poststuff and post-body -->
    </div><!-- end of .wrap -->
    <?php
}
