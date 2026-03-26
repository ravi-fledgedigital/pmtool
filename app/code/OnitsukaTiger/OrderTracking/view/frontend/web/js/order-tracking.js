/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    'mage/apply/main',
    'underscore',
    'mage/template',
    'jquery-ui-modules/widget'
], function ($,$t,mage) {
    'use strict';

    $.widget('mage.orderTracking', {

        options: {
        },

        /** @inheritdoc */
        _create: function () {
            var self = this;
            $('#oar-order-id').on('keydown', function (e) {
                if(/[0-9a-zA-Z]/i.test(e.key)){
                    var key = e.key;
                    return key;
                }
                return false;
            });
            $(document).on('click', '#trackOrder', function (e) {
                self.showOrder();
                e.preventDefault();
                return false
            });
        },
        /**
         * ajax handle call
         */
        showOrder: function (){
            var self = this;
            var oar_order_id =  $('#oar-order-id').val();
            var oar_email = $('#oar_email').val();
            $('.error-noti').css('display','none');

            if(oar_order_id == "" || oar_email == ""){
                $('.error-noti').text($t('Order ID and Email is Mandatory.'));
                $('.error-noti').css('display','block');
                return false;
            }
            $.ajax({
                url: self.options.actionUrlPrefix,
                data: {
                    'oar_order_id': $('#oar-order-id').val(),
                    'oar_email':  $('#oar_email').val(),
                    'isAjax': 1,
                },
                type: 'post',
                dataType: '',
                showLoader: true,
                cache: false,
                success: function (res) {
                    if(res != 'false'){
                        $('.order-details').html($(res).find('.column.main .order-details-items.ordered')[0].outerHTML);
                        $('.order-details').append($(res).find('.column.main .block.block-order-details-view')[0].outerHTML);
                        $('.order-details').trigger('contentUpdated');
                        mage.apply();
                    }else {
                        $('.order-details').html('<div class="empty-order">'+$t('No Details found for the specified OrderNo and Email/MobileNo.')+'</div>').trigger('contentUpdate');
                    }
                },
                error: function (res) {
                    console.log(res);
                }
            });
        },
    });

    return $.mage.orderTracking;
});
