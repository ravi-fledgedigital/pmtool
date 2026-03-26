define(
    [
        'mage/translate',
        'Magento_Checkout/js/model/quote',
        'Magento_Ui/js/model/messageList'
    ],
    function ($t,quote,messageList) {
        'use strict';
        return{

            validate:function(){
                var isActiveMinimumOrder = window.checkoutConfig.isActiveMinimumOrder;
                var minimumOrderMessage = window.checkoutConfig.minimumOrderMessage;
                var minimumOrderValue = window.checkoutConfig.minimumOrderValue;
                var isValidMinimumOrder = window.checkoutConfig.isValidMinimumOrder;
                if(isActiveMinimumOrder) {
                    if (!isValidMinimumOrder || quote.totals._latestValue.base_grand_total < minimumOrderValue) {
                        messageList.addErrorMessage({message: $t(minimumOrderMessage)});
                        return false;
                    }
                }
            }
        };

    }

);
