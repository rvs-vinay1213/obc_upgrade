define(
    [
        'jquery',
        'underscore',
        'uiRegistry',
        'Magento_Customer/js/model/customer'
    ],
    function ($, _, registry, customer) {
        'use strict';

        return {
            /**
             * Validate Login Form on checkout if available
             *
             * @returns {Boolean}
             */
            validate: function () {
                var createAcc,
                    loginForm = 'form[data-role=email-with-possible-login]',
                    password = $(loginForm).find('#customer-password'),
                    customerEmail = registry.get('checkout.steps.shipping-step.shippingAddress.customer-email');

                if (window.checkoutConfig !== undefined && window.checkoutConfig.quoteData.additional_options) {
                    createAcc = +window.checkoutConfig.quoteData.additional_options.create_account;
                }

                if (customer.isLoggedIn() || createAcc <= 1) {
                    return true;
                }

                if (createAcc === 3
                    && !_.isUndefined(customerEmail)
                    && customerEmail.isPassword()) {
                    return $(loginForm).validation() && $(loginForm).validation('isValid');
                }

                if (password.val()) {
                    return $(loginForm).validation() && $(loginForm).validation('isValid');
                }

                return true;
            }
        };
    }
);
