var config = {
    shim: {
        'Amasty_Base/js/lib/es6-collections': {
            deps: ['Amasty_Rma/vendor/amcharts4/plugins/polyfill.min']
        },
        'Amasty_Rma/vendor/amcharts4/core.min': {
            deps: ['Amasty_Base/js/lib/es6-collections']
        },
        'Amasty_Rma/vendor/amcharts4/charts': {
            deps: [
                'Amasty_Rma/vendor/amcharts4/core.min'
            ]
        },
        'Amasty_Rma/vendor/amcharts4/animated': {
            deps: ['Amasty_Rma/vendor/amcharts4/core.min']
        }
    }
};
