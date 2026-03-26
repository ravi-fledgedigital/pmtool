define(['jquery'], function ($) {
    'use strict';

    return function () {
        $.validator.addMethod(
            'validate-key',
            function (v) {
                if (v == '******' || v === '------') {
                    return true;
                }
                return (/^-----BEGIN (RSA )?PRIVATE KEY-----.*-----END (RSA )?PRIVATE KEY-----$/s).test(v);
            },
            $.mage.__('Private Key field is invalid. It must include header ' +
                'and footer of the private key. Please check and try again')
        )
    }
});
