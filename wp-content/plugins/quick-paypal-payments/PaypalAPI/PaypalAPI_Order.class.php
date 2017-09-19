<?php
	/**
		* Creates an Order object which manages Item objects
		*
		* @package		PaypalAPI
		* @author		Russell <RussellReal@gmail.com>
		* @license 		https://tldrlegal.com/license/mit-license MIT License 3.01
		* @version		Release: 1.0
		* @since		2016-05-21
	*/
	if (!class_exists('PaypalAPI_Order')) {
		class PaypalAPI_Order {
			
			protected $items, $fields, $attributes, $current_item;
			
			/**
				* Instantiates the PaypalAPI_Order object
				*
				* @return	Returns no data
			*/
			public function __construct() {
				$this->fields = array(
					'AMT' => 'float',
					'CURRENCYCODE' => 'string',
					'ITEMAMT' => 'float',
					'SHIPPINGAMT' => 'float',
					'INSURANCEAMT' => 'float',
					'SHIPDISCAMT' => 'float',
					'INSURANCEOPTIONOFFERED' => 'string',
					'HANDLINGAMT' => 'float',
					'TAXAMT' => 'float',
					'DESC' => 'string',
					'CUSTOM' => 'string',
					'INVNUM' => 'string',
					'NOTIFYURL' => 'string',
					'MULTISHIPPING' => 'string',
					'NOTETEXT' => 'string',
					'NOTETOBUYER' => 'string', // move to Main Class
					'TRANSACTIONID' => 'string',
					'PAYMENTACTION' => 'string',
					'PAYMENTREQUESTOD' => 'string',
					'BUCKETCATEGORYTYPE' => 'float',
					'LOCATION_TYPE' => 'float',
					'LOCATION_ID' => 'string',
					'PAYMENTREASON' => 'string',
					
					/*
						Address information
					*/
					'SHIPTONAME' => 'string',
					'SHIPTOSTREET' => 'string',
					'SHIPTOSTREET2' => 'string',
					'SHIPTOCITY' => 'string',
					'SHIPTOSTATE' => 'string',
					'SHIPTOZIP' => 'string',
					'SHIPTOCOUNTRYCODE' => 'string',
					'SHIPTOPHONENUM' => 'string',
					
					/*
						Seller Information
					*/
					'SELLERID' => 'string',
					'SELLERUSERNAME' => 'string',
					'SELLERREGISTRATIONDATE' => 'string'
				);
				
				$this->setAttribute('PAYMENTACTION','Sale');
			}
			
			/**
				* Sets an attribute for the PaypalAPI_Order object
				*
				* @param	String	$attribute	The name of the attribute to set 
				* @param	String	$value		The value of the attribute being set
				*
				* @return 	Bool	Returns true or false depending on success
			*/
			public function setAttribute($attribute, $value) {
				
				// Check if the attribute is a valid Item attribute
				if (array_key_exists($attribute,$this->fields)) {
					$et = $this->fields[$attribute];
					if ($et == gettype($value) || ($et == 'float' && is_numeric($value)) || ($et == 'int' && in_array(gettype($value),array('int','integer')))) {
						$this->attributes[$attribute] = $value;
						return true;
					}
				}
				
				return false;
				
			}
			
			/**
				* Gets an attribute from the PaypalAPI_Order object
				*
				* @param	String	$attr	The name of the attribute being fetched
				*
				* @return 	Mixed	Returns a String containing the attribute's value, false on failure
			*/
			public function a($attr) {
				if (isset($this->attributes[$attr])) return $this->attributes[$attr];
				return false;
			}
			
			/**
				* Gets an attribute from PaypalAPI_Order and fills it with it's default datatype if it is unset
				*
				* @param	String		$attr	The index of the attribute
				*
				* @return	Mixed		A guaranteed return value relative to it's datatype
				* 						False if attr is an invalid field
			*/
			public function f($attr) {
				if (isset($this->attributes[$attr])) return $this->attributes[$attr];
				if (isset($this->fields[$attr])) {
					switch ($this->fields[$attr]) {
						case 'int':
							return 0;
						case 'float':
							return 0;
						case 'string':
							return '';
						case 'bool':
							return 0;
					}
				}
				return false;
			}
			
			/**
				* Creates and sets a PaypalAPI_Item object as the current Item being edited
				*
				* @param	Float	$p		The price of the item being created
				* @param	Int		$q		The quantity of the item being sold
				*
				* @return	PaypalAPI_Item 	Returns the newly created PaypalAPI_Item object
			*/
			public function NewItem($p, $q) {
				if (count($this->items) >= 10) return false;
				
				$x = new PaypalAPI_Item();
				$x->setAttribute('AMT',$p);
				$x->setAttribute('QTY',$q);
				
				$this->current_item = $x;
				$this->items[] = $x;
				return $x;
			}
			
			/**
				* Dumps the contents of this order into proper NVP format for paypal
				*
				* @param	Int		$order	The index [0-9] of the current order
				*
				* @return	Returns a properly formatted order in NVP format
			*/
			public function dump($order) {
				
				$return = array();
				
				/*
					Start BASE amounts for adding to later on
				*/
				$base = $this->f('SHIPPINGAMT') + $this->f('INSURANCEAMOUNT') + $this->f('HANDLINGAMT') + $this->f('SHIPDISCAMT');
				$sub = $tax = $sub = 0;
				
				$showtax = false;
				
				/*
					Build Item NVP
				*/
				for ($i = 0; $i < count($this->items); $i++) {
					$return = array_merge($return,$this->items[$i]->dump($order,$i));
					$base += $this->items[$i]->total();
					$sub += $this->items[$i]->subtotal();
					$tax += $this->items[$i]->tax();
					
					/*
						Check if TAXAMT is set, if it is Order->TAXAMT is required
					*/
					if ($this->items[$i]->a('TAXAMT')) $showtax = true;
				}
				
				/*
					Apply all collected information
				*/
				if ($showtax) $this->setAttribute('TAXAMT',$tax);
				$this->setAttribute('ITEMAMT',$sub);
				$this->setAttribute('AMT',$base);
				
				/*
					Build Order NVP
				*/
				if (count($this->attributes)) {
					foreach ($this->attributes as $attribute => $value) {
						$return[sprintf('PAYMENTREQUEST_%d_%s',$order,$attribute)] = $value;
					}
				}
				return $return;
			}

		}
	}
?>