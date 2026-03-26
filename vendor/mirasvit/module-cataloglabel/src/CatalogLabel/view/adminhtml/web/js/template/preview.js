define([
    'jquery',
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/lib/view/utils/dom-observer'
], function ($, _, Registry, DomObserver) {
    $.widget('mst.templatePreview', {
        previewUrl: '',

        _create: function () {
            this.previewUrl = this.options.previewUrl;

            var trottler = _.throttle(this.updatePreview, 1000).bind(this);

            DomObserver.get('.main-col textarea[name=style]', function (elem) {
                this.updatePreview();

                $('.main-col textarea').on('keyup', trottler);
                $('.main-col input').on('keyup', trottler);
            }.bind(this));
        },

        updatePreview: function() {
            $(".loader-wrapper").show();

            let data = Registry.get('cataloglabel_template_form.cataloglabel_template_form').source.data;

            $('.main-col input, .main-col textarea').each(function (elem) {
                if (this.name) {
                    data[this.name] = this.value;
                }
            });

            data['preview'] = '';

            $.ajax({
                url:      this.previewUrl,
                method:   'POST',
                dataType: 'json',
                data:     data,
                success:  function (response) {
                    $(".loader-wrapper").hide();
                    $('.mst_cataloglabel_template_preview .htmlcontent').html(response.html);
                    $('.mst_cataloglabel_template_preview .style').html(response.styles);
                },
                error: function(response) {
                    console.log(response);
                    $(".loader-wrapper").hide();
                },
            })
        }
    });

    return $.mst.templatePreview;
})
