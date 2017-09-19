<?php
/*
Plugin Name: Quick Paypal Payments
Plugin URI: http://quick-plugins.com/quick-paypal-payments/
Description: Accept any amount or payment ID before submitting to paypal.
Version: 5.5.1
Author: aerin
Author URI: http://quick-plugins.com/
Text-domain: quick-paypal-plugins
*/

/*
	Register the scripts we need
*/
include('PaypalAPI/PaypalAPI.bootstrap.php');

function qpp_shutdown() {
    $error = error_get_last();
}

register_shutdown_function('qpp_shutdown');

add_action('init', 'qpp_register_scripts');
/*
	Add footer event to fire and include the javascript file only when needed
*/
add_action('wp_footer','qpp_display_scripts');

add_shortcode( 'qpp', 'qpp_loop' );
add_shortcode( 'qppreport', 'qpp_report' );
add_filter( 'plugin_action_links', 'qpp_plugin_action_links', 10, 2 );
add_action( 'wp_enqueue_scripts','qpp_enqueue_scripts' );
add_action( 'template_redirect', 'qpp_ipn' );
add_action( 'wp_head', 'qpp_head_css' );

/*
	@Change
	@Add [ADDED]
*/
add_action( 'wp_ajax_qpp_validate_form', 'qpp_validate_form_callback');
add_action( 'wp_ajax_nopriv_qpp_validate_form', 'qpp_validate_form_callback');
add_action( 'wp_ajax_qpp_process_payment', 'qpp_process_payment');
add_action( 'wp_ajax_nopriv_qpp_process_express_checkout_payment', 'qpp_process_express_checkout_payment');

/*
	[/ADDED]
*/

/*
	Add global variables QPP_END_LOOP, QPP_CURRENT_CUSTOM
*/
$qpp_end_loop = false;
$qpp_current_custom = '';

require_once( plugin_dir_path( __FILE__ ) . '/quick-paypal-options.php' );
require_once( plugin_dir_path( __FILE__ ) . '/mailchimp/mailchimp.init.php');

if (is_admin()) require_once( plugin_dir_path( __FILE__ ) . '/settings.php' );


/*
	Function which registers qpp scripts
*/
function qpp_register_scripts() {
	wp_register_script('qpp_script', plugins_url('quick-paypal-payments.js', __FILE__), array('jquery'), false, true);
}
/*
	Function which displays registered scripts
	ONLY IF $qpp_shortcode_exists EXISTS
*/
function qpp_display_scripts() {
	global $qpp_shortcode_exists;

	if ($qpp_shortcode_exists)
		wp_print_scripts('qpp_script');
}

function qpp_create_css_file ($update) {
    if (function_exists('file_put_contents')) {
        $css_dir = plugin_dir_path( __FILE__ ) . '/quick-paypal-payments-custom.css' ;
        $filename = plugin_dir_path( __FILE__ );
        if (is_writable($filename) && (!file_exists($css_dir)) || !empty($update)) {
            $data = qpp_generate_css();
            file_put_contents($css_dir, $data, LOCK_EX);
        }
    }
    else add_action('wp_head', 'qpp_head_css');
}

