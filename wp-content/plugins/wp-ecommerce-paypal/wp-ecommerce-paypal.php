<?php

/*
Plugin Name: PayPal Buy Now Button
Description: Add a PayPal Buy Now Button to your website and start selling today. No Coding Required. Official PayPal Partner.
Plugin URI: https://wpplugin.org/easy-paypal-button/
Tags: PayPal payment, PayPal, button, payment, online payments, pay now, buy now, ecommerce, gateway, paypal button, paypal buy now button, paypal plugin
Author: Scott Paterson
Author URI: https://wpplugin.org
License: GPL2
Version: 1.7.2
*/

/*  Copyright 2014-2017 Scott Paterson

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/



global $pagenow, $typenow;


// add media button for editor page
if ( in_array( $pagenow, array( 'post.php', 'page.php', 'post-new.php', 'post-edit.php' ) ) && $typenow != 'download' ) {

	add_action('media_buttons', 'wpecpp_add_my_media_button', 20);
	function wpecpp_add_my_media_button() {
		echo '<a href="#TB_inline?width=600&height=400&inlineId=wpecpp_popup_container" title="PayPal Button" id="insert-my-media" class="button thickbox">PayPal Button</a>';
	}

	add_action( 'admin_footer',  'wpecpp_add_inline_popup_content' );
	function wpecpp_add_inline_popup_content() {
		?>
		
		
		
		<script type="text/javascript">
		function wpecpp_InsertShortcode(){
			
			var wpecpp_scname = 	document.getElementById("wpecpp_scname").value;
			var wpecpp_scprice = 	document.getElementById("wpecpp_scprice").value;
			var wpecpp_alignmentc = document.getElementById("wpecpp_alignment");
			var wpecpp_alignmentb = wpecpp_alignmentc.options[wpecpp_alignmentc.selectedIndex].value;
			
			if(!wpecpp_scname.match(/\S/)) { 	alert("Item Name is required."); return false; }
			if(!wpecpp_scprice.match(/\S/)) { 	alert("Item Price is required."); return false; }
			if(wpecpp_alignmentb == "none") { 	var wpecpp_alignment = ""; } else { var wpecpp_alignment = ' align="' + wpecpp_alignmentb + '"'; };
			
			document.getElementById("wpecpp_scname").value = "";
			document.getElementById("wpecpp_scprice").value = "";
			wpecpp_alignmentc.selectedIndex = null;
			
			window.send_to_editor('[wpecpp name="' + wpecpp_scname + '" price="' + wpecpp_scprice + '"' + wpecpp_alignment + ']');
		}
		</script>
		
		
		<div id="wpecpp_popup_container" style="display:none;">
		
		<h2>Insert a Buy Now Button</h2>
		
		<table><tr><td>
		
		Item Name: </td><td><input type="text" name="wpecpp_scname" id="wpecpp_scname" value="">The name of the item</td><td></td></tr><tr><td>
		Item Price: </td><td><input type="text" name="wpecpp_scprice" id="wpecpp_scprice" value=""> Example format: 6.99</td><td></td></tr><tr><td>
		Alignment: </td><td><select name="wpecpp_alignment" id="wpecpp_alignment"><option value="none"></option><option value="left">Left</option><option value="center">Center</option><option value="right">Right</option></select> 
		Optional</td><td></td></tr><tr><td>
		
		</td></tr><tr><td>
		
		<br />
		</td></tr><tr><td>
		
		<input type="button" id="wpecpp-insert" class="button-primary" onclick="wpecpp_InsertShortcode();" value="Insert">
		<br /><br />
		
		</td></tr></table>
		
		</div>
		<?php
	}
}







// plugin functions

register_activation_hook( __FILE__, "wpecpp_activate" );
register_deactivation_hook( __FILE__, "wpecpp_deactivate" );
register_uninstall_hook( __FILE__, "wpecpp_uninstall" );

function wpecpp_activate() {

	$wpecpp_settingsoptions = array(
	'currency'    		=> '25',
	'language'    		=> '3',
	'liveaccount'    	=> '',
	'sandboxaccount'    => '',
	'mode'    			=> '2',
	'size'    			=> '2',
	'opens'    			=> '2',
	'cancel'    		=> '',
	'return'    		=> '',
	'note'    			=> '1',
	'tax_rate'    		=> '',
	'tax'    			=> '',
	'weight_unit'    	=> '1',
	'cbt'    			=> '',
	'upload_image'    	=> '',
	'showprice'    		=> '2',
	'showname'    		=> '2',
	'paymentaction' 	=> '1'
	);

	add_option("wpecpp_settingsoptions", $wpecpp_settingsoptions);

}


function wpecpp_deactivate() {
	delete_option("wpecpp_my_plugin_notice_shown");
}

function wpecpp_uninstall() {
}



// display activation notice

add_action('admin_notices', 'wpecpp_my_plugin_admin_notices');

function wpecpp_my_plugin_admin_notices() {
	if (!get_option('wpecpp_my_plugin_notice_shown')) {
		echo "<div class='updated'><p><a href='admin.php?page=wp-ecommerce-settings'>Click here to view the plugin settings</a>.</p></div>";
		update_option("wpecpp_my_plugin_notice_shown", "true");
	}
}






// settings page menu link
add_action( "admin_menu", "wpecpp_plugin_menu" );

function wpecpp_plugin_menu() {
	add_options_page( "PayPal Button", "PayPal Button", "manage_options", "wp-ecommerce-settings", "wpecpp_plugin_options" );
}
add_filter('plugin_action_links', 'wpecpp_myplugin_plugin_action_links', 10, 2);

function wpecpp_myplugin_plugin_action_links($links, $file) {
	static $this_plugin;
	
	if (!$this_plugin) {
		$this_plugin = plugin_basename(__FILE__);
	}
	
	if ($file == $this_plugin) {
		$settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wp-ecommerce-settings">Settings</a>';
		array_unshift($links, $settings_link);
	}
	
	return $links;
}

function wpecpp_plugin_settings_link($links) {
	unset($links['edit']);

	$forum_link   = '<a target="_blank" href="https://wordpress.org/support/plugin/wp-ecommerce-paypal">' . __('Support', 'PTP_LOC') . '</a>';
	$premium_link = '<a target="_blank" href="https://wpplugin.org/downloads/easy-paypal-buy-now-button/">' . __('Purchase Premium', 'PTP_LOC') . '</a>';
	array_push($links, $forum_link);
	array_push($links, $premium_link);
	return $links;
}

$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'wpecpp_plugin_settings_link' );



function wpecpp_plugin_options() {
	
	if ( !current_user_can( "manage_options" ) )  {
		wp_die( __( "You do not have sufficient permissions to access this page." ) );
	}

	// settings page




	echo "<table width='100%'><tr><td width='70%'><br />";
	echo "<label style='color: #000;font-size:18pt;'><center>PayPal Buy Now Button Settings</center></label>";
	echo "<form method='post' action='".$_SERVER["REQUEST_URI"]."'>";


	// save and update options
	if (isset($_POST['update'])) {

		$options['currency'] = 			$_POST['currency'];
		$options['language'] = 			$_POST['language'];
		$options['liveaccount'] = 		$_POST['liveaccount'];
		$options['sandboxaccount'] = 	$_POST['sandboxaccount'];
		$options['mode'] = 				$_POST['mode'];
		$options['size'] = 				$_POST['size'];
		$options['opens'] = 			$_POST['opens'];
		$options['cancel'] = 			$_POST['cancel'];
		$options['return'] = 			$_POST['return'];
		$options['paymentaction'] = 	$_POST['paymentaction'];

		update_option("wpecpp_settingsoptions", $options);

		echo "<br /><div class='updated'><p><strong>"; _e("Settings Updated."); echo "</strong></p></div>";

	}


	// get options
	$options = get_option('wpecpp_settingsoptions');
	foreach ($options as $k => $v ) { $value[$k] = $v; }


	echo "</td><td></td></tr><tr><td>";





	// form
	echo "<br />";
	?>

	<div style="background-color:#333333;padding:8px;color:#eee;font-size:12pt;font-weight:bold;">
	&nbsp; Usage
	</div><div style="background-color:#fff;border: 1px solid #E5E5E5;padding:5px;"><br />

	In a page or post editor you will see a new button called "PayPal Button" located above the text area beside the "Add Media" button. By using this you can 
	create shortcodes which will show up as Buy Now button on your site.
	<br /><br />

	You can put the Buy Now buttons as many times in a page or post as you want, there is no limit. If you want to remove a Buy Now button, just remove the shortcode text in your page or post.


	<br /><br />
	</div><br /><br />

	<div style="background-color:#333333;padding:8px;color:#eee;font-size:12pt;font-weight:bold;">
	&nbsp; Language & Currency
	</div><div style="background-color:#fff;border: 1px solid #E5E5E5;padding:5px;"><br />

	<b>Language:</b>
	<select name="language">
	<option <?php if ($value['language'] == "1") { echo "SELECTED"; } ?> value="1">Danish</option>
	<option <?php if ($value['language'] == "2") { echo "SELECTED"; } ?> value="2">Dutch</option>
	<option <?php if ($value['language'] == "3") { echo "SELECTED"; } ?> value="3">English</option>
	<option <?php if ($value['language'] == "20") { echo "SELECTED"; } ?> value="20">English - UK</option>
	<option <?php if ($value['language'] == "4") { echo "SELECTED"; } ?> value="4">French</option>
	<option <?php if ($value['language'] == "5") { echo "SELECTED"; } ?> value="5">German</option>
	<option <?php if ($value['language'] == "6") { echo "SELECTED"; } ?> value="6">Hebrew</option>
	<option <?php if ($value['language'] == "7") { echo "SELECTED"; } ?> value="7">Italian</option>
	<option <?php if ($value['language'] == "8") { echo "SELECTED"; } ?> value="8">Japanese</option>
	<option <?php if ($value['language'] == "9") { echo "SELECTED"; } ?> value="9">Norwgian</option>
	<option <?php if ($value['language'] == "10") { echo "SELECTED"; } ?> value="10">Polish</option>
	<option <?php if ($value['language'] == "11") { echo "SELECTED"; } ?> value="11">Portuguese</option>
	<option <?php if ($value['language'] == "12") { echo "SELECTED"; } ?> value="12">Russian</option>
	<option <?php if ($value['language'] == "13") { echo "SELECTED"; } ?> value="13">Spanish</option>
	<option <?php if ($value['language'] == "14") { echo "SELECTED"; } ?> value="14">Swedish</option>
	<option <?php if ($value['language'] == "15") { echo "SELECTED"; } ?> value="15">Simplified Chinese -China only</option>
	<option <?php if ($value['language'] == "16") { echo "SELECTED"; } ?> value="16">Traditional Chinese - Hong Kong only</option>
	<option <?php if ($value['language'] == "17") { echo "SELECTED"; } ?> value="17">Traditional Chinese - Taiwan only</option>
	<option <?php if ($value['language'] == "18") { echo "SELECTED"; } ?> value="18">Turkish</option>
	<option <?php if ($value['language'] == "19") { echo "SELECTED"; } ?> value="19">Thai</option>
	</select>

	PayPal currently supports 18 languages.
	<br /><br />

	<b>Currency:</b> 
	<select name="currency">
	<option <?php if ($value['currency'] == "1") { echo "SELECTED"; } ?> value="1">Australian Dollar - AUD</option>
	<option <?php if ($value['currency'] == "2") { echo "SELECTED"; } ?> value="2">Brazilian Real - BRL</option> 
	<option <?php if ($value['currency'] == "3") { echo "SELECTED"; } ?> value="3">Canadian Dollar - CAD</option>
	<option <?php if ($value['currency'] == "4") { echo "SELECTED"; } ?> value="4">Czech Koruna - CZK</option>
	<option <?php if ($value['currency'] == "5") { echo "SELECTED"; } ?> value="5">Danish Krone - DKK</option>
	<option <?php if ($value['currency'] == "6") { echo "SELECTED"; } ?> value="6">Euro - EUR</option>
	<option <?php if ($value['currency'] == "7") { echo "SELECTED"; } ?> value="7">Hong Kong Dollar - HKD</option> 	 
	<option <?php if ($value['currency'] == "8") { echo "SELECTED"; } ?> value="8">Hungarian Forint - HUF</option>
	<option <?php if ($value['currency'] == "9") { echo "SELECTED"; } ?> value="9">Israeli New Sheqel - ILS</option>
	<option <?php if ($value['currency'] == "10") { echo "SELECTED"; } ?> value="10">Japanese Yen - JPY</option>
	<option <?php if ($value['currency'] == "11") { echo "SELECTED"; } ?> value="11">Malaysian Ringgit - MYR</option>
	<option <?php if ($value['currency'] == "12") { echo "SELECTED"; } ?> value="12">Mexican Peso - MXN</option>
	<option <?php if ($value['currency'] == "13") { echo "SELECTED"; } ?> value="13">Norwegian Krone - NOK</option>
	<option <?php if ($value['currency'] == "14") { echo "SELECTED"; } ?> value="14">New Zealand Dollar - NZD</option>
	<option <?php if ($value['currency'] == "15") { echo "SELECTED"; } ?> value="15">Philippine Peso - PHP</option>
	<option <?php if ($value['currency'] == "16") { echo "SELECTED"; } ?> value="16">Polish Zloty - PLN</option>
	<option <?php if ($value['currency'] == "17") { echo "SELECTED"; } ?> value="17">Pound Sterling - GBP</option>
	<option <?php if ($value['currency'] == "18") { echo "SELECTED"; } ?> value="18">Russian Ruble - RUB</option>
	<option <?php if ($value['currency'] == "19") { echo "SELECTED"; } ?> value="19">Singapore Dollar - SGD</option>
	<option <?php if ($value['currency'] == "20") { echo "SELECTED"; } ?> value="20">Swedish Krona - SEK</option>
	<option <?php if ($value['currency'] == "21") { echo "SELECTED"; } ?> value="21">Swiss Franc - CHF</option>
	<option <?php if ($value['currency'] == "22") { echo "SELECTED"; } ?> value="22">Taiwan New Dollar - TWD</option>
	<option <?php if ($value['currency'] == "23") { echo "SELECTED"; } ?> value="23">Thai Baht - THB</option>
	<option <?php if ($value['currency'] == "24") { echo "SELECTED"; } ?> value="24">Turkish Lira - TRY</option>
	<option <?php if ($value['currency'] == "25") { echo "SELECTED"; } ?> value="25">U.S. Dollar - USD</option>
	</select>
	PayPal currently supports 25 currencies.
	<br /><br /></div>

	<?php


	?>
	<br /><br /><div style="background-color:#333333;padding:8px;color:#eee;font-size:12pt;font-weight:bold;">
	&nbsp; PayPal Account </div><div style="background-color:#fff;border: 1px solid #E5E5E5;padding:5px;"><br />

	<?php

	echo "<b>Live Account: </b><input type='text' name='liveaccount' value='".$value['liveaccount']."'> Required";
	echo "<br />Enter a valid Merchant account ID (strongly recommend) or PayPal account email address. All payments will go to this account.";
	echo "<br /><br />You can find your Merchant account ID in your PayPal account under Profile -> My business info -> Merchant account ID";

	echo "<br /><br />If you don't have a PayPal account, you can sign up for free at <a target='_blank' href='https://paypal.com'>PayPal</a>. <br /><br />";


	echo "<b>Sandbox Account: </b><input type='text' name='sandboxaccount' value='".$value['sandboxaccount']."'> Optional";
	echo "<br />Enter a valid sandbox PayPal account email address. A Sandbox account is a PayPal accont with fake money used for testing. This is useful to make sure your PayPal account and settings are working properly being going live.";
	echo "<br /><br />To create a Sandbox account, you first need a Developer Account. You can sign up for free at the <a target='_blank' href='https://www.paypal.com/webapps/merchantboarding/webflow/unifiedflow?execution=e1s2'>PayPal Developer</a> site. <br /><br />";

	echo "Once you have made an account, create a Sandbox Business and Personal Account <a target='_blank' href='https://developer.paypal.com/webapps/developer/applications/accounts'>here</a>. Enter the Business acount email on this page and use the Personal account username and password to buy something on your site as a customer. <br /><br /><br />";

	echo "<b>Sandbox Mode:</b>";
	echo "&nbsp; &nbsp; <input "; if ($value['mode'] == "1") { echo "checked='checked'"; } echo " type='radio' name='mode' value='1'>On (Sandbox mode)";
	echo "&nbsp; &nbsp; <input "; if ($value['mode'] == "2") { echo "checked='checked'"; } echo " type='radio' name='mode' value='2'>Off (Live mode)";

	echo "<br /><br /><b>Payment Action:</b>";
	echo "&nbsp; &nbsp; <input "; if ($value['paymentaction'] == "1") { echo "checked='checked'"; } echo " type='radio' name='paymentaction' value='1'>Sale (Default)";
	echo "&nbsp; &nbsp; <input "; if ($value['paymentaction'] == "2") { echo "checked='checked'"; } echo " type='radio' name='paymentaction' value='2'>Authorize (Learn more <a target='_blank' href='https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/authcapture/'>here</a>)";

	echo "<br /><br /></div>";



	?>

	<br /><br />
	<div style="background-color:#333333;padding:8px;color:#eee;font-size:12pt;font-weight:bold;">
	&nbsp; Other Settings
	</div><div style="background-color:#fff;border: 1px solid #E5E5E5;padding:5px;"><br />

	<?php
	echo "<table><tr><td valign='top'>";




	echo "<b>Button Size and type:</b></td><td valign='top' style='text-align: center;'>";
	echo "<input "; if ($value['size'] == "1") { echo "checked='checked'"; } echo " type='radio' name='size' value='1'>Small <br /><img src='https://www.paypalobjects.com/en_US/i/btn/btn_buynow_SM.gif'></td><td valign='top' style='text-align: center;'>";
	echo "<input "; if ($value['size'] == "2") { echo "checked='checked'"; } echo " type='radio' name='size' value='2'>Big <br /><img src='https://www.paypalobjects.com/en_US/i/btn/btn_buynow_LG.gif'></td><td valign='top' style='text-align: center;'>";
	echo "<input "; if ($value['size'] == "3") { echo "checked='checked'"; } echo " type='radio' name='size' value='3'>Big with credit cards <br /><img src='https://www.paypalobjects.com/en_US/i/btn/btn_buynowCC_LG.gif'></td><td valign='top' style='text-align: center;'>";
	echo "<input "; if ($value['size'] == "5") { echo "checked='checked'"; } echo " type='radio' name='size' value='5'>Gold (English Only) <br /><img src='https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png'></td><td valign='top' style='text-align: center;'>";




	echo "</td></tr><tr><td><b>PayPal opens in:</b></td>";
	echo "<td><input "; if ($value['opens'] == "1") { echo "checked='checked'"; } echo " type='radio' name='opens' value='1'>Same window</td>";
	echo "<td><input "; if ($value['opens'] == "2") { echo "checked='checked'"; } echo " type='radio' name='opens' value='2'>New window</td></tr>";



	echo "</table><br /><br />";



	$siteurl = get_site_url();

	echo "<b>Cancel URL: </b>";
	echo "<input type='text' name='cancel' value='".$value['cancel']."'> Optional <br />";
	echo "If the customer goes to PayPal and clicks the cancel button, where do they go. Example: $siteurl/cancel. Max length: 1,024. <br /><br />";

	echo "<b>Return URL: </b>";
	echo "<input type='text' name='return' value='".$value['return']."'> Optional <br />";
	echo "If the customer goes to PayPal and successfully pays, where are they redirected to after. Example: $siteurl/thankyou. Max length: 1,024. <br /><br />";

	echo "Note: The Cancel and Return pages are not automatically created; /cancel and /thankyou are just possible example page names.";


	?>
	<br /><br /></div>

	<input type='hidden' name='update'><br />
	<input type='submit' name='btn2' class='button-primary' style='font-size: 17px;line-height: 28px;height: 32px;' value='Save Settings'>

	<br /><br /><br />


	WPPlugin is an offical PayPal Partner. Various trademarks held by their respective owners.


	</form>


	</td><td width='5%'>
	</td><td width='24%' valign='top'>

	<br />

	<div style="background-color:#333333;padding:8px;color:#eee;font-size:12pt;font-weight:bold;">
	&nbsp; PayPal Buy Now Button Pro
	</div>

	<div style="background-color:#fff;border: 1px solid #E5E5E5;padding:8px;">


	<center><label style="font-size:14pt;">With the Pro version you'll <br /> be able to: </label></center>
	 
	<br />
	<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Manage Buttons In One Place<br />
	<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> View Sales In Your Dashboard<br />
	<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Send out Email Notifications<br />
	<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Have Inventory Management<br />
	<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Have Separate PayPal Accounts<br />
	<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Add Dropdown Menus<br />
	<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Add Text Boxes<br />
	<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Charge Tax & Shipping <br />
	<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Add Item ID and SKU<br />
	<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Add Discounts <br />
	<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Change Quantity <br />
	<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Have A Custom Button Image<br />
	<div class="dashicons dashicons-yes" style="margin-bottom: 6px;"></div> Further Plugin Development <br />

	<br />
	<center><a target='_blank' href="https://wpplugin.org/downloads/easy-paypal-buy-now-button/" class='button-primary' style='font-size: 17px;line-height: 28px;height: 32px;'>Learn More</a></center>
	<br />
	</div>

	<br /><br />


	<div style="background-color:#333333;padding:8px;color:#eee;font-size:12pt;font-weight:bold;">
	&nbsp; Quick Links
	</div>

	<div style="background-color:#fff;border: 1px solid #E5E5E5;padding:8px;"><br />

	<div class="dashicons dashicons-arrow-right" style="margin-bottom: 6px;"></div> <a target="_blank" href="https://wordpress.org/support/plugin/wp-ecommerce-paypal">Support Forum</a> <br />

	<div class="dashicons dashicons-arrow-right" style="margin-bottom: 6px;"></div> <a target="_blank" href="https://wpplugin.org/documentation/">FAQ</a> <br />

	<div class="dashicons dashicons-arrow-right" style="margin-bottom: 6px;"></div> <a target="_blank" href="https://wpplugin.org/downloads/easy-paypal-buy-now-button/">PayPal Button Pro</a> <br />

	</div>

	</td><td width='1%'>

	</td></tr></table>


	<?php

	// end settings page and required permissions
}







// shortcode

add_shortcode('wpecpp', 'wpecpp_options');

function wpecpp_options($atts) {

	// get shortcode user fields
	$atts = shortcode_atts(array('name' => 'Example Name','price' => '0.00','size' => '','align' => ''), $atts);

	// get settings page values
	$options = get_option('wpecpp_settingsoptions');
	foreach ($options as $k => $v ) { $value[$k] = $v; }


	// live of test mode
	if ($value['mode'] == "1") {
		$account = $value['sandboxaccount'];
		$path = "sandbox.paypal";
	} elseif ($value['mode'] == "2")  {
		$account = $value['liveaccount'];
		$path = "paypal";
	}

	// payment action
	if ($value['paymentaction'] == "1") {
		$paymentaction = "sale";
	} elseif ($value['paymentaction'] == "2")  {
		$paymentaction = "authorization";
	} else {
		$paymentaction = "sale";
	}

	// currency
	if ($value['currency'] == "1") { $currency = "AUD"; }
	if ($value['currency'] == "2") { $currency = "BRL"; }
	if ($value['currency'] == "3") { $currency = "CAD"; }
	if ($value['currency'] == "4") { $currency = "CZK"; }
	if ($value['currency'] == "5") { $currency = "DKK"; }
	if ($value['currency'] == "6") { $currency = "EUR"; }
	if ($value['currency'] == "7") { $currency = "HKD"; }
	if ($value['currency'] == "8") { $currency = "HUF"; }
	if ($value['currency'] == "9") { $currency = "ILS"; }
	if ($value['currency'] == "10") { $currency = "JPY"; }
	if ($value['currency'] == "11") { $currency = "MYR"; }
	if ($value['currency'] == "12") { $currency = "MXN"; }
	if ($value['currency'] == "13") { $currency = "NOK"; }
	if ($value['currency'] == "14") { $currency = "NZD"; }
	if ($value['currency'] == "15") { $currency = "PHP"; }
	if ($value['currency'] == "16") { $currency = "PLN"; }
	if ($value['currency'] == "17") { $currency = "GBP"; }
	if ($value['currency'] == "18") { $currency = "RUB"; }
	if ($value['currency'] == "19") { $currency = "SGD"; }
	if ($value['currency'] == "20") { $currency = "SEK"; }
	if ($value['currency'] == "21") { $currency = "CHF"; }
	if ($value['currency'] == "22") { $currency = "TWD"; }
	if ($value['currency'] == "23") { $currency = "THB"; }
	if ($value['currency'] == "24") { $currency = "TRY"; }
	if ($value['currency'] == "25") { $currency = "USD"; }

	// language
	if ($value['language'] == "1") {
		$language = "da_DK";
		$image = "https://www.paypalobjects.com/da_DK/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/da_DK/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/da_DK/DK/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //Danish

	if ($value['language'] == "2") {
		$language = "nl_BE";
		$image = "https://www.paypalobjects.com/nl_NL/NL/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/nl_NL/NL/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/nl_NL/NL/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //Dutch

	if ($value['language'] == "3") {
		$language = "EN_US";
		$image = "https://www.paypalobjects.com/en_US/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/en_US/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/en_US/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //English

	if ($value['language'] == "20") {
		$language = "en_GB";
		$image = "https://www.paypalobjects.com/en_US/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/en_US/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/en_US/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //English - UK

	if ($value['language'] == "4") {
		$language = "fr_CA";
		$image = "https://www.paypalobjects.com/fr_CA/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/fr_CA/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/fr_CA/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //French

	if ($value['language'] == "5") {
		$language = "de_DE";
		$image = "https://www.paypalobjects.com/de_DE/DE/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/de_DE/DE/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/de_DE/DE/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //German

	if ($value['language'] == "6") {
		$language = "he_IL";
		$image = "https://www.paypalobjects.com/he_IL/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/he_IL/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/he_IL/IL/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //Hebrew

	if ($value['language'] == "7") {
		$language = "it_IT";
		$image = "https://www.paypalobjects.com/it_IT/IT/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/it_IT/IT/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/it_IT/IT/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //Italian

	if ($value['language'] == "8") {
		$language = "ja_JP";
		$image = "https://www.paypalobjects.com/ja_JP/JP/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/ja_JP/JP/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/ja_JP/JP/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //Japanese

	if ($value['language'] == "9") {
		$language = "no_NO";
		$image = "https://www.paypalobjects.com/no_NO/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/no_NO/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/no_NO/NO/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //Norwgian

	if ($value['language'] == "10") {
		$language = "pl_PL";
		$image = "https://www.paypalobjects.com/pl_PL/PL/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/pl_PL/PL/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/pl_PL/PL/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //Polish

	if ($value['language'] == "11") {
		$language = "pt_BR";
		$image = "https://www.paypalobjects.com/pt_PT/PT/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/pt_PT/PT/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/pt_PT/PT/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //Portuguese

	if ($value['language'] == "12") {
		$language = "ru_RU";
		$image = "https://www.paypalobjects.com/ru_RU/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/ru_RU/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/ru_RU/RU/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //Russian

	if ($value['language'] == "13") {
		$language = "es_ES";
		$image = "https://www.paypalobjects.com/es_ES/ES/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/es_ES/ES/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/es_ES/ES/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //Spanish

	if ($value['language'] == "14") {
		$language = "sv_SE";
		$image = "https://www.paypalobjects.com/sv_SE/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/sv_SE/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/sv_SE/SE/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //Swedish

	if ($value['language'] == "15") {
		$language = "zh_CN";
		$image = "https://www.paypalobjects.com/zh_XC/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/zh_XC/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/zh_XC/C2/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //Simplified Chinese - China

	if ($value['language'] == "16") {
		$language = "zh_HK";
		$image = "https://www.paypalobjects.com/zh_HK/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/zh_HK/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/zh_HK/HK/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //Traditional Chinese - Hong Kong

	if ($value['language'] == "17") {
		$language = "zh_TW";
		$image = "https://www.paypalobjects.com/zh_TW/TW/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/zh_TW/TW/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/zh_TW/TW/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //Traditional Chinese - Taiwan

	if ($value['language'] == "18") {
		$language = "tr_TR";
		$image = "https://www.paypalobjects.com/tr_TR/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/tr_TR/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/tr_TR/TR/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //Turkish

	if ($value['language'] == "19") {
		$language = "th_TH";
		$image = "https://www.paypalobjects.com/th_TH/i/btn/btn_buynow_SM.gif";
		$imageb = "https://www.paypalobjects.com/th_TH/i/btn/btn_buynow_LG.gif";
		$imagecc = "https://www.paypalobjects.com/th_TH/TH/i/btn/btn_buynowCC_LG.gif";
		$imagenew = "https://www.paypalobjects.com/webstatic/en_US/i/buttons/buy-logo-medium.png";
	} //Thai

	if (!empty($atts['size'])) {
		if ($atts['size'] == "1") { $img = $image; }
		if ($atts['size'] == "2") { $img = $imageb; }
		if ($atts['size'] == "3") { $img = $imagecc; }
		if ($atts['size'] == "5") { $img = $imagenew; }
	} else {
		if ($value['size'] == "1") { $img = $image; }
		if ($value['size'] == "2") { $img = $imageb; }
		if ($value['size'] == "3") { $img = $imagecc; }
		if ($value['size'] == "4") { $img = $value['upload_image']; }
		if ($value['size'] == "5") { $img = $imagenew; }
	}

	// window action
	if ($value['opens'] == "1") { $target = ""; }
	if ($value['opens'] == "2") { $target = "_blank"; }

	// alignment
	if ($atts['align'] == "left") { 	$alignment = "style='float: left;'"; }
	if ($atts['align'] == "right") { 	$alignment = "style='float: right;'"; }
	if ($atts['align'] == "center") { 	$alignment = "style='margin-left: auto;margin-right: auto;width:170px'"; }

	if (!isset($alignment)) { $alignment = ""; }

	if (!isset($note)) { $note = ""; }



	$output = "";

	if (empty($account)) {
		$output .= "(Please enter your PayPal Account or Merchant ID on the settings pages.)";
	}

	$output .= "<div $alignment>";
	$output .= "<form target='$target' action='https://www.$path.com/cgi-bin/webscr' method='post'>";
	$output .= "<input type='hidden' name='cmd' value='_xclick' />";
	$output .= "<input type='hidden' name='business' value='$account' />";
	$output .= "<input type='hidden' name='item_name' value='". $atts['name'] ."' />";
	$output .= "<input type='hidden' name='currency_code' value='$currency' />";
	$output .= "<input type='hidden' name='amount' value='". $atts['price'] ."' />";
	$output .= "<input type='hidden' name='lc' value='$language'>";
	$output .= "<input type='hidden' name='no_note' value='$note'>";
	$output .= "<input type='hidden' name='paymentaction' value='$paymentaction'>";
	$output .= "<input type='hidden' name='return' value='". $value['return'] ."' />";
	$output .= "<input type='hidden' name='bn' value='WPPlugin_SP'>";
	$output .= "<input type='hidden' name='cancel_return' value='". $value['cancel'] ."' />";
	$output .= "<input style='border: none;' class='paypalbuttonimage' type='image' src='$img' border='0' name='submit' alt='Make your payments with PayPal. It is free, secure, effective.'>";
	$output .= "<img alt='' border='0' style='border:none;display:none;' src='https://www.paypal.com/$language/i/scr/pixel.gif' width='1' height='1'>";
	$output .= "</form></div>";

	return $output;

}

?>