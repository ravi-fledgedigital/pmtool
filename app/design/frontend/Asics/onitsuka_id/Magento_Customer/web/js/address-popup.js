/**
 * Copyright Â© Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'mage/apply/main',
    'jquery-ui-modules/widget'
], function ($, $t, modal, mage) {
    'use strict';

    $.widget('mage.addressPopup', {
        modalAddress : null,
        addressList : null,
        addressId : null,
        address : null,
        options: {
            processStart: null,
            processStop: null,
            editSelector: '.address-actions a[role="edit-address"]',
            deleteSelector: '.address-actions a[role="delete-address"]',
            createSelector: '[role="add-address"]',
            messagesSelector: '.page.messages',
        },

        /** @inheritdoc */
        _create: function () {
            var self = this;
            this.initModal();
            this.initAddress();
            $(this.options.editSelector).off('click').on('click', function (e) {
                self.addressId = parseInt($(e.currentTarget).data('address'));
                self._getAddress();
                self._bindAddress();
                self._openModal();
                e.preventDefault;
                return false;
            });
            $(this.options.deleteSelector).off('click').on('click', function (e) {
                $('.popup-confirm-delete').data('address',parseInt($(e.currentTarget).data('address')));
                $('.popup-confirm-delete').show();
                e.preventDefault;
                return false;
            });
            $(this.options.createSelector).off('click').on('click', function (e) {
                self._clearAddress();
                self._openModal();
                e.preventDefault;
                return false;
            });
            $('#popup_ok').off('click').on('click', function (e) {
                $('.popup-confirm-delete').hide();
                self.deleteAddress();
                return false;
            });
            $('#popup_cancel').off('click').on('click', function (e) {
                $('.popup-confirm-delete').hide();
                return false;
            });
            if(self.options.addressId) {
                self.addressId = parseInt(self.options.addressId);
                self._getAddress();
                self._bindAddress();
                self._openModal();
                return false;
            }
        },
        /**
         *
         */
        deleteAddress: function (){
            var self = this;
            if(self.options.deleteUrlPrefix){
                $('body').addClass('active-ajax-loading');
                $.ajax({
                    url: self.options.deleteUrlPrefix,
                    data: {
                        'id': $('.popup-confirm-delete').data('address'),
                        'isAjax': 1,
                        'form_key': $('.block-address-grid-container input[name="form_key"]').val()
                    },
                    type: 'post',
                    dataType: 'json',
                    cache: false,
                    /** @inheritdoc */
                    beforeSend: function () {
                        if (self.isLoaderEnabled()) {
                            $('body').trigger(self.options.processStart);
                        }
                    },
                    success: function (res) {
                        if(res.success){
                            $('.block-address-grid-container').html(res.address);
                            $('.block-address-grid-container').trigger('contentUpdated');
                            $.cookieStorage.set('mage-messages', '');
                            self.options.processStart = null;
                        }
                        $(self.options.messagesSelector).trigger('contentUpdated');
                        mage.apply();
                        $('body').removeClass('active-ajax-loading');
                    },
                    /** @inheritdoc */
                    error: function (res) {
                        $(self.options.messagesSelector).trigger('contentUpdated');
                        $('body').removeClass('active-ajax-loading');
                    }
                });
            }
        },
        /**
         * init Address Popup Modal
         */
        initModal: function () {
            var self = this;
            var options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: $t('Shipping Address'),
                modalClass: 'address-edit-popup',
                buttons: [{
                    text: $.mage.__('Save and Continue'),
                    class: '',
                    click: function () {
                        self.submitForm(self.element,this);
                    }
                }]
            };
            this.modalAddress = modal(options, $('.form-address-edit-popup'));
        },
        /**
         * init Address Popup Modal
         */
        initAddress: function () {
            this.addressList = $.parseJSON(this.options.addressJson);
        },
        /**
         * @private
         */
        _openModal: function () {
            var self = this;
            this.modalAddress.openModal();
        },
        /**
         *
         * @param id
         * @private
         */
        _bindAddress: function (id) {
            var self = this;
            $.each(self.address, function( index, address ) {
                if(index.includes('street') && address) {
                    self.element.find('[id="'+index+'"]').val($.parseHTML(address)[0]['textContent']);
                    self.element.find('[name="'+index+'"]').val($.parseHTML(address)[0]['textContent']);
                } else {
                    self.element.find('[id="'+index+'"]').val(address);
                    self.element.find('[name="'+index+'"]').val(address);
                }
            });
            self._bindTelephone();
            self.element.find('#country').val(self.address.country_id).trigger('change');
            self.element.find('#region_id').val(self.address.region_id);
            self.element.find('div.mage-error').hide();
            var regionVal = self.address.region;
            if(regionVal == 'Wilayah Persekutuan Labuan') {
                self.element.find("[name='postcode']").prop('readonly', true);
            }else {
                self.element.find("[name='postcode']").prop('readonly', false);
            }
            // custom indo
            self.element.find('#region_id').val(self.address.region_id).trigger('change');
            self.element.find('#city').val(self.address.city).trigger('change');
            self.element.find('#district').val(self.address.district).trigger('change');
            self.element.find("[name='postcode']").val(self.address.postcode).trigger('change');
        },
        /**
         *
         * @private
         */
        _bindTelephone: function(){
            var self = this;
            var telephoneNumber = self.address.telephone.replace(self.element.find('#phone-countrycode').val(),'');
            self.element.find('#mobile-number').val(telephoneNumber);
        },
        /**
         *
         * @param id
         * @private
         */
        _clearAddress: function () {
            var self = this;
            self.element.find('input[type="text"]:not(".phone-countrycode")').val('');
            self.element.find('input#id').val('');
            self.element.find('input#telephone').val('');
            self.element.find('select:not(#country)').val('');
            self.element.find('#region_id').val('').trigger('change');
            self.element.find('div.mage-error').hide();
        },
        /**
         *
         * @param id
         * @private
         */
        _getAddress: function (id) {
            var self = this;
            $.each(this.addressList, function( index, address ) {
                if(self.addressId == parseInt(address.id)){
                    self.address = address;
                }
            });
            return self.address;

        },
        /**
         * Handler for the form 'submit' event
         *
         * @param {jQuery} form
         */
        submitForm: function (form,modal) {
            if(form.valid()){
                this.ajaxSubmit(form);
                modal.closeModal();
            }
            return false;
        },
        /**
         * @return {Boolean}
         */
        isLoaderEnabled: function () {
            return this.options.processStart && this.options.processStop;
        },
        /**
         * @param {jQuery} form
         */
        ajaxSubmit: function (form) {
            var self = this,
                formData;
            $('body').addClass('active-ajax-loading');
            formData = new FormData(form[0]);
            $.ajax({
                url: form.attr('action'),
                data: formData,
                type: 'post',
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                /** @inheritdoc */
                beforeSend: function () {
                    if (self.isLoaderEnabled()) {
                        $('body').trigger(self.options.processStart);
                    }
                },
                success: function (res) {
                    if(res.success){
                        $('.block-address-grid-container').html(res.address);
                        $('.block-address-grid-container').trigger('contentUpdated');
                        $.cookieStorage.set('mage-messages', '');
                        self.options.processStart = null;
                    }
                    $(self.options.messagesSelector).trigger('contentUpdated');
                    mage.apply();
                    $('body').removeClass('active-ajax-loading');
                },
                /** @inheritdoc */
                error: function (res) {
                    $(self.options.messagesSelector).trigger('contentUpdated');
                    $('body').removeClass('active-ajax-loading');
                }
            });
        },

    });

    return $.mage.addressPopup;
});