function qpp_enqueue_scripts() {
	$qpp_setup = qpp_get_stored_setup();
	wp_enqueue_script( 'paypal_checkout_js', "https://www.paypalobjects.com/api/checkout.js", array(), false, true);
    wp_enqueue_script( 'qpp_script',plugins_url('quick-paypal-payments.js', __FILE__), array(), false, true);
    wp_enqueue_style( 'qpp_style',plugins_url('quick-paypal-payments.css', __FILE__));
   
    if ($qpp_setup['location'] == 'php') {
        qpp_create_css_file ('');
        wp_enqueue_style ('qpp_custom_style',plugins_url('quick-paypal-payments-custom.css', __FILE__));
    } else {
        add_action('wp_head', 'qpp_head_css');
    }
    wp_enqueue_script("jquery-effects-core");
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style ('jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
}

function qpp_get_incontext() {
	$setup = qpp_get_stored_setup();
	
	$mode = ((isset($setup['sandbox']) && $setup['sandbox'] == 'checked')? 'SANDBOX':'PRODUCTION');
	
	if ($mode == 'SANDBOX') { $incontext = qpp_get_stored_sandbox(); }
	else { $incontext = qpp_get_stored_incontext(); }
	
	return $incontext;
}
/*
	@Change
	@Add function qpp_validate_form
*/
function qpp_validate_form_callback($degrade = false) {

	$incontext = qpp_get_incontext();
	$setup = qpp_get_stored_setup();
	
	if (isset($_POST['form_id'])) {
		$formerrors = array();
		$form = $_POST['form_id'];
		$style = qpp_get_stored_style($form);
		$error = qpp_get_stored_error($form);
		$currency = qpp_get_stored_curr();
		$current_currency = $currency[$_POST['form_id']];
		$qpp = qpp_get_stored_options($form);
		$send = qpp_get_stored_send($form);

		$json = (object) array(
			'success' => false,
			'errors' => array(),
			'display' => $error['errortitle'],
			'blurb' => $error['errorblurb'],
			'error_color' => $style['error-colour']
		);
		if (!qpp_verify_form($_POST, $formerrors, $_POST['form_id'])) {
			
			/* Format Form Errors */
			foreach ($formerrors as $k => $v) {
				if ($k == 'captcha') $k = 'maths';
				if ($k == 'use_stock') $k = 'stock';
				if ($k == 'useterms') $k = 'termschecked';
				if ($k == 'use_message') $k = 'yourmessage';
				$json->errors[] = (object) array(
					'name' => $k,
					'error' => $v
				);
			}
			
		} else {
			
			$json->success = true;
			
			if ((isset($incontext['useincontext']) && $incontext['useincontext'] == 'checked') && (!$degrade)) {
				
				
				/*
					Get current operating mode
				*/
				$mode = ((isset($setup['sandbox']) && $setup['sandbox'] == 'checked')? 'SANDBOX':'PRODUCTION');
				/*
					Build PaypalAPI object
				*/
				$paypal = new PaypalAPI($incontext['api_username'],$incontext['api_password'],$incontext['api_key'],$mode);
				$paypal->setMethod('SetExpressCheckout');
				if (isset($_POST['sc']['post'])) $post = $_POST['sc']['post'];
				
				$paypal->setAttribute('RETURNURL',get_permalink($post));
				$paypal->setAttribute('CANCELURL',get_permalink($post));
				
				if (strlen($send['thanksurl'])) $paypal->setAttribute('RETURNURL',get_permalink($send['thanksurl']));
				if (strlen($send['cancelurl'])) $paypal->setAttribute('CANCELURL',get_permalink($send['cancelurl']));
				
				/*
					Process form and generate IPN code
				*/
				// No errors
				$v = array();

				$form = $amount = $id = '';
				
				formulate_v($_POST['sc'],$v,$form,$amount,$id);

				qpp_process_form($v,$form);

				/*
					Start Transaction
				*/
				//qpp_start_transaction($paypal,$current_currency,$qpp,$amount);
				qpp_start_transaction($paypal,$current_currency,$qpp,$v,$form);

				
				/*
					Do paypal request
				*/
				$return = qpp_execute_transaction($paypal);
				
				if (strtolower($return['ACK']) == 'success') { 
				
					/*
						Build In-Context code
					*/
					$x = json_encode($return);
					$json->ic = array(
						'id' => $incontext['merchantid'],
						'token' => $return['TOKEN'],
						'environment' => $mode
					);
					
					
				} else {
					/*
						Degrade
					*/
					
					qpp_validate_form_callback(true);
					return;
				}
			} else {
			
				// No errors
				$v = array();

				$form = $amount = $id = '';
				
				formulate_v($_POST['sc'],$v,$form,$amount,$id);

				if (strlen($amount)) $v['amount'] = $amount;
				if (strlen($id)) $v['reference'] = $id;
				
				$json->html = qpp_process_form($v,$form);
				
			} 
		}
	} else {
		// error
	}
	echo json_encode($json);
	wp_die();
}

function formulate_v($atts, &$v, &$form = '', &$amount = '', &$id = '', &$stock = '', &$labels = '') {
	extract(shortcode_atts(array( 'form' =>'','amount' => '' , 'id' => '','stock' => '', 'labels' => ''), $atts));
    $qpp = qpp_get_stored_options($form);
    $address = qpp_get_stored_address($form);
    $coupon = qpp_get_stored_coupon ($form);
	$incontext = qpp_get_incontext();
    $shortcodereference = '';

    global $_REQUEST;
	
	/*
		Make sure this form is the form which is being submitted
	*/
	if (isset($_REQUEST['form_id']) && $_REQUEST['form_id'] == $form) {
		if(isset($_REQUEST["reference"])) {$id = $_REQUEST["reference"];}
		if(isset($_REQUEST["amount"])) {
			$amount = $_REQUEST["amount"];
		}
        if(isset($_REQUEST["item"])) {$qpp['stocklabel'] = $_REQUEST["item"];}
		if(isset($_REQUEST["form"])) {$form = $_REQUEST["form"];}
	}
	
    $arr = array('email','firstname','lastname','address1','address2','city','state','zip','country','night_phone_b');
    foreach($arr as $item) $v[$item] = $address[$item];
    $v['quantity'] = 1;
    $v['couponerror'] = $v['option1'] = false;
    $v['stock'] = $qpp['stocklabel'];
	$v['otheramount'] = $qpp['comboboxlabel'];
    $v['couponblurb'] = $qpp['couponblurb'];
    $v['yourmessage'] = $qpp['messagelabel'];
    $v['datepicker'] = $qpp['datepickerlabel'];
    
    for ($i=1;$i<=9;$i++) {
        $v['qtyproduct'.$i] = '0';
    }
    
	$v['srt'] = $qpp['srt'];
    $v['combine'] = $v['couponapplied'] = $v['couponget'] =$v['maths'] = $v['explodepay'] =  $v['explode'] = $v['recurring'] = $v['termschecked'] = '';
    
    if (!$address['email'] || !$qpp['useaddress']) {
        $v['email'] = $qpp['emailblurb'];
    }
    
    if ($qpp['refselector'] != 'refnone' && (strrpos($qpp['inputreference'],';') || strrpos($id,';'))) {
        $v['combine'] = 'initial';
    }
    
    if (!$labels) {
        $shortcodeamount = $qpp['shortcodeamount'].' ';
	}
    
    if ($id) {
        $v['setref'] = 'checked';
        if (strrpos($id,',') ) {
            $v['reference'] = $id;
            if (!$v['combine']) $v['explode'] = 'checked';
        } else {
            $v['reference'] = $shortcodereference.$id;
        }
    } else {
        $v['reference'] = $qpp['inputreference'];
        $v['setref'] = '';
    }
    
    if ($qpp['fixedreference'] && !$id) {
        if (strrpos($qpp['inputreference'],',')) {
            $v['reference'] = $qpp['inputreference'];
            if (!$v['combine']) $v['explode'] = 'checked';
            $v['setref'] = 'checked';
        } else {
			$v['reference'] = $shortcodereference.$qpp['inputreference'];
			$v['setref'] = 'checked';
        }
    }
    
    if ($amount) {
        $v['setpay'] = 'checked';
        if (strrpos($amount,',')) {
            $v['amount'] = $amount;
            $v['explodepay'] = 'checked';
            $v['fixedamount'] = $amount;
        } else {
            $v['amount'] = $shortcodeamount.$amount;
            $v['fixedamount'] = $amount;
        }
    } else {
        $v['amount'] = $qpp['inputamount'];
        $v['setpay'] = '';
    }
    
    if ($qpp['fixedamount'] && !$amount) {
        if (strrpos($qpp['inputamount'],',')) {
            $v['amount'] = $qpp['inputamount'];
            $v['explodepay'] = 'checked';
            $v['setpay'] = 'checked';
            $a = explode(",",$qpp['inputamount']);
            $v['fixedamount'] = $a[0];
        } else {
            $v['amount'] = $shortcodeamount.$qpp['inputamount'];
            $v['fixedamount'] = $qpp['inputamount'];
            $v['setpay'] = 'checked';
        }
    }
	$d = qpp_sanitize($_POST);
	
	for ($i=1;$i<=9;$i++) {
		if (isset($d['qtyproduct'.$i])) $v['qtyproduct'.$i] = $d['qtyproduct'.$i];
	}
		
    if (isset($_POST['qppapply'.$form]) || isset($_POST['qppsubmit'.$form]) || isset($_POST['qppsubmit'.$form.'_x'])) {
		
        if (isset($d['reference'])) $id = $d['reference'];
        if (isset($d['amount'])) $amount = $d['amount'];

		
        // check for combobox option
        if (isset($d['otheramount']) && isset($d['use_other_amount'])) {
			if (strtolower($d['use_other_amount']) == 'true') $d['amount'] = $d['otheramount'];
		} 
        if ($qpp['use_options'] && $qpp['optionselector'] == 'optionscheckbox') {
            $checks ='';
            $arr = explode(",",$qpp['optionvalues']);
            foreach ($arr as $key) if ($d['option1_' . str_replace(' ','',$key)]) $checks .= $key . ', ';
            $d['option1'] = rtrim( $checks , ', ' );
        }

        $arr = array(
            'reference',
            'amount',
            'stock',
            'quantity',
            'option1',
            'couponblurb',
            'maths',
            'thesum',
            'answer',
            'termschecked',
            'yourmessage',
            'datepicker',
            'email',
            'firstname',
            'lastname',
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'country',
            'night_phone_b',
            'combine',
			'srt'
        );
		
        foreach($arr as $item) {
			if (isset($d[$item])) $v[$item] = $d[$item];
		}
  
    }

    if (isset($d['qppapply'.$form])) {
        if ($v['combine']) {
            $arr = explode('&',$v['reference']);
            $v['reference'] = $arr[0];
            $v['amount'] = $arr[1];
        }
        $check = qpp_format_amount($currency[$form],$qpp,$v['amount']);
        $coupon = qpp_get_stored_coupon($form);
        $c = qpp_currency ($form);
        for ($i=1; $i<=$coupon['couponnumber']; $i++) {
            if ($coupon['expired'.$i]) $v['couponerror'] = $coupon['couponexpired'];
            if ($v['couponblurb'] == $coupon['code'.$i]) {
				$v['itemvalue'] = $check;
                if ($coupon['coupontype'.$i] == 'percent'.$i) $check = $check - ($check * $coupon['couponpercent'.$i]/100);
                if ($coupon['coupontype'.$i] == 'fixed'.$i) $check = $check - $coupon['couponfixed'.$i];
                if ($qpp['use_multiples']) {
                    for ($i=1;$i<=9;$i++) {
                        if ($v['qtyproduct'.$i]) $v['couponapplied'] = 'checked';
                    }
                    if (!$v['couponapplied']) $v['noproduct'] = 'error';
                } elseif ($check > 0) { // Coupons
                    $check = number_format($check, 2,'.','');
                    $v['couponapplied'] = 'checked';
                    $v['setpay'] = 'checked';
                    $v['amount'] = $shortcodeamount.$c['b'].$check.$c['a'];
                    $v['fixedamount'] = $check;
                    $v['explodepay'] = $v['combine'] ='';
                } else {
                   $v['couponblurb'] = $qpp['couponblurb'];
                }
            }
        }
        if (!$v['couponapplied'] && !$v['couponerror']) $v['couponerror'] = $coupon['couponerror'];
    }
	
	$v['items'] = array();
	
	if ($qpp['use_multiples']) {
		
		$multiples = qpp_get_stored_multiples($form);
		$pointer = 0;
		$v['amount'] = 0;
		
		for ($i=1;$i<=9;$i++) {
			$check = $multiples['cost'.$i];
            if ($d['qtyproduct'.$i] == 'checked') $d['qtyproduct'.$i] = 1;  //converts checked to 1
			if (isset($d['qtyproduct'.$i]) && $d['qtyproduct'.$i] > 0) {
                
				$pointer++;

				$v['items'][] = array(
					'item_name' => $multiples['product'.$i],
					'amount' => $check,
					'quantity' => (int) $d['qtyproduct'.$i]
				);
				
				$v['amount'] += ($check * $d['qtyproduct'.$i]);
				
			}

        }
		
    } else {
		$v['items'][] = array(
			'item_name' => $v['reference'],
			'amount' => $v['amount'],
			'quantity' => $v['quantity']
		);
	}

	
	$amount = $v['amount'];
}

function qpp_get_total($a) {
	$total = 0;
	foreach ($a as $item) {
		$total += ($item['amount'] * $item['quantity']);
	}
	return $total;
}
function qpp_display_success($form, $tid, $amt) {
	$f = (($form == 'default')? '':$form);
	$style = qpp_get_stored_style($f);
    $message = qpp_get_stored_messages();
	$send = qpp_get_stored_send($f);
    $c = qpp_currency ($f);
	$post = $_POST['sc']['post'];
	$url = get_permalink($post);

	$display = '';
	if (strlen($send['thanksurl'])) {
		$display .= "<script type='text/javascript'>";
		$display .= "window.location.href = '{$send['thanksurl']}';";
		$display .= "</script>";
	}
	$display .= <<<Form
	<div class="qpp-style qpp-complete {$form}"><div id="{$style['border']}">
		<form id="frmPayment{$f}" name="frmPayment{$f}" method="post" action="">
			<h2>{$message['confirmationtitle']}</h2>
            <p class="qpp-blurb">{$message['confirmationblurb']}</p>
			<p class="qpp-blurb"><b>{$message['confirmationreference']}</b> {$tid}</p>
			<p class="qpp-blurb"><b>{$message['confirmationamount']}</b> {$c['b']}{$amt}{$c['a']}</p>
			<p><a href="{$url}">{$message['confirmationanchor']}</a></p>
		</form>
	</div></div>
Form;
	echo $display;
}

function qpp_display_pending($form,$token,$payerid) {
	$f = (($form == 'default')? '':$form);
	$style = qpp_get_stored_style($f);
    $message = qpp_get_stored_messages();
	$send = qpp_get_stored_send($f);
	$post = $_POST['sc']['post'];
	$url = get_permalink($post);
	
	$display = <<<Form
	<div class="qpp-style qpp-complete {$form}"><div id="{$style['border']}">
		<form id="frmPayment{$f}" name="frmPayment{$f}" method="post" action="">
			<h2>{$message['pendingtitle']}</h2>
			<p class="qpp-blurb">{$message['pendingblurb']}</p>
			<p><a href="{$url}?token={$token}&PayerID={$payerid}">{$message['pendinganchor']}</a></p>
		</form>
	</div></div>
Form;
	echo $display;
}

function qpp_display_failure($form,$result = false) {
	$f = (($form == 'default')? '':$form);
	$style = qpp_get_stored_style($f);
    $message = qpp_get_stored_messages();
	$send = qpp_get_stored_send($f);
	$post = $_POST['sc']['post'];
	$url = get_permalink($post);
	
	$display = '';
	if (strlen($send['cancelurl'])) {
		$display .= "<script type='text/javascript'>";
		$display .= "window.location.href = '{$send['cancelurl']}';";
		$display .= "</script>";
	}
	$display .= <<<Form
	<div class="qpp-style qpp-complete {$form}"><div id="{$style['border']}">
		<form id="frmPayment{$f}" name="frmPayment{$f}" method="post" action="">
		<h2>{$message['failuretitle']}</h2>
Form;
	if ($result && isset($result['L_LONGMESSAGE0'])) {
		$display .= '<p class="qpp-blurb">'.$result['L_LONGMESSAGE0'].'</p><br />';
	}
	$display .= <<<Form
			<p class="qpp-blurb">{$message['failureblurb']}</p><br />
			<a href="{$url}">{$message['failureanchor']}</a>
		</form>
	</div></div>
Form;
	echo $display;
}

function qpp_loop($atts) {
	
	$incontext = qpp_get_incontext();
	$setup = qpp_get_stored_setup();
	
	/*
		Let the rest of wordpress know that there is a shortcode that we're looking for!
	*/
	global $qpp_shortcode_exists, $qpp_end_loop;
	
	if ($qpp_end_loop) return;
	
	$qpp_shortcode_exists = true;
	
	$v = array();
	$form = $amount = $id = '';
	formulate_v($atts,$v,$form,$amount,$id);
	
	$form = (($form)? $form:'default');
		
    ob_start();
	$doic = (isset($incontext['useincontext']) && $incontext['useincontext'] == 'checked');
	if ($doic && (isset($_GET['token']) && isset($_GET['PayerID']))) {
		
		// Success (allegedly)
		$mode = ((isset($setup['sandbox']) && $setup['sandbox'] == 'checked')? 'SANDBOX':'PRODUCTION');
		
		$paypal = new PaypalAPI($incontext['api_username'],$incontext['api_password'],$incontext['api_key'],$mode);
		$paypal->setMethod('GetExpressCheckoutDetails');
		
		$paypal->setAttribute('TOKEN',$_GET['token']);
		
		$return = $paypal->execute();
		
		if ($return['ACK'] == 'Success') {
			$go = false;
			switch ($return['CHECKOUTSTATUS']) { 
				case 'PaymentActionNotInitiated':
					//its waiting for us to process it!
					$paypal->reloadFromResponse('DoExpressCheckoutPayment');
					$r = $paypal->execute();

					if ($r['PAYMENTINFO_0_ACK'] == 'Success') qpp_display_success($form, $r['PAYMENTINFO_0_TRANSACTIONID'],$r['PAYMENTINFO_0_AMT']);
					else {
						qpp_display_failure($form,$r);
					}
				break;
				case 'PaymentActionFailed':
					//payment failed
					qpp_display_failure($form,$return);
				break;
				case 'PaymentActionInProgress':
					//processing/pending
					qpp_display_pending($form,$return['TOKEN'],$return['PAYERID']);
				break;
				case 'PaymentActionCompleted':
					//100% Success
					qpp_display_success($form, $return['PAYMENTREQUEST_0_TRANSACTIONID'],$return['PAYMENTREQUEST_0_AMT']);
				break;
			}
			
		} else {
			qpp_display_failure($form,$return);
		}
		
		$qpp_end_loop = true;
	} elseif ($doic && isset($_GET['token'])) {
		
		// Failure
		qpp_display_failure($form);
		$qpp_end_loop = true;
		
	} else {
		$v = array();
		$form = $amount = $id = '';
		formulate_v($atts,$v,$form,$amount,$id);
		if (isset($_POST['qppsubmit'.$form]) || isset($_POST['qppsubmit'.$form.'_x'])) {
			$formerrors = array();
			if (!qpp_verify_form($v,$formerrors,$form)) {
				qpp_display_form($v,$formerrors,$form,$atts);
			} else {
				if ($amount) $v['amount'] = $amount;
				if ($id) $v['reference'] = $id;
				
				echo qpp_process_form($v,$form);
				
				echo '<script language="JavaScript">document.getElementById("frmCart").submit();</script>';
				if(function_exists(qem_qpp_places)) qem_qpp_places();
			}
		} else {
			$digit1 = mt_rand(1,10);
			$digit2 = mt_rand(1,10);
			if( $digit2 >= $digit1 ) {
				$v['thesum'] = "$digit1 + $digit2";
				$v['answer'] = $digit1 + $digit2;
			} else {
				$v['thesum'] = "$digit1 - $digit2";
				$v['answer'] = $digit1 - $digit2;
			}
			qpp_display_form($v,null,$form,$atts);
		}
	}
    $output_string=ob_get_contents();
    ob_end_clean();
    return $output_string;
}

/* Changes
	12/21/2015
	Added class "qpp-default" to all text inputs (To enable focus/blur events)
	Added attribute "rel" with default value set (To enable focus/blur events)
	
	Added 4 new fields:
		@Added processing_type
		@Added postage_type
		@Added postage
		@Added processing
*/
function qpp_display_form($values, $errors, $id, $attr = '') {
	if (!$attr) $attr = array();
    global $_GET;
    if(isset($_GET["form"]) && !$id) {
        $id = $_GET["form"];
    }
    if(isset($_GET["reference"])) {
        $values['reference'] = $_GET["reference"];
        $values['setref'] = true;
    }
     if(isset($_GET["amount"])) {
         $values['amount'] = $_GET["amount"];
         $values['setpay'] = true;
    }
    if(isset($_GET["coupon"])) {
        $values['couponblurb'] = $_GET["coupon"];$values['couponget']=$coupon['couponget'];
    }
    
    $qpp_form = qpp_get_stored_setup();
    $qpp = qpp_get_stored_options($id);
    $error = qpp_get_stored_error($id);
    $coupon = qpp_get_stored_coupon($id);
    $send = qpp_get_stored_send($id);
    $style = qpp_get_stored_style($id);
    $currency = qpp_get_stored_curr();
    $address = qpp_get_stored_address($id);
	$messages = qpp_get_stored_messages();
    $list = qpp_get_stored_mailinglist();
    $qppkey = get_option('qpp_key');
    $curr = ($currency[$id] == '' ? 'USD' : $currency[$id]);
    $check = preg_replace ( '/[^.0-9]/', '', $values['amount']);
    $decimal = array('HKD','JPY','MYR','TWD');$d='2';
    foreach ($decimal as $item) if ($item == $currency[$id]) $d ='0';
    //$values['producttotal'] = $values['quantity'] * $check;
    $p = $h = '';
    if ($qpp['use_slider']) $values['amount'] = $qpp['initial'];
    $c = qpp_currency($id);
    //$p = qpp_postage($qpp,$values['producttotal'],'1');
    //$h = qpp_handling($qpp,$values['producttotal'],'1');
    $t = $id ? $id : 'default';
    $hd = $style['header-type'];
    //$values['producttotal'] = $values['producttotal'] + $p +$h;
    //$values['producttotal'] = number_format($values['producttotal'], $d,'.','');
    
	$content = "<script type='text/javascript'>ajaxurl = '".admin_url('admin-ajax.php')."';</script>";
	
    if ($id) $formstyle=$id; else $formstyle='default';
	if (!empty($qpp['title'])) $qpp['title'] = '<'.$hd.' id="qpp_reload" class="qpp-header">' . $qpp['title'] . '</'.$hd.'>';
	if (!empty($qpp['blurb'])) $qpp['blurb'] = '<p class="qpp-blurb">' . $qpp['blurb'] . '</p>';
    
    $content .= '<div class="qpp-style '.$formstyle.'"><div id="'.$style['border'].'">';
	
    $content .= '<form id="frmPayment'.$t.'" name="frmPayment'.$t.'" method="post" action="">';
    if (count($errors) > 0 || $values['noproduct']) {
        $content .= "<script type='text/javascript' language='javascript'>document.querySelector('#qpp_reload').scrollIntoView();</script>";
		$content .= "<".$hd." class='qpp-header' id='qpp_reload' style='color:".$style['error-colour'].";'>" . $error['errortitle'] . "</".$hd.">
        <p class='qpp-blurb' style='color:".$style['error-colour'].";'>" . $error['errorblurb'] . "</p>";
        $arr = array(
            'amount',
            'reference',
            'quantity',
            'use_stock',
            'answer',
            'quantity',
            'email',
            'firstname',
            'lastname',
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'country',
            'night_phone_b'
        );
        foreach ($arr as $item) if ($errors[$item] == 'error') 
            $errors[$item] = ' style="border:1px solid '.$style['error-colour'].';" ';
        if ($errors['useterms']) $errors['useterms'] = 'border:1px solid '.$style['error-colour'].';';
        if ($errors['captcha']) $errors['captcha'] = 'border:1px solid '.$style['error-colour'].';';
        if ($errors['quantity']) $errors['quantity'] = 'border:1px solid '.$style['error-colour'].';';
    } else {
        $content .= $qpp['title'];
        if ($qpp['paypal-url'] && $qpp['paypal-location'] == 'imageabove') $content .= "<img src='".$qpp['paypal-url']."' />";
        $content .=  $qpp['blurb'];
    }
	
	/*
		Build shortcode value array
	*/
	$attr['post'] = get_the_ID();
	if (count($attr)) {
		foreach ($attr as $k => $v) {
			$content .= "<input type='hidden' name='sc[".$k."]' value='".$v."' />";
		}
	}
    $content .= '<input type="hidden" name="form_id" value="'.$id.'" />';

	$qpp_multiples = false;
	foreach (explode( ',',$qpp['sort']) as $name) {
        switch ( $name ) {
            case 'field1':
            if (!$qpp['use_multiples']) {
                if (!$values['setref']) {
                    $required = (!$errors['reference'] ? ' class="required" ' : '');
                    $content .= '<p><input type="text" '.$required.$errors['reference'].' id="reference" name="reference" value="' . $values['reference'] . '" rel="'. $values['reference'] . '" onfocus="qppclear(this, \'' . $values['reference'] . '\')" onblur="qpprecall(this, \'' . $values['reference'] . '\')"/></p>';
                } else {
                    if ($values['combine']) {
                        $checked = 'checked';
                        $ret = array_map ('explode_by_semicolon', explode (',', $values['reference']));
                        if ($qpp['refselector'] == 'refdropdown') {
                            $content .= qpp_dropdown($ret,$values,'reference',$qpp['shortcodereference'],true);
                        } else {
                            $content .= '<p class="payment" >'.$qpp['shortcodereference'].'</p>';
                            $content .= '<input type="hidden" name="combined_radio_amount" value="0.00" />';
                            foreach ($ret as $item) {
                                if (strrpos($values['reference'],$item[0]) !==false && $values['combine'] != 'initial') 
                                    $checked = 'checked';
                                $content .=  '<p><label><input type="radio" style="margin:0; padding: 0; border:none;width:auto;" name="reference" value="' .  $item[0].'&'.$item[1] . '" ' . $checked . '> ' .  $item[0].' '.$item[1] . '</label></p>';$checked='';
                            }
                        
                        }
                        $content .= '<input type="hidden" name="combine" value="checked" />';
                    } elseif ($values['explode']  && $qpp['refselector'] != 'ignore') {
                        $checked = 'checked';
                        $ref = explode(",",$values['reference']);
                        if ($qpp['refselector'] == 'refdropdown') {
                            $content .= qpp_dropdown($ref,$values,'reference',$qpp['shortcodereference']);
                        } elseif ($qpp['refselector'] == 'refradio') {
                            $content .= '<p class="payment" >'.$qpp['shortcodereference'].'</p>';
                            foreach ($ref as $item)
                                $content .=  '<label><p><input type="radio" style="margin:0; padding: 0; border:none;width:auto;" name="reference" value="' .  $item . '" ' . $checked . '> ' .  $item . '</label></p>';
                            $checked='';
                        } else {
                            $content .= '<p class="payment" >'.$qpp['shortcodereference'].'</p><p>';
                            foreach ($ref as $item)
                                $content .=  '<label><input type="radio" style="margin:0; padding: 0; border:none;width:auto;" name="reference" value="' .  $item . '" ' . $checked . '> ' .  $item . '</label>';
                            $content .=  '</p>';
                            $checked='';
                        }    
                    } else {
                        $content .= '<p class="input" >'.$qpp['shortcodereference'].' '.$values['reference'].'</p><input type="hidden" name="reference" value="' . $values['reference'] . '" /><input type="hidden" name="setref" value="' . $values['setref'] . '" />';
                    }
                }
            }
            break;
            
            case 'field2':
            if ($qpp['use_stock']) {
                $requiredstock = (!$errors['use_stock'] && $qpp['ruse_stock'] ? ' class="required" ' : '');
                if ($qpp['fixedstock'] || isset($_REQUEST["item"])) {
                    $content .= '<p class="input" >'.$values['stock'].'</p>';
                } else {
                    $content .= '<p><input type="text" '.$requiredstock.$errors['use_stock'].' id="stock" name="stock" value="' . $values['stock'] . '" onfocus="qppclear(this, \'' . $values['stock'] . '\')" onblur="qpprecall(this, \'' . $values['stock'] . '\')"/>
                </p>';
                }
            }
            break;			
            
            case 'field3':
            if ($qpp['use_quantity'] && !$qpp['use_multiples']) {
                $content .= '<p>
                <span class="input">'.$qpp['quantitylabel'].'</span>
                <input type="text" style=" '.$errors['quantity'].'width:3em;margin-left:5px" id="qppquantity'.$t.'" label="quantity" name="quantity" value="' . $values['quantity'] . '" onfocus="qppclear(this, \'' . $values['quantity'] . '\')" onblur="qpprecall(this, \'' . $values['quantity'] . '\')" />';
                if ($qpp['quantitymax']) $content .= '&nbsp;'.$qpp['quantitymaxblurb'];
                $content .= '</p>';
            } else { $content .='<input type ="hidden" id="qppquantity'.$t.'" name="quantity" value="1">';}
            break;
            
            case 'field4':
            if (!$qpp['use_multiples']) {  
                if ($qpp['usecoupon'] && $values['couponapplied']) 
                    $content .= '<p>'.$qpp['couponref'].'</p>';
                if ($qpp['use_slider'] && !$values['combine']) {
                    $content .= '<p style="margin-bottom:0.7em;">'.$qpp['sliderlabel'].'</p>
                    <input type="range" id="qppamount'.$t.'" name="amount" min="'.$qpp['min'].'" max="'.$qpp['max'].'" value="'.$values['amount'].'" step="'.$qpp['step'].'" data-rangeslider>
                    <div class="qpp-slideroutput">
                    <span class="qpp-sliderleft">'.$qpp['min'].'</span>
                    <span class="qpp-slidercenter"><output></output></span>
                    <span class="qpp-sliderright">'.$qpp['max'].'</span>
                    </div><div style="clear: both;"></div>';
                } else {
                    if (!$values['combine']) {
                        if (!$values['setpay']){
                            $required = (!$errors['amount'] ? ' class="required" ' : '');
                            $content .= '<p><input type="text" rel="'.$values['amount'].'" '.$required.$errors['amount'].' id="qppamount'.$t.'" label="Amount" name="amount" value="' . $values['amount'] . '" onfocus="qppclear(this, \'' . $values['amount'] . '\')" onblur="qpprecall(this, \'' . $values['amount'] . '\' )" /></p>';
                        } else {
                            if ($values['explodepay']) {
                                $ref = explode(",",$values['amount']);
                                if($qpp['selector'] == 'dropdown') {
                                    // add combobox script
                                    if ($qpp['combobox']) {
									   array_push($ref,$qpp['comboboxword']);
									   $content .= qpp_dropdown($ref,$values,'amount',$qpp['shortcodeamount']).'<div id="otheramount"><input type="text" label="'.$qpp['comboboxlabel'].'" onfocus="qppclear(this, \'' . $qpp['comboboxlabel'] . '\')" onblur="qpprecall(this, \'' . $qpp['comboboxlabel'] . '\' )" value="'.$values['otheramount'].'" name="otheramount" style="display: none;" /><input type="hidden" name="use_other_amount" value="false" /></div>';
                                    } else {
									   $content .= qpp_dropdown($ref,$values,'amount',$qpp['shortcodeamount']);
								    }
                                } else {
                                    $checked = 'checked';
                                    $bron = ($qpp['inline_amount'] ? '' : '<p>');
                                    $broff = ($qpp['inline_amount'] ? '&nbsp;' : '</p>');
                                    $mar = ($qpp['combobox'] ? '12px 0' : '0');
                                    $content .= '<p class="payment" >'.$qpp['shortcodeamount'].'</p>';
                                    foreach ($ref as $item) {
                                        $content .=  $bron.'<label><input type="radio" id="qpptiddles" style="margin:'.$mar.'; padding: 0; border:none;width:auto;" name="amount" value="' .  $item . '" ' . $checked . '> ' .  $item . '</label>'.$broff;
									   $checked='';
                                    }
                                    if ($qpp['combobox']) {
                                        $content .=  '<input type="radio" id="qpptiddles" style="margin:0; padding: 0; border:none;width:auto;" name="amount" value="otheramount" ' . $checked . '>&nbsp;<input type="text" style="width:80%;" value ="'.$values['otheramount'].'" name="otheramount" onfocus="qppclear(this, \'' . $qpp['comboboxlabel'] . '\')" onblur="qpprecall(this, \'' . $qpp['comboboxlabel'] . '\' )" /><input type="hidden" name="use_other_amount" value="false" />';
                                    }
                                }
                            }
                            else {
								$content .= '<input type="hidden" name="itemamount" value="'.$values['itemvalue'].'" />';
								$content .= '<p class="input" >' . $values['amount'] . '</p><input type="hidden" id="qppamount'.$t.'" name="amount" value="'.$values['fixedamount'].'" />';
							}
							// #holder
                        }
                        $content .= '<input type="hidden" name="radio_amount" value="0.00" />';
                    }
                }
            }
            break;
            
            case 'field5':
            if ($qpp['use_options']){
                $content .= '<p class="input">' . $qpp['optionlabel'] . '</p><p>';
                $arr = explode(",",$qpp['optionvalues']);
                $br = ($qpp['inline_options'] ? '&nbsp;' : '<br>');
                if ($qpp['optionselector'] == 'optionsdropdown') {
                    $content .= qpp_dropdown($arr,$values,'option1','');
                } elseif ($qpp['optionselector'] == 'optionscheckbox') {
                    $content .= qpp_checkbox($arr,$values,'option1',$br);
                } else {
                    foreach ($arr as $item) {
                        $checked = '';
                        if ($values['option1'] == $item) $checked = 'checked';
                        if ($item === reset($arr)) $content .= '<input type="radio" style="margin:0; padding: 0; border: none" name="option1" value="' .  $item . '" id="' .  $item . '" checked><label for="' .  $item . '"> ' .  $item . '</label>'.$br;
                        else $content .=  '<input type="radio" style="margin:0; padding: 0; border: none" name="option1" value="' .  $item . '" id="' .  $item . '" ' . $checked . '><label for="' .  $item . '"> ' .  $item . '</label>'.$br;
                    }
                    $content .= '</p>';
                }
            }
            break;
            case 'field6':
            if ($qpp['usepostage']) {
				$content .= '<p class="input" >'.$qpp['postageblurb'].'</p>';
				
				// @Change
				// @Add name='postage_type'
				$content .= '<input type="hidden" name="postage_type" value="'.((htmlentities($qpp['postagetype']) == 'postagepercent')? 'percent':'fixed').'" />';
				// @Add name='postage'
				$content .= '<input type="hidden" name="postage" value="'.htmlentities($qpp[$qpp['postagetype']]).'" />';
				
				$content .= '</p>';
			}
            break;
            case 'field7':
            if ($qpp['useprocess']) {
				$content .= '<p class="input" >'.$qpp['processblurb'];
				// @Change
				// @Add name='processing_type'
				$content .= '<input type="hidden" name="processing_type" value="'.((htmlentities($qpp['processtype']) == 'processpercent')? 'percent':'fixed').'" />';
				// @Add name='processing'
				$content .= '<input type="hidden" name="processing" value="'.htmlentities($qpp[$qpp['processtype']]).'" />';
				$content .= '</p>';
			}
            break;
            case 'field8':
            if ($qpp['captcha']) {
                $required = (!$errors['captcha'] ? ' class="required" ' : '');
                if (!empty($qpp['mathscaption'])) $content .= '<p class="input">' . $qpp['mathscaption'] . '</p>';
                $content .= '<p>' . strip_tags($values['thesum']) . ' = <input type="text" '.$required.' style="width:3em;font-size:100%;'.$errors['captcha'].'" label="Sum" name="maths"  value="' . $values['maths'] . '"></p> 
                <input type="hidden" name="answer" value="' . strip_tags($values['answer']) . '" />
                <input type="hidden" name="thesum" value="' . strip_tags($values['thesum']) . '" />';
            }
            break;
            case 'field9':
            $content .= '<input type="hidden" name="couponapplied" value="'.$values['couponapplied'].'" />';
            if ($qpp['usecoupon'] && $values['couponapplied']) {
				$co = qpp_get_coupon($values['couponblurb'],$id);
                $content .= '<input type="hidden" name="couponblurb" value="'.$values['couponblurb'].'" />';
				$content .= '<input type="hidden" name="coupontype" value="'.$co['type'].'" />';
				$content .= '<input type="hidden" name="couponvalue" value="'.$co[$co['type']].'" />';
			}

            if ($qpp['usecoupon'] && ($values['couponapplied'] != 'checked')) {
                if ($values['couponerror']) {
					if ($values['noproduct']) $content .= '<p style="color:'.$style['error-colour'].';">No products selected.</p>';
					else $content .= '<p style="color:'.$style['error-colour'].';">'.$values['couponerror'].'</p>';
				}
                $content .= '<p>'.$values['couponget'].'</p>';
                $content .= '<p><input type="text" id="coupon" name="couponblurb" value="' . $values['couponblurb'] . '" rel="' . $values['couponblurb'] . '" onfocus="qppclear(this, \'' . $values['couponblurb'] . '\')" onblur="qpprecall(this, \'' . $values['couponblurb'] . '\')"/></p>
                <p class="submit">
                <input type="submit" value="'.$qpp['couponbutton'].'" id="couponsubmit" name="qppapply'.$id.'" />
                </p>';
            }
            break;
            
            case 'field10':
            if ($qpp['useterms']) {
                if ($qpp['termspage']) $target = ' target="blank" ';
                $required = (!$errors['useterms'] ? 'border:'.$style['required-border'].';' : $errors['useterms']);
                $color = ($errors['useterms'] ? ' style="color:'.$style['error-colour'].';" ' : '');
                $content .= '<p class="input" '.$errors['useterms'].'>
                <input type="checkbox" style="margin:0; padding: 0;width:auto;'.$required.'" name="termschecked" value="checked" ' . $values['termschecked'] . '>
                &nbsp;
                <a href="'.$qpp['termsurl'].'"'.$target.$color.'>'.$qpp['termsblurb'].'</a></p>';
            }
            break;
            
            case 'field11':
            if ($qpp['useblurb']) $content .= '<p>' . $qpp['extrablurb'] . '</p>';
            break;
            case 'field12':
            if ($qpp['userecurring'] && !$qpp['use_multiples']) {
                $recurringperiod = $qpp['recurring'].'period';
                $content .= '<p>' . $qpp['recurringblurb']. '<br>';
                if ($qpp['variablerecurring']) $content .= '<input type="text" style=" '.$errors['srt'].'width:3em;margin-left:5px" id="srt'.$t.'" label="srt" name="srt" value="' . $values['srt'] . '" onfocus="qppclear(this, \'' . $values['srt'] . '\')" onblur="qpprecall(this, \'' . $values['srt'] . '\')" />';
                else $content .= $qpp['recurringhowmany'].' '. $qpp['every'].' '.$qpp[$recurringperiod];
                $content .= '</p>';
                $checked = 'checked';
                $ref = explode(",",$values['recurring']);
            }
            break;
            
            case 'field13':
            if ($qpp['useaddress']) {
                $content .= '<p>' . $qpp['addressblurb'] . '</p>';
                $arr = array('firstname','lastname','email','address1','address2','city','state','zip','country','night_phone_b');
                foreach($arr as $item)
                    if ($address[$item]) {
                    $required = ($address['r'.$item] && !$errors[$item] ? ' class="required" ' : '');
                    $content .='<p><input type="text" id="'.$item.'" name="'.$item.'" '.$required.$errors[$item].' value="'.$values[$item].'" rel="' . $values[$item] . '" onfocus="qppclear(this, \'' . $values[$item] . '\')" onblur="qpprecall(this, \'' . $values[$item] . '\')"/></p>';
                    }
            }
            break;
            
            case 'field14':
            if ($qpp['usetotals']) {
                $content .= '<p style="font-weight:bold;">'.$qpp['totalsblurb'].' '.$c['b'].'<input type="text" id="qpptotal" name="total" value="0.00" readonly="readonly" />'.$c['a'].'</p>';
            } else {
             $content .= '<input type="hidden" id="qpptotal" name="total"  />';   
            }
            break;
            
            case 'field16':
            if ($qpp['useemail'] && !$qpp['useaddress']) {
                $requiredstock = (!$errors['useemail'] && $qpp['ruseemail'] ? ' class="required" ' : '');
                $content .= '<input type="text" id="email" name="email"'.$required.$errors['email'].'value="' . $values['email'] . '" rel="' . $values['email'] . ' "onfocus="qppclear(this, \'' . $values['email'] . '\')" onblur="qpprecall(this, \'' . $values['email'] . '\')"/>';
            }
            break;
                
            case 'field17':
            if ($qpp['use_message']) {
                $requiredmessage = (!$errors['yourmessage'] && $qpp['ruse_message'] ? ' class="required" ' : '');
                $content .= '<textarea rows="4" name="yourmessage" '.$requiredmessage.$errors['use_message'].' onblur="if (this.value == \'\') {this.value = \''.$values['yourmessage'].'\';}" onfocus="if (this.value == \''.$values['yourmessage'].'\') {this.value = \'\';}" />' . stripslashes($values['yourmessage']) . '</textarea>';
            }
            break;
            case 'field18':
            if ($qpp['use_datepicker']) {
                $required = (!$errors['yourdatepicker'] && $qpp['ruse_datepicker'] ? ' class="required" ' : '');
                $content .= '<input type="text" id="qppdate" name="datepicker" '.$required.'value="' . $values['datepicker'] . '" onfocus="qppclear(this, \'' . $values['datepicker'] . '\')" onblur="qpprecall(this, \'' . $values['datepicker'] . '\')"/><script type="text/javascript">jQuery(document).ready(function() {jQuery(\'\#qppdate\').datepicker({dateFormat : \'dd M yy\'});});</script>';
            }
            break;
            
            case 'field19':
            if ($qpp['use_multiples']) {
                if ($qpp['usecoupon'] && $values['couponapplied']) 
                    $content .= '<p>'.$qpp['couponref'].'</p>';
                $content .= qpp_display_multiples($id,$values);
				$qpp_multiples = true;
            }
            break;
        }
    }
	
    if ($qppkey['authorised'] && $list['enable']) {
        $content .= '<p><input type="checkbox" name="mailchimp" value="checked" '.$value['mailchimp'].'>&nbsp;'.$list['mailchimpoptin'].'</p>';
    }

	$content .= '<input type="hidden" name="multiples" value="'.$qpp_multiples.'" />';
    $caption = $qpp['submitcaption'];
    if ($style['submit-button']) {
        $content .= '<p class="submit pay-button"><input type="image" id="submitimage" value="' . $caption . '" src="'.$style['submit-button'].'" name="qppsubmit'.$id.'" /></p>';
    } else {
        $content .= '<p class="submit pay-button"><input type="submit" value="' . $caption . '" id="submit" name="qppsubmit'.$id.'" /></p>';
    }
    if ($qpp['use_reset']) $content .= '<p><input type="reset" value="'.$qpp['resetcaption'] . '" /></p>';
    $content .= '</form>'."\r\t";
	$content .= '<script type="text/javascript"> qpp_containers.push(jQuery(document.getElementById("frmPayment'.(($id)? $id:'default').'")).find("p.pay-button input").get(0)); </script>';
	$content .= "<div class='qpp-loading'>".$send['waiting']."</div>";
	$content .= "<div class='qpp-validating-form'>".$messages['validating']."</div>";
	$content .= "<div class='qpp-processing-form'>".$messages['waiting']."</div>";
	
    if ($qpp['paypal-url'] && $qpp['paypal-location'] == 'imagebelow') $content .= '<img src="'.$qpp['paypal-url'].'" />';
	
    if ($qpp['usetotals'] || $qpp['use_slider'] || (isset($qpp['combobox']) && $qpp['combobox'] == 'checked')) 
        $content .='<script type="text/javascript">jQuery(document).ready(function() { jQuery("#frmPayment'.$t.'").qpp(); });</script>';
	
    $content .= '<script>jQuery("select option:selected").click(); //force calculation by clicking on default values</script>';
    $content .= '<div style="clear:both;"></div></div></div>'."\r\t";
	echo $content;
}

function qpp_display_multiples($id,$values) {
    $multiples = qpp_get_stored_multiples($id);
    for ($i=1;$i<=9;$i++) {
        $label = $multiples['shortcode'];
        $label = str_replace('[product]', $multiples['product'.$i], $label);
        $label = str_replace('[cost]', $multiples['cost'.$i], $label);
        if ($multiples['product'.$i]) {
			
            if ($multiples['use_quantity']) {
                $content .= '<div style="clear:both;"><span style="float:left;padding:7px 0">'.$label.'</span><span style="float:right;"><input type="text" style="width:4em;text-align:right" name="qtyproduct'.$i.'" id="qtyproduct'.$i.'" value="'.$values['qtyproduct'.$i].'" onfocus="qppclear(this, \''.$values['qtyproduct'.$i].'\')" onblur="qpprecall(this,\''.$values['qtyproduct'.$i].'\')" /></span></div>';
            } else {
                $content .= '<p><input type="checkbox" style="margin:0; padding: 0;width:auto;" name="qtyproduct'.$i.'" value="checked" ' . $values['qtyproduct'.$i] . '>&nbsp'.$label.'</p>';
            }
			
			$content .= '<input type="hidden" name="product'.$i.'" value="'.$multiples['cost'.$i].'" />';
        }
    }
    $content .= '<div style="clear:both;"></div>';
    return $content;
}

function qpp_dropdown($arr,$values,$name,$blurb,$combine = false) {
    $content='';
    if ($blurb) $content = '<p class="payment" >'.$blurb.'</p>';
    $content .= '<select name="'.$name.'">';
    if(!$combine) {
        foreach ($arr as $item) {
            $selected = '';
            if ($values[$name] == $item) $selected = 'selected';
            $content .= '<option value="' .  $item . '" ' . $selected .'>' .  $item . '</option>'."\r\t";
        }
    } else {
        foreach ($arr as $item) {
            $selected = (strrpos($values['reference'],$item[0]) !==false && $values['combine'] != 'initial' ? 'selected' : '');
            $content .=  '<option value="' .  $item[0].'&'.$item[1] . '" ' . $selected . '> ' .  $item[0].' '.$item[1] . '</option>';$selected='';
        }
    }
    $content .= '</select>'."\r\t";
    return $content;
}

function qpp_checkbox($arr,$values,$name,$br) {
    $content .= '<p class="input">';
    foreach ($arr as $item) {
        $checked = '';
        if ($values[$name.'_'. str_replace(' ','',$item)] == $item) $checked = 'checked';
        $content .= '<label><input type="checkbox" style="margin:0; padding: 0; border: none" name="'.$name.'_' . str_replace(' ','',$item) . '" value="' .  $item . '" ' . $checked . '> ' .  $item . '</label>'.$br;
        }
    $content .= '</p>';
    return $content;
}

function explode_by_semicolon ($_) {return explode (';', $_);}

function qpp_handling ($qpp,$check,$quantity){
    if ($qpp['useprocess'] && $qpp['processtype'] == 'processpercent') {
        $percent = preg_replace ( '/[^.,0-9]/', '', $qpp['processpercent']) / 100;
        $handling = $check * $quantity * $percent;}
    if ($qpp['useprocess'] && $qpp['processtype'] == 'processfixed') {
        $handling = preg_replace ( '/[^.,0-9]/', '', $qpp['processfixed']);}
    else $handling = '';
    return $handling;
}

function qpp_postage($qpp,$check,$quantity){
    $packing='';
    if ($qpp['usepostage'] && $qpp['postagetype'] == 'postagepercent') {
        $percent = preg_replace ( '/[^.,0-9]/', '', $qpp['postagepercent']) / 100;
        $packing = $check * $quantity * $percent;}
    if ($qpp['usepostage'] && $qpp['postagetype'] == 'postagefixed') {
        $packing = preg_replace ( '/[^.,0-9]/', '', $qpp['postagefixed']);}
    else $packing='';
    return $packing;
}

function qpp_format_amount($currency,$qpp,$amount) {
    $curr = ($currency == '' ? 'USD' : $currency);
    $decimal = array('HKD','JPY','MYR','TWD');$d='2';
    foreach ($decimal as $item) {
        if ($item == $curr) {$d = '';break;}
    }
    if (!$d) {
        $check = preg_replace ( '/[^.0-9]/', '', $amount);
        $check = intval($check);
    } elseif ($qpp['currency_seperator'] == 'comma' && strpos($amount,',')) {
        $check = preg_replace ( '/[^,0-9]/', '', $amount);
        $check = str_replace(',','.',$check);
        $check = number_format($check, $d,'.','');
    } else {
        $check = preg_replace ( '/[^.0-9]/', '', $amount);
        $check = number_format((float) $check, $d,'.','');
    }
    return $check;
}

function qpp_verify_form(&$v,&$errors,$form) {
    $qpp = qpp_get_stored_options($form);
    $address = qpp_get_stored_address($form);
    $check = preg_replace ( '/[^.,0-9]/', '', $v['amount']);
    $arr = array('amount','reference','quantity','stock','email','yourmessage');
	
    foreach ($arr as $item) $v[$item] = filter_var($v[$item], FILTER_SANITIZE_STRING);
	
    if ($qpp['use_multiples']) {
        $qpp['use_quantity'] = false;
        $v['setpay'] = $v['setref'] = true;
        for ($i=1;$i<=9;$i++) {
            if ($v['qtyproduct'.$i]) $checkmultiple = true;
        }
        if (!$checkmultiple) $errors['multiple'] = 'error';
    }
    
	/*
		Edit: More precise quantity checking
	*/
	if ($qpp['use_quantity']) {
		$max = preg_replace ( '/[^0-9]/', '', $qpp['quantitymaxblurb']);
		if (is_numeric($v['quantity']) && $v['quantity'] >= 1) {
			// quantity exists and is a number!
			// double check if the quanity is invalid with the max blurb
			if ($qpp['quantitymax']) {
				if ($max < $v['quantity']) $errors['quantity'] = 'error';
			}
		} else {
			// is not a number or is 0
			$errors['quantity'] = 'error';
		}
	}

	if (!$v['setpay']) {
		if ((($v['amount'] == $qpp['inputamount']) && ($qpp['fixedamount'] != 'checked')) || (empty($v['amount']))) {
			$errors['amount'] = 'error';
		}
	}
	if ($qpp['allow_amount'] || $v['combine']) $errors['amount'] = '';
		
    if (!$v['setref']) if ($v['reference'] == $qpp['inputreference'] || empty($v['reference'])) 
        $errors['reference'] = 'error';
	
    if($qpp['captcha'] == 'checked') {
        $v['maths'] = strip_tags($v['maths']); 
        if($v['maths'] <> $v['answer']) $errors['captcha'] = 'error';
        if(empty($v['maths'])) $errors['captcha'] = 'error'; 
    }
    
    if($qpp['useterms'] && !$v['termschecked']) $errors['useterms'] = 'error';
    
    if($qpp['useaddress']) {
        $arr = array('email','firstname','lastname','address1','address2','city','state','zip','country','night_phone_b');
        foreach ($arr as $item) {
            $v[$item] = filter_var($v[$item], FILTER_SANITIZE_STRING);
            if ($address['r'.$item] && ($v[$item] == $address[$item] || empty($v[$item]))) $errors[$item] = 'error';
        }
    }
    
    if (!$qpp['fixedstock'] && $qpp['use_stock'] && $qpp['ruse_stock'] && ($v['stock'] == $qpp['stocklabel'] || empty($v['stock'])))
        $errors['use_stock'] = 'error';
    
    if ($qpp['use_message'] && $qpp['ruse_message'] && ($v['yourmessage'] == $qpp['messagelabel'] || empty($v['yourmessage'])))
        $errors['use_message'] = 'error';
    
    if ($qpp['useemail'] && $qpp['ruseemail'] && ($v['email'] == $qpp['emailblurb'] || empty($v['email'])))
        $errors['email'] = 'error';
    
    $errors = array_filter($errors);
    return (count($errors) == 0);
}

/*
	@Change
	@Changed from "Echo" to "Return"
*/
function qpp_process_form($values,$id) {

    $currency = qpp_get_stored_curr();
    $qpp = qpp_get_stored_options($id);
    $send = qpp_get_stored_send($id);
    $auto = qpp_get_stored_autoresponder($id);
    $coupon = qpp_get_stored_coupon($id);
    $address = qpp_get_stored_address($id);
    $style = qpp_get_stored_style($id);
    $qpp_setup = qpp_get_stored_setup();
    $ipn = qpp_get_stored_ipn();
    $qppkey = get_option('qpp_key');
    $list = qpp_get_stored_mailinglist();
	$ajaxurl = admin_url('admin-ajax.php');
    $page_url = qpp_current_page_url();
	$page_url = (($ajaxurl == $page_url)? $_SERVER['HTTP_REFERER']:$page_url);
	
    $paypalurl = 'https://www.paypal.com/cgi-bin/webscr';
	
	if ($values['srt']) $qpp['srt'] = $values['srt'];
    if ($send['customurl']) $paypalurl = $send['customurl'];
    if ($qpp_setup['sandbox']) $paypalurl = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

    if (empty($send['thanksurl'])) $send['thanksurl'] = $page_url;
    if (empty($send['cancelurl'])) $send['cancelurl'] = $page_url;
    if ($send['target'] == 'newpage') $target = ' target="_blank" ';
	else $target = '';
	
    $custom = ($qpp['custom'] ? $qpp['custom'] : md5(mt_rand()));
    $email = ($send['email'] ? $send['email'] : $qpp_setup['email']);
	if ($_REQUEST['combine'] == 'checked') {
		$arr = explode('&',$values['reference']);
		$values['reference'] = $arr[0];
		$values['amount'] = $arr[1];
	}
    $c = (float) qpp_format_amount($currency[$id],$qpp,$values['amount']);
	$check = $c;
	
	if ($_POST['itemamount'] >= $values['amount']) $check = (float) qpp_format_amount($currency[$id],$qpp,$_POST['itemamount']);
    $quantity = (float) ($values['quantity'] < 1 ? '1' : strip_tags($values['quantity']));
   	if ($qpp['useprocess'] && $qpp['processtype'] == 'processpercent') {
        $percent = preg_replace ( '/[^.,0-9]/', '', $qpp['processpercent']) / 100;
        $handling = $check * $quantity * $percent;
        $handling = (float) qpp_format_amount($currency[$id],$qpp,$handling);
    }
	if ($qpp['useprocess'] && $qpp['processtype'] == 'processfixed') {
        $handling = preg_replace ( '/[^.,0-9]/', '', $qpp['processfixed']);
        $handling = (float) qpp_format_amount($currency[$id],$qpp,$handling);
    }
	if ($qpp['usepostage'] && $qpp['postagetype'] == 'postagepercent') {
        $percent = preg_replace ( '/[^.,0-9]/', '', $qpp['postagepercent']) / 100;
        $packing = $check * $quantity * $percent;
        $packing = (float) qpp_format_amount($currency[$id],$qpp,$packing);
    }
	if ($qpp['usepostage'] && $qpp['postagetype'] == 'postagefixed') {
        $packing = preg_replace ( '/[^.,0-9]/', '', $qpp['postagefixed']);
        $packing = (float) qpp_format_amount($currency[$id],$qpp,$packing);
    }
	$check = $c;
	
    $qpp_messages = get_option('qpp_messages'.$id);
   	if(!is_array($qpp_messages)) $qpp_messages = array();
	$sentdate = date_i18n('d M Y');

    $amounttopay = $check * $quantity + $handling + $packing;
    if ($send['combine']) {
        $check = $check + $handling + $packing;
    }
    
    if ($qpp['stock'] == $values['stock'] && !$qpp['fixedstock']) $values['stock'] ='';
    $arr = array(
        'email',
        'firstname',
        'lastname',
        'address1',
        'address2',
        'city',
        'state',
        'zip',
        'country',
        'night_phone_b'
    );
    foreach ($arr as $item) {
        if ($address[$item] == $values[$item])
            $values[$item] = '';
    }
    $custom = md5(mt_rand());
    $qpp_messages[] = array(
        'field0' => $sentdate,
        'field1' => $values['reference'],
        'field2' => $values['quantity'],
        'field3' => $amounttopay,
        'field4' => $values['stock'],
        'field5' => $values['option1'],
        'field6' => $values['couponblurb'],
        'field8' => $values['email'],
        'field9' => $values['firstname'],
        'field10' => $values['lastname'],
        'field11' => $values['address1'],
        'field12' => $values['address2'],
        'field13' => $values['city'],
        'field14' => $values['state'],
        'field15' => $values['zip'],
        'field16' => $values['country'],
        'field17' => $values['night_phone_b'],
        'field18' => $custom,
        'field19' => $values['yourmessage'],
        'field20' => $values['datepicker'],
    );
    update_option('qpp_messages'.$id,$qpp_messages);
    if (!$ipn['ipn']) qpp_check_coupon($values['couponblurb'],$id);
    
    $automessage  = $confirmmessage = false;
    if ($auto['enable'] && $values['email'] && $auto['whenconfirm'] == 'aftersubmission') $automessage = true;
    if ($send['confirmmessage'] && $auto['whenconfirm'] == 'aftersubmission') $confirmmessage = true;

    $amounttopay = qpp_total_amount ($currency,$qpp,$values);
    qpp_send_confirmation($values,$id,$amounttopay,$automessage,$confirmmessage);
	
    $content = '<span><h2 id="qpp_reload">'.$send['waiting'].'</h2>
    <script type="text/javascript" language="javascript">
    document.querySelector("#qpp_reload").scrollIntoView();
    </script>
    <form action="'.$paypalurl.'" method="post" name="frmCart" id="frmCart" ' . $target . '>
    <input type="hidden" name="custom" value="' .$custom. '"/>
    <input type="hidden" name="tax" value="0.00">
    <input type="hidden" name="bn" value="quickplugins_SP">
    <input type="hidden" name="business" value="'.$email.'">
    <input type="hidden" name="return" value="'.$send['thanksurl'].'">
    <input type="hidden" name="cancel_return" value="'.$send['cancelurl'].'">
    <input type="hidden" name="currency_code" value="'.$currency[$id].'">';
    
    if ($qpp['use_multiples']) {
		$content .= '<input type="hidden" name="upload" value="1">';
		// Coupons
        $coupon = qpp_get_stored_coupon($fid);
        for ($i=1; $i<=$coupon['couponnumber']; $i++) {
            if ($values['couponblurb'] == $coupon['code'.$i]) {
                if ($coupon['coupontype'.$i] == 'percent'.$i) $values['couponrate'] = $coupon['couponpercent'.$i];
                if ($coupon['coupontype'.$i] == 'fixed'.$i) $values['couponamount'] = $coupon['couponfixed'.$i];
                }
            }
        if ($values['couponrate']) $content .= '<input type="hidden" name="discount_rate_cart" value="'.$values['couponrate'].'" />';
        if ($values['couponamount']) $content .= '<input type="hidden" name="discount_amount_cart" value="'.$values['couponamount'].'" />';
        // Coupons End
        foreach ($values['items'] as $k => $item) {
			$content .= '<input type="hidden" name="item_name_'.($k + 1).'" value="'.$item['item_name'].'">
				<input type="hidden" name="amount_'.($k + 1).'" value="'.$item['amount'].'">
				<input type="hidden" name="quantity_'.($k + 1).'" value="'.$item['quantity'].'">';
        }
    } else {
        $content .= '<input type="hidden" name="item_name" value="' .strip_tags($values['reference']). '"/>';
    }
    
    if ($ipn['listener']) $content .= '<input type="hidden" name="notify_url" value = "'.$ipn['listener'].'">';
    
    if ($qpp['userecurring']) {
        $content .= '<input type="hidden" name="cmd" value="_xclick-subscriptions">';
    } elseif ($qpp['use_multiples']) {
        $content .= '<input type="hidden" name="cmd" value="_cart">';
    } elseif (isset($send['donate']) && $send['donate']) {
        $content .= '<input type="hidden" name="cmd" value="_donations">';
    } else {
        $content .= '<input type="hidden" name="cmd" value="_xclick">';
    }
    
    if ($qpp['use_stock']) {
        $content .= '<input type="hidden" name="item_number" value="' . strip_tags($values['stock']) . '">';
    }
    
	$multi_p_s = '';
	$multi_p_h = '';
	if ($qpp['use_multiples']) {
		$multi_p_s = '_1';
		$multi_p_h = '_cart';
	}
	
    if ($qpp['userecurring']) {
        $content .= '<input type="hidden" name="a3" value="' . $check . '">
        <input type="hidden" name="p3" value="1">
        <input type="hidden" name="t3" value="' .$qpp['recurring'] . '">
        <input type="hidden" name="src" value="1">
        <input type="hidden" name="srt" value="' .$qpp['recurringhowmany'] . '">';
    } else {
        if (!$qpp['use_multiples']) {
			$content .= '<input type="hidden" name="quantity" value="' . $quantity . '">
			<input type="hidden" name="amount" value="' . $check . '">';
        }
        if ($qpp['use_options']) {
            $content .= '<input type="hidden" name="on0" value="'.$qpp['optionlabel'].'" />
            <input type="hidden" name="os0" value="'.$values['option1'].'" />';
        }
        if ((isset($send['combine']) && !$send['combine']) || !isset($send['combine'])) {
            if ($qpp['useprocess']) {
                $content .='<input type="hidden" name="handling'.$multi_p_h.'" value="' . $handling . '">';
            } else {
                $content .='<input type="hidden" name="handling'.$multi_p_h.'" value="0.00">';
            }
            if ($qpp['usepostage']) {
                $content .='<input type="hidden" name="shipping'.$multi_p_s.'" value="' . $packing . '">';
            } else {
                $content .='<input type="hidden" name="shipping'.$multi_p_s.'" value="0.00">';
            }
        }
    }
    
    if (isset($send['use_lc']) && $send['use_lc']) {
        $content .= '<input type="hidden" name="lc" value="' . $send['lc'] . '">
        <input type="hidden" name="country" value="' . $send['lc'] . '">';
    }
    
    if ($qpp['useaddress']) {
        $arr = array('email','firstname','lastname','address1','address2','city','state','zip','country','night_phone_b');
        foreach($arr as $item) {
            if ($values[$item] && $address[$item] != $values[$item] )
                $content .= '<input type="hidden" name="'.$item.'" value="' . strip_tags($values[$item]) . '">';
        }
    }
    $content .='</form>';
    if (isset($send['createuser']) && $send['createuser'])
        qpp_create_user($values);
     
    if ($list['enable'] && $qppkey['authorised'] && $values['email']) {
		$name = ((isset($values['firstname']))? $values['firstname']:'');
        QPP\subscribe($values['email'],$name);
    }

	if (isset($ipn['ipn']) && $ipn['ipn'] == 'checked') {
		global $qpp_current_custom;
		$qpp_current_custom = $custom;
	}
	return $content;
}

function qpp_current_page_url() {
	$pageURL = 'http';
	if (!isset($_SERVER['HTTPS'])) $_SERVER['HTTPS'] = '';
	if (!empty($_SERVER["HTTPS"])) {
		$pageURL .= "s";
	}
	$pageURL .= "://";
	if (($_SERVER["SERVER_PORT"] != "80") && ($_SERVER['SERVER_PORT'] != '443'))
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	else 
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	return $pageURL;
}

function qpp_currency ($id) {
    $currency = qpp_get_stored_curr();
    $c = array();
    $c['a'] = $c['b'] = '';
	$before = array(
        'USD'=>'&#x24;',
        'CDN'=>'&#x24;',
        'EUR'=>'&euro;',
        'GBP'=>'&pound;',
        'JPY'=>'&yen;',
        'AUD'=>'&#x24;',
        'BRL'=>'R&#x24;',
        'HKD'=>'&#x24;',
        'ILS'=>'&#x20aa;',
        'MXN'=>'&#x24;',
        'NZD'=>'&#x24;',
        'PHP'=>'&#8369;',
        'SGD'=>'&#x24;',
        'TWD'=>'NT&#x24;',
        'TRY'=>'&pound;');
    $after = array(
        'CZK'=>'K&#269;',
        'DKK'=>'Kr',
        'HUF'=>'Ft',
        'MYR'=>'RM',
        'NOK'=>'kr',
        'PLN'=>'z&#322',
        'RUB'=>'&#1056;&#1091;&#1073;',
        'SEK'=>'kr',
        'CHF'=>'CHF',
        'THB'=>'&#3647;');
    foreach($before as $item=>$key) {if ($item == $currency[$id]) $c['b'] = $key;}
    foreach($after as $item=>$key) {if ($item == $currency[$id]) $c['a'] = $key;}
    return $c;
}

function qpp_sanitize($input) {
    $output = array();
    if (is_array($input)) foreach($input as $var=>$val) $output[$var] = filter_var($val, FILTER_SANITIZE_STRING);
    return $output;
    }

function register_qpp_widget() {register_widget( 'qpp_Widget' );}

add_action( 'widgets_init', 'register_qpp_widget' );

class qpp_widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'qpp_widget', // Base ID
            'Paypal Payments', // Name
            array( 'description' => __( 'Paypal Payments', 'Add paypal payment form to your sidebar' ), ) // Args
        );
    }
    public function widget( $args, $instance ) {
        extract($args, EXTR_SKIP);
        $id=$instance['id'];
        $amount=$instance['amount'];
        $form=$instance['form'];
        echo qpp_loop($instance);
    }
    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['id'] = $new_instance['id'];
        $instance['amount'] = $new_instance['amount'];
        $instance['form'] = $new_instance['form'];
        return $instance;
    }
    public function form( $instance ) {
        $instance = wp_parse_args( (array) $instance, array( 'amount' => '' , 'id' => '','form' => '' ) );
        $id = $instance['id'];
        $amount = $instance['amount'];
        $form=$instance['form'];
        $qpp_setup = qpp_get_stored_setup();
        ?>
        <h3>Select Form:</h3>
        <select class="widefat" name="<?php echo $this->get_field_name('form'); ?>">
        <?php
        $arr = explode(",",$qpp_setup['alternative']);
        foreach ($arr as $item) {
            if ($item == '') {$showname = 'default'; $item='';} else $showname = $item;
            if ($showname == $form || $form == '') $selected = 'selected'; else $selected = '';
            ?><option value="<?php echo $item; ?>" id="<?php echo $this->get_field_id('form'); ?>" <?php echo $selected; ?>><?php echo $showname; ?></option><?php } ?>
        </select>
        <h3>Presets:</h3>
        <p><label for="<?php echo $this->get_field_id('id'); ?>">Payment Reference: <input class="widefat" id="<?php echo $this->get_field_id('id'); ?>" name="<?php echo $this->get_field_name('id'); ?>" type="text" value="<?php echo attribute_escape($id); ?>" /></label></p>
        <p><label for="<?php echo $this->get_field_id('amount'); ?>">Amount: <input class="widefat" id="<?php echo $this->get_field_id('amount'); ?>" name="<?php echo $this->get_field_name('amount'); ?>" type="text" value="<?php echo attribute_escape($amount); ?>" /></label></p>
        <p>Leave blank to use the default settings.</p>
        <p>To configure the payment form use the <a href="'.get_admin_url().'options-general.php?page=quick-paypal-payments/quick-paypal-payments.php">Settings</a> page</p>
        <?php
    }
}

