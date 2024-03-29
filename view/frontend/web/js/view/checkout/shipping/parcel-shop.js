define([
    'Wexo_Shipping/js/view/parcel-shop',
    'ko',
    'mage/translate',
    'underscore',
    '../../../../js/model/parcel-shop-searcher'
], function(AbstractParcelShop, ko, $t, _, parcelShopSearcher) {

    return AbstractParcelShop.extend({
        defaults: {
            parcelShopSearcher: parcelShopSearcher
        },

        /**
         * @returns {exports}
         */
        initialize: function() {
            this._super();
            this.shippingPostcode.subscribe(function(newVal) {
                if (!this.shippingMethod()) {
                    this.source.set('wexoShippingData.postcode', newVal);
                }
            }, this);

            return this;
        },

        /**
         * @returns {*}
         */
        getPopupText: function() {
            return ko.pureComputed(function() {
                return $t('%1 service points in postcode <u>%2</u>')
                    .replace('%1', this.parcelShops().length)
                    .replace('%2', this.wexoShippingData().postcode);
            }, this);
        },

        /**
         * @param parcelShop
         * @returns {string}
         */
        formatOpeningHours: function(parcelShop) {
            try {
                // this is nessecary as Magento generates "translation maps" for JS translations. For some reason a
                // value has to be specificly declared to be included in said mapping. This means dynamic content
                // can not be translated if the expected translation has not been used elsewhere in a static reference.
                let staticReference = [$t("Monday"),$t("Tuesday"),$t("Wednesday"),$t("Thursday"),$t("Friday"),$t("Saturday"),$t("Sunday")];
                if (parcelShop.opening_hours.length && parcelShop.opening_hours) {
                    var openingHours = JSON.parse(parcelShop.opening_hours);
                    var formattedHours = [];
                    openingHours.forEach(function (openingHour) {
                        openingHour.day = $t(openingHour.day);
                        formattedHours.push(openingHour);
                    });
                    return '<table>' + _.map(formattedHours, function(day) {
                        return '<tr><th>%1</th><td>%2 - %3</td></tr>'.replace('%1', day.day)
                            .replace('%2', day.opens_at)
                            .replace('%3', day.closes_at);
                    }).join('') + '</table>';
                }
                return '';
            }
            catch (e) {
                return '';
            }
        }
    });
});
