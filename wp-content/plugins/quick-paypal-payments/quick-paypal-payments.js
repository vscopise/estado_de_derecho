function qppclear(thisfield, defaulttext) {if (thisfield.value == defaulttext) {thisfield.value = "";}}
function qpprecall(thisfield, defaulttext) {if (thisfield.value == "") {thisfield.value = defaulttext;}}

function handleValidationResponse(e,f) {
	fp = f.closest('.qpp-style');
	var element = '';
	if (typeof e === 'object') {
		if (e.responseJSON === undefined) {
			if (e.responseText === undefined) {
				/*
					Normalize so that it works even if the handling is wrong
				*/
				var obj = {'responseJSON':e}
				e = obj;
			} else {
				e.responseJSON = JSON.parse(e.responseText);
			}
		}
	} else {
		var obj = {'responseJSON':JSON.parse(e)};
		e = obj;
	}
	if (e.responseJSON) {
		data = e.responseJSON;
		if (data.errors.length) { // errors found
			/* Remove all prior errors */
			f.find('input[type=text]').css('border-color','');
			
			// Display error header
			// Check if header exists:
			if (!fp.find('.qpp-header').size()) {
				f.prepend("<h2 class='qpp-header' id='qpp_reload'></h2>")
			}
			if (!fp.find('.qpp-blurb').size()) {
				fp.find('.qpp-header').after("<p class='qpp-blurb'></p>");
			}
			fp.find('.qpp-header').css('color',data.error_color).html(data.display);
			fp.find('.qpp-blurb').css('color',data.error_color).html(data.blurb);
			
			
			for (i = 0; i < data.errors.length; i++) {
				error = data.errors[i];
				element = f.find('[name='+error.name+']');
				if (element.length) element.css({'border':'1px solid '+data.error_color});
			}
			
			/*
				Scroll To Top Of Form
			*/
			$('html, body').animate({
				scrollTop: Math.max(fp.offset().top - 100,0),
			}, 300);
		} else {
			/*
				Successful validation!
						
				Disable this callback and officially submit the form.
			*/
			if (data.hasOwnProperty('ic')) {
				
				/*
					return the data.ic object
				*/
				return data;
				
			} else {
				// Hide form, show loading screen
				f.hide();
				f.parent().find('.qpp-loading').show();
				
				/*
					Scroll To Top Of Form
				*/
				$('html, body').animate({
					scrollTop: Math.max(fp.offset().top - 100,0),
				}, 300);
				
				f.unbind('submit');
						
				// Invoke submit
				container = $('<div style="display: none;" class="hidden-form"></div>');
				container.append($(data.html).find('form'));
				f.append(container);

				f.find('.hidden-form form').submit();
			}
		}
	} else {
		// Catastrophic error... no handler for this.
	}
	
	return false;
}
function qpp_show_form(el) {
	if (el.size()) {
		$('html:not(:animated), body:not(:animated)').animate({
			scrollTop: el.offset().top - 155
		}, 800);
	}
}
function validateForm(ev) {
	var f = $(this);
	var c = f.find('input[clicked=true]');

	if (c.attr('id') == 'couponsubmit') { // check if clicked button is the coupon apply button
		// just submit form regularly
		return true;
	}
	
	// reset the buttons' clicked state
	f.find("input[type=image],input[type=submit]").removeAttr("clicked");

	// Intercept request and handle with AJAX
	var fd = $(this).serialize();
	fd += '&' + c.attr('name') + '=' + c.val() + '&action=qpp_validate_form';

	$.post(ajaxurl, fd,function(e) {
		handleValidationResponse(e,f)
	},'JSON');
	
	ev.preventDefault();
	return false;
}

