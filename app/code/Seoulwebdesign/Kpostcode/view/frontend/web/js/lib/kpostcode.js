define([
    'jquery',
    'mage/translate',
], function ($) {
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

            $this.btnClose.click(function () {
                $this.close()
            })
        },
        init: function (countryElement, type, callback, elementEventHook) {
            eval('SWD.KPCODE.callbackFuc.' + type + ' = callback')
            countryElement.data('type', type)
            this.detectShowIco(countryElement, type)
            var $this = this

            countryElement.change(function () {
                $this.detectShowIco(countryElement, type)
            })
            if (typeof elementEventHook != 'undefined') {
                for (var i = 0; i < elementEventHook.length; i++) {
                    var tmpOb = elementEventHook[i];
                    if (tmpOb.element) {
                        switch (tmpOb.type) {
                            case 'text':
                            default : {
                                tmpOb.element.click(function () {
                                    if ($this.countryCode == countryElement.val()) {
                                        var isShowPopup = confirm($.mage.__('대한민국 우편번호 찾기를 사용하시겠습니까?'))
                                        if (isShowPopup) {
                                            $this.show(type)
                                        }
                                    }
                                })
                            }
                        }
                    }
                }
            }
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
            var streetAddressBlock = $($Ele.closest('form').find('.field.street')[0])
            $('<div/>', {
                id: this.priGetEleID($Ele),
                class: 'swdkpostcode-ico',
                html: '<button type="button">우편번호 검색</button>',
                click: function () {
                    SWD.KPCODE.show(type)
                }
            }).appendTo(streetAddressBlock)
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