function qpp_generate_css() {
    $qpp_form = qpp_get_stored_setup();
    $arr = explode(",",$qpp_form['alternative']);
    $code='';
    foreach ($arr as $item) {
        $corners=$input=$background=$paragraph=$submit='';
        $style = qpp_get_stored_style($item);
        if ($item !='') $id = '.'.$item; else $id = '.default';
        if ($style['font'] == 'plugin') {
            $font = "font-family: ".$style['text-font-family']."; font-size: ".$style['text-font-size'].";color: ".$style['text-font-colour'].";line-height:100%;";
            $inputfont = "font-family: ".$style['font-family']."; font-size: ".$style['font-size']."; color: ".$style['font-colour'].";";
            $selectfont = "font-family: ".$style['font-family']."; font-size: inherit; color: ".$style['font-colour'].";";
            $submitfont = "font-family: ".$style['font-family'];
            if ($style['header-size'] || $style['header-colour']) $header = ".qpp-style".$id." ".$style['header-type']." {font-size: ".$style['header-size']."; color: ".$style['header-colour'].";}";
        }
        $input = ".qpp-style".$id." input[type=text], .qpp-style".$id." textarea {border: ".$style['input-border'].";".$inputfont.";height:auto;line-height:normal; ".$style['line_margin'].";}";
        $input .= ".qpp-style".$id." select {border: ".$style['input-border'].";".$selectfont.";height:auto;line-height:normal;}";
        $input .= ".qpp-style".$id." .qppcontainer input + label, .qpp-style".$id." .qppcontainer textarea + label {".$inputfont."}";
        $required = ".qpp-style".$id." input[type=text].required, .qpp-style".$id." textarea.required {border: ".$style['required-border'].";}";
        $paragraph = ".qpp-style".$id." p {margin:4px 0 4px 0;padding:0;".$font.";}";
        if ($style['submitwidth'] == 'submitpercent') $submitwidth = 'width:100%;';
        if ($style['submitwidth'] == 'submitrandom') $submitwidth = 'width:auto;';
        if ($style['submitwidth'] == 'submitpixel') $submitwidth = 'width:'.$style['submitwidthset'].';';
        
        if ($style['submitposition'] == 'submitleft') $submitposition = 'text-align:left;'; else $submitposition = 'text-align:right;';
        if ($style['submitposition'] == 'submitmiddle') $submitposition = 'margin:0 auto;text-align:center;';
        
        $submitbutton = ".qpp-style".$id." p.submit {".$submitposition."}
.qpp-style".$id." #submitimage {".$submitwidth."height:auto;overflow:hidden;}
.qpp-style".$id." #submit, .qpp-style".$id." #submitimage {".$submitwidth."color:".$style['submit-colour'].";background:".$style['submit-background'].";border:".$style['submit-border'].";".$submitfont.";font-size: inherit;text-align:center;}";
        $submithover = ".qpp-style".$id." #submit:hover {background:".$style['submit-hover-background'].";}";
        
        $couponbutton = ".qpp-style".$id." #couponsubmit, .qpp-style".$id." #couponsubmit:hover{".$submitwidth."color:".$style['coupon-colour'].";background:".$style['coupon-background'].";border:".$style['submit-border'].";".$submitfont.";font-size: inherit;margin: 3px 0px 7px;padding: 6px;text-align:center;}";
        if ($style['border']<>'none') $border =".qpp-style".$id." #".$style['border']." {border:".$style['form-border'].";}";
        if ($style['background'] == 'white') {$bg = "background:#FFF";$background = ".qpp-style".$id." div {background:#FFF;}";}
        if ($style['background'] == 'color') {$background = ".qpp-style".$id." div {background:".$style['backgroundhex'].";}";$bg = "background:".$style['backgroundhex'].";";}
        if ($style['backgroundimage']) $background = ".qpp-style".$id." #".$style['border']." {background: url('".$style['backgroundimage']."');}";
        $formwidth = preg_split('#(?<=\d)(?=[a-z%])#i', $style['width']);
        if (!isset($formwidth[1])) $formwidth[1] = 'px';
        if ($style['widthtype'] == 'pixel') $width = $formwidth[0].$formwidth[1];
        else $width = '100%';
        if ($style['corners'] == 'round') $corner = '5px'; else $corner = '0';
        $corners = ".qpp-style".$id." input[type=text], .qpp-style".$id." textarea, .qpp-style".$id." select, .qpp-style".$id." #submit {border-radius:".$corner.";}";
        if ($style['corners'] == 'theme') $corners = '';
        
        $handle = $style['slider-thickness'] + 1;
        $slider = '.qpp-style'.$id.' div.rangeslider, .qpp-style'.$id.' div.rangeslider__fill {height: '.$style['slider-thickness'].'em;background: '.$style['slider-background'].';}
.qpp-style'.$id.' div.rangeslider__fill {background: '.$style['slider-revealed'].';}
.qpp-style'.$id.' div.rangeslider__handle {background: '.$style['handle-background'].';border: 1px solid '.$style['handle-border'].';width: '.$handle.'em;height: '.$handle.'em;position: absolute;top: -0.5em;-webkit-border-radius:'.$style['handle-colours'].'%;-moz-border-radius:'.$style['handle-corners'].'%;-ms-border-radius:'.$style['handle-corners'].'%;-o-border-radius:'.$style['handle-corners'].'%;border-radius:'.$style['handle-corners'].'%;}
.qpp-style'.$id.' div.qpp-slideroutput{font-size:'.$style['output-size'].';color:'.$style['output-colour'].';}';
        
        $code .= ".qpp-style".$id." {width:".$width.";max-width:100%; }".$border.$corners.$header.$paragraph.$input.$required.$background.$submitbutton.$submithover.$couponbutton.$slider;
        $code  .= '.qpp-style'.$id.' input#qpptotal {font-weight:bold;font-size:inherit;padding: 0;margin-left:3px;border:none;'.$bg.'}';
        if ($style['use_custom'] == 'checked') $code .= $style['custom'];
    }
    return $code;
}

