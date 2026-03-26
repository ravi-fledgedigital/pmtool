var config = {
    map: {
        "*": {
            xmlCharactersValidate: "OnitsukaTiger_NetsuiteOrderSync/js/xml-characters-validate"
        }
    },
    config: {
        mixins: {
            'Magento_Ui/js/lib/validation/validator': {
                'OnitsukaTiger_NetsuiteOrderSync/js/validator-mixin': true
            }
        }
    }
};
