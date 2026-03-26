/* phpcs:ignoreFile */
/* eslint-disable */
define([
    'uiCollection',
    'underscore',
    'jquery',
    'uiLayout',
    'mageUtils',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function (Collection, _, $, layout, utils, message) {
    'use strict';
    return Collection.extend({
        defaults: {
            template: 'Amasty_Rma/chat/container',
            emptyChatTemplate: 'Amasty_Rma/chat/message/empty',
            files: [],
            lastId: null,
            fetchUrl: null,
            saveUrl: null,
            uploadUrl: null,
            deleteUrl: null,
            deleteMessageUrl: null,
            isAdmin: false,
            quick_replies: [],
            chat : '[data-amrma-js="chat-block"]',
            quickreply: ""
        },
        initObservable: function () {
            this._super().observe([
                'value',
                'files'
            ]);

            return this;
        },
        initLinks: function () {
            this._super();
            if (!_.isUndefined(this.urlhash)) {
                this.fetchUrl += 'hash/' + this.urlhash;
                this.saveUrl += 'hash/' + this.urlhash;
                this.uploadUrl += 'hash/' + this.urlhash;
                this.deleteUrl += 'hash/' + this.urlhash;
                this.deleteMessageUrl += 'hash/' + this.urlhash;
            }
            this.fetchChatItems();
            setInterval(this.fetchChatItems.bind(this), 10000);

            return this;
        },
        fetchChatItems: function () {
            var self = this;
            $.ajax({
                url: this.fetchUrl,
                data: {'lastId': this.lastId},
                method: 'get',
                global: false,
                dataType: 'json',
                success: function (response) {
                    self.createItems(response);
                }
            });
        },
        createItems: function (items) {
            if (!_.isEmpty(items)) {
                _.each(items, function (item) {
                    item.deleteMessageUrl = this.deleteMessageUrl;
                    layout([
                        this.createComponent(item)
                    ]);
                    this.insertChild('chat-item-' + item.message_id);
                }.bind(this));
                this.lastId = _.last(items).message_id;
            }
        },
        createComponent: function (item) {
            return utils.extend(item, {
                'name': 'chat-item-' + item.message_id,
                'component': 'Amasty_Rma/js/chat/message/view'
            });
        },
        changeTextareaSize: function (data, e) {
            var textarea = $('.amrma-chat-container .amrma-textarea'),
                nextElement = $(textarea).next(),
                $chatBlock = $(this.chat),
                maxChatHeight = 400,
                heightConst = 110,
                maxMessageHeight = 250;

            nextElement.width($(textarea).width());
            nextElement.text($(textarea).val());

            if (nextElement.height() > maxMessageHeight) {
                return;
            }

            $(textarea).height(nextElement.height());
            $chatBlock.css('max-height', maxChatHeight - nextElement.height() - heightConst);
        },
        deleteFile: function (index) {
            $.ajax({
                url: this.deleteUrl,
                data: {file: this.files()[index()]},
                method: 'post',
                global: false,
                dataType: 'json',
                success: function (data) {
                    var files = _.clone(this.files());
                    files.splice(index(), 1);
                    this.files(files);

                }.bind(this)
            });
        },
        sendMessage: function () {
            var value = this.value(),
                files = this.files();
            if (_.isUndefined(value) || value === null) {
                value = '';
            }
            if (_.isUndefined(this.files()) || this.files() === null) {
                files = [];
            }
            if (value === '' && !files.length) {
                message({'content': $.mage.__('Message or Message Files are empty.')});
                return;
            }
            var postData = {'message': value};
            if (this.files().length) {
                postData.files = this.files();
            }

            $.ajax({
                url: this.saveUrl,
                data: postData,
                method: 'post',
                global: false,
                dataType: 'json',
                success: function () {
                    this.value('');
                    this.files([]);
                    this.fetchChatItems();
                    this.changeTextareaSize();
                }.bind(this)
            });
        },
        getEmptyChatTemplate: function () {
            return this.emptyChatTemplate;
        },
        uploadFiles: function () {
            $('body').trigger('processStart');
            var formData = new FormData(),
                filesFromForm = $('#amrma-attach')[0].files;
            formData.append('form_key', $('[name="form_key"]').val());
            $.each(filesFromForm, function (i, file) {
                formData.append(file.name.substr(0, file.name.lastIndexOf('.')), file);
            });
            $.ajax({
                showLoader: true,
                url: this.uploadUrl,
                processData: false,
                contentType: false,
                global: false,
                data: formData,
                method: "POST",
                success: function (result) {
                    if (!_.isUndefined(result.error)) {
                        message({'content': result.error});
                    } else if (result.length) {
                        this.files(this.files().concat(result));
                    }
                    $('#amrma-attach').val('');
                }.bind(this)
            }).complete(function () {
                $('body').trigger('processStop');
            }).error(function (jqXHR, textStatus, errorThrown) {
                var errorMessage = 'File you are trying to upload exceeds maximum file size limit.';
                require(['Magento_Ui/js/modal/alert'], function (alert) {
                    alert({
                        title: 'Error',
                        content: errorMessage,
                        actions: {
                            always: function(){}
                        }
                    });
                });
            })
        },
        setQuickMessage: function (self, event) {
            var selected = $(event.target).find('option:selected').val();
            if (selected !== "") {
                this.value(this.quick_replies[selected].label);
                this.changeTextareaSize();
                $(event.target).find('option[value=""]').attr('selected', true);
            }
        }
    });
});
