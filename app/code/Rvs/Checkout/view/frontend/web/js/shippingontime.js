define([
        'jquery',
        'Magento_Checkout/js/model/quote',
		'Magento_Customer/js/customer-data',
		'Magento_Checkout/js/checkout-data'
],function ($,quote,customerData,checkoutData) {
    'use strict';
	var shippingAddressData = checkoutData.getSelectedShippingRate();
			if(shippingAddressData!= null){
		 checkoutData.setSelectedShippingRate(null);
			}

	$('.checkout-payment-method.submit').hide(); // CSTM CODE TO SHOW/HIDE ORDER PLACE BUTTON

	//$(".form.methods-shipping table").hide();
	 /*setTimeout(function () {
		$(".form.methods-shipping table").hide();
		$(".form.methods-shipping").append("<div class='shipping-no-block'>Sorry, No shipping methods available. All shipping methods availble on Delivery Time Iterval.</div>");
		if (quote.shippingMethod() != null) {
		  	quote.shippingMethod(null);
		}
	}, 20000); */
	// $.each(window.checkoutConfig.quoteData.initPaymentMethods.payment_methods, function(ind,val){
	    // if(val.method == 'magenest_stripe_paymentintents'){
			$('body').one('DOMNodeInserted','.step-content.amcheckout-content .amcheckout-wrapper .payment-method .payment-method-title label.label[for="paypal_express"]', function() {	
		    	setTimeout(function(){
		    		var a = $('.step-content.amcheckout-content .amcheckout-wrapper .payment-method .payment-method-title label.label');
					var arr = [];
					$.each(a,function(ind,val){
					    arr.push($(val).attr('for'));
					});

					if($.inArray("magenest_stripe_paymentintents", arr) !== -1){
					    console.log('001 exists ===');
					} else {
					    console.log('001 not exists ===');
					    window.location.reload();
					}
		    	},1000);
		    });	 
		// }
   // });	 
    $('body').one('DOMNodeInserted','.additional-options', function() {
		 $("._has-datepicker").val("");
		 $("input[name='date']").val('');
		 $("div[name='amcheckoutDelivery.date'] input").val("");
		 $("div[name='amcheckoutDelivery.time'] select option:eq(0)").prop("selected", true);
        $(".additional-options").insertAfter(".amcheckout-delivery-date");		
		$(".amcheckout-comment .control textarea").attr('placeholder','Please write here anything that we need to know regarding your order');
		setTimeout(function(){ 
			$("._has-datepicker").val("");
			$("input[name='date']").val('');
			$("div[name='amcheckoutDelivery.date'] input").val("");
			$("div[name='amcheckoutDelivery.time'] select option:eq(0)").prop("selected", true);
		$(".amcheckout-comment label span").text("Order Instructions");
		$(".amcheckout-comment .control textarea").attr('placeholder','Please write here anything that we need to know regarding your order');
         }, 2000);
    });
    $('body').one('DOMNodeInserted','.form.methods-shipping table', function() {
		$("._has-datepicker").val("");
		$("input[name='date']").val('');
		$("div[name='amcheckoutDelivery.date'] input").val("");
		$("div[name='amcheckoutDelivery.time'] select option:eq(0)").prop("selected", true);
		$(".comment label span").text("Order Instructions");
		setTimeout(function(){ 
		$("._has-datepicker").val("");
		$("input[name='date']").val('');
		$(".comment label span").text("Order Instructions");
		$(".comment .control textarea").attr('placeholder','Please write here anything that we need to know regarding your order');
         }, 2000);
        $(".form.methods-shipping table.amcheckout-shipping-methods").hide();
		$("input").val("");
		  $(".shipping-no-block").hide();
		$("div[name='amcheckoutDelivery.date'] input").val("");
		$("div[name='amcheckoutDelivery.time'] select option:eq(0)").prop("selected", true);
		if($(".shipping-no-block").length < 1) {
            $(".form.methods-shipping").append("<div class='shipping-no-block'>Sorry, No delivery methods available. All delivery methods availble on Delivery Time.</div>");
        }
        else {
            $(".shipping-no-block").show();
        }
    });

    $('body').one('DOMNodeInserted','.form.methods-shipping table', function() {
        updateShipping();
    });

    if (quote.shippingMethod() != null) {
        quote.shippingMethod(null);
    }
	$('body').on('change', 'div[name="amcheckoutDelivery.date"]', function() {
		$("div[name='amcheckoutDelivery.time'] select option:eq(0)").prop("selected", true);
		$(".form.methods-shipping table.amcheckout-shipping-methods").hide();
		 $(".shipping-no-block").show();
		var shippingAddressData = checkoutData.getSelectedShippingRate();
		if(shippingAddressData!= null){
		 	checkoutData.setSelectedShippingRate(null);
		}

		if($("div[name='amcheckoutDelivery.time'] select option:selected").val() > 0){ // CSTM CODE TO SHOW/HIDE ORDER PLACE BUTTON
			$('.checkout-payment-method.submit').show();
		} else {
			$('.checkout-payment-method.submit').hide();
		}
	});
	$('body').on('change', 'div[name="amcheckoutDelivery.time"]', function() {

		if($("div[name='amcheckoutDelivery.time'] select option:selected").val() > 0 && $("div[name='amcheckoutDelivery.date'] input").val()){ // CSTM CODE TO SHOW/HIDE ORDER PLACE BUTTON
			$('.checkout-payment-method.submit').show();
		} else {
			$('.checkout-payment-method.submit').hide();
		}
		
		var obj = $(JSON.parse(localStorage['mage-cache-storage']));	
		var groupId = obj[0]['customer']['groupId'];
		
		var origin   = window.location.origin;
		if(origin == 'https://www.owensisters.com' || origin == 'http://www.owensisters.com'){			
			$(".form.methods-shipping table").show();			
			$("#s_method_freeshipping").click();
			 $(".shipping-no-block").hide();
			 $('.hours-error').hide();
		}
		else{
		var str = $("._has-datepicker").val();
		console.log(str);
		var weekday = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
		var a = new Date(str);
		var dayname = weekday[a.getDay()];
		
		if (quote.shippingMethod() != null) {
		  	quote.shippingMethod(null);
		}
  		$('.form.methods-shipping input.radio').prop('checked', false);
  		$(".shipping-no-block").hide();
  		$(".form.methods-shipping table").show();
		if(dayname == "Sunday" || dayname == "Saturday")
			{
			console.log('groupId: '+groupId);
				if(groupId == '16' || groupId == '29'){
					$('.form.methods-shipping table').find('tbody tr:first').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(2)').hide();	
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(3)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(4)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(5)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(6)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(7)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(8)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(9)').show();
					$('.form.methods-shipping table').find('tbody tr:last').hide();				
				//	$("#s_method_flatrateseven_flatrate").show();
					$("#s_method_flatrateseven_flatrate").click();					
				}
				else if(groupId == '23' || groupId == '21' || groupId == '17' || groupId == '20' || groupId == '18' || groupId == '26' || groupId == '24'){
				
					$('.form.methods-shipping table').find('tbody tr:first').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(2)').hide();	
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(3)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(4)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(5)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(6)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(7)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(8)').show();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(9)').hide();
					$('.form.methods-shipping table').find('tbody tr:last').hide();				
					//$("#s_method_flatratesix_flatrate").show();
					$("#s_method_flatratesix_flatrate").click();					
				}
				else{
					$('.form.methods-shipping table').find('tbody tr:first').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(2)').hide();						
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(3)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(5)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(4)').show();					
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(6)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(7)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(8)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(9)').hide();
					$('.form.methods-shipping table').find('tbody tr:last').hide();				
				//	$("#s_method_flatratetwo_flatrate").show();
					$("#s_method_flatratetwo_flatrate").click();
				}
			}
		else
			{	
				console.log('groupId: '+groupId);
				if(groupId == '16'  || groupId == '29'){
					if($(this).find(":selected").val() == -1) {
						$(".form.methods-shipping table").hide();
						$(".shipping-no-block").show();
						$('.hours-error').hide();
					}
					else if($(this).find(":selected").val() >= 6 && $(this).find(":selected").val() < 14) {
						$('.form.methods-shipping table').find('tbody tr:first').hide();
						$('.form.methods-shipping table').find('tbody tr:last').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(3)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(4)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(5)').hide();							
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(6)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(7)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(8)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(9)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(2)').show();	
						$("#s_method_freeshipping_freeshipping").click();
						$('.hours-error').hide();
					}
					else {
						$('.form.methods-shipping table').find('tbody tr:first').hide();
						$('.form.methods-shipping table').find('tbody tr:last').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(3)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(4)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(2)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(5)').show();	
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(7)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(8)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(9)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(6)').hide();
						$("#s_method_flatratethree_flatrate").click();
					if($(".hours-error").length < 1)
						{
							$(".amcheckout-delivery-date").append("<div class='hours-error field-error'>If you require delivery before 6am or after 2 pm then we charge £15 + VAT. This will be automatically added to your order. Be sure to check your delivery time carefully as you go through Checkout.</div>");
						}
					$('.hours-error').show();
					}
				}
				else if(groupId == '28'){
					if($(this).find(":selected").val() == -1) {
						$(".form.methods-shipping table").hide();
						$(".shipping-no-block").show();
						$('.hours-error').hide();
					}
					else {
						$('.form.methods-shipping table').find('tbody tr:first').hide();
						$('.form.methods-shipping table').find('tbody tr:last').show();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(3)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(4)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(2)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(5)').hide();	
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(7)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(8)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(6)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(9)').hide();
						$("#s_method_flatrateeight_flatrate").click();
					
					}
				}
				else if(groupId == '23' || groupId == '21' || groupId == '17' || groupId == '20' || groupId == '18' || groupId == '26'){
					if($(this).find(":selected").val() == -1) {
						$(".form.methods-shipping table").hide();
						$(".shipping-no-block").show();
						$('.hours-error').hide();
					}
					else if($(this).find(":selected").val() >= 6 && $(this).find(":selected").val() < 14) {
						$('.form.methods-shipping table').find('tbody tr:first').hide();
						$('.form.methods-shipping table').find('tbody tr:last').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(3)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(4)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(5)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(2)').hide();							
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(6)').show();						
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(7)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(8)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(9)').hide();
						$("#s_method_flatratefour_flatrate").click();
						$('.hours-error').hide();
					}
					else {
					$('.form.methods-shipping table').find('tbody tr:first').hide();
						$('.form.methods-shipping table').find('tbody tr:last').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(3)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(4)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(2)').hide();						
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(5)').show();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(6)').hide();						
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(7)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(8)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(9)').hide();
					$("#s_method_flatratethree_flatrate").click();
					if($(".hours-error").length < 1)
						{
							$(".amcheckout-delivery-date").append("<div class='hours-error field-error'>If you require delivery before 6am or after 2 pm then we charge £15 + VAT. This will be automatically added to your order. Be sure to check your delivery time carefully as you go through Checkout.</div>");
						}
					$('.hours-error').show();
					}
				}
				else if(groupId == '24' ){
					if($(this).find(":selected").val() == -1) {
						$(".form.methods-shipping table").hide();
						$(".shipping-no-block").show();
						$('.hours-error').hide();
					}
					else if($(this).find(":selected").val() >= 6 && $(this).find(":selected").val() < 14) {
						$('.form.methods-shipping table').find('tbody tr:first').hide();
						$('.form.methods-shipping table').find('tbody tr:last').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(3)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(4)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(5)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(2)').hide();							
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(6)').hide();						
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(7)').show();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(8)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(9)').hide();
						$("#s_method_flatratefive_flatrate").click();
						$('.hours-error').hide();
					}
					else {
					$('.form.methods-shipping table').find('tbody tr:first').hide();
						$('.form.methods-shipping table').find('tbody tr:last').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(3)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(4)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(2)').hide();						
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(5)').show();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(6)').hide();						
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(7)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(8)').hide();
						$('.form.methods-shipping table').find('tbody tr:nth-of-type(9)').hide();
					$("#s_method_flatratethree_flatrate").click();
					if($(".hours-error").length < 1)
						{
							$(".amcheckout-delivery-date").append("<div class='hours-error field-error'>If you require delivery before 6am or after 2 pm then we charge £15 + VAT. This will be automatically added to your order. Be sure to check your delivery time carefully as you go through Checkout.</div>");
						}
					$('.hours-error').show();
					}
				}
				else{
				if($(this).find(":selected").val() == -1) {
					$(".form.methods-shipping table").hide();
					$(".shipping-no-block").show();
					$('.hours-error').hide();
				}
				else if($(this).find(":selected").val() >= 7 && $(this).find(":selected").val() < 14) {
					$('.form.methods-shipping table').find('tbody tr:first').hide();
					$('.form.methods-shipping table').find('tbody tr:last').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(3)').show();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(2)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(4)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(5)').hide();					
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(6)').hide();					
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(7)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(8)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(9)').hide();
					$("#s_method_flatrateone_flatrate").click();
					$('.hours-error').hide();
				}
				else {
					$('.form.methods-shipping table').find('tbody tr:last').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(2)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(3)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(4)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(5)').hide();					
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(6)').hide();					
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(7)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(8)').hide();
					$('.form.methods-shipping table').find('tbody tr:nth-of-type(9)').hide();
					$('.form.methods-shipping table').find('tbody tr:first').show();
					$("#s_method_flatrate_flatrate").click();
					if($(".hours-error").length < 1)
						{
							$(".amcheckout-delivery-date").append("<div class='hours-error field-error'>If you require delivery before 7am or after 2 pm then we charge £25 + VAT. This will be automatically added to your order. Be sure to check your delivery time carefully as you go through Checkout.</div>");
						}
					$('.hours-error').show();
				}
				}
			}
		}
	});
	/*$('body').on('change', 'div[name="amcheckoutDelivery.date"]', function() {
		var str = $("._has-datepicker").val();
		//console.log(str);
		var weekday = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
		var a = new Date(str);
		var dayname = weekday[a.getDay()];
		console.log(dayname);		
	});*/
    $('body').on('change', 'select[name="country_id"]', function() {
        setTimeout(function () {
            quote.shippingMethod(null);
        }, 4000);
    });

    function updateShipping()
    {
		$(".field.datepickertimemy .control > select").val($(".field.datepickertimemy .control > select option:first").val());
        // if($('div[name="amcheckoutDelivery.time"] :selected').val() == -1) {
            // $(".form.methods-shipping table").hide();
            // $(".shipping-no-block").show();
        // }
        // else if($('div[name="amcheckoutDelivery.time"] :selected').val() >= 7 && $('div[name="amcheckoutDelivery.time"] :selected').val() <= 14) {
            // $('.form.methods-shipping table').find('tbody tr:first').hide();
            // $('.form.methods-shipping table').find('tbody tr:last').show();
        // }
        // else {
            // $('.form.methods-shipping table').find('tbody tr:last').hide();
            // $('.form.methods-shipping table').find('tbody tr:first').show();
        // }
    }
});
