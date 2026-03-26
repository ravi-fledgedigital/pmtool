define([
    'jquery',
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/form',
    'Magento_Ui/js/lib/view/utils/dom-observer'
], function ($, _, uiRegistry, Form, DomObserver) {
    return Form.extend({
        initialize: function () {
            this._super();

            DomObserver.get('.main-col [data-index=appearence] select', function (elem) {
                const origValue = this.source.data.general.appearence;

                $(elem).on('change', function () {
                    if (this.value !== origValue) {
                        $('a#tab_display').hide();
                    } else {
                        $('a#tab_display').show();
                    }
                })
            }.bind(this))

            DomObserver.get('.main-col [data-index=display]', function (elem) {
                this.markConfiguredAttributeLabels();
            }.bind(this));

            DomObserver.get('.main-col [data-index=template_id]', function (elem) {
                this.adjustTemplatesSize(elem);
            }.bind(this));

            DomObserver.get('.main-col [data-index=display] .admin__control-textarea', function (elem) {
                var container = elem.closest('[data-index=display_data]');

                this.previewTemplates(container);
            }.bind(this));

            this.initVariableList();

            return this;
        },

        initVariableList: function () {
            DomObserver.get('.admin__page-nav-items', function (elem) {
                $('a.admin__page-nav-link', $(elem)).on('click', function (e) {
                    const variableList = $('.side-col .variablelist');

                    if (this.id == 'tab_display') {
                        variableList.show();
                    } else {
                        variableList.hide();
                    }
                });
            });
        },

        adjustTemplatesSize: function (elem) {
            var options = $('.template-preview', $(elem));

            options.each(function () {
                const previewElem = this.firstElementChild;

                if (previewElem) {
                    const maxSize = Math.max(previewElem.offsetWidth, previewElem.offsetHeight);

                    let adjustment = 0;

                    if (maxSize >= 95 || maxSize <= 45) {
                        adjustment = 0.7 * (100 / maxSize);
                    }

                    if (adjustment) {
                        previewElem.style.transform = 'scale(' + adjustment + ')'
                    }
                }
            })
        },

        markConfiguredAttributeLabels: function () {
            const labelData = this.source.data;

            if (labelData.general.type == 'attribute') {
                const data = labelData.display;

                for (let optionId in data) {
                    let attributeLabel = $(
                        '[data-index='
                        + optionId
                        + ']>.fieldset-wrapper-title>.admin__collapsible-title>span:first-child'
                    );

                    let configured = false;
                    let marker = $('<span class="marker"/>');

                    if (data[optionId].list && data[optionId].list.display_data.display_id) {
                        configured = true;
                        let listMarker = $('<span/>').html('list&check;');
                        marker.append(listMarker);
                    }

                    if (data[optionId].view && data[optionId].view.display_data.display_id) {
                        configured = true;
                        let viewMarker = $('<span/>').html('view&check;');
                        marker.append(viewMarker);
                    }

                    if (data[optionId].both && data[optionId].both.display_data.display_id) {
                        configured = true;
                        let viewMarker = $('<span/>').html('both&check;');
                        marker.append(viewMarker);
                    }

                    if (configured) {
                        marker.insertAfter(attributeLabel);
                    }
                }
            }
        },

        previewTemplates: function (container) {
            this.requestTemplates(null, $(container));

            var throttler = _.throttle(this.requestTemplates, 1000).bind(this);

            $('input[type=text], input[type=file], textarea', $(container)).on('keyup', throttler);
        },

        requestTemplates: function (e, element) {
            let data = {};
            let imageData;

            const fieldset     = element ? element : $(e.target).parents('[data-index=display_data]');
            const fields       = $('input[type=text], input[type=file], textarea', fieldset);
            const viewFieldset = fieldset.parents('[data-index]');
            const viewKey      = viewFieldset.attr('data-index');

            if (viewFieldset.is('.view')) {
                let classes = '';

                viewFieldset.filter('.view').first()[0].classList.forEach(className => {
                    classes = classes + '.' + className;
                });

                data['class'] = classes;
            }

            if (viewFieldset.is('.list')) {
                let classes = '';

                viewFieldset.filter('.list').first()[0].classList.forEach(className => {
                    classes = classes + '.' + className;
                });

                data['class'] = classes;
            }

            if (this.source.data.general.type == 'attribute') {
                const optionKey = viewFieldset.parents('[data-index]').attr('data-index');

                imageData = this.source.data.display[optionKey][viewKey].display_data.image;
            } else {
                imageData = this.source.data.display[viewKey].display_data.image;
            }

            if (imageData.length) {
                data['image_url'] = imageData[0].url;
            }

            fields.each(function () {
                if (this.name) {
                    const key = this.name.match(/title|description|style|url|file/is);

                    if (key && key[0]) {
                        data[key[0]] = this.value;
                    }
                }
            });

            const imagePreview = $('.file-uploader-preview .preview-link', fieldset);

            if (imagePreview.length) {
                data['image_url'] = imagePreview.attr('href');
            }

            data['form_key'] = window.FORM_KEY;

            $.ajax({
                url:      this.source.data.general.template_preview_url,
                method:   'POST',
                dataType: 'json',
                data:     data,
                success:  function (response) {
                    if (response.success) {
                        for (key in response.preview) {
                            $('[data-index="style"] .CodeMirror').removeClass('_error');
                            const templateOption = $('[data-index=template_id] input[value=' + key + ']', fieldset);

                            $('label', templateOption.parent()).html(response.preview[key]);
                        }

                        this.adjustTemplatesSize(fieldset);
                    } else {
                        $('[data-index="style"] .CodeMirror').addClass('_error');
                        console.error(response.message);
                    }

                }.bind(this)
            })
        },
    })
});
