define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Ui/js/model/messages',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'mage/cookies',
        'mage/translate',
        'Magento_Checkout/js/action/set-payment-information',
    ],
    function (  Component, $, ko, quote, customer, fullScreenLoader, redirectOnSuccessAction, messageContainer, additionalValidators, url, setPaymentInformationAction,
    ) {
        'use strict';
        var stripe, elements, cardElement, errorMessage;
        var dataSecret =  '';

        return Component.extend({
            defaults: {
                template: 'Magenest_StripePayment/payment/stripe-payments-intents',
                classCardComplete: 'form-control StripeElement StripeElement--complete',
                authentication_failure: 'payment_intent_authentication_failure',
                source_id: "",
                redirectAfterPlaceOrder: true,
                isLogged: window.checkoutConfig.payment.magenest_stripe_config.isLogin,
                saveCardConfig: window.checkoutConfig.payment.magenest_stripe_intents.isSave,
                customerCard: ko.observableArray(JSON.parse(window.checkoutConfig.payment.magenest_stripe_intents.saveCards)),
                cardAllowed: ko.observableArray(window.checkoutConfig.payment.magenest_stripe_intents.card_type_allowed),
                cardTypeValidate: ko.observable(false),
                cardTypeOther: "OT",
                hasCard: window.checkoutConfig.payment.magenest_stripe_intents.hasCard,
                saveCardOption: "",
                cardId: ko.observable(0),
                showPaymentField: ko.observable(false),
                isSelectCard: ko.observable(false),
            },

            initObservable: function () {
                var self = this;
                this._super();
                this.isSelectCard = ko.computed(function () {
                    if (self.cardId() && self.hasCard){
                        return true;
                    }else{
                        return false;
                    }
                }, this);
                this.showPaymentField = ko.computed(function () {
                    if((!this.saveCardConfig) || !this.isSelectCard()){
                        return true;
                    }
                }, this);
                return this;
            },

            getCode: function() {
                return 'magenest_stripe_intents';
            },

            isActive: function() {
                return true;
            },

            initStripePaymentIntents: function(){
                if (this.validate()) {
                    var self = this;
                    stripe = Stripe(window.checkoutConfig.payment.magenest_stripe_config.publishableKey);
                    var style = {
                        base: {
                            iconColor: '#666ee8',
                            color: '#31325f',
                            fontWeight: 400,
                            fontFamily:
                                '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
                            fontSmoothing: 'antialiased',
                            fontSize: '16px',
                            '::placeholder': {
                                color: '#aab7c4',
                            },
                            ':-webkit-autofill': {
                                color: '#666ee8',
                            },
                        },
                    };
                    elements = stripe.elements();
                    cardElement = elements.create('card', {
                        style: style,
                        hidePostalCode: true,
                    });
                    cardElement.mount('#' + self.getCode() + '-card-element');

                    cardElement.addEventListener('change', function (event) {
                        var displayError = document.getElementById(self.getCode() + '-card-errors');
                        document.getElementById(self.getCode() + '-card-element').style.removeProperty('border-color');
                        self.cardCondition = event.complete;
                        if (event.error) {
                            displayError.textContent = event.error.message;
                        } else {
                            var cardsType = ["amex", "visa", "mastercard", "discover"];
                            if (event.brand && (cardsType.includes(event.brand) && !self.cardAllowed().includes(event.brand))
                                || (!cardsType.includes(event.brand)  && !self.cardAllowed().includes(self.cardTypeOther))) {
                                displayError.textContent = $.mage.__("Card type not support.");
                                self.cardTypeValidate(false);
                            } else {
                                displayError.textContent = '';
                                self.cardTypeValidate(true);
                            }
                        }
                    });
                }
            },

            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self  = this;
                var address = quote.billingAddress();
                var firstName = quote.billingAddress().firstname;
                var lastName = quote.billingAddress().lastname;
                var ownerInfo = {
                    owner: {
                        name: firstName +" "+ lastName,
                        address: {
                            line1: address.street[0],
                            line2: address.street[1],
                            city: address.city,
                            postal_code: address.postcode,
                            country: address.countryId,
                            state: address.region
                        },
                        email: (!customer.customerData.email)?quote.guestEmail:customer.customerData.email
                    }
                };
                if (address.telephone) {
                    ownerInfo.owner.phone = address.telephone;
                }

                if (this.validate() &&
                    additionalValidators.validate() && (self.cardTypeValidate() || self.cardId())
                ) {
                        this.isPlaceOrderActionAllowed(false);
                        var checkDisplay = document.getElementById('stripe-itents-intray').style.display;

                        var cardElement = document.getElementById(self.getCode() + '-card-element');
                        var checkCardEmpty = cardElement.className;
                        var displayError = document.getElementById(self.getCode() + '-card-errors');
                        if(checkCardEmpty.substr(-5) === 'empty' || checkCardEmpty.substr(-13) === 'StripeElement') {
                            displayError.textContent = 'Your information is incomplete.';
                            cardElement.style.borderColor = '#fa755a';
                        }

                            if (self.cardCondition === true || checkDisplay === 'none') {
                                this.getPlaceOrderDeferredObject()
                                    .done(
                                        function () {
                                            self.afterPlaceOrder();
                                        }
                                    ).fail(
                                    function () {
                                        location.reload();
                                    }
                                    ).always(
                                    function () {
                                        self.isPlaceOrderActionAllowed(true);
                                    }
                                );
                             }
                        return true;
                }
                return false;
            },

            afterPlaceOrder: function () {
                var self = this;
                fullScreenLoader.startLoader();
                $.post(
                    url.build("stripe/checkout_intents/redirect"),
                    {
                        form_key: $.cookie('form_key')
                    },
                    function (response) {
                        if (response.success) {
                            if(response.client_secret){
                                dataSecret = response.client_secret;
                                var cardName;
                                cardName = response.card_name;
                                self.card_id = response.card_id;
                                stripe.confirmCardPayment(
                                    dataSecret, {
                                        payment_method: {
                                            card: cardElement,
                                            billing_details: {
                                                name: cardName
                                            }
                                        },
                                        setup_future_usage: 'off_session'
                                    }).then(function (result) {
                                    if (result.paymentIntent){
                                        self.source = result.paymentIntent;
                                        if (response.save_option) {
                                            self.saveCard(self.source);
                                        }
                                        self.handleCaptureAndAuthorize();
                                    } else if (result.error) {
                                        if (result.error.code === 'card_declined' ||
                                            result.error.code === self.authentication_failure){
                                            self.handleCaptureAndAuthorize(true);
                                        }
                                    } else {
                                        if (self.card_id) {
                                            self.handleCaptureAndAuthorize();
                                        } else if (!self.card_id) {
                                            var errorElement = document.getElementById('intray-show-error');
                                            errorElement.className += "message message-warning warning";
                                            fullScreenLoader.stopLoader();
                                            errorElement.textContent = result.error.message ? result.error.message : "Sorry, but something went wrong. Please, reload the page.";
                                        }
                                    }
                                });
                            }else{
                                self.messageContainer.addErrorMessage({
                                    message: "Intents create error"
                                });
                            }
                        }
                        if (response.error){
                            self.isPlaceOrderActionAllowed(true);
                            self.messageContainer.addErrorMessage({
                                message: response.message
                            });
                        }
                    },
                    "json"
                );
            },

            saveCard: function (paymentIntent) {
                console.log(paymentIntent);
                var self = this;
                $.post(
                    url.build("stripe/checkout_intents/savecard"),
                    {
                        form_key: $.cookie('form_key'),
                        payment_intent: paymentIntent
                    },
                    function (response) {
                        if (response.success) {
                            if(response.client_secret){
                                dataSecret = response.client_secret;
                                var cardName;
                                cardName = response.card_name;
                                stripe.confirmCardSetup(
                                    dataSecret,
                                    {
                                        payment_method: {
                                            card: cardElement,
                                            billing_details: {name: cardName}
                                        }
                                    }
                                ).then(function (result) {
                                    if (result.success) {
                                        self.messageContainer.addErrorMessage({
                                            message: "Save card error"
                                        });
                                    }
                                });
                            }
                        }
                        if (response.error){
                            self.isPlaceOrderActionAllowed(true);
                            self.messageContainer.addErrorMessage({
                                message: response.message
                            });
                        }
                    },
                    "json"
                );
            },

            handleCaptureAndAuthorize: function (declined = null) {
                fullScreenLoader.startLoader();
                var self = this;
                $.ajax({
                    url: url.build('stripe/checkout_intents/response'),
                    dataType: "json",
                    data: {
                        form_key: $.cookie('form_key'),
                        declined: declined
                    },
                    type: 'POST',
                    success: function (response) {
                        if (response.success) {
                            if (self.redirectAfterPlaceOrder) {
                                redirectOnSuccessAction.execute();
                            }
                        }

                        if (response.error){
                            var errorElement = document.getElementById('intray-show-error');
                            errorElement.className += "message message-warning warning";
                            errorElement.textContent = response.error.message ?
                                response.error.message : $.mage.__('Something went wrong. Please reload the page.');
                            fullScreenLoader.stopLoader();
                        }
                    },
                    error: function () {
                        self.isPlaceOrderActionAllowed(true);
                        fullScreenLoader.stopLoader();
                        self.messageContainer.addErrorMessage({
                            message: $.mage.__('Something went wrong, please try again.')
                        });
                    }
                });
            },

            getData: function() {
                var self = this;
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'saved': this.saveCardOption,
                        'cardId': this.cardId()
                    }
                }
            },

            checkCard: function() {
                var self  = this;
                cardElement.addEventListener('change', function (event) {
                    var displayError = document.getElementById(self.getCode() + '-card-errors');
                    if (event.complete == "false" ) {
                        var cardErr = event.complete;
                    }
                    try {
                        displayError.textContent = '';
                    }catch (cardErr) {
                        throw new TypeError(event.error);
                    }
                });
            },

            validate: function() {
                var self = this;
                if(window.checkoutConfig.payment.magenest_stripe_config.https_check){
                    if (window.location.protocol !== "https:") {
                        self.messageContainer.addErrorMessage({
                            message: $.mage.__("Error: HTTPS is not enabled")
                        });
                        return false;
                    }
                }
                if(window.checkoutConfig.payment.magenest_stripe_config.publishableKey === "" ){
                    self.messageContainer.addErrorMessage({
                        message: $.mage.__("No API key provided.")
                    });
                    return false;
                }
                if(window.checkoutConfig.payment.magenest_stripe_intents === "" ){
                    self.messageContainer.addErrorMessage({
                        message: $.mage.__("No Data Secret provided.")
                    });
                    return false;
                }
                if (typeof Stripe === "undefined"){
                    self.messageContainer.addErrorMessage({
                        message: $.mage.__("Stripe js load error")
                    });
                    return false;
                }

                return true;
            },
        });
    }
);
