/**
 * Attach file
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'Amasty_Rma/js/modal/alert/ammodal-popup'
], function (Abstract, $, malert) {
    'use strict';

    return Abstract.extend({
        defaults: {
            chatDeleteUrl: '',
            chatUploadUrl: '',
            files: '',
            filesData: [],
            formKeySelector: '[name="form_key"]',
            links: {
                files: '${ $.provider }:${ $.dataScope }'
            }
        },

        initObservable: function () {
            this._super()
                .observe([
                    'files',
                    'filesData'
                ]);

            return this;
        },

        processingFile: function (filesFromForm) {
            var formData = new FormData(),
                result;

            $.each(filesFromForm, function (i, file) {
                formData.append(file.name.substr(0, file.name.lastIndexOf('.')), file);
            });
            formData.append('form_key', $(this.formKeySelector).val());

            $('body').trigger('processStart');
            $.ajax({
                showLoader: true,
                url: this.chatUploadUrl,
                processData: false,
                contentType: false,
                global: false,
                data: formData,
                method: 'POST',
                dataType: 'text',
                success: function (resultJson) {
                    if (!resultJson.length) {
                        return;
                    }

                    result = JSON.parse(resultJson);

                    if (result.error) {
                        this.validateError(result.message);

                        return;
                    }

                    this.files(JSON.stringify(result));
                    this.filesData(result);
                }.bind(this)
            }).always(function () {
                $('body').trigger('processStop');
            });
        },

        deleteFile: function (item) {
            var postData = {
                    filehash: item.filehash,
                    extension: item.extension,
                    form_key: $(this.formKeySelector).val()
                },
                attachedFiles;

            $('body').trigger('processStart');
            $.ajax({
                url: this.chatDeleteUrl,
                data: { file: postData },
                method: 'post',
                global: false,
                dataType: 'json',
                success: function () {
                    attachedFiles = this.filesData().filter(function (file) {
                        return file.filehash !== postData.filehash;
                    });

                    this.filesData(attachedFiles);
                    this.files(JSON.stringify(attachedFiles));
                }.bind(this)
            }).always(function () {
                $('body').trigger('processStop');
            });
        },

        validateError: function (message) {
            malert({
                title: 'Error',
                content: message
            });
        }
    });
});
