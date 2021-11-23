define([
    'ko',
    'mage/storage',
    'Magento_Checkout/js/model/quote',
    'jquery'
], function(ko, storage, quote, $) {

    var currentRequest = null;

    return function(wexoShippingData, shippingCountryId) {
        if (currentRequest && currentRequest.abort) {
            currentRequest.abort();
        }

        $('body').trigger('processStart');
        return storage.get('/rest/V1/wexo-webshipper/get-parcel-shops?' + $.param({
            country: shippingCountryId,
            postcode: wexoShippingData.postcode,
            method: quote.shippingMethod().method_code,
            shipping_address: JSON.stringify(quote.shippingAddress()),
            cache: true
        })).always(function() {
            currentRequest = null;
            $('body').trigger('processStop');
        });
    };
});
