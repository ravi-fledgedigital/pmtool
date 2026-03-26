/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'jquery-ui-modules/widget'
], function ($, $t, modal) {
    'use strict';

    $.widget('mage.shipmentDetails', {
        modalHTML : null,
        shipment : null,
        modalShipment : null,
        shipments: null,
        options: {
            processStart: null,
            processStop: null,
            detailsSelector: '[data-role="shipment-details"]'
        },

        /** @inheritdoc */
        _create: function () {
            var self = this;
            self.initModal();
            this.initShipments();
            $(self.options.detailsSelector).off('click').on('click', function (e) {
                self.shipment = self.shipments[parseInt($(e.currentTarget).data('shipment'))];
                self._bindShipment();
                self._openModal();
                e.preventDefault;
                return false;
            });
        },
        /**
         * init Shipment Popup Modal
         */
        initModal: function () {
            var self = this;
            var options = {
                type: 'popup',
                title: 'Popup title',
                responsive: true,
                modalClass: 'shipment-track-popup',
                buttons: [{
                    text: jQuery.mage.__('Submit'),
                    class: 'action'
                }]
            };
            self.modalShipment = modal(options, $('#modal-shipment'));
        },
        /**
         * init Shipment Popup Modal
         */
        initShipments: function () {
            this.shipments = $.parseJSON(this.options.shipmentJson);
        },
        /**
         * @private
         */
        _openModal: function () {
            this.modalShipment.openModal();
        },
        /**
         *
         * @param id
         * @private
         */
        _bindShipment: function () {
            var self = this;
            $.each(self.shipment.data, function( index, value ) {
                self.element.find('[data-role="'+index+'"]').text(value);
            });
            self._bindItems();
        },
        _bindItems:function () {
            var self = this;
            self.element.find('.order-details-items tbody').remove();
            var html = '';
            $.each(self.shipment.items, function( index, value ) {
                html +='<tbody>';
                html +='<tr>';
                html +='<td class="item-info">';
                html +='<a href="'+value.link+'"><img src="'+value.image+'" alt="'+value.name+'"/></a>';
                html +='</td>';
                html +='<td class="col name">';
                html +='<div class="item-description-content">';
                html +='<strong class="product name product-item-name">'+value.name+'</strong>';
                html +='</div>'
                html +='<div class="item-details-container">';
                html +='<div class="item-qty"><label>'+$t('Quantity: ')+'</label><span>'+value.qty+'</span></div>';
                html +='<div class="unit-price"><label>'+$t('Unit Price: ')+'</label><span>'+value.price+'</span></div>';
                html +='<div class="sub-total"><label>'+$t('Sub Total: ')+'</label><span>'+value.total+'</span></div>';
                html +='</div>';
                html +='</td>';
                html +='</tr>';
                html +='</tbody>';
            });
            self.element.find('.order-details-items .table-order-items').append(html);
        }
    });

    return $.mage.shipmentDetails;
});