function qpp_head_css() {
	$incontext = qpp_get_incontext();
	$qpp_setup = qpp_get_stored_setup();
	$mode = (($qpp_setup['sandbox'])? 'SANDBOX':'PRODUCTION');
	$data = '<style type="text/css" media="screen">'."\r\n".qpp_generate_css()."\r\n".'</style>
    <script type="text/javascript">qpp_containers = new Array();</script>';
	if (isset($incontext['useincontext']) && $incontext['useincontext'] == 'checked') {
		$data .= "\r\n<script type='text/javascript'> qpp_ic = {id:'".$incontext['merchantid']."',environment:'{$mode}'};</script>\r\n";
	}
    echo $data;
}

function qpp_plugin_action_links($links, $file ) {
    if ( $file == plugin_basename( __FILE__ ) ) {
        $qpp_links = '<a href="'.get_admin_url().'options-general.php?page=quick-paypal-payments/settings.php">'.__('Settings').'</a>';
        array_unshift( $links, $qpp_links );
    }
    return $links;
}

function qpp_report($atts) {
    extract(shortcode_atts(array( 'form' =>''), $atts));
    return qpp_messagetable($form,'');
}

function qpp_messagetable ($id,$email) {
    $qpp_setup = qpp_get_stored_setup();
    $qpp_ipn = qpp_get_stored_ipn();
    $options = qpp_get_stored_options ($id);
    $message = get_option('qpp_messages'.$id);
    $coupon = qpp_get_stored_coupon($id);
    $messageoptions = qpp_get_stored_msg();
    $address = qpp_get_stored_address($id);
    $c = qpp_currency ($id);
    $showthismany = '9999';
    $content = $padding = $count = $arr = '';
    if ($messageoptions['messageqty'] == 'fifty') $showthismany = '50';
    if ($messageoptions['messageqty'] == 'hundred') $showthismany = '100';
    ${$messageoptions['messageqty']} = "checked";
    ${$messageoptions['messageorder']} = "checked";
    if(!is_array($message)) $message = array();
    $title = $id; if ($id == '') $title = 'Default';
    
    if ($options['fixedamount'] && strrpos($options['inputamount'],',')) {
        $options['inputamount'] = ($options['shortcodeamount'] ? $options['shortcodeamount'] : 'Amount');
    }
    if ($options['fixedreference'] && strrpos($options['inputreference'],';')) {
        $options['inputreference'] = ($options['shortcodereference'] ? $options['shortcodereference'] : 'Reference');
    }
    
    
    if (!$email) $dashboard = '<div class="wrap"><div id="qpp-widget">';
    else $padding = 'cellpadding="5"';      
    $dashboard .= '<table cellspacing="0" '.$padding.'><tr>';
    if (!$email) $dashboard .= '<th></th>';
    $dashboard .= '<th style="text-align:left">Date Sent</th>';
    foreach (explode( ',',$options['sort']) as $name) {
        $title='';
        switch ( $name ) {
            case 'field1': $dashboard .= '<th style="text-align:left">'.$options['inputreference'].'</th>';break;
            case 'field2': $dashboard .= '<th style="text-align:left">'.$options['quantitylabel'].'</th>';break;
            case 'field3': $dashboard .= '<th style="text-align:left">'.$options['inputamount'].'</th>';break;
            case 'field4': if ($options['use_stock']) $dashboard .= '<th style="text-align:left">'.$options['stocklabel'].'</th>';break;
            case 'field5': if ($options['use_options']) $dashboard .= '<th style="text-align:left">'.$options['optionlabel'].'</th>';break;
            case 'field6': if ($options['usecoupon']) $dashboard .= '<th style="text-align:left">'.$options['couponblurb'].'</th>';break;
            case 'field8': if ($options['useemail'] || (!$options['useemail'] && $address['email'])) $dashboard .= '<th style="text-align:left">'.$options['emailblurb'].'</th>';break;
            case 'field17': if ($options['use_message']) $dashboard .= '<th style="text-align:left:max-width:20%;">'.$options['messagelabel'].'</th>';break;
            case 'field18': if ($options['use_datepicker']) $dashboard .= '<th style="text-align:left:max-width:20%;">'.$options['datepickerlabel'].'</th>';break;
        }
    }
    if ($messageoptions['showaddress']) {
        $arr = array('firstname','lastname','address1','address2','city','state','zip','country','night_phone_b');
        foreach ($arr as $item) $dashboard .= '<th style="text-align:left">'.$address[$item].'</th>';
    }
    if ($qpp_ipn['ipn']) $dashboard .= '<th>'.$qpp_ipn['title'].'</th>';
    $dashboard .= '</tr>';
    if ($messageoptions['messageorder'] == 'newest') {
        $i=count($message) - 1;
        foreach(array_reverse( $message ) as $value) {
            if ($count < $showthismany ) {
                if ($value['field0']) $report = 'messages';
                $content .= qpp_messagecontent ($id,$value,$options,$c,$messageoptions,$address,$arr,$i,$email);
                $count = $count+1;
                $i--;
            }
        }
    } else {
        $i=0;
        foreach($message as $value) {
            if ($count < $showthismany ) {
                if ($value['field0']) $report = 'messages';
                $content .= qpp_messagecontent ($id,$value,$options,$c,$messageoptions,$address,$arr,$i,$email);
                $count = $count+1;
                $i++;
            }
        }
    }	
    if ($report) $dashboard .= $content.'</table>';
    else $dashboard .= '</table><p>No messages found</p>';
    
    for ($i=1; $i<=$coupon['couponnumber']; $i++) {
        if ($coupon['qty'.$i] > 0) $coups .= '<p>'.$coupon['code'.$i].' - '.$coupon['qty'.$i].'</p>';
    }
    if($coups) $dashboard.= '<h2>Coupons remaining</h2>'.$coups;
    
    $dashboard .= '</div></div>';
    
    return $dashboard;
}