jQuery(document).ready(function() {
	$ = jQuery;
	
	/*
		Scroll to .qpp-complete 
	*/
	qpp_show_form($('.qpp-complete'));
	
	/*
		Add in some catches to detect which button was clicked in a form!
	*/
	$(".qpp-style input[type=submit], .qpp-style input[type=image]").click(function() {
		$("input[type=submit]", $(this).parents("form")).removeAttr("clicked");
		$(this).attr("clicked", "true");
	});
	
	/*
		Fix all fields which have a value (Normally from reloads or back buttons)
	*/
	if (typeof qpp_ic !== 'undefined') {
		
		/*
			Set up Paypal
		
		*/

		buttons = [];
		if (qpp_containers.length > 0) {
			for (i in qpp_containers) {

				buttons.push({'button':qpp_containers[i],'click':function(event) {

					var x = paypal.checkout;
					event.preventDefault();
					/*
						Open modal
					*/
					paypal.checkout.initXO();
					
					/*
						Collect Important Data
					*/
					$target = event.target;
					c = $($target);
					$ = jQuery;
					form = c.closest('form');
					f = form.get(0);
					
					var k = form.closest('div');
					k.find('form').hide();
					k.find('.qpp-validating-form').show();
					
					var fd = $(form).serialize();
					fd += '&' + c.attr('name') + '=' + c.val() + '&action=qpp_validate_form';
					$.ajax({
						type:'POST',
						url:ajaxurl,
						data:fd,
						success:function(e) {
							var data = handleValidationResponse(e,form);
							
							if (data) {
								
								/*
									Call .startFlow()
								*/
								k.find('.qpp-validating-form').hide();
								k.find('.qpp-processing-form').show();
								
								paypal.checkout.startFlow(data.ic.token);
							} else {
								
								k.find('.qpp-validating-form, .qpp-processing-form').hide();
								if (e.success == false) {
									form.show();
								}
								
								paypal.checkout.closeFlow();
							}
						},
						error:function(e) {
							k.find('.qpp-validating-form, .qpp-processing-form').hide();
							form.show();
							paypal.checkout.closeFlow();
						},
						dataType:'JSON'
					});
				}});
			}
			paypal.checkout.setup(qpp_ic.id,{
				environment:qpp_ic.environment.toLowerCase(),
				buttons:buttons
			});
		}
		
		$('.qpp-style form').submit(function() { 
			var c = f.find('input[clicked=true]');

			if (c.attr('id') == 'couponsubmit') return true;
			
			return false;
		});
	} else {
		$('.qpp-style form').submit(validateForm);
	}
	
});

