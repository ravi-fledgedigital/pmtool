define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/confirm'
], function ($, $t, confirmation) {
    'use strict'

    function KPCODEPM() {
        this.countryCode = 'KR';
        this.postCodeEleWapper = '#swd-kpostcode-popup';
        this.postCodediv = '#postcode-area-pm';
        this.postCodeIcoStr = null;
        this.language = 'en';
        this.btnClose = $('#kpostcodeIconX');
        this.formElementPopup = null;
        this.currentProcess = null;
        this.callbackFuc = {};
        this.showJibun = true;

        this.exceCallbackFuc = function (kpOj) {
            var $this = this
            eval('$this.callbackFuc.' + this.currentProcess + '(kpOj)')
            this.close()
        }

        this.setup = function (option) {
            var $this = this
            this.language = option.language
            this.postCodeIcoStr = option.postCodeIcoStr == null ? 'Korea Postcode' : option.postCodeIcoStr
            this.postCodeEleWapper = option.postCodeEleWapper == null ? '#swd-kpostcode-popup' : option.postCodeEleWapper
            this.postCodediv = option.postCodediv == null ? '#postcode-area-pm' : option.postCodediv
            this.btnClose = option.btnClose == null ? $('#kpostcodeIconX') : $(option.btnClose) // option.btnClose;
            this.showJibun = option.showJibun == 1 ? 1 : 0;

            $this.btnClose.on("click", function (e) {
                $this.close()
            })
        }

        this.init = function (countryElement, type, callback, elementEventHook) {
            var $this = this;
            var formElementPopup = countryElement.closest('form');
            var userAgent = navigator.userAgent || navigator.vendor || window.opera;
            eval('$this.callbackFuc.' + type + ' = callback');
            countryElement.data('type', type);
            this.detectShowIco(countryElement, type);


            countryElement.on("change", function (e) {
                $this.detectShowIco(countryElement, type)
            });
            $(document).on('click', "input[name='postcode'] , input[name='street[0]'] , input[name='city'] , input[id='street_1']", function (e) {
                $(".web_kr_ko input[id='street_1'],.web_kr_ko input[name='street[0]'],.web_kr_ko input[name='postcode']").attr('readonly',"true");
                $(".web_kr_ko input[id='street_1'],.web_kr_ko input[name='street[0]'],.web_kr_ko input[name='postcode']").attr('autocomplete',"off");
                if ($this.countryCode == countryElement.val()) {
                    $this.showPopup(type, formElementPopup);
                }
                e.preventDefault();
                return false
            });
        }
        this.detectShowIco = function (countryElement, type) {
            if (this.countryCode == countryElement.val()) { // Is Korean then popup form
                this.addPostCodeIcoAfterEle(countryElement, type)
            } else {
                this.removePostCodeIcoAfterEle(countryElement)
            }
        }

        this.popupSearchForm = function (countryElement) {
            if (this.countryCode == countryElement.val()) { // Is Korean then popup form
                this.show()
                this.addPostCodeIcoAfterEle(countryElement)
            } else {
                this.removePostCodeIcoAfterEle(countryElement)
            }
        }

        this.showPostCodeIco = function (countryElement) {
            if (this.countryCode == countryElement.val()) { // Is Korean then popup form
                this.addPostCodeIcoAfterEle(countryElement)
            } else {
                this.removePostCodeIcoAfterEle(countryElement)
            }
        }

        this.show = function (type) {
            this.currentProcess = type;
            $(this.postCodeEleWapper).fadeIn(500);
            if (window.kPostCodeConfig.version === 'daum') {
                this.daumPostcode();
            }
        }
        this.showPopup = function (type, formElementPopup) {
            this.currentProcess = type;
            this.formElementPopup = formElementPopup;
            if(formElementPopup.find('.kpostcode-popup').length) {
                formElementPopup.find('.kpostcode-popup').fadeIn(500);
            }else{
                formElementPopup.closest('#co-payment-form').find('.kpostcode-popup').fadeIn(500);
            }
            if (window.kPostCodeConfig.version === 'daum') {
                this.daumPostcode();
            }
        }
        this.close = function () {
            $(this.postCodeEleWapper).fadeOut(500);
            this.formElementPopup.find('.kpostcode-popup').fadeOut(500);
        }

        this.addPostCodeIcoAfterEle = function ($Ele, type) {
            var $this = this;
            var streetAddressBlock = $($Ele.closest('form').find('.field.street')[0]);
            var formElementPopup = $Ele.closest('form');
            var idElement = '#'+this.priGetEleID($Ele);
            if ($($Ele.closest('form').find('.swdkpostcode-ico')).length == 0) {
                $('<div/>', {
                    id: this.priGetEleID($Ele),
                    class: 'swdkpostcode-ico',
                    html: '<button type="button">' + $t('Kpostcode') + '</button>'
                }).prependTo(streetAddressBlock);
                $(document).on('click', idElement, function (e) {
                    $this.showPopup(type,formElementPopup);
                    e.preventDefault();
                    return false;
                });
                if (window.kPostCodeConfig.mode === 'iframe') {
                    this.changePopupPosition($($Ele.closest('form').find('.swdkpostcode-ico')));
                }
            }
        }

        this.removePostCodeIcoAfterEle = function ($Ele) {
            $('#' + this.priGetEleID($Ele)).remove()
        }
        this.priGetEleID = function ($Ele) {
            try {
                var id = 'swdkpostcode-ico' + $Ele.attr('id').replace(':', '_')
                return id
            } catch (e) {

            }
        }

        this.daumPostcode = function () {
            let $this = this
            daum.postcode.load(function () {
                new daum.Postcode({
                    oncomplete: function (data) {
                        if ($this.language == 'en') {
                            var kpOj = new KpostcodeOb({
                                city: data.sido + ' ' + data.sigungu,
                                zipcode: data.zonecode,
                                // need config to use old address or not
                                address: data.addressEnglish + (this.showJibun ? ' (' + data.jibunAddressEnglish + ')' : '')
                            })
                        }
                        if ($this.language == 'ko') {
                            var kpOj = new KpostcodeOb({
                                city: data.sido + ' ' + data.sigungu,
                                zipcode: data.zonecode,
                                address: data.address + (this.showJibun ? ' (' + data.jibunAddress + ')' : '')
                            })
                        }
                        $this.exceCallbackFuc(kpOj)
                    },
                }).embed(document.getElementById($this.postCodediv.attr('id')))
            })
        }

        this.changePopupPosition = function (afterElementId) {
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

    return new KPCODEPM();
})
