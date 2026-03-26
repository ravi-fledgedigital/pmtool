define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/confirm'
], function ($, $t , confirmation) {
    'use strict'
    if (typeof SWD == 'undefined') {
        var SWD = {}
    }
    SWD.KPCODE = {
        countryCode: 'KR',
        postCodeEleWapper: '#swd-kpostcode-popup',
        postCodediv: '#postcode-area',
        postCodeIcoStr: null,
        language: 'en',
        btnClose: $('#kpostcodeIconX'),
        currentProcess: null,
        callbackFuc: {},
        showJibun : 1,
        exceCallbackFuc: function (kpOj) {
            eval('SWD.KPCODE.callbackFuc.' + this.currentProcess + '(kpOj)')
            this.close()
        },
        setup: function (option) {
            var $this = this
            this.language = option.language
            this.postCodeIcoStr = option.postCodeIcoStr == null ? 'Korea Postcode' : option.postCodeIcoStr
            this.postCodeEleWapper = option.postCodeEleWapper == null ? '#swd-kpostcode-popup' : option.postCodeEleWapper
            this.postCodediv = option.postCodediv == null ? '#postcode-area' : option.postCodediv
            this.btnClose = option.btnClose == null ? $('#kpostcodeIconX') : $(option.btnClose) // option.btnClose;
            this.showJibun = option.showJibun == 1 ? 1 : 0;

            $this.btnClose.on("click", function (e) {
                $this.close()
            })
        },
        init: function (countryElement, type, callback, elementEventHook) {
            eval('SWD.KPCODE.callbackFuc.' + type + ' = callback');
            var userAgent = navigator.userAgent || navigator.vendor || window.opera;
            countryElement.data('type', type)
            this.detectShowIco(countryElement, type)
            var $this = this

            countryElement.on("change", function (e) {
                $this.detectShowIco(countryElement, type)
            });
            $(document).on('click', "input[name='postcode'] , input[name='street[0]'] , input[name='city'] , input[id='street_1']", function (e) {
                $("input[id='street_1'],input[name='street[0]'],input[name='postcode']").attr('readonly',"true");
                $("input[id='street_1'],input[name='street[0]'],input[name='postcode']").attr('autocomplete',"off");
                if ($this.countryCode == countryElement.val()) {
                    $this.show(type)
                }
                e.preventDefault();
                return false
            });
        },
        detectShowIco: function (countryElement, type) {
            if (this.countryCode == countryElement.val()) { // Is Korean then popup form
                this.addPostCodeIcoAfterEle(countryElement, type)
            } else {
                this.removePostCodeIcoAfterEle(countryElement)
            }
        },
        popupSearchForm: function (countryElement) {
            if (this.countryCode == countryElement.val()) { // Is Korean then popup form
                this.show()
                this.addPostCodeIcoAfterEle(countryElement)
            } else {
                this.removePostCodeIcoAfterEle(countryElement)
            }
        },
        showPostCodeIco: function (countryElement) {
            if (this.countryCode == countryElement.val()) { // Is Korean then popup form
                this.addPostCodeIcoAfterEle(countryElement)
            } else {
                this.removePostCodeIcoAfterEle(countryElement)
            }
        },
        show: function (type) {
            this.currentProcess = type
            $(this.postCodeEleWapper).fadeIn(500)
            if (window.kPostCodeConfig.version === 'daum') {
                this.daumPostcode()
            }
        },
        close: function () {
            $(this.postCodeEleWapper).fadeOut(500)
        },
        addPostCodeIcoAfterEle: function ($Ele, type) {
            var idElement = '#'+this.priGetEleID($Ele);
            var streetAddressBlock = $($Ele.closest('form').find('.field.street')[0])
            $('<div/>', {
                id: this.priGetEleID($Ele),
                class: 'swdkpostcode-ico',
                html: '<button type="button">'+$t('Kpostcode')+'</button>'
            }).prependTo(streetAddressBlock);
            $(document).on('click', idElement, function (e) {
                SWD.KPCODE.show(type);
                e.preventDefault();
                return false
            });
            if (window.kPostCodeConfig.mode === 'iframe') {
                this.changePopupPosition(this.priGetEleID($Ele))
            }
        },
        removePostCodeIcoAfterEle: function ($Ele) {
            $('#' + this.priGetEleID($Ele)).remove()
        },
        priGetEleID: function ($Ele) {
            try {
                var id = 'swdkpostcode-ico' + $Ele.attr('id').replace(':', '_')
                return id
            } catch (e) {

            }
        },
        daumPostcode: function () {
            var $this = this
            daum.postcode.load(function () {
                new daum.Postcode({
                    oncomplete: function (data) {

                        if ($this.language == 'en') {
                            var kpOj = new KpostcodeOb({
                                city: data.sido + ' ' + data.sigungu,
                                zipcode: data.zonecode,
                                // need config to use old address or not
                                address: data.addressEnglish + ($this.showJibun?' (-' + data.jibunAddressEnglish + ')':'')
                            })
                        }
                        if ($this.language == 'ko') {
                            var kpOj = new KpostcodeOb({
                                city: data.sido + ' ' + data.sigungu,
                                zipcode: data.zonecode,
                                address: data.address + ($this.showJibun?' (-' + data.jibunAddress + ')':'')
                            })
                        }
                        SWD.KPCODE.exceCallbackFuc(kpOj)
                    },
                }).embed(document.getElementById('postcode-area'))
            })
        },
        changePopupPosition: function (afterElementId) {
            $('.kpostcode-popup').addClass('iframe-mode').insertAfter('#' + afterElementId)
        }
    }

    // Class KpostcodeOb
    function KpostcodeOb(option) {
        var _city = option.city
        var _zipcode = option.zipcode
        var _address = option.address
        this.getCity = function () {
            return _city
        }
        this.getAddress = function () {
            return _address
        }
        this.getZipCode = function () {
            return _zipcode
        }
    }

    return SWD
})
