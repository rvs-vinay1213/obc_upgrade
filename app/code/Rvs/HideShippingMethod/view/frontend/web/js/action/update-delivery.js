define(
    [
        'Amasty_CheckoutCore/js/model/resource-url-manager',
        'Amasty_CheckoutCore/js/model/delivery',
        'Magento_Checkout/js/model/quote',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/shipping-service',
        'Magento_Checkout/js/model/shipping-rate-registry',
        'Magento_Checkout/js/model/resource-url-manager',
        'Magento_Checkout/js/model/cart/cache'
    ],
    function (resourceUrlManager, deliveryService, quote, storage, errorProcessor, shippingService, rateRegistry, resourceUrlManager2,cartCache) {
        "use strict";
        return function (payload) {
            if (deliveryService.isLoading()) {
                return;
            }

            // deliveryService.isLoading(true);
            var serviceUrl = resourceUrlManager.getUrlForDelivery(quote);

            storage.post(
                serviceUrl, JSON.stringify(payload), false
            ).done(
                function (result) {

                }
            ).fail(
                function (response) {
                    errorProcessor.process(response);
                }
            ).always(
                function () {
                    deliveryService.isLoading(false);
                    var address = quote.shippingAddress();
                    shippingService.isLoading(true);
                    console.log('updatedelivery1');
                    var posturl = resourceUrlManager2.getUrlForEstimationShippingMethodsForNewAddress(quote);
                    var addresspayload = JSON.stringify({
                            address: {
                                'street': address.street,
                                'city': address.city,
                                'region_id': address.regionId,
                                'region': address.region,
                                'country_id': address.countryId,
                                'postcode': address.postcode,
                                'email': address.email,
                                'customer_id': address.customerId,
                                'firstname': address.firstname,
                                'lastname': address.lastname,
                                'middlename': address.middlename,
                                'prefix': address.prefix,
                                'suffix': address.suffix,
                                'vat_id': address.vatId,
                                'company': address.company,
                                'telephone': address.telephone,
                                'fax': address.fax,
                                'custom_attributes': address.customAttributes,
                                'save_in_address_book': address.saveInAddressBook
                            }
                        }
                    );
                    if (address.customerAddressId){
                        posturl = resourceUrlManager2.getUrlForEstimationShippingMethodsByAddressId();
                        addresspayload = JSON.stringify({
                            addressId: address.customerAddressId
                        });
                    }
                    storage.post(
                        posturl,
                        addresspayload,
                        false
                    ).done(function (result) {
                        rateRegistry.set(address.getKey(), result);
                        shippingService.setShippingRates(result);
                    }).fail(function (response) {
                        shippingService.setShippingRates([]);
                        errorProcessor.process(response);
                    }).always(function () {
                            shippingService.isLoading(false);
                            shippingService.getShippingRates().subscribe(function (rates) {
				    cartCache.set('rates', rates);
				});
                        }
                    );

                }
            );
        }
    }
);
