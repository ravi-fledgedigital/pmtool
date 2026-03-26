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
        var postCodeInit = true;
        var addressListItem =[];
        var mixin = {

            initialize: function () {
                var self = this;
                $(document).ready(function () {
                    $(document).on('change', "[name='region_id']", function () {
                        $(this).parents('form').find('[name="city"]').val('').trigger('change');
                        self._setPostCode($(this),"",false);
                        if($(this).parents('form').find("[name='country_id']").val() == 'MY') {
                            if ($(this).find('option:selected').text() == "Wilayah Persekutuan Labuan") {
                                self._setPostCode($(this),"87000",true);
                            }
                        }
                        if (postCodeInit && quote.shippingAddress()) {
                            var city = quote.shippingAddress().city,
                                postalCode = quote.shippingAddress().postcode,
                                countryId = quote.shippingAddress().countryId,
                                regionId = quote.shippingAddress().region;
                            if ($(this).closest('fieldset').data('form') == "billing-new-address") {
                                city = quote.billingAddress().city;
                                postalCode = quote.billingAddress().postcode;
                                regionId = quote.billingAddress().region;
                                countryId = quote.billingAddress().countryId;
                            }
                            $(this).parents('form').find('[name="city"]').val(city).trigger('change');
                            self._setPostCode($(this),postalCode,false);
                            if (regionId == "Wilayah Persekutuan Labuan" && countryId == "MY") {
                                self._setPostCode($(this),"87000",true);
                            }
                        }
                        postCodeInit = false;
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
            _setPostCode: function(element,value,visible) {
                element.parents('form').find("[name='postcode']").prop('readonly', visible);
                element.parents('form').find("[name='postcode']").val(value).trigger('change')
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
                var checkErrorCharaterAddress = $("#shipping-new-address-form input[name='validations-address-character']").parent().find('.field-error');
                if ($("#shipping-new-address-form input[name='validations-address-character']").length > 0 && checkErrorCharaterAddress.length > 0) {
                    return false;
                }

                if (!this.source.get('params.invalid')) {
                    $('body').trigger('processStart');
                    $('body > .loading-mask').show();
                    var formData = new FormData($('#co-shipping-form')[0]);
                    formData.append("form_key", $('.columns .column.main input[name="form_key"]').val());
                    if($('.opc-wrapper .shipping-address-item:not(.hidden)').length) {
                        formData.append("default_shipping", '1');
                    }
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
                            $('body > .loading-mask').hide();
                            self._closePopup();
                        },
                        /** @inheritdoc */
                        error: function (res) {
                            $('body > .loading-mask').hide();
                            self._closePopup();
                        }
                    });
                }
            },
            /**
             * Set shipping information handler
             */
            setShippingInformation: function () {
                if (this.validateShippingInformation()) {
                    quote.billingAddress(null);
                    checkoutDataResolver.resolveBillingAddress();
                    registry.async('checkoutProvider')(function (checkoutProvider) {
                        var shippingAddressData = checkoutData.getShippingAddressFromData();

                        if (shippingAddressData) {
                            checkoutProvider.set(
                                'shippingAddress',
                                $.extend(true, {}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                            );
                        }
                    });
                    $('body > .loading-mask').show();
                    setShippingInformationAction().done(
                        function () {
                            selectBillingAddress(quote.shippingAddress());
                            stepNavigator.next();
                            $('body > .loading-mask').hide();
                        }
                    );
                }
            },
            _closePopup: function (){
                $('body > .loading-mask').hide();
                $('body').trigger('processStop');
                $.cookieStorage.set('mage-messages', '');
                this.getPopUp().closeModal();
            },
            _addAddress: function(){
                $.each(window.customerData.addresses, function (key, item) {
                    var addressData = new Address(item);
                    if(!Object.keys(addressListItem).includes(item.id.toString())) {
                        addressList.push(addressData);
                        selectShippingAddress(addressData);
                        checkoutData.setSelectedShippingAddress(addressData.getKey());
                    }
                });
            },
            _setShippingAddress: function (){
                var self = this;
                $.each(window.customerData.addresses, function (key, item) {
                    var addressData = new Address(item);
                    if(!Object.keys(addressListItem).includes(item.id.toString())) {
                        self.isFormInline = false;
                        selectShippingAddress(addressData);
                        checkoutData.setSelectedShippingAddress(addressData.getKey());
                        selectBillingAddress(quote.shippingAddress());
                        checkoutData.setSelectedBillingAddress(addressData.getKey());
                        $('#shipping-method-buttons-container button').trigger('click');
                    }
                });
            },
            _modifyAddress: function (){
                $.each(addressList(), function (key, item) {
                    if(Object.keys(window.customerData.addresses).includes(parseInt(item.customerAddressId).toString())) {
                        var addressData = new Address(window.customerData.addresses[item.customerAddressId]);
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
                $('.modal-popup .form-shipping-address').removeClass('hidden');
            },
            createNewAddress: function () {
                var self = this;
                this.source.set('params.invalid', false);
                this.triggerShippingDataValidateEvent();
                var checkErrorCharaterAddress = $("#shipping-new-address-form input[name='validations-address-character']").parent().find('.field-error');
                if($("#shipping-new-address-form input[name='validations-address-character']").length > 0 && checkErrorCharaterAddress.length > 0) {
                    return false;
                }
                $('.form-shipping-address input').trigger('change');
                if(!$('.form-shipping-address .field-error').length) {
                    if (!this.source.get('params.invalid')) {
                        if (customer.isLoggedIn()) {
                            $('body').trigger('processStart');
                            $('body > .loading-mask').show();
                            var formData = new FormData($('#co-shipping-form')[0]);
                            formData.append("form_key", $('.columns .column.main input[name="form_key"]').val());
                            if(!$('.opc-wrapper .shipping-address-item:not(.hidden)').length) {
                                formData.append("default_shipping", '1');
                            }
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
                                    $('body > .loading-mask').hide();
                                    $('body').trigger('processStop');
                                    $.cookieStorage.set('mage-messages', '');
                                },
                                /** @inheritdoc */
                                error: function (res) {
                                    $('body > .loading-mask').hide();
                                    $('body').trigger('processStop');
                                    $.cookieStorage.set('mage-messages', '');
                                }
                            });
                        } else {
                            $('#shipping-method-buttons-container button').trigger('click');
                        }
                    }
                }
            },
            showPopupAddress : function () {
                var self = this;
                self.temporaryAddress = $.extend(true, {}, checkoutData.getShippingAddressFromData());
                if($('.modal-popup .form-address-new-popup').length) {
                    if(!$('.modal-popup .form-address-new-popup #opc-new-shipping-address').length) {
                        $("#opc-new-shipping-address").appendTo($(".modal-popup .form-address-new-popup"));
                    }
                }
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
            },
            validateShippingInformation: function () {
                var shippingAddress,
                    addressData,
                    loginFormSelector = 'form[data-role=email-with-possible-login]',
                    emailValidationResult = customer.isLoggedIn(),
                    field,
                    country = registry.get(this.parentName + '.shippingAddress.shipping-address-fieldset.country_id'),
                    countryIndexedOptions = country.indexedOptions,
                    option = countryIndexedOptions[quote.shippingAddress().countryId],
                    messageContainer = registry.get('checkout.errors').messageContainer;

                if (!quote.shippingMethod()) {
                    this.errorValidationMessage(
                        $t('The shipping method is missing. Select the shipping method and try again.')
                    );

                    return false;
                }

                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }

                if (this.isFormInline) {
                    this.source.set('params.invalid', false);
                    this.triggerShippingDataValidateEvent();

                    if (emailValidationResult &&
                        this.source.get('params.invalid') ||
                        !quote.shippingMethod()['method_code'] ||
                        !quote.shippingMethod()['carrier_code']
                    ) {
                        this.focusInvalid();

                        return false;
                    }

                    shippingAddress = quote.shippingAddress();
                    addressData = addressConverter.formAddressDataToQuoteAddress(
                        this.source.get('shippingAddress')
                    );

                    //Copy form data to quote shipping address object
                    for (field in addressData) {
                        if (addressData.hasOwnProperty(field) &&  //eslint-disable-line max-depth
                            shippingAddress.hasOwnProperty(field) &&
                            typeof addressData[field] != 'function' &&
                            _.isEqual(shippingAddress[field], addressData[field])
                        ) {
                            shippingAddress[field] = addressData[field];
                        } else if (typeof addressData[field] != 'function' &&
                            !_.isEqual(shippingAddress[field], addressData[field])) {
                            shippingAddress = addressData;
                            break;
                        }
                    }

                    if (customer.isLoggedIn()) {
                        shippingAddress['save_in_address_book'] = 1;
                    }
                    selectShippingAddress(shippingAddress);
                } else if (customer.isLoggedIn() &&
                    option &&
                    option['is_region_required'] &&
                    !quote.shippingAddress().region
                ) {
                    messageContainer.addErrorMessage({
                        message: $t('Please specify a regionId in shipping address.')
                    });

                    return false;
                }

                if (!emailValidationResult) {
                    $(loginFormSelector + ' input[name=username]').focus();

                    return false;
                }

                return true;
            }
        };

        return function (target) {
            return target.extend(mixin);
        };
    }
);
