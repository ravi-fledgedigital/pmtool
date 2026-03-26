define([
    'uiElement',
    'underscore',
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'mage/loader'
],function (Element, _, $, message) {
    'use strict';

    var mixin = {
        addTracking: function () {
            if (!this.carrier() || !this.value()) {
                message({'content': $.mage.__('Carrier wasn\'t selected or tracking number wasn\'t filled.')});
                return;
            }
            $('body').loader("show");
            $.ajax({
                url: this.saveUrl,
                data: { 'code': this.carrier(), 'number': this.value()},
                method: 'post',
                global: false,
                dataType: 'json',
                success: function (data) {
                    if (!_.isUndefined(data.success)) {
                        var numbers = this.trackingNumbers();
                        //TODO validate
                        numbers.push(
                            {
                                'id': data.id,
                                'code': this.carrier(),
                                'number': this.value(),
                                'customer': 1
                            }
                        );
                        $('body').loader("hide");
                        this.trackingNumbers(numbers);
                        this.carrier("");
                        this.value("");
                    }
                }.bind(this)
            });

            return this;
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