function qpp_messagecontent ($id,$value,$options,$c,$messageoptions,$address,$arr,$i,$email) {
    $qpp_setup = qpp_get_stored_setup();
    $qpp_ipn = qpp_get_stored_ipn();
    $content = '<tr>';
    if (!$email) $content .= '<td><input type="checkbox" name="'.$i.'" value="checked" /></td>';
    $content .= '<td>'.strip_tags($value['field0']).'</td>';
    foreach (explode( ',',$options['sort']) as $name) {
        $title='';
        $amount = preg_replace ( '/[^.,0-9]/', '', $value['field3']);                 
        switch ( $name ) {
            case 'field1': $content .= '<td>'.$value['field1'].'</td>';break;
            case 'field2': $content .= '<td>'.$value['field2'].'</td>';break;
            case 'field3': $content .= '<td>'.$c['b'].$amount.$c['a'].'</td>';break;
            case 'field4': if ($options['use_stock']) {
                if ($options['stocklabel'] == $value['field4']) $value['field4']='';
                $content .= '<td>'.$value['field4'].'</td>';}break;
            case 'field5': if ($options['use_options']) {
                if ($options['optionlabel'] == $value['field5']) $value['field5']='';
                $content .= '<td>'.$value['field5'].'</td>';}break;
            case 'field6': if ($options['usecoupon']) {
                if ($options['couponblurb'] == $value['field6']) $value['field6']='';
                $content .= '<td>'.$value['field6'].'</td>';}break;
            case 'field8': if ($options['useemail'] || (!$options['useemail'] && $address['email'])) {
                if ($options['emailblurb'] == $value['field8']) $value['field8']='';
                $content .= '<td>'.$value['field8'].'</td>';}break;
            case 'field17': if ($options['use_message']) {
                if ($options['messagelabel'] == $value['field19']) $value['field19']='';
                $content .= '<td>'.$value['field19'].'</td>';}break;
            case 'field18': if ($options['use_datepicker']) {
                if ($options['datepickerlabel'] == $value['field20']) $value['field20']='';
                $content .= '<td>'.$value['field20'].'</td>';}break;
        }
    }
    if ($messageoptions['showaddress']) {
        $arr = array('field9','field10','field11','field12','field13','field14','field15','field16','field17');
        foreach ($arr as $item) {
            if ($value[$item] == $address[$item]) $value[$item] = '';
            $content .= '<td>'.$value[$item].'</td>';
        }
    }
    if ($qpp_ipn['ipn']) {
        $ipn = ($qpp_setup['sandbox'] ? $value['field18'] : '');
        $content .= ($value['field18'] == "Paid" ? '<td>'.$qpp_ipn['paid'].'</td>' : '<td>'.$ipn.'</td>');
    }
    $content .= '</tr>';
    return $content;	
}

