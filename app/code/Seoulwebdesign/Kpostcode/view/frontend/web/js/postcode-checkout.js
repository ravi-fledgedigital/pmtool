define(
    [
        'ko',
        "jquery",
        "Magento_Checkout/js/model/quote",
        "uiComponent",
        "swdkpostcode",
        "domReady!"
    ]
    , function (ko, $, quote, Component, SWD) {
        return Component.extend({
            element: Component.element,
            FirstTime: 1,
            lstInputInit: [],
            getPopupTitle: ko.observable(),
            initialize: function () {
                this._super();
                this.getPopupTitle(window.kPostCodeConfig.popupTitle);
            },
            initModalShow: function (element) {
                var self = this;
                if (quote.shippingAddress() && window.kPostCodeConfig.isEnable == 1) {
                    if (self.FirstTime == 1) {
                        var refreshIntervalId = setInterval(function () {
                            var form = $(element).closest("form");
                            var country_id = form.find("select[name=country_id]");
                            if (country_id.length) {
                                var address0 = form.find("input[name='street[0]']");
                                var address1 = form.find("input[name='street[1]']");
                                var postcode = form.find("input[name='postcode']");
                                var city = form.find("input[name='city']");
                                if (!window.runsetup) {
                                    window.runsetup = true;
                                    SWD.KPCODE.setup({
                                        postCodeEleWapper: $(element).find(".swd-kpostcode-popup")[0],
                                        btnClose: $(element).find(".kpostcodeIconX")[0],
                                        postCodediv: $(element).find("#postcode-area"),
                                        postCodeIcoStr: "<img src='" + window.kPostCodeConfig.postCodeIcoStr + "'/>",
                                        language: window.kPostCodeConfig.language,
                                        version: window.kPostCodeConfig.version,
                                        showJibun: window.kPostCodeConfig.showJibun
                                    });
                                }
                                SWD.KPCODE.init(country_id, "newaddressaccount_and_multiaddresscheckout", function (ob) {
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
                                clearInterval(refreshIntervalId);
                            }
                        });
                        self.FirstTime = 0;
                    }
                }
            },
            isInDaumMode: function () {
                return window.kPostCodeConfig.version === 'daum'
            }
        });
    });