(function(factory) {
    'use strict';

    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], factory);
    }
    else if (typeof exports === 'object') {
        // CommonJS
        factory(require('jquery'));
    } else {
        // Browser globals
        factory(jQuery);
    }
}(function($) {
    'use strict';

    /**
     * Range feature detection
     * @return {Boolean}
     */
    function supportsRange() {
        var input = document.createElement('input');
        input.setAttribute('type', 'range');
        return input.type !== 'text';
    }

    var pluginName = 'rangeslider',
        pluginInstances = [],
        inputrange = supportsRange(),
        defaults = {
            polyfill: true,
            rangeClass: 'rangeslider',
            disabledClass: 'rangeslider--disabled',
            fillClass: 'rangeslider__fill',
            handleClass: 'rangeslider__handle',
            startEvent: ['mousedown', 'touchstart', 'pointerdown'],
            moveEvent: ['mousemove', 'touchmove', 'pointermove'],
            endEvent: ['mouseup', 'touchend', 'pointerup']
        };

    /**
     * Delays a function for the given number of milliseconds, and then calls
     * it with the arguments supplied.
     *
     * @param  {Function} fn   [description]
     * @param  {Number}   wait [description]
     * @return {Function}
     */
    function delay(fn, wait) {
        var args = Array.prototype.slice.call(arguments, 2);
        return setTimeout(function(){ return fn.apply(null, args); }, wait);
    }

    /**
     * Returns a debounced function that will make sure the given
     * function is not triggered too much.
     *
     * @param  {Function} fn Function to debounce.
     * @param  {Number}   debounceDuration OPTIONAL. The amount of time in milliseconds for which we will debounce the function. (defaults to 100ms)
     * @return {Function}
     */
    function debounce(fn, debounceDuration) {
        debounceDuration = debounceDuration || 100;
        return function() {
            if (!fn.debouncing) {
                var args = Array.prototype.slice.apply(arguments);
                fn.lastReturnVal = fn.apply(window, args);
                fn.debouncing = true;
            }
            clearTimeout(fn.debounceTimeout);
            fn.debounceTimeout = setTimeout(function(){
                fn.debouncing = false;
            }, debounceDuration);
            return fn.lastReturnVal;
        };
    }

    /**
     * Plugin
     * @param {String} element
     * @param {Object} options
     */
    function Plugin(element, options) {
        this.$window    = $(window);
        this.$document  = $(document);
        this.$element   = $(element);
        this.options    = $.extend( {}, defaults, options );
        this._defaults  = defaults;
        this._name      = pluginName;
        this.startEvent = this.options.startEvent.join('.' + pluginName + ' ') + '.' + pluginName;
        this.moveEvent  = this.options.moveEvent.join('.' + pluginName + ' ') + '.' + pluginName;
        this.endEvent   = this.options.endEvent.join('.' + pluginName + ' ') + '.' + pluginName;
        this.polyfill   = this.options.polyfill;
        this.onInit     = this.options.onInit;
        this.onSlide    = this.options.onSlide;
        this.onSlideEnd = this.options.onSlideEnd;

        // Plugin should only be used as a polyfill
        if (this.polyfill) {
            // Input range support?
            if (inputrange) { return false; }
        }

        this.identifier = 'js-' + pluginName + '-' +(+new Date());
        this.min        = parseFloat(this.$element[0].getAttribute('min') || 0);
        this.max        = parseFloat(this.$element[0].getAttribute('max') || 100);
        this.value      = parseFloat(this.$element[0].value || this.min + (this.max-this.min)/2);
        this.step       = parseFloat(this.$element[0].getAttribute('step') || 1);
        this.$fill      = $('<div class="' + this.options.fillClass + '" />');
        this.$handle    = $('<div class="' + this.options.handleClass + '" />');
        this.$range     = $('<div class="' + this.options.rangeClass + '" id="' + this.identifier + '" />').insertAfter(this.$element).prepend(this.$fill, this.$handle);

        // visually hide the input
        this.$element.css({
            'position': 'absolute',
            'width': '1px',
            'height': '1px',
            'overflow': 'hidden',
            'opacity': '0'
        });

        // Store context
        this.handleDown = $.proxy(this.handleDown, this);
        this.handleMove = $.proxy(this.handleMove, this);
        this.handleEnd  = $.proxy(this.handleEnd, this);

        this.init();

        // Attach Events
        var _this = this;
        this.$window.on('resize' + '.' + pluginName, debounce(function() {
            // Simulate resizeEnd event.
            delay(function() { _this.update(); }, 300);
        }, 20));

        this.$document.on(this.startEvent, '#' + this.identifier + ':not(.' + this.options.disabledClass + ')', this.handleDown);

        // Listen to programmatic value changes
        this.$element.on('change' + '.' + pluginName, function(e, data) {
            if (data && data.origin === pluginName) {
                return;
            }

            var value = e.target.value,
                pos = _this.getPositionFromValue(value);
            _this.setPosition(pos);
        });
    }

    Plugin.prototype.init = function() {
        if (this.onInit && typeof this.onInit === 'function') {
            this.onInit();
        }
        this.update();
    };

    Plugin.prototype.update = function() {
        this.handleWidth    = this.$handle[0].offsetWidth;
        this.rangeWidth     = this.$range[0].offsetWidth;
        this.maxHandleX     = this.rangeWidth - this.handleWidth;
        this.grabX          = this.handleWidth / 2;
        this.position       = this.getPositionFromValue(this.value);

        // Consider disabled state
        if (this.$element[0].disabled) {
            this.$range.addClass(this.options.disabledClass);
        } else {
            this.$range.removeClass(this.options.disabledClass);
        }

        this.setPosition(this.position);
    };

    Plugin.prototype.handleDown = function(e) {
        e.preventDefault();
        this.$document.on(this.moveEvent, this.handleMove);
        this.$document.on(this.endEvent, this.handleEnd);

        // If we click on the handle don't set the new position
        if ((' ' + e.target.className + ' ').replace(/[\n\t]/g, ' ').indexOf(this.options.handleClass) > -1) {
            return;
        }

        var posX = this.getRelativePosition(this.$range[0], e),
            handleX = this.getPositionFromNode(this.$handle[0]) - this.getPositionFromNode(this.$range[0]);

        this.setPosition(posX - this.grabX);

        if (posX >= handleX && posX < handleX + this.handleWidth) {
            this.grabX = posX - handleX;
        }
    };

    Plugin.prototype.handleMove = function(e) {
        e.preventDefault();
        var posX = this.getRelativePosition(this.$range[0], e);
        this.setPosition(posX - this.grabX);
    };

    Plugin.prototype.handleEnd = function(e) {
        e.preventDefault();
        this.$document.off(this.moveEvent, this.handleMove);
        this.$document.off(this.endEvent, this.handleEnd);

        if (this.onSlideEnd && typeof this.onSlideEnd === 'function') {
            this.onSlideEnd(this.position, this.value);
        }
    };

    Plugin.prototype.cap = function(pos, min, max) {
        if (pos < min) { return min; }
        if (pos > max) { return max; }
        return pos;
    };

    Plugin.prototype.setPosition = function(pos) {
        var value, left;

        // Snapping steps
        value = (this.getValueFromPosition(this.cap(pos, 0, this.maxHandleX)) / this.step) * this.step;
        left = this.getPositionFromValue(value);

        // Update ui
        this.$fill[0].style.width = (left + this.grabX)  + 'px';
        this.$handle[0].style.left = left + 'px';
        this.setValue(value);

        // Update globals
        this.position = left;
        this.value = value;

        if (this.onSlide && typeof this.onSlide === 'function') {
            this.onSlide(left, value);
        }
    };

    Plugin.prototype.getPositionFromNode = function(node) {
        var i = 0;
        while (node !== null) {
            i += node.offsetLeft;
            node = node.offsetParent;
        }
        return i;
    };

    Plugin.prototype.getRelativePosition = function(node, e) {
        return (e.pageX || e.originalEvent.clientX || e.originalEvent.touches[0].clientX || e.currentPoint.x) - this.getPositionFromNode(node);
    };

    Plugin.prototype.getPositionFromValue = function(value) {
        var percentage, pos;
        percentage = (value - this.min)/(this.max - this.min);
        pos = percentage * this.maxHandleX;
        return pos;
    };

    Plugin.prototype.getValueFromPosition = function(pos) {
        var percentage, value;
        percentage = ((pos) / (this.maxHandleX || 1));
        value = this.step * Math.ceil((((percentage) * (this.max - this.min)) + this.min) / this.step);
        return Number((value).toFixed(2));
    };

    Plugin.prototype.setValue = function(value) {
        if (value !== this.value) {
            this.$element.val(value).trigger('change', {origin: pluginName});
        }
    };

    Plugin.prototype.destroy = function() {
        this.$document.off(this.startEvent, '#' + this.identifier, this.handleDown);
        this.$element
            .off('.' + pluginName)
            .removeAttr('style')
            .removeData('plugin_' + pluginName);

        // Remove the generated markup
        if (this.$range && this.$range.length) {
            this.$range[0].parentNode.removeChild(this.$range[0]);
        }

        // Remove global events if there isn't any instance anymore.
        pluginInstances.splice(pluginInstances.indexOf(this.$element[0]),1);
        if (!pluginInstances.length) {
            this.$window.off('.' + pluginName);
        }
    };

    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn[pluginName] = function(options) {
        return this.each(function() {
            var $this = $(this),
                data  = $this.data('plugin_' + pluginName);

            // Create a new instance.
            if (!data) {
                $this.data('plugin_' + pluginName, (data = new Plugin(this, options)));
                pluginInstances.push(this);
            }

            // Make it possible to access methods from public.
            // e.g `$element.rangeslider('method');`
            if (typeof options === 'string') {
                data[options]();
            }
        });
    };

}));

