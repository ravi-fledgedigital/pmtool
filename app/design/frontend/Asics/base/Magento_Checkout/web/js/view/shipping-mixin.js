define([
        'jquery',
        'underscore',
        'Magento_Ui/js/form/form',
        'ko',
        'Magento_Customer/js/model/customer',
        'Magento_Customer/js/model/address-list',
        'Magento_Checkout/js/model/address-converter',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/create-shipping-address',
        'Magento_Checkout/js/action/select-shipping-address',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-address/form-popup-state',
        'Magento_Checkout/js/model/shipping-service',
        'Magento_Checkout/js/action/select-shipping-method',
        'Magento_Checkout/js/model/shipping-rate-registry',
        'Magento_Checkout/js/action/set-shipping-information',
        'Magento_Checkout/js/model/step-navigator',
        'Magento_Ui/js/modal/modal',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Magento_Checkout/js/checkout-data',
        'uiRegistry',
        'mage/translate',
        'Magento_Customer/js/model/customer/address',
        'Magento_Checkout/js/action/select-billing-address',
        'Magento_Checkout/js/model/shipping-rate-service',
        'mage/mage'
    ], function (
    $,
    _,
    Component,
    ko,
    customer,
    addressList,
    addressConverter,
    quote,
    createShippingAddress,
    selectShippingAddress,
    shippingRatesValidator,
    formPopUpState,
    shippingService,
    selectShippingMethodAction,
    rateRegistry,
    setShippingInformationAction,
    stepNavigator,
    modal,
    checkoutDataResolver,
    checkoutData,
    registry,
    $t,
    Address,
    selectBillingAddress
    ) {
        'use strict';
        var popUp = null;
        var addressListItem =[];
        var mixin = {

            initialize: function () {
                $(document).ready(function () {
                    $(document).on('change', "[name='region_id']", function () {
                        $(this).parents('form').find('[name="city"]').val('').trigger('change');
                    });
                });
                this.isVisible = ko.observable(!quote.isVirtual() && customer.isLoggedIn());
                this._super();
                if(!document.cookie.match('checkoutStepCurrent')){
                    if(!quote.isVirtual() && customer.isLoggedIn()){
                        stepNavigator.setHash('shipping');
                        document.cookie = 'checkoutStepCurrent=shipping; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                    }
                }else{
                    var cookiesValue = this._getCookies('checkoutStepCurrent');
                    if(cookiesValue){
                        stepNavigator.setHash(cookiesValue);
                        $(".promotion-popup,.promotion-popup-overlay").remove();
                    }else{
                        if(!quote.isVirtual() && customer.isLoggedIn()){
                            stepNavigator.setHash('shipping');
                            document.cookie = 'checkoutStepCurrent=shipping; expires='.concat(new Date(Date.now() + 6048e5).toUTCString(), "; path=/;");
                        }
                    }
                }

                this._initAddressList();
                return this;
            },
            _getCookies: function(cname){
                var name = cname + "=";
                var decodedCookie = decodeURIComponent(document.cookie);
                var ca = decodedCookie.split(';');
                for(var i = 0; i <ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ') {
                        c = c.substring(1);
                    }
                    if (c.indexOf(name) == 0) {
                        return c.substring(name.length, c.length);
                    }
                }
                return "";
            },
            _initAddressList: function (){
                addressListItem =[];
                $.each(addressList(), function (key, item) {
                    addressListItem[item.customerAddressId] = item;
                });
            },
            getPopUp: function () {
                var self = this,
                    buttons;

                if (!popUp) {
                    buttons = this.popUpForm.options.buttons;
                    this.popUpForm.options.buttons = [
                        {
                            text: $t('Save & Continue'),
                            class: buttons.save.class ? buttons.save.class : 'action primary action-save-address',
                            click: self.saveNewAddress.bind(self)
                        },
                        {
                            text: buttons.cancel.text ? buttons.cancel.text : $t('Cancel'),
                            class: buttons.cancel.class ? buttons.cancel.class : 'action secondary action-hide-popup',

                            /** @inheritdoc */
                            click: this.onClosePopUp.bind(this)
                        }
                    ];

                    /** @inheritdoc */
                    this.popUpForm.options.closed = function () {
                        self.isFormPopUpVisible(false);
                    };

                    this.popUpForm.options.modalCloseBtnHandler = this.onClosePopUp.bind(this);
                    this.popUpForm.options.keyEventHandlers = {
                        escapeKey: this.onClosePopUp.bind(this)
                    };

                    /** @inheritdoc */
                    this.popUpForm.options.opened = function () {
                        // Store temporary address for revert action in case when user click cancel action
                        self.temporaryAddress = $.extend(true, {}, checkoutData.getShippingAddressFromData());
                    };
                    popUp = modal(this.popUpForm.options, $(this.popUpForm.element));
                }

                return popUp;
            },
            checkToolbar: function () {
                if(customer.isLoggedIn()){
                    if(customer.getShippingAddressList().length){
                        return false;
                    }
                }
                return true;
            },
            /**
             * Save  shipping address
             */
            saveNewAddress: function () {
                var self = this;
                this.source.set('params.invalid', false);
                this.triggerShippingDataValidateEvent();

                if (!this.source.get('params.invalid')) {
                    var formData = new FormData($('#co-shipping-form')[0]);
                    formData.append("form_key", $('.columns .column.main input[name="form_key"]').val());
                    $.ajax({
                        url: $('#checkout').data('save'),
                        data: formData,
                        type: 'post',
                        dataType: 'json',
                        cache: false,
                        contentType: false,
                        processData: false,
                        success: function (res) {
                            if(res.success){
                                window.customerData = res.customerData;
                                self._addAddress();
                                self._modifyAddress();
                                self._initAddressList();
                            }
                            self._closePopup();
                        },
                        /** @inheritdoc */
                        error: function (res) {
                            self._closePopup();
                        }
                    });
                }
            },
            _closePopup: function (){
                $.cookieStorage.set('mage-messages', '');
                this.getPopUp().closeModal();
            },
            _addAddress: function(){
                var self = this;
                $.each(window.customerData.addresses, function (key, item) {
                    var addressData = new Address(item);
                    addressData = self._addAddressExtension(addressData);
                    if(!Object.keys(addressListItem).includes(item.id.toString())) {
                        addressList.push(addressData);
                        selectShippingAddress(addressData);
                        checkoutData.setSelectedShippingAddress(addressData.getKey());
                    }
                });
            },
            _addAddressExtension: function (addressData){
                return addressData;
            },
            _setShippingAddress: function (){
                var self = this;
                $.each(window.customerData.addresses, function (key, item) {
                    var addressData = new Address(item);
                    addressData = self._addAddressExtension(addressData);
                    if(!Object.keys(addressListItem).includes(item.id.toString())) {
                        self.isFormInline = false;
                        selectShippingAddress(addressData);
                        checkoutData.setSelectedShippingAddress(addressData.getKey());
                        selectBillingAddress(quote.shippingAddress());
                        $('#shipping-method-buttons-container button').trigger('click');
                    }
                });
            },
            _modifyAddress: function (){
                $.each(addressList(), function (key, item) {
                    if(Object.keys(window.customerData.addresses).includes(parseInt(item.customerAddressId).toString())) {
                        var addressData = new Address(window.customerData.addresses[item.customerAddressId]);
                        addressData = self._addAddressExtension(addressData);
                        addressList.splice(key, 1, addressData);
                    }
                });
            },
            addressPopup: function () {
                this.isFormPopUpVisible(true);
                $('#opc-new-shipping-address').find('input[type="text"]:not(".mobile-country-code")').val('').trigger('change');
                $('#opc-new-shipping-address').find('input#id').val('');
                $('#opc-new-shipping-address').find('input#ShipMobile').val('');
                $('#opc-new-shipping-address').find('select').val('');
                $('#opc-new-shipping-address').find('[name="country_id"]').val(window.defaultCountry).trigger('change');
                $('#opc-new-shipping-address').find('[name="region_id"]').val('').trigger('change');
                $('#opc-new-shipping-address').find('#region').hide();
                $('.form-shipping-address .fieldset.address .field').removeClass('_error');
                $('.form-shipping-address .fieldset.address .field div.field-error').hide();
            },
            createNewAddress: function () {
                var self = this;

                this.source.set('params.invalid', false);
                this.triggerShippingDataValidateEvent();
                if (!this.source.get('params.invalid')) {
                    if (customer.isLoggedIn()) {
                        var formData = new FormData($('#co-shipping-form')[0]);
                        formData.append("form_key", $('.columns .column.main input[name="form_key"]').val());
                        $.ajax({
                            url: $('#checkout').data('save'),
                            data: formData,
                            type: 'post',
                            dataType: 'json',
                            cache: false,
                            contentType: false,
                            processData: false,
                            success: function (res) {
                                if (res.success) {
                                    window.customerData = res.customerData;
                                    self._addAddress();
                                    self._setShippingAddress();
                                    self._initAddressList();
                                    $('.checkout-shipping-address > .step-title').text($t('Shipping Address'));
                                }
                                $.cookieStorage.set('mage-messages', '');
                            },
                            /** @inheritdoc */
                            error: function (res) {
                                $.cookieStorage.set('mage-messages', '');
                            }
                        });
                    } else {
                        $('#shipping-method-buttons-container button').trigger('click');
                    }
                }
            },
            showPopupAddress : function () {
                var self = this;
                self.temporaryAddress = $.extend(true, {}, checkoutData.getShippingAddressFromData());
                jQuery('.new-address-popup.hidden .action-show-popup').trigger('click')
            },
            checkFormVisible: function () {
                return ko.observable(!addressList().length);
            },
            /**
             * Trigger Shipping data Validate Event.
             */
            triggerShippingDataValidateEvent: function () {
                $('.form-shipping-address .fieldset.address .field div.field-error').show();
                this.source.trigger('shippingAddress.data.validate');

                if (this.source.get('shippingAddress.custom_attributes')) {
                    this.source.trigger('shippingAddress.custom_attributes.data.validate');
                }
            }
        };

        return function (target) {
            return target.extend(mixin);
        };
    }
);