function qpp_ipn() {
    $qpp_setup = qpp_get_stored_setup();
    $qpp_ipn = qpp_get_stored_ipn();
    if ( !isset( $_REQUEST['paypal_ipn_result'] ) && !$qpp_ipn['ipn'])
        return;
    if ($qpp_setup['disable_error']) define("DEBUG", 0);
    else define("DEBUG", 1);
    define("LOG_FILE", "./ipn.log");
    $raw_post_data = file_get_contents('php://input');
    $raw_post_array = explode('&', $raw_post_data);
    $myPost = array();
    foreach ($raw_post_array as $keyval) {
        $keyval = explode ('=', $keyval);
        if (count($keyval) == 2)
            $myPost[$keyval[0]] = urldecode($keyval[1]);
    }
    $req = 'cmd=_notify-validate';
    if(function_exists('get_magic_quotes_gpc')) {
        $get_magic_quotes_exists = true;
    }
    foreach ($myPost as $key => $value) {
        if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
            $value = urlencode(stripslashes($value));
        } else {
            $value = urlencode($value);
        }
        $req .= "&$key=$value";
    }
    
    if ($qpp_setup['sandbox']) {
        $paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    } else {
        $paypal_url = "https://www.paypal.com/cgi-bin/webscr";
    }
    
    $ch = curl_init($paypal_url);
    if ($ch == FALSE) {
        return FALSE;
    }

    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);

    if(DEBUG == true) {
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
    }

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

    $res = curl_exec($ch);
    if (curl_errno($ch) != 0) // cURL error
    {
        if(DEBUG == true) {	
            error_log(date('[Y-m-d H:i e] '). "Can't connect to PayPal to validate IPN message: " . curl_error($ch) . PHP_EOL, 3, LOG_FILE);
        }
        curl_close($ch);
        // exit;
    } else {
		if(DEBUG == true) {
			error_log(date('[Y-m-d H:i e] '). "HTTP request of validation request:". curl_getinfo($ch, CURLINFO_HEADER_OUT) ." for IPN payload: $req" . PHP_EOL, 3, LOG_FILE);
			error_log(date('[Y-m-d H:i e] '). "HTTP response of validation request: $res" . PHP_EOL, 3, LOG_FILE);
        }
		curl_close($ch);
    }

    $tokens = explode("\r\n\r\n", trim($res));
    $res = trim(end($tokens));

    if (strcmp ($res, "VERIFIED") == 0) {
        $custom = $_POST['custom'];
        $arr = explode(",",$qpp_setup['alternative']);
        foreach ($arr as $item) {
            $message = get_option('qpp_messages'.$item);
            $count = count($message);
            for($i = 0; $i <= $count; $i++) {
                if ($message[$i]['field18'] == $custom && $message[$i]['field1']) {
                    $message[$i]['field18'] = 'Paid';
                    $auto = qpp_get_stored_autoresponder($item);
                    $send = qpp_get_stored_send($item);
                    qpp_check_coupon($message[$i]['field6'],$item);
                    if ($send['confirmmessage'] && $auto['whenconfirm'] == 'afterpayment') $confirmmessage = true;
                    if ($auto['enable'] && $message[$i]['field8'] && $auto['whenconfirm'] == 'afterpayment' ) {
                        $values = array(
                            'reference' => $message[$i]['field1'],
                            'quantity'  => $message[$i]['field2'],
                            'amount'    => $message[$i]['field3'],
                            'stock'     => $message[$i]['field4'],
                            'option1'   => $message[$i]['field5'],
                            'email'     => $message[$i]['field8'],
                            'firstname' => $message[$i]['field9'],
                            'lastname'  => $message[$i]['field10'],
                            'address1'  => $message[$i]['field11'],
                            'address2'  => $message[$i]['field12'],
                            'city'      => $message[$i]['field13'],
                            'state'     => $message[$i]['field14'],
                            'zip'       => $message[$i]['field15'],
                            'country'   => $message[$i]['field16'],
                            'night_phone_b' => $message[$i]['field17'],
                            'datepicker'=> $message[$i]['field20'],
                        );
                        qpp_send_confirmation($values,$item,$message[$i]['field3'],true,$confirmmessage);
                    }
                    update_option('qpp_messages'.$item,$message);
                }
            }
        }
        
        if(DEBUG == true) {
            error_log(date('[Y-m-d H:i e] '). "Verified IPN: $req ". PHP_EOL, 3, LOG_FILE);
        }
    
    } else if (strcmp ($res, "INVALID") == 0) {
        if(DEBUG == true) {
            error_log(date('[Y-m-d H:i e] '). "Invalid IPN: $req" . PHP_EOL, 3, LOG_FILE);
        }
    }
}

