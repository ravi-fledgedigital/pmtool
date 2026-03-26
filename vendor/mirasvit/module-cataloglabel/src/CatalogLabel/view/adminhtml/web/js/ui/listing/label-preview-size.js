require([
    'jquery'
], function ($) {
    'use strict';
    $(document).ready(function(){
        $(document).ajaxStop(function () {
            setTimeout(function () {
                $('.mst-cataloglabel-preview__label').each(function () {
                    const previewElem = this.firstElementChild;

                    if (previewElem) {
                        const maxSize  = Math.max(previewElem.offsetWidth, previewElem.offsetHeight);
                        let adjustment = 0;

                        if (maxSize >= 70 || maxSize <= 30) {
                            adjustment = 0.9 * (80 / maxSize);
                        }

                        if (adjustment) {
                            previewElem.style.transform = 'scale(' + adjustment + ')';
                        }
                    }
                })
            }, 100);
        });
    });
});
