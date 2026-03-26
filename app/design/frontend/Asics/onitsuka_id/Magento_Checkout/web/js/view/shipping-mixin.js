/* eslint-disable */
/** phpcs:ignoreFile */
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
        'Magento_Checkout/js/model/pick_up_store',
        'Magento_InventoryInStorePickupFrontend/js/model/pickup-address-converter',
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
    selectBillingAddress,
    pickUpStore,
    pickupAddressConverter
    ) {
        'use strict';
        var popUp = null;
        var postCodeInit = true;
        var addressListItem =[];
        var pickUpConfig = checkoutConfig.pickup_config;
        var mixin = {

            initialize: function () {
                var self = this;
                $(document).ready(function () {
                    $(document).on('change', "[name='region_id']", function () {
                        $(this).parents('form').find('[name="city"]').val('').trigger('change');
                        self._setPostCode($(this),"",false);
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
                $('#shipping-new-address-form').find('[name="custom_attributes[district]"]').trigger('change');
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
                    if (quote.shippingMethod() && quote.shippingMethod().carrier_code == 'freeshipping') {
                        checkoutData.setSelectedBillingAddress(null);
                        $('body').addClass('pickup-store-payment');
                    }
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
                if (!$('.form-shipping-address .field-error').length) {
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
                jQuery('.new-address-popup.hidden .action-show-popup').trigger('click');
                $('.modal-popup .form-shipping-address').removeClass('hidden');
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
                if (!customer.isLoggedIn()) {
                    Boolean($('form[data-role=email-with-possible-login]' + ' input[name=username]').valid());
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

                    if (customer.isLoggedIn() && quote.shippingMethod()['carrier_code'] !== 'freeshipping') {
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
            },
            checkPickupAvailability : function () {
                if (customer.isLoggedIn()) {
                    return false;
                } else if (quote.shippingMethod()) {
                    if(quote.shippingMethod()['carrier_code'] === 'freeshipping') {
                        return true;
                    }
                }

                return false;
            },
            getClassPickUp : function() {
                if (checkoutConfig.pickup_config.enable && checkoutConfig.pickup_config.enable == 1) {
                    return 'pick-up-store';
                }
                return '';
            },
            isPickUpScroll : function() {
                if (checkoutConfig.pickup_config.pickItems.length > 2) {
                    return true;
                }

                return false;
            },
            visiblePickUpStore : function () {
                return (quote.shippingMethod() && quote.shippingMethod().carrier_code == 'freeshipping') && stepNavigator.getActiveItemIndex() == 1;
            },
            getDataPickupStores : function() {
                return pickUpConfig.pickItems;
            },
            selectItemStore: function (item, storeId){
                var latitude = "-6.200000", longtitude = "106.816666", zoom = 5;

                if (typeof(event) != 'undefined' && typeof($(event.currentTarget).html()) != 'undefined') {
                    pickUpStore.getMap(item.latitude, item.longitude, 18);
                    pickUpStore.storeId(storeId._latestValue);
                    $('.select-store.message-error').addClass('hidden');
                    $('.pickup-store__info').find('div').removeClass('store-selected');
                    $(event.currentTarget).find('div').addClass('store-selected');
                } else {
                    pickUpStore.getMap(latitude, longtitude, zoom);
                }

                pickUpStore.setFieldAddress(item);
            },
            checkActiveMethod: function () {
                if (quote.shippingMethod() && stepNavigator.getActiveItemIndex() > 0) {
                    if(quote.shippingMethod()['carrier_code'] === 'freeshipping') {
                        return true;
                    }
                }
            },
            changeFieldPickUp: function (data, event){
                var value = $(event.target).val().trim(),
                    textError = $t('This is a required field.'),
                    targetElement = $(event.target),
                    showMessageError = false;

                if (targetElement.hasClass('mobile-number')) {
                    targetElement = targetElement.parent();
                }

                if (value != '') {
                    if ($(event.target).hasClass('phone')) {
                        value = value.trim();
                        if ($.isNumeric(value) && value.indexOf(' ') == -1) {
                            if (value.length < 7) {
                                textError = $t('Please enter more or equal than %1 symbols.').replace('%1', 7);
                                showMessageError = true;
                            } else if (value.length > 13 && typeof(window.telephonePrefix) == 'undefined') {
                                textError = $t('Please enter less or equal than %1 symbols.').replace('%1', 13);
                                showMessageError = true;
                            }
                        }else {
                            textError = $t('Please enter a valid number in this field.');
                            showMessageError = true;
                        }
                    }
                    if ($(event.target).hasClass('name')) {
                        if (value.length > 15) {
                            textError = $t('Please enter less or equal than %1 symbols.').replace('%1', 15);
                            showMessageError = true;
                        }
                    }
                } else {
                    showMessageError = true;
                }

                if (showMessageError) {
                    targetElement.next().find('span').text(textError);
                    targetElement.next().removeClass('hidden');
                } else {
                    targetElement.next().addClass('hidden');
                }
            },
            showValuePrefix: function() {
                if(typeof(window.telephonePrefix) !='undefined') {
                    return window.telephonePrefix;
                }
                return '';
            },
            telephoneLength: function() {
                if(typeof(window.telephoneLength) !='undefined') {
                    return window.telephoneLength;
                }
                return 9;
            },
            validatePickUpField: function () {
                if ($('[name="pickup[firstname]"]').val().trim() == '') {
                    $('[name="pickup[firstname]"]').next().removeClass('hidden');
                }
                if ($('[name="pickup[lastname]"]').val().trim() == '') {
                    $('[name="pickup[lastname]"]').next().removeClass('hidden');
                }
                if ($('[name="pickup[phone]"]').val().trim() == '') {
                    if(typeof(window.telephonePrefix) != 'undefined') {
                        $('[name="pickup[phone]"]').parent().next().removeClass('hidden');
                    } else {
                        $('[name="pickup[phone]"]').next().removeClass('hidden');
                    }
                }
                if (!customer.isLoggedIn()) {
                    Boolean($('form[data-role=email-with-possible-login]' + ' input[name=username]').valid());
                }
            },
            searchAndFilter: function (data, e) {
                var searchTerm = $(e.target).val(),
                    countStore = this.getDataPickupStores().length;

                if (searchTerm === '') {
                    $(".pickup-store li").show();
                } else {
                    $(".pickup-store li").each(function() {
                        var currentTextCity = $(this).find('.info.city').text(),
                            currentTextZip = $(this).find('.info.zip-code').text();

                        currentTextCity = currentTextCity.toUpperCase();
                        currentTextZip = currentTextZip.toUpperCase();
                        searchTerm = searchTerm.toUpperCase();
                        if (currentTextCity.indexOf(searchTerm) >= 0 || currentTextZip.indexOf(searchTerm) >= 0) {
                            $(this).show();
                        } else {
                            $(this).hide();
                            countStore = countStore - 1;
                        }
                    });
                }

                $(".search-box-container").find(".stores-count").text(this.storesCountText(countStore));
            },
            storesCountText: function (countStore) {
                return $t('Available in %1 Stores').replace('%1', countStore);
            },
            getLocation: function () {
                var self = this;
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function (position) {
                        pickUpStore.getMap(position.coords.latitude, position.coords.longitude, 18);
                    }, this.navigateFail.bind(self));
                } else {
                    console.log('Geolocation is not supported by this browser.');
                }
            },
            navigateFail: function (error) {
                // error param exists when user block browser location
                if (error.code == 1) {
                    console.log(error.message);
                }
            },
            /**
             * Select location for sipping.
             *
             * @returns void
             */
            selectForShipping: function () {
                this.validatePickUpField();
                if (pickUpStore.getStoreId() != null) {
                    var telephoneNumber = $('[name="pickup[phone]"]').val().trim();
                    if (typeof(window.telephonePrefix) != 'undefined') {
                        $('.form-shipping-address').find('#ShipMobileCode').val(window.telephonePrefix).trigger('change');
                        $('.form-shipping-address').find('input.mobile-number').val(telephoneNumber).trigger('change');
                        telephoneNumber = window.telephonePrefix + telephoneNumber;
                    } else {
                        $('.form-shipping-address').find('input[name="telephone"]').val(telephoneNumber).trigger('change');
                    }

                    $('.form-shipping-address').find('input[name="firstname"]').val($('[name="pickup[firstname]"]').val()).trigger('change');
                    $('.form-shipping-address').find('input[name="lastname"]').val($('[name="pickup[lastname]"]').val()).trigger('change');
                    $('.form-shipping-address').find('select[name="custom_attributes[district]"]').trigger('change');

                    if (!customer.isLoggedIn()) {
                        $('.action.primary.create-new-address').trigger('click');
                    }else{
                        var nextStep = true;
                        $('.fieldset.pickup-customer').find('.field-error').each(function () {
                            if (!$(this).hasClass('hidden')) {
                                nextStep = false;
                            }
                        });

                        if (nextStep) {
                            var location = checkoutConfig.pickup_config.pickItems[pickUpStore.getStoreId()],
                                regionId = checkoutConfig.pickup_config.jsonRegion[location.state],
                                address = $.extend(
                                    {},
                                    addressConverter.formAddressDataToQuoteAddress({
                                        firstname: $('[name="pickup[firstname]"]').val(),
                                        lastname: $('[name="pickup[lastname]"]').val(),
                                        street: [location.address1, location.address2],
                                        city: location.city,
                                        postcode: location.zipCode,
                                        'country_id': 'ID',
                                        telephone: telephoneNumber,
                                        'region_id': regionId,
                                        'save_in_address_book': 0,
                                        'custom_attributes': {
                                            'district': location.district
                                        }
                                    }));

                            address = pickupAddressConverter.formatAddressToPickupAddress(address);
                            // this.selectedLocation(location);
                            selectShippingAddress(address);
                            checkoutData.setSelectedShippingAddress(address.getKey());
                            checkoutData.setSelectedPickupAddress(
                                addressConverter.quoteAddressToFormAddressData(address)
                            );

                            $('#shipping-method-buttons-container button').trigger('click');
                        }
                    }
                } else {
                    $('.select-store.message-error').removeClass('hidden');
                }
                //todo show error
            },
            setActiveMethod: function(method) {
                if (quote.shippingMethod() && quote.shippingMethod().carrier_code == method.carrier_code) {
                    return true;
                } else if (!quote.shippingMethod() && method.carrier_code == "flatrate") {
                    selectShippingMethodAction(method);
                    return true;
                }
                return false;
            },
            selectShippingMethod: function (shippingMethod) {
                var latitude = "-6.200000", longitude = "106.816666", zoom = 5,
                    resetDataForm = false;

                if (quote.shippingMethod() && quote.shippingMethod().carrier_code == 'freeshipping') {
                    resetDataForm = true;
                }

                selectShippingMethodAction(shippingMethod);
                checkoutData.setSelectedShippingRate(shippingMethod['carrier_code'] + '_' + shippingMethod['method_code']);

                if(shippingMethod['carrier_code'] === 'freeshipping') {
                    $('.fieldset.pickup-customer').find('input[type="text"]').val('');
                    $('.fieldset.pickup-customer').find('.field-error').addClass('hidden');
                    $('.select-store.message-error.field-error').addClass('hidden');
                    if (!resetDataForm) {
                        pickUpStore.getMap(latitude, longitude, zoom);
                        $('.pickup-store__info').find('div').removeClass('store-selected');
                        pickUpStore.storeId(null);
                    }
                } else if (resetDataForm) {
                    $('#co-shipping-form').trigger('reset');
                    $('#co-shipping-form').find('[name="country_id"]').val(window.defaultCountry);
                    if (typeof(window.telephonePrefix) != 'undefined') {
                        $('#ShipMobileCode').val(window.telephonePrefix);
                    }
                    $('#co-shipping-form').find('.field._required').removeClass('_error');
                    $('#co-shipping-form').find('.field-error').hide();
                }

                return true;
            }
        };

        return function (target) {
            return target.extend(mixin);
        };
    }
);