add_action( 'template_redirect', 'qpp_upgrade_ipn' );

function qpp_upgrade_ipn() {
    $qppkey = get_option('qpp_key');
    if ( (!isset( $_REQUEST['paypal_ipn_result']) && !isset($_POST['custom'])) || $qppkey['authorised'])
        return;
    define("DEBUG", 0);
    define("LOG_FILE", "./ipn.log");
    $raw_post_data = file_get_contents('php://input');
    $raw_post_array = explode('&', $raw_post_data);
    $myPost = array();
    foreach ($raw_post_array as $keyval) {
        $keyval = explode ('=', $keyval);
        if (count($keyval) == 2)
            $myPost[$keyval[0]] = urldecode($keyval[1]);
    }
    $req = 'cmd=_notify-validate';
    if(function_exists('get_magic_quotes_gpc')) {
        $get_magic_quotes_exists = true;
    }
    foreach ($myPost as $key => $value) {
        if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
            $value = urlencode(stripslashes($value));
        } else {
            $value = urlencode($value);
        }
        $req .= "&$key=$value";
    }

    $ch = curl_init("https://www.paypal.com/cgi-bin/webscr");
    if ($ch == FALSE) {
        return FALSE;
    }

    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);

    if(DEBUG == true) {
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
    }

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

    $res = curl_exec($ch);
    if (curl_errno($ch) != 0) // cURL error
    {
        if(DEBUG == true) {	
            error_log(date('[Y-m-d H:i e] '). "Can't connect to PayPal to validate IPN message: " . curl_error($ch) . PHP_EOL, 3, LOG_FILE);
        }
        curl_close($ch);
        // exit;
    } else {
		if(DEBUG == true) {
			error_log(date('[Y-m-d H:i e] '). "HTTP request of validation request:". curl_getinfo($ch, CURLINFO_HEADER_OUT) ." for IPN payload: $req" . PHP_EOL, 3, LOG_FILE);
			error_log(date('[Y-m-d H:i e] '). "HTTP response of validation request: $res" . PHP_EOL, 3, LOG_FILE);
        }
		curl_close($ch);
    }

    $tokens = explode("\r\n\r\n", trim($res));
    $res = trim(end($tokens));

    if (strcmp ($res, "VERIFIED") == 0 && $qppkey['key'] == $_POST['custom']) {
        $qppkey['authorised'] = 'true';
        update_option('qpp_key',$qppkey);
        $email = get_option('admin_email');
        $qpp_setup = qpp_get_stored_setup();
        $email  = ($qpp_setup['email'] ? $qpp_setup['email'] : $email);
        $headers = "From: Quick Plugins <mail@quick-plugins.com>\r\n"
. "MIME-Version: 1.0\r\n"
. "Content-Type: text/html; charset=\"utf-8\"\r\n";	
        $message = '<html><p>Thank for upgrading to QPP Pro. Your authorisation key is:</p><p>'.$qppkey['key'].'</p></html>';
        wp_mail($email,'Quick Plugins Authorisation Key',$message,$headers);
    }
    exit();
}

function qpp_get_coupon($couponcode,$id) {
    $coupon = qpp_get_stored_coupon($id);
    for ($i=1; $i<=$coupon['couponnumber']; $i++) {
        if ($couponcode == $coupon['code'.$i] && $coupon['qty'.$i] > 0) {
			return array(
				'code' => $coupon['code'.$i],
				'qty' => $coupon['qty'.$i],
				'expired' => $coupon['expired'.$i],
				'fixed' => $coupon['couponfixed'.$i],
				'percent' => $coupon['couponpercent'.$i],
				'type' => preg_replace('/([0-9]+)/','',$coupon['coupontype'.$i])
			);
		}
	}
	return false;
}

function qpp_check_coupon($couponcode,$id) {
    $coupon = qpp_get_stored_coupon($id);
    for ($i=1; $i<=$coupon['couponnumber']; $i++) {
        if ($couponcode == $coupon['code'.$i] && $coupon['qty'.$i] > 0) {
            $coupon['qty'.$i] = $coupon['qty'.$i] - 1;
            if ($coupon['qty'.$i] == 0) {
                $coupon['code'.$i] = $coupon['qty'.$i]= '';
                $coupon['expired'.$i] = true;
            }
            update_option( 'qpp_coupon'.$id, $coupon );
        }
    }
}

