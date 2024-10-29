jQuery( function( $ ) {
    'use strict';


    let initAsiabillPaymentSdk =  {

        is_init : false,
        form : null,
        checkPage : wc_asiabill_params.checkoutPayPage,

        init : function () {
            this.form =  $( 'form.woocommerce-checkout' );
            if( !this.form.length ){
                this.form = $( '#order_review' );
            }

            if( !this.form.length ){
                console.log(this.form);
                initAsiabillPaymentSdk.errorMessage('The form undefined')
                return false;
            }

            if( this.checkPage === 'no' ){
                initAsiabillPaymentSdk.createElements();
            }
            $( document.body ).on( 'updated_checkout', function() {
                initAsiabillPaymentSdk.createElements();
            });
        },

        createElements: function () {
            let btn = $('#place_order,.vi-wcaio-sidebar-cart-bt-checkout-place_order');
            btn.on('click',function () {
                return initAsiabillPaymentSdk.checkoutOrder();
            });
        },

        checkoutOrder : function () {

            if( this.checkPage === 'no' ){
                var billing = wc_asiabill_params.billing;
            }else {

                var billing = {
                    "address": {
                        "city": $( '#billing_city' ).val(),
                        "country": $( '#billing_country' ).val(),
                        "line1": $( '#billing_address_1' ).val(),
                        "line2": $( '#billing_address_2' ).val(),
                        "postalCode": $( '#billing_postcode' ).val(),
                        "state": $( '#billing_state' ).val()
                    },
                    "email": $( '#billing_email' ).val(),
                    "firstName": $( '#billing_first_name' ).val(),
                    "lastName": $( '#billing_last_name' ).val() ,
                    "phone": $( '#billing_phone' ).val()
                };
            }

            let paymentMethodObj = {
                "billingDetail": billing,
                "card": {
                    "cardNo": "",
                    "cardExpireMonth": "",
                    "cardExpireYear": "",
                    "cardSecurityCode": "",
                    "issuingBank": ""
                },
                "customerId": '',
            };

            initAsiabillPaymentSdk.errorMessage('');

            if($('#payment_method_wc_asiabill_creditcard').is( ':checked' )){

                let token = $('input[name=wc-wc_asiabill_creditcard-payment-token]:checked').val();

                if( token != undefined && token!== 'new' ){
                    // 使用token支付
                    return true
                }
                else {

                    // 使用卡支付
                    $( '.asiabill-payment' ).remove();

                    document.getElementById('wp-ab-iframe').contentWindow.postMessage(paymentMethodObj,'http://192.168.2.12')

                    return false;

                }

            }
        },

        complete: function (data){

            console.log(data);

            if( data.code === "0" ){
                // 保存成功
                initAsiabillPaymentSdk.form.append(
                    $( '<input type="hidden" />' )
                        .addClass( 'asiabill-payment' )
                        .attr( 'name', 'asiabill_payment' )
                        .val( data.data.customerPaymentMethodId )
                );
                initAsiabillPaymentSdk.form.append(
                    $( '<input type="hidden" />' )
                        .addClass( 'asiabill-payment' )
                        .attr( 'name', 'asiabill_check_page' )
                        .val( initAsiabillPaymentSdk.checkPage )
                );
                initAsiabillPaymentSdk.form.trigger( 'submit' );
            }
            else {
                // 保存失败
                initAsiabillPaymentSdk.errorMessage(result.data.message);
            }
        },

        errorMessage : function (message = ''){
            if( message.trim() !== '' ){
                $( document.body ).trigger( 'checkout_error',message );
                $('#asiabill-card-error').html(message).removeClass('hide');
            }else{
                $('#asiabill-card-error').html('').addClass('hide');
            }
        }

    };

    window.addEventListener("message",(event) => {

        console.log(event.origin);
        if( event.origin !== 'http://192.168.2.12' ){
            return;
        }
        initAsiabillPaymentSdk.complete(event.data);
    })

    window.addEventListener("getErrorMessage", e => {
        initAsiabillPaymentSdk.errorMessage(e.detail.errorMessage);
    });

    initAsiabillPaymentSdk.init();


});












