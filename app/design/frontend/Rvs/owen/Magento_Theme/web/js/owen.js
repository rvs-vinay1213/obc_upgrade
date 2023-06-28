require([ 'jquery'], function($) {
	'use strict';

    // setTimeout(function () {
    //     $(".payment-method").hide();
    //     $(".no-quotes-block").show();
    // }, 25000);

    // $('body').on('change', 'div[name="amcheckoutDelivery.time"]', function() {
    //     $(".no-quotes-block").hide();
    //     $('.payment-method .payment-method-title input.radio').prop('checked', false);
    //     if($(this).find(":selected").val() == -1) {
    //         $(".payment-method").hide();
    //         $(".no-quotes-block").show();
    //     }
    //     else if($(this).find(":selected").val() >= 7 && $(this).find(":selected").val() <= 14) {
    //         $(".payment-method").not("div:eq(0)").hide();
    //         $(".payment-method").not("div:eq(1)").show();
    //     }
    //     else {
    //         $(".payment-method").not("div:eq(1)").hide();
    //         $(".payment-method").not("div:eq(0)").show();
    //     }
    // });

    $(document).ready(function(){
        // console.log(".block-minicart .action.close");
		setTimeout(function(){
			if($('#worldpayform').is(':checked'))
			{
				$(".amasty span").text("CONTINUE TO SECURE PAYMENT GATEWAY");
			}
			}, 5000);
    });
	

    $('.block-minicart').on('click', '#btn-minicart-close', function (event) {
		$(".block-minicart").parent().hide();
    });

    /*$('.checkout-index-index .columns').on('keyup', 'textarea', function (event) {
        var regex = new RegExp("^[a-zA-Z0-9.,/ $@()]+$");
        var key = String.fromCharCode(!event.charCode ? event.which : event.charCode);
        if (!regex.test(key) && event.which != 8 && event.which != 16 && event.which != 17 && event.which != 18 && event.which != 20 || regex.test(this.value) == false) {
            $('.comment-error').show();
        }
        else {
            $('.comment-error').hide();
        }
        if(event.which == 8 && this.value.length == 0) {
            $('.comment-error').hide();
        }
    });*/

    $(document).mouseup(function(e) {
    	var container = $(".block-minicart");
		if(!container.is(e.target)&&container.has(e.target).length === 0) {
			container.parent().hide();
		}
    });
	$('body').on('click','#worldpayform',function () {
			$(".amasty span").text("CONTINUE TO SECURE PAYMENT GATEWAY");
		});
});