function qpp_send_confirmation ($values,$form,$amounttopay,$automessage,$confirmmessage) {
    $qpp_setup = qpp_get_stored_setup();
    $qpp = qpp_get_stored_options($form);
    $address = qpp_get_stored_address($form);
    $send = qpp_get_stored_send($form);
    $auto = qpp_get_stored_autoresponder($form);
    $currency = qpp_get_stored_curr();
    $curr = $currency[$form];
    $c = qpp_currency ($form);
    
    $amounttopay = qpp_format_amount($curr,$qpp,$amounttopay);
    
    if (empty($auto['fromemail'])) {
        $auto['fromemail'] = $qpp_setup['email'];
    }
    if (empty($auto['fromname'])) {
        $auto['fromname'] = get_bloginfo('name');
    }
    
    if (empty($send['confirmemail'])) {
        $confirmemail = get_bloginfo('admin_email');
    } else {
        $confirmemail = $send['confirmemail'];
    }
    
    $fullamount = $c['b'].$amounttopay.$c['a'];
    $headers = "From: {$auto['fromname']} <{$auto['fromemail']}>\r\n"
. "MIME-Version: 1.0\r\n"
. "Content-Type: text/html; charset=\"utf-8\"\r\n";	
    $subject = $auto['subject'];
    
    $ref = ($qpp['fixedreference'] && $qpp['shortcodereference'] ? $qpp['shortcodereference'] : $qpp['inputreference']);
    
    $amt = ($qpp['shortcodeamount'] && $qpp['fixedamount'] ? $qpp['shortcodeamount'] : $qpp['inputamount']);

    $rcolon = (strpos($ref,':') ? '' : ': ');
    $acolon = (strpos($amt,':') ? '' : ': ');
    
    $details = '<h2>Order Details:</h2>';
    
    $details .= '<p>'.$ref.$rcolon.$values['reference'].'</p>';
    
    $details .= '<p>'.$qpp['quantitylabel'].': '.$values['quantity'].'</p>';
    if ($qpp['use_stock']) {
        if ($qpp['fixedstock']) $details .= '<p>'.$qpp['stocklabel'].'</p>';
        else $details .= '<p>'.$qpp['stocklabel'].': ' . strip_tags($values['stock']) . '</p>';
    }
    if ($qpp['use_options']) $details .= '<p>'.$qpp['optionlabel'].': ' . strip_tags($values['option1']) . '</p>';
    
    $details .= '<p>'.$amt.$acolon.$fullamount.'</p>';
    
    if ($qpp['use_message'] && $qpp['messagelabel'] !=$values['yourmessage']) $details .= '<p>'.$qpp['messagelabel'].': ' . strip_tags($values['yourmessage']) . '</p>';
    
    if ($qpp['use_datepicker']) $details .= '<p>'.$qpp['datepickerlabel'].': ' . strip_tags($values['datepicker']) . '</p>';
    
    $content = '<p>' . $auto['message'] . '</p>';
    $content = str_replace('<p><p>', '<p>', $content);
    $content = str_replace('</p></p>', '</p>', $content);
    $content = str_replace('[firstname]', $values['firstname'], $content);
    $content = str_replace('[name]', $values['firstname'].' '.$values['lastname'], $content);
    $content = str_replace('[reference]', $values['reference'], $content);
    $content = str_replace('[quantity]', $values['quantity'], $content);
    $content = str_replace('[fullamount]', $fullamount, $content);
    $content = str_replace('[amount]', $amounttopay, $content);
    $content = str_replace('[stock]', $values['stock'], $content);
    $content = str_replace('[option]', $values['option1'], $content);
    $content = str_replace('[details]', $details, $content);

    if ($auto['paymentdetails']) {
        $content .= $details;
    }
    if ($automessage) {
        wp_mail($values['email'], $subject, '<html>'.$content.'</html>', $headers);
    }
    
    if ($confirmmessage) {
        $subject = 'Payment for '.$values['reference'];
        if ($qpp['useaddress']) {
            $contentb .= '<h2>Personal Details</h2>
            <table>
            <tr><td>'.$address['email'].'</td><td>'.$values['email'].'</td></tr></tr>
            <tr><td>'.$address['firstname'].'</td><td>'.$values['firstname'].'</td></tr>
            <tr><td>'.$address['lastname'].'</td><td>'.$values['lastname'].'</td></tr>
            <tr><td>'.$address['address1'].'</td><td>'.$values['address1'].'</td></tr>
            <tr><td>'.$address['address2'].'</td><td>'.$values['address2'].'</td></tr>
            <tr><td>'.$address['city'].'</td><td>'.$values['city'].'</td></tr>
            <tr><td>'.$address['state'].'</td><td>'.$values['state'].'</td></tr>
            <tr><td>'.$address['zip'].'</td><td>'.$values['zip'].'</td></tr>
            <tr><td>'.$address['country'].'</td><td>'.$values['country'].'</td></tr>
            <tr><td>'.$address['night_phone_b'].'</td><td>'.$values['night_phone_b'].'</td></tr>
            </table>';
        }
        $content = '<html>'.$details.$contentb.'</html>';
        wp_mail($confirmemail, $subject, $content, $headers);
    }
}

function qpp_total_amount ($currency,$qpp,$values) {
    $check = qpp_format_amount($currency,$qpp,$values['amount']);
    $quantity =($values['quantity'] < 1 ? '1' : strip_tags($values['quantity']));
   	if ($qpp['useprocess'] && $qpp['processtype'] == 'processpercent') {
        $percent = preg_replace ( '/[^.,0-9]/', '', $qpp['processpercent']) / 100;
        $handling = $check * $quantity * $percent;
        $handling = (float) qpp_format_amount($currency,$qpp,$handling);
    }
	if ($qpp['useprocess'] && $qpp['processtype'] == 'processfixed') {
        $handling = preg_replace ( '/[^.,0-9]/', '', $qpp['processfixed']);
        $handling = (float) qpp_format_amount($currency,$qpp,$handling);
    }
	if ($qpp['usepostage'] && $qpp['postagetype'] == 'postagepercent') {
        $percent = preg_replace ( '/[^.,0-9]/', '', $qpp['postagepercent']) / 100;
        $packing = $check * $quantity * $percent;
        $packing = (float) qpp_format_amount($currency,$qpp,$packing);
    }
	if ($qpp['usepostage'] && $qpp['postagetype'] == 'postagefixed') {
        $packing = preg_replace ( '/[^.,0-9]/', '', $qpp['postagefixed']);
        $packing = (float) qpp_format_amount($currency,$qpp,$packing);
    }
    $amounttopay = $check * $quantity + $handling + $packing;
    return $amounttopay;
}

function qpp_create_user($values) {
    $user_name = $values['firstname'];
    $user_email = $values['email'];
    $user_id = username_exists( $user_name );
    if ( !$user_id and email_exists($user_email) == false and $user_name and $user_email) {
        $password = wp_generate_password( $length=12, $include_standard_special_chars=false );
        $user_id = wp_create_user( $user_name, $password , $user_email );
        wp_update_user(array('ID' =>  $user_id, 'role' => 'subscriber'));
        wp_new_user_notification( $user_id, $notify = 'both' );
    }
}
        
function qpp_mailchimp($values,$send) {
    $content = '<form action="http://mailchimp.us8.list-manage.com/subscribe/post" method="POST" id="mailchimpsubmit">
    <input type="hidden" name="u" value="'.$send['mailchimpuser'].'">
    <input type="hidden" name="id" value="'.$send['mailchimpid'].'">
    <input type="hidden" name="MERGE0" id="MERGE0" value='.$values['email'].'>
    <input type="hidden" name="FNAME" id="FNAME" value='.$values['firstname'].'>
    <input type="hidden" name="LNAME" id="LNAME" value='.$values['lastname'].'>
    </form>
    <script language="JavaScript">document.getElementById("mailchimpsubmit").submit();</script>';
    return $content;
}

function qpp_start_transaction(&$paypal, $currency, $qpp, $v, $form) {

	/*
		Use GET Variables
	*/
	$amount = (float) $v['amount'];
	$name = (string) $v['items'][0]['item_name'];
	$qty = (float) $v['items'][0]['quantity'];

	if (!$amount && isset($_POST['combine'])) {
		$c = array();
		if (strpos($name,'&')) $c = explode('&',$name);
		if (count($c)) {
			$name = $c[0];
			$amount = $c[1];
		}
	}
	
	$amount = (float) qpp_format_amount($currency,$qpp,preg_replace('/([^0-9.,])/','',$amount));

	$order = $paypal->NewOrder();
	$items = array();
	
	if ($qpp['use_multiples']) {
		$amount = 0;
		foreach ($v['items'] as $k => $i) {
			$item = $order->NewItem($i['amount'],$i['quantity']);
			
			$amount += ($i['amount'] * $i['quantity']);
			
			/*
				Build Item Name
			*/
			$option = '';
			if (isset($_POST['option1'])) $option = ' ('.$_POST['option1'].')';
			
			$item->setAttribute('NAME',$i['item_name'].$option);
			
			$items[] = $item;
		}
	} else {
		$item = $order->NewItem($amount,$qty);
		
		$option = '';
		if (isset($_POST['option1'])) $option = ' ('.$_POST['option1'].')';
		$item->setAttribute('NAME', $name.$option);
		
		$items[] = $item;
	}
	
	/*
		Build Note
	*/
	if (isset($_POST['yourmessage']) && strlen($_POST['yourmessage'])) $order->setAttribute('NOTETEXT',$_POST['yourmessage']);
	
	/*
		Build Address
	*/
	$a_vars = array(
		'firstname' => 'CALCULATE',
		'lastname' => 'CALCULATE',
		'address1' => 'SHIPTOSTREET',
		'address2' => 'SHIPTOSTREET',
		'city' => 'SHIPTOCITY',
		'state' => 'SHIPTOSTATE',
		'zip' => 'SHIPTOZIP',
		'country' => 'SHIPTOCOUNTRY',
		'night_phone_b' => 'SHIPTOPHONENUM'
	);
	
	$name = $fname = $lname = '';
	foreach ($_POST as $k => $val) {
		if (array_key_exists($k,$a_vars)) {
			
			$prop = $a_vars[$k];
			
			// Address variable -- COLLECT IT 
			switch ($prop) {
				case 'CALCULATE':
					if ($k == 'firstname') $fname = $val;
					else $lname = $val;
				break;
				default:
					$order->setAttribute($prop,$val);
				break;
			}
		}
	}
	if (!empty($fname) || !empty($lname)) {
		$name = trim($fname.' '.$lname);
		$order->setAttribute('SHIPTONAME',$name);
	}
	
	
	
	/*
		Handle Shipping & Handling
	*/
	$postage = 0;
	$processing = 0;

	if ($qpp['use_multiples']) {
		
		// Shipping
		if (isset($_POST['postage_type'])) {
				
			$postage = $qpp['postagefixed'];
			if (strtolower($_POST['postage_type']) == 'percent') {
				$postage = $qpp['postagepercent'];
				$postage = qpp_get_total($v['items']) * ($postage * .01);
			}
				
			$postage = (float) qpp_format_amount($currency,$qpp,preg_replace('/([^0-9.,])/','',$postage));
			$postage = $postage/* * $qty */;
				
			$order->setAttribute('SHIPPINGAMT',$postage);
			
		}
			
		// Shipping
		if (isset($_POST['processing_type'])) {
				
			if (strtolower($_POST['processing_type']) == 'percent') {
				$processing = (float) $qpp['processpercent'];
				$processing = qpp_get_total($v['items']) * ($processing * .01);
			}
				
			$processing = (float) qpp_format_amount($currency,$qpp,preg_replace('/([^0-9.,])/','',$processing));
			$processing = $processing/* * $qty */;
				
			$order->setAttribute('HANDLINGAMT',$processing);
		}
		
		$amounttopay = $processing + $postage + ($amount * $qty);
		
		/*
			Apply discounts
		*/
		if ($qpp['usecoupon'] == 'checked' && $qpp['use_multiples']) {

			if ($_POST['couponapplied'] == 'checked') {
			
				$x = $order->dump();
                $x = $x['PAYMENTREQUEST_0_ITEMAMT'];

				/*
					Get coupon details
				*/
				$coupon = qpp_get_coupon($v['couponblurb'],$form);
				if ($coupon) {
					// Coupon exists
					if ($coupon['type'] == 'percent') { // get the value of the coupon
						$discount = $x * ($coupon['percent'] * .01);
					} else {
						$discount = $coupon['fixed'];
					}
				}
			
				if ($discount > 0 && $x > $discount) {
					$item = $order->NewItem(-1 * abs($discount),1);
					$item->setAttribute('NAME','Coupon Code: '.$coupon['code']);
				}
			}
		}
	} else {
		/*
			Non-Multiples incontext payment
		*/
		
		/*
			Original Amount
		*/
		$o_amt = $amount * $qty;
		if ($_POST['couponapplied'] == 'checked') {
					
			$x = $order->dump()['PAYMENTREQUEST_0_ITEMAMT'];
			/*
				Get coupon details
			*/
			$c = qpp_get_coupon($v['couponblurb'],$form);
				
			if ($c['type'] == 'percent') {
				$o_amt = $o_amt / ((100 - $c['percent']) * .01);
			} else {
				$o_amt = $o_amt + $c['fixed'];
			}
		}

		// Shipping
		if (isset($_POST['postage_type'])) {
				
			$postage = $qpp['postagefixed'];
			if (strtolower($_POST['postage_type']) == 'percent') {
				$postage = $qpp['postagepercent'];
				$postage = $o_amt * ($postage * .01);
			}
				
			$postage = (float) qpp_format_amount($currency,$qpp,preg_replace('/([^0-9.,])/','',$postage));
			$postage = $postage/* * $qty */;
				
			$order->setAttribute('SHIPPINGAMT',$postage);
			
		}
			
		// Shipping
		if (isset($_POST['processing_type'])) {
				
			if (strtolower($_POST['processing_type']) == 'percent') {
				$processing = (float) $qpp['processpercent'];
				$processing = $o_amt * ($processing * .01);
			}
				
			$processing = (float) qpp_format_amount($currency,$qpp,preg_replace('/([^0-9.,])/','',$processing));
			$processing = $processing/* * $qty */;
				
			$order->setAttribute('HANDLINGAMT',$processing);
		}
		
		$amounttopay = $processing + $postage + ($amount * $qty);
		
	}
	/*
		Add IPN code and put this transaction into the MESSAGES table
	*/
	$ipn = qpp_get_stored_ipn();
	if ((isset($ipn['ipn']) && $ipn['ipn'] == 'checked') && (($ipn['listener']) || ($ipn['default']))) {
		$ipn_url = (($ipn['listener'])? $ipn['listener']:$ipn['default']);
		
		global $qpp_current_custom;
		
		$order->setAttribute('CUSTOM',$qpp_current_custom);
		$order->setAttribute('NOTIFYURL',$ipn_url);
	}
	
	
	
	/*
		Add currency code
		Defaults to USD
	*/
	if (!empty($currency)) $order->setAttribute('CURRENCYCODE',$currency);

}

function qpp_execute_transaction($p) {
	return $p->execute();
}