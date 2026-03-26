define([
    'Magento_Ui/js/form/element/file-uploader',
    'underscore',
    'uiRegistry',
    'jquery',
    'prototype'
], function (fileUploader, _, registry, $) {
    return fileUploader.extend({
        defaults: {
            deleteUrl: null
        },
        uploaderConfig: null,
        initUploader: function (fileInput) {
            if (typeof Uppy === 'object') {
                const url = new URL(this.uploaderConfig.url);
                url.searchParams.set('request_id', this.request_id);
                this.uploaderConfig.url = url.toString();
            }

            this._super(fileInput);
        },
        getFilePreviewType: function (file) {
            if (!_.isUndefined(file.previewUrl)) {
                return 'image';
            }

            return this._super();
        },
        getFilePreview: function (file) {
            if (!_.isUndefined(file.previewUrl)) {
                return file.previewUrl;
            }

            return file.url;
        },
        getFileLink: function (file) {
            return file.url;
        },
        onBeforeFileUpload: function (e, data) {
            data.request_id = this.request_id;
            $(e.target).on('fileuploadsend', function (event, postData) {
                postData.data.append('request_id', this.request_id);
            }.bind(data));
            this._super();

            return;
        },
        removeFile: function (file) {
            var formData = new FormData(),
                deleted = false;

            formData.append('form_key', $('[name="form_key"]').val());
            formData.append('request_id', this.request_id);

            $.ajax({
                async: false,
                showLoader: true,
                url: this.deleteUrl,
                processData: false,
                contentType: false,
                data: formData,
                method: "POST",
                success: function (res) {
                    if (res.deleted) {
                        deleted = true;
                    }
                }
            });

            if (deleted) {
                this._super();
            }

            return this;
        }
    })
});
