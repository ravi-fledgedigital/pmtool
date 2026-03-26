define(
    [
        'ko',
        "jquery",
        "Magento_Checkout/js/model/quote",
        "uiComponent",
        "swdkpostcodepm",
        "domReady!"
    ]
    , function (ko, $, quote, Component, SWD) {
        return Component.extend({
            defaults: {
                template: 'Seoulwebdesign_Kpostcode/checkoutkpostcodepm'
            },
            element: Component.element,
            FirstTime: 1,
            lstInputInit: [],
            element_code: 'new',
            getPopupTitle: ko.observable(),
            kpcode: null,
            initialize: function () {
                this._super();
                this.getPopupTitle(window.kPostCodeConfig.popupTitle);
                this.kpcode = SWD;
            },
            initModalShow: function (element) {
                var self = this;
                $(element).closest("form").find('.init-modal-external').removeClass('not-active');
                if (!window.runsetuppm) {
                    window.runsetuppm = true;
                    self.kpcode.setup({
                        postCodeEleWapper: $(element).find(".swd-kpostcode-popup")[0],
                        //postCodeEleWapper: $('#' + self.element_code),
                        btnClose: $(element).find(".kpostcodeIconX")[0],
                        postCodediv: $(element).find("#postcode-area-pm"),
                        //postCodediv: $('#' + self.getPostcodeAreaId()),
                        postCodeIcoStr: "<img src='" + window.kPostCodeConfig.postCodeIcoStr + "'/>",
                        language: window.kPostCodeConfig.language,
                        version: window.kPostCodeConfig.version,
                        showJibun: window.kPostCodeConfig.showJibun
                    });
                }
                var refreshIntervalId1 = setInterval(function () {
                    if ($('.checkout-billing-address').find("input[name='billing-address-same-as-shipping']").length) {
                        $('body').on('click', "input[name='billing-address-same-as-shipping']", function () {
                            self.initButtonsBox($(this))
                            setTimeout(function () {
                                self.initButtonsBox($(this));

                            },200);
                            setTimeout(function () {
                                self.initButtonsBoxPayment();
                            },400);
                        });
                        clearInterval(refreshIntervalId1);
                    }
                },200);
            },
            initButtonsBoxPayment: function(){
                var self = this;
                let form = $(".checkout-payment-method .payment-method._active .billing-address-form form");
                let country_id = form.find("select[name=country_id]");
                let address0 = form.find("input[name='street[0]']");
                let postcode = form.find("input[name='postcode']");
                let city = form.find("input[name='city']");

                self.kpcode.init(country_id, self.element_code, function (ob) {
                    if(address0.length) {
                        address0.val(ob.getAddress()).trigger('change');
                        city.val(ob.getCity()).trigger('change');
                        postcode.val(ob.getZipCode()).trigger('change');
                    }else{
                        var formElement = address0.prevObject;
                        formElement.find("input[name='street[0]']").val(ob.getAddress()).trigger('change');
                        formElement.find("input[name='city']").val(ob.getCity()).trigger('change');
                        formElement.find("input[name='postcode']").val(ob.getZipCode()).trigger('change');
                    }
                }, [
                    {
                        element: address0,
                        type: "text"
                    },
                    {
                        element: postcode,
                        type: "text"
                    }
                ]);
            },
            initButtonsBox:function(element){
                var self = this;
                let form = element.closest(".checkout-billing-address").find('.billing-address-form form');
                let country_id = form.find("select[name=country_id]");
                let address0 = form.find("input[name='street[0]']");
                let postcode = form.find("input[name='postcode']");
                let city = form.find("input[name='city']");

                self.kpcode.init(country_id, self.element_code, function (ob) {
                    debugger;
                    address0.val(ob.getAddress()).change();
                    city.val(ob.getCity()).change();
                    postcode.val(ob.getZipCode()).change();
                    self.kpcode.formElementPopup.find("input[name='street[0]']").val(ob.getAddress()).trigger('change');
                    self.kpcode.formElementPopup.find("input[name='postcode']").val(ob.getZipCode()).val(ob.getZipCode()).trigger('change');
                    self.kpcode.formElementPopup.find("input[name='city']").val(ob.getCity()).trigger('change');
                }, [
                    {
                        element: address0,
                        type: "text"
                    },
                    {
                        element: postcode,
                        type: "text"
                    }
                ]);
            },
            initModalShowRenderer: function () {
                var element = window.kpostCodeElementTrigger;
                var self = this;
                $(element).closest("form").find('.init-modal-external').removeClass('not-active');
                if (!window.runsetuppm) {
                    window.runsetuppm = true;
                    self.kpcode.setup({
                        postCodeEleWapper: $(element).find(".swd-kpostcode-popup")[0],
                        //postCodeEleWapper: $('#' + self.element_code),
                        btnClose: $(element).find(".kpostcodeIconX")[0],
                        postCodediv: $(element).find("#postcode-area-pm"),
                        //postCodediv: $('#' + self.getPostcodeAreaId()),
                        postCodeIcoStr: "<img src='" + window.kPostCodeConfig.postCodeIcoStr + "'/>",
                        language: window.kPostCodeConfig.language,
                        version: window.kPostCodeConfig.version,
                        showJibun: window.kPostCodeConfig.showJibun
                    });
                }
                $('body').on('click', "input[name='billing-address-same-as-shipping']", function () {
                    let form = $(this).closest(".checkout-billing-address").find('.billing-address-form form');
                    let country_id = form.find("select[name=country_id]");
                    let address0 = form.find("input[name='street[0]']");
                    let address1 = form.find("input[name='street[1]']");
                    let postcode = form.find("input[name='postcode']");
                    let city = form.find("input[name='city']");

                    self.kpcode.init(country_id, self.element_code, function (ob) {
                        debugger;
                        address0.val(ob.getAddress()).change();
                        city.val(ob.getCity()).change();
                        postcode.val(ob.getZipCode()).change();
                        self.kpcode.formElementPopup.find("input[name='street[0]']").val(ob.getAddress()).trigger('change');
                        self.kpcode.formElementPopup.find("input[name='postcode']").val(ob.getZipCode()).val(ob.getZipCode()).trigger('change');
                        self.kpcode.formElementPopup.find("input[name='city']").val(ob.getCity()).trigger('change');
                    }, [
                        {
                            element: address0,
                            type: "text"
                        },
                        {
                            element: postcode,
                            type: "text"
                        }
                    ]);
                });
            },
            isInDaumMode: function () {
                return window.kPostCodeConfig.version === 'daum'
            },
            getItemCssClass: function () {
                var self = this;
                return self.element_code;
            },
            getPostcodeAreaId: function () {
                var self = this;
                return 'postcode-area-' + self.element_code;
            }
        });
    });
