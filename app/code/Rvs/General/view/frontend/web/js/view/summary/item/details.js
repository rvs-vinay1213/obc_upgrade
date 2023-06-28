define(
[
    'uiComponent'
],
function (Component) {
    "use strict";
    var quoteItemData = window.checkoutConfig.quoteItemData;
    return Component.extend({

        defaults: {
            template: 'Magento_Checkout/summary/item/details'
        },

        quoteItemData: quoteItemData,
        getValue: function(quoteItem) {
            return quoteItem.name;
        },

        getItemSku: function(quoteItem) {
            var itemProduct = this.getItemProduct(quoteItem.item_id);
            return itemProduct.sku;
        },

        getItemQty: function(quoteItem) {
            var itemProduct = this.getItemProduct(quoteItem.item_id);
            return itemProduct.qty;
        },

        getItemProduct: function(item_id) {
            var itemElement = null;
            _.each(this.quoteItemData, function(element, index) {
                if (element.item_id == item_id) {
                    itemElement = element;
                }
            });
            return itemElement;
        }
    });
});