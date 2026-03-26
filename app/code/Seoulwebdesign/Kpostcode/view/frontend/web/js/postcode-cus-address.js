define([
    "jquery",
    "swdkpostcode"
], function ($,SWD) {
    "use strict";
    return function (config) {
        if (window.kPostCodeConfig.isEnable == 1) {
            SWD.KPCODE.setup(window.kPostCodeConfig);
            //------------------------------------
            SWD.KPCODE.init($("#country"), "newaddressaccount_and_multiaddresscheckout", function (ob) {
                console.log(ob);
                $("#zip").val(ob.getZipCode());
                $("#city").val(ob.getCity());
                $("#street_1").val(ob.getAddress());
            }, [
                {
                    element: $("#street_1"),
                    type: "text"
                },
                {
                    element: $("#city"),
                    type: "text"
                },
                {
                    element: $("input[name='postcode']"),
                    type: "text"
                }
            ]);
        }
    }
});