(function ( $ ) {
	
	var commands = {'updateTotal':updateTotal,'getFields':getFields,'showOther':showOther,'hideOther':hideOther};
	var form = {}, fields = {};
	
	/*
		Function: Initialize
		@Description	The plugin psuedo-constructor
	*/
	function initialize() {
		var instance = $(this).attr('id');
		$(this).data('instance',instance);
		form[instance] = $(this);
		fields[instance] = {};
		// Set up price factoring elements
		form[instance].find('select, input').each(function() {
			// Give the parent form id as unique ID
			$(this).data("instance",instance);
			
			// if the input is a submit button, exit
			if ($(this).attr('type') == "submit") return; 
			
			// If the input is a radio or checkbox create an object for the group instead of the element
			if ($(this).attr('type') == "radio" || $(this).attr('type') == "checkbox") {
				// add click to updateTotal
				$(this).click(updateTotal);
				
				// check if this group exists already
				if (!fields[instance][this.name]) fields[instance][this.name] = form[instance].find("[name='"+this.name+"']");
				else return;
			} else {
				
				// add keyup to change TEXT elements
				if ($(this).attr('type') == 'text') $(this).keyup(updateTotal);
				else {
					// add change event to drop downs
					$(this).change(updateTotal);
				}
				
				// append field to the fields object.
				fields[instance][this.name] = $(this);
			}
		});
		
		/*
			Set up the 'otheramount' drop box
		*/
		var f = fields[instance];
		if (f.use_other_amount !== undefined) {
			
			/*
				Set up the onchange option
			*/
			if (f.amount.prop("tagName").toLowerCase() == 'select') {
				// Select Tag
				f.amount.change(function() {
					if (this.value == 'Other') {
						showOther.apply($(this).data('instance'));
					} else {
						hideOther.apply($(this).data('instance'));
					}
				});
				
				/*
					Has an other amount field
				*/
				if (f.use_other_amount == 'true' || f.amount.qpp_value() == 'Other') {
					showOther.apply($(this).data('instance'));
				} else {
					hideOther.apply($(this).data('instance'));
				}
				
			} else {
				// Radio
				f.amount.change(function() {
					if (this.value == 'otheramount') {
						f.use_other_amount.val('true');
						f.otheramount.focus();
						updateTotal.apply(f.otheramount.get(0));
					} else {
						f.otheramount.val('');
						f.otheramount.blur();
						f.use_other_amount.val('false');
						updateTotal.apply(f.otheramount.get(0));
					}
				});
				
				f.otheramount.focus(function(event) {
					// Create indicator
					if (f.amount.qpp_value() != 'otheramount') {
						/*
							Check the other radio button
						*/
						f.amount.prop('checked','false');
						f.amount.filter('[value=otheramount]').prop('checked','true');
						f.use_other_amount.val('true');
						updateTotal.apply(f.otheramount.get(0));
					}
				});
			}
		}
		
		// Fix slider              
		var $document = $(document), 
			$inputRange = form[instance].find('[data-rangeslider]');
		
		function valueOutput(element) {
			var value = element.value, output = element.parentNode.getElementsByTagName("output")[0];
			output.innerHTML = value;
		}
		for (var i = $inputRange.length - 1; i >= 0; i--) {
			valueOutput($inputRange[i]);
		};
		$inputRange.change(function(e) {
			valueOutput(e.target);
		});
		$inputRange.rangeslider({
			polyfill: false,
			onSlide: function(position, value) {
				updateTotal.apply($('#'+instance));
			}
		});
		
		// Update Amount
		updateTotal.apply(this);
	}
	
	/*
		@Method	formatMoney
		@Description	Formats numbers into money notation	
	*/
	function formatMoney(n, c, d, t){
		var c = isNaN(c = Math.abs(c)) ? 2 : c, 
			d = d == undefined ? "." : d, 
			t = t == undefined ? "," : t, 
			s = n < 0 ? "-" : "", 
			i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", 
			j = (j = i.length) > 3 ? j % 3 : 0;
		return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
	}
	
	/*
		@Method	updateTotal
		@Description	updates the total on live-updates
	*/
	function updateTotal() {

		var instance = $(this).data('instance');
		var f = fields[instance];
		f.items = [];
		
		if (f.multiples) {
			for (i = 0; i <= 9; i++) {
				if (f.hasOwnProperty('qtyproduct'+i)) {
					if (f['qtyproduct'+i].attr('type') == 'checkbox') {
						if (f['qtyproduct'+i].is(':checked')) f.items.push({'cost':parseFloat(f['product'+i].qpp_value()),'quantity':1});
					} else {
						f.items.push({'cost':parseFloat(f['product'+i].val()),'quantity':parseInt(f['qtyproduct'+i].val())||0});
					}
				}
			}
		}
		
		// Handle combined radio amounts
		if (f.combine !== undefined && f.combine.qpp_value() == 'checked') {
			// Amount if Combined
			var AMT = f.reference.qpp_value().split('&')[1].replace(/^\D+/g, "") || 0;

			// Set combined_radio_amount
			if (f.combined_radio_amount !== undefined) f.combined_radio_amount.val(AMT);
		} else {
			
			// Amount
			/*
				Handle Other Amount
			*/
			if (f.use_other_amount !== undefined) {
				if (f.use_other_amount.val() == 'true') {
					var AMT = f.otheramount.qpp_value();
				}
			}
			
			if (AMT === undefined) {
				if (!f.multiples.val()) {
					var AMT = f.amount.qpp_value();
				} else {
					var AMT = 0;
					for (i = 0; i < f.items.length; i++) {
						AMT += f.items[i].cost * f.items[i].quantity;
					}
					
					AMT = String(AMT);
				}
			}
			
			AMT = parseFloat(AMT.replace(/^\D+/g, "")) || 0;

		}
		
		// Quantity
		var QTY = 1;

		if (f.quantity) {
			QTY = parseInt(f.quantity.qpp_value()) || 1;
		}

		// Sub Total
		var SUB = QTY * AMT;
		
		if (f.multiples.qpp_value() == 1) {
			/*
				Apply the coupon code
			*/
			var c_v = 0;
			if (f.couponapplied.qpp_value() == 'checked') {
				c_v = f.couponvalue.qpp_value();
				if (f.coupontype.qpp_value() == 'percent') c_v = AMT * (f.couponvalue.qpp_value() * .01);
			}
			var PRED = SUB;
			SUB = SUB - c_v;
		}
		if (f.couponapplied.qpp_value() == 'checked' && f.multiples.qpp_value() != 1) {
			var PRED = QTY * f.itemamount.qpp_value();
		}
		var PROCESSING = 0, POSTAGE = 0;
		// Processing
		if (f.processing_type) {
			PROCESSING = parseFloat(f.processing.qpp_value().replace(/^\D/g, ""));
			if (f.processing_type.qpp_value() == 'percent') {
				if (f.couponapplied.qpp_value() == 'checked') {	
					PROCESSING = (.01 * PROCESSING) * PRED;
				}
				else PROCESSING = (.01 * PROCESSING) * SUB;
			}
		}
		
		
		// Postage
		if (f.postage_type) {
			POSTAGE = parseFloat(f.postage.qpp_value().replace(/^\D/g, ""));
			
			if (f.postage_type.qpp_value() == 'percent') {
				if (f.couponapplied.qpp_value() == 'checked') {
					POSTAGE = (.01 * POSTAGE) * PRED;
				}
				else POSTAGE = (.01 * POSTAGE) * SUB;
			}
		}

		var TOTAL = Number(POSTAGE) + Number(PROCESSING) + Number(SUB);

		f.total.val(formatMoney(TOTAL));
	}
	
	/*
		@Method	getFields
		@Description	Public method.
	*/
	function getFields() {
		return fields[this];
	}
	
	/*
		@Method showOther
		@Description	Shows the 'otheramount' input
	*/
	function showOther() {
		var f = fields[this];
		f.use_other_amount.val('true');
		f.otheramount.show();
	}
	
	/*
		@Method hideOther
		@Description	Hides the 'otheramount' input
	*/
	function hideOther() {
		var f = fields[this];
		f.use_other_amount.val('false');
		f.otheramount.hide();
		f.otheramount.val('').blur();
	}
	
	/*
		@Method	.fn.qpp
		@Description	Plugin definition
	*/ 	
	$.fn.qpp = function() {
		if (typeof arguments[0] === 'string') {
			
            var property = arguments[1];
			
            //remove the command name from the arguments
            var args = Array.prototype.slice.call(arguments);
            args.splice(0, 1);

            x = commands[arguments[0]].apply(this.data('instance'), args);
			return x; 
        }
        else {
			initialize.apply(this, arguments);
        }
		
		return $(this);
		
	}
	
	/*
		@Method	fn.qpp_value
		@Description	Gets the value of Text, Hidden, Radio, Checkbox, Select elements in 1 call.
	*/
	$.fn.qpp_value = function() {
		
		// get the value whether its a radio or text
		if ($(this).attr('type') == 'radio' || $(this).attr('type') == 'checkbox') {
			return $(this).filter(':checked,:selected').val();
		}

		return $(this).val();
		
	}
}( jQuery ));