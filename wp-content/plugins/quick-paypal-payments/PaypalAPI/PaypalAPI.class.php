<?php	
	/**
		* Executes Paypal Express Checkout methods with NVP
		*
		* @package		PaypalAPI
		* @author 		Russell <RussellReal@gmail.com>
		* @license		https://tldrlegal.com/license/mit-license MIT License 3.01
		* @version		Release: 1.0
		* @since		2016-05-24
	*/
	if (!class_exists('PaypalAPI')) {
		class PaypalAPI {
			private $username, $password, $key, $api, $attributes, $fields, $method, $orders;
			public $response;
			
			/**
				* Collects basic starting data aswell as constructs the PaypalAPI object
				* 
				* @param String $u The username for the API user
				* @param String $p The password for the API user
				* @param String $k The signature for the API user
				* @param String $m [production|sandbox] The mode which the API user wishes to operate in
				* 
				* @return No return data
			*/ 
			public function __construct($u,$p,$k,$m) {
				$this->username = $u;
				$this->password = $p;
				$this->key = $k;
				$this->orders = array();
				$this->attributes = array();
				
				$mode = strtolower($m);
				
				if ($mode == 'production') {
					$this->api = 'https://api-3t.paypal.com/nvp';
				} else {
					$this->api = 'https://api-3t.sandbox.paypal.com/nvp';
				}
				
				/*
					Build list of variables accepted by Paypal
				*/
				$this->fields = array();
				$this->fields['SetExpressCheckout'] = array(
					'MAXAMT' => 'float',
					'RETURNURL' => 'string',
					'CANCELURL' => 'string',
					'CALLBACK' => 'string',
					'CALLBACKTIMEOUT' => 'string',
					'REQCONFIRMSHIPPING' => 'int',
					'ALLOWNOTE' => 'int',
					'ADDOVERRIDE' => 'int',
					'CALLBACKVERSION' => 'float',
					'LOCALECODE' => 'string',
					'PAGESTYLE' => 'string',
					'HDRIMG' => 'string',
					'PAYFLOWCOLOR' => 'string',
					'CARTBORDERCOLOR' => 'string',
					'LOGOIMG' => 'string',
					'EMAIL' => 'string',
					'SOLUTIONTYPE' => 'string',
					'LANDINGPAGE' => 'string',
					'CHANNELTYPE' => 'string',
					'TOTALTYPE' => 'string',
					'GIROPAYSUCCESSURL' => 'string',
					'GIROPAYCANCELURL' => 'string',
					'BANKTXNPENDINGURL' => 'string',
					'BRANDNAME' => 'string',
					'CUSTOMERSERVICENUMBER' => 'string',
					'GIFTMESSAGEENABLE' => 'int',
					'GIFTRECEIPTENABLE' => 'int',
					'GIFTWRAPENABLE' => 'int',
					'GIFTWRAPNAME' => 'string',
					'GIFTWRAPAMOUNT' => 'float',
					'BUYEREMAILOPTINENABLE' => 'int',
					'SURVEYQUESTION' => 'string',
					'SURVEYENABLE' => 'int',
					'L_SURVEYCHOICE0' => 'string',
					'L_SURVEYCHOICE1' => 'string',
					'L_SURVEYCHOICE2' => 'string',
					'L_SURVEYCHOICE3' => 'string',
					'L_SURVEYCHOICE4' => 'string',
					'L_SURVEYCHOICE5' => 'string',
					'L_SURVEYCHOICE6' => 'string',
					'L_SURVEYCHOICE7' => 'string',
					'L_SURVEYCHOICE8' => 'string',
					'L_SURVEYCHOICE9' => 'string',
					'BUYERID' => 'string',
					'BUYERUSERNAME' => 'string',
					'BUYERREGISTRATIONDATE' => 'string',
					'ALLOWPUSHFUNDING' => 'int',
					'USERSELECTEDFUNDINGSOURC' => 'string',
					'TAXIDTYPE' => 'string',
					'TAXID' => 'string'
				);
				
				$this->fields['GetExpressCheckoutDetails'] = array(
					'TOKEN' => 'string'
				);
				
				$this->fields['DoExpressCheckoutPayment'] = array(
					'TOKEN' => 'string',
					'PAYERID' => 'string',
					'MSGSUBID' => 'string',
					'GIFTMESSAGE' => 'string',
					'GIFTRECEIPTENABLE' => 'int',
					'GIFTWRAPNAME' => 'string',
					'GIFTWRAPAMOUNT' => 'string',
					'BUYERMARKETINGEMAIL' => 'string',
					'SURVEYQUESTION' => 'string',
					'SURVEYCHOICESELECTED' => 'string',
					'BUTTONSOURCE' => 'string',
					'SKIPBACREATION' => 'int',
					'RETURNFMFDETAILS' => 'int',
					'INSURANCEOPTIONSELECTED' => 'string',
					'SHIPPINGOPTIONISDEFAULT' => 'string',
					'SHIPPINGOPTIONAMOUNT' => 'float',
					'SHIPPINGOPTIONNAME' => 'string'
				);
			}
			
			/**
				* Sets attributes for the PaypalAPI object
				*
				* @param 	String 		$attribute	The index/key of the attribute
				* @param 	String 		$value		The value of the attribute
				* 
				* @return 	Bool 		Returns true or false if method exists
			*/ 
			public function setAttribute($attribute, $value) {
				
				if (!empty($this->method)) {
					// Check if the attribute is a valid Item attribute
					if (array_key_exists($attribute,$this->fields[$this->method])) {
						$et = $this->fields[$this->method][$attribute];
						if ($et == gettype($value) || ($et == 'float' && is_numeric($value)) || ($et == 'int' && in_array(gettype($value),array('int','integer')))) {
							$this->attributes[$attribute] = $value;
							return true;
						}
					}
					
					return false;
				}
				return false;
				
			}
			
			/**
				* Gets an attribute from the PaypalAPI class
				*
				* @param 	String 		$attr	The index/key of the attribute
				* 
				* @return 	Bool 		Returns true or false if method exists
			*/ 
			public function a($attr) {
				if (!empty($this->method)) {
					if (isset($this->attributes[$attr])) return $this->attributes[$attr];
				}
				return false;
			}
			
			/**
				* Sets the "METHOD" attribute in the Paypal Request
				* Also defines the allowed field-set for the specific method
				*
				* @param 	String 		$method The intended method for the NVP transaction
				* 
				* @return 	Bool 		Returns true or false if method exists
			*/ 
			public function setMethod($method) {
				if (isset($this->fields[$method])) {
					$this->method = $method;
					return true;
				}
				return false;
			}
			
			/**
				* Adds an order to the transaction
				*
				* @return Mixed		Returns new PaypalAPI_Order object on success, false upon failure
			*/
			public function NewOrder() {
				$x = new PaypalAPI_Order();
				
				$this->orders[] = $x;
				$this->currentorder = $x;
				
				return $x;
			}
			
			
			
			/**
				* Dumps this object of all of the important information of this object
				*
				* @return 	Array	Returns an array of all of the information
			*/
			public function dump() {
				
				/*
					Build standard API signing code
				*/
				$return = array(
					'USER' => $this->username,
					'PWD' => $this->password,
					'SIGNATURE' => $this->key,
					'METHOD' => $this->method,
					'VERSION' => 204
				);

				$return = array_merge($return,$this->attributes);

				for ($i = 0; $i < count($this->orders); $i++) {
					$return = array_merge($return,$this->orders[$i]->dump($i));

				}

				
				return $return;
				
			}
			
			
			/**
				* Executes the built request
				* 
				* @param String $method 
				*
				* @return Returns a response array
			*/
			public function execute() {
				
				/*
					Do not allow to execute without method
				*/
				if (empty($this->method)) return;
				
				$post = $this->dump();
				$query = http_build_query($post);
				$ch = curl_init();
				
				/*
					Do request
				*/
				curl_setopt($ch,CURLOPT_URL, $this->api);
				curl_setopt($ch,CURLOPT_POST, count($post));
				curl_setopt($ch,CURLOPT_POSTFIELDS, $query);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				$this->response = curl_exec($ch);

				if ($this->response) $this->formatResponse();
				

				/*
					Terminate connection
				*/
				curl_close($ch);

				return $this->response;
				
			}
			
			/**
				* Formats the paypal object into a useable array
				*
				* @return	No return data
			*/
			public function formatResponse() {
				$arr = array();
				parse_str($this->response,$arr);
				$this->response = $arr;
				
			}
			/**
				* Reloads the paypal object from a response from paypal
				*
				* @param	String		$method		The method to use when creating this PaypalAPI object
				*
				* @return	PaypalAPI	Returns a PaypalAPI object formed around the response of the request
			*/
			public function reloadFromResponse($method) {
				$this->setMethod($method);
				/*
					Reload the orders
				*/
				$this->attributes = array();
				$this->orders = array();
				
				/*
					Load all applicable data into the attributes fields
				*/
				foreach ($this->response as $key => $value) {
					if ($key == 'METHOD') continue;
					$this->setAttribute($key,$value);
				}
				
				/*
					Sort out all of the order details and item details
				*/
				$orders = array(); // Two dimensional array, first index is order_number second index is associative and is property in question
				$items = array(); // Three Dimensional Array, first index is order_number second index is Item_Number, 3rd is associative and is the property in question
				foreach ($this->response as $key => $value) {
					$matches = array();
					if (preg_match('/^((?:L_)?PAYMENTREQUEST)_(\d)_([a-zA-Z]+?)(\d)?$/i',$key,$matches)) {
						
						@list($null,$type,$order,$prop,$item) = $matches;
						
						switch ($type) {
							case 'L_PAYMENTREQUEST':
								
								/*
									Item Value
								*/
								if (!isset($items[$order])) $items[$order] = array();
								if (!isset($items[$order][$item])) $items[$order][$item] = array();
								$items[$order][$item][$prop] = $value;
								
							break;
							case 'PAYMENTREQUEST':
								/*
									Order Value
								*/
								if (!isset($orders[$order])) $orders[$order] = array();
								$orders[$order][$prop] = $value;
							break;
						}
					}
				}
				
				/*
					Recreate the orders with items
				*/
				for ($i = 0; $i < count($orders); $i++) {
					/*
						Create Order 
					*/
					$order = $this->NewOrder();
					
					/*
						Apply order-specific properties
					*/
					foreach ($orders[$i] as $oProp => $oValue) {
						$order->setAttribute($oProp,$oValue);
					}
					
					/*
						Now items
					*/
					if (isset($items[$i])) {
						for ($x = 0; $x < count($items[$i]); $x++) {
							$item = $items[$i][$x];
							$new = $order->NewItem(0,0);
							
							foreach ($item as $k=>$v) {
								$new->setAttribute($k,$v);
							}
						}
					}
				}
			}
		}
	}
?>