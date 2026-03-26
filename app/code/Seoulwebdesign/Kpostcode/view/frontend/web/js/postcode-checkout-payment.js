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
                    if ($('.field-select-billing').find("select[name='billing_address_id']").length) {
                        $('.field-select-billing').find("select[name='billing_address_id']").each(function( index, element ) {
                            $(element).change(function (){
                                let fieldset = $(element).closest("fieldset");
                                let form = fieldset.find("form");
                                let country_id = form.find("select[name=country_id]");
                                let address0 = form.find("input[name='street[0]']");
                                let address1 = form.find("input[name='street[1]']");
                                let postcode = form.find("input[name='postcode']");
                                let city = form.find("input[name='city']");

                                self.kpcode.init(country_id, self.element_code, function (ob) {
                                    address0.val(ob.getAddress()).change();
                                    city.val(ob.getCity()).change();
                                    postcode.val(ob.getZipCode()).change();
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
                        });
                        clearInterval(refreshIntervalId1);
                    }
                },200);
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
