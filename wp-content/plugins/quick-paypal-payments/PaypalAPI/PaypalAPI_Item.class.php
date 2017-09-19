<?php
	/**
		* Creates an Item object which belongs to an Order object
		*
		* @package		PaypalAPI
		* @author		Russell <RussellReal@gmail.com>
		* @license 		https://tldrlegal.com/license/mit-license MIT License 3.01
		* @version		Release: 1.0
		* @since		2016-05-21
	*/
	if (!class_exists('PaypalAPI_Item')) {
		class PaypalAPI_Item {
			
			protected $id, $attributes, $fields;
			
			/**
				* Initializes an empty PaypalAPI_Item object
				*
				* @return 	Bool 		No return data
			*/ 
			public function __construct() {
				$this->fields = array(
					'NAME' => "string",
					'DESC' => "string",
					'AMT' => "float",
					'NUMBER' => "string",
					'QTY' => "float",
					'TAXAMT' => "float",
					
					'ITEMWEIGHTVALUE' => "float",
					'ITEMWEIGHTUNIT' => "string",
					
					'ITEMLENGTHVALUE' => "float",
					'ITEMLENGTHUNIT' => "string",
					
					'ITEMWIDTHVALUE' => "float",
					'ITEMWIDTHUNIT' => "string",
					
					'ITEMHEIGHTVALUE' => "float",
					'ITEMHEIGHTUNIT' => "string",
					
					'ITEMURL' => "string",
					'ITEMCATEGORY' => "string"
				); 
				
			}
			
			/**
				* Sets attributes for the PaypalAPI_Item object
				*
				* @param 	String 		$attribute	The index/key of the attribute
				* @param 	String 		$value		The value of the attribute
				* 
				* @return 	Bool 		Returns true or false if method exists
			*/ 
			public function setAttribute($attribute, $value) {
				
				// Check if the attribute is a valid Item attribute
				if (array_key_exists($attribute,$this->fields)) {
					$et = $this->fields[$attribute];
					if ($et == gettype($value) || ($et == 'float' && is_numeric($value)) || ($et == 'int' && in_array(gettype($value),array('int','integer')))) {
						settype($value,$et);
						$this->attributes[$attribute] = $value;
						return true;
					}
				}
				
				return false;
				
			}
			
			/**
				* Gets the tax on the Item
				*
				* @return	Float		The total tax on this item
			*/
			public function tax() {
				return $this->f('TAXAMT') * $this->f('QTY');
			}
			
			/**
				
			/**
				* Gets the total cost of this item with tax included
				* 
				* @return 	Float 		The total cost of the item
			*/ 
			public function total() {
				return (float) ($this->f('TAXAMT') * $this->f('QTY')) + ($this->f('AMT') * $this->f('QTY'));
			}
			
			/**
				* Gets the cost of this item without tax included
				* 
				* @return 	Float 		The subtotal of the item
			*/ 
			public function subtotal() {
				return (float) ($this->f('AMT') * $this->f('QTY'));
			}
			
			/**
				* Gets an attribute from the PaypalAPI_Item class
				*
				* @param 	String 		$attr	The index/key of the attribute
				* 
				* @return 	Bool 		Returns true or false if method exists
			*/ 
			public function a($attr) {
				if (isset($this->attributes[$attr])) return $this->attributes[$attr];
				return false;
			}
			
			/**
				* Gets an attribute from PaypalAPI_Item and fills it with it's default datatype if it is unset
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
				* Generates Name-Value-Pairs for the given item's attributes
				*
				* @param 	Int 		$order	The order number [0-9]
				* @param 	Int 		$item	The item number [0-9]
				* 
				* @return 	Array 		Returns an array of Name Value Pairs
			*/ 
			public function dump($order,$item) {

				/*
					Build Item NVP
				*/
				$return = array();
				if (count($this->attributes)) {
					foreach ($this->attributes as $attribute => $value) {
						$return[sprintf('L_PAYMENTREQUEST_%d_%s%d',$order,$attribute,$item)] = $value;
					}
				}
				return $return;
			}

		}
	
	}
?>