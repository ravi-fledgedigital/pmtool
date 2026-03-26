/* global tinymce, varienGlobalEvents, Base64 */
/* eslint-disable strict */
define('customTabEditorPlugin', [
    'Amasty_CustomTabs/js/variable/attribute-directive-generator',
    'wysiwygAdapter',
    'jquery'
], function (attributeDirectiveGenerator, wysiwyg, jQuery) {
    return function (config) {
        tinymce.create('tinymce.plugins.amcustomtabs_attribute', {

            /**
             * Initialize editor plugin.
             *
             * @param {tinymce.editor} editor - Editor instance that the plugin is initialized in.
             * @param {String} url - Absolute URL to where the plugin is located.
             */
            init: function (editor, url) {
                var self = this;

                /**
                 * Add new command to open attributes selector slideout.
                 */
                editor.addCommand('openAttributesSlideout', function (commandConfig) {
                    var selectedElement;

                    if (commandConfig) {
                        selectedElement = commandConfig.selectedElement;
                    } else {
                        selectedElement = tinymce.activeEditor.selection.getNode();
                    }
                    AmastyProductTabsAttributePlugin.setEditor(editor);
                    AmastyProductTabsAttributePlugin.loadChooser(
                        config.url,
                        wysiwyg.getId(),
                        selectedElement
                    );
                });

                /**
                 * Add button to the editor toolbar.
                 */
                editor.addButton('amcustomtabs_attribute', {
                    title: jQuery.mage.__('Insert Attribute'),
                    tooltip: jQuery.mage.__('Insert Attribute'),
                    cmd: 'openAttributesSlideout',
                    image: url + '/img/icon.png',

                    /**
                     * Highlight or dismiss Insert Attribute button when attribute is selected or deselected.
                     */
                    onPostRender: function () {
                        var _this = this,

                        /**
                         * Toggle active state of Insert Attribute button.
                         *
                         * @param {Object} e
                         */
                        toggleAttributeButton = function (e) {
                            _this.active(false);

                            if (jQuery(e.target).hasClass('amcustomtabs-attribute')) {
                                _this.active(true);
                            }
                        };

                        editor.on('click', toggleAttributeButton);
                        editor.on('change', toggleAttributeButton);
                    }
                });

                /**
                 * Double click handler on the editor to handle dbl click on attribute placeholder.
                 */
                editor.on('dblclick', function (evt) {
                    if (jQuery(evt.target).hasClass('amcustomtabs-attribute')) {
                        editor.selection.collapse(false);
                        editor.execCommand('openAttributesSlideout', {
                            ui: true,
                            selectedElement: evt.target
                        });
                    }
                });

                /**
                 * Attach event handler for when wysiwyg editor is about to encode its content
                 */
                varienGlobalEvents.attachEventHandler('wysiwygEncodeContent', function (content) {
                    content = self.encodeAttributes(content);

                    return content;
                });

                /**
                 * Attach event handler for when wysiwyg editor is about to decode its content
                 */
                varienGlobalEvents.attachEventHandler('wysiwygDecodeContent', function (content) {
                    content = self.decodeAttributes(content);

                    return content;
                });
            },

            /**
             * Encode attributes in content
             *
             * @param {String} content
             * @returns {*}
             */
            encodeAttributes: function (content) {
                content = content.gsub(/\{\{amcustomtabs_attribute code=\"([^\"]+)\"\}\}/i, function (match) {
                    var path = match[1],
                        amAttributes,
                        imageHtml;

                    amAttributes = JSON.parse(config.placeholders);

                    if (amAttributes[match[1]]) {
                        imageHtml = '<span id="%id" class="amcustomtabs-attribute magento-placeholder mceNonEditable">' +
                            '%s</span>';
                        imageHtml = imageHtml.replace('%s', amAttributes[match[1]]['label']);
                    } else {
                        imageHtml = '<span id="%id" class="' +
                            'amcustomtabs-attribute magento-placeholder magento-placeholder-error ' +
                            'mceNonEditable' +
                            '">' +
                            'Not found' +
                            '</span>';
                    }

                    return imageHtml.replace('%id', Base64.idEncode(path));
                });

                return content;
            },

            /**
             * Decode attributes in content.
             *
             * @param {String} content
             * @returns {String}
             */
            decodeAttributes: function (content) {
                var doc = (new DOMParser()).parseFromString(content.replace(/&quot;/g, '&amp;quot;'), 'text/html');

                [].forEach.call(doc.querySelectorAll('span.amcustomtabs-attribute'), function (el) {
                    var $el = jQuery(el);

                    $el.replaceWith(
                        attributeDirectiveGenerator.processConfig(
                            Base64.idDecode(
                                $el.attr('id')
                            )
                        )
                    );
                });

                return doc.body ? doc.body.innerHTML.replace(/&amp;quot;/g, '&quot;') : content;
            },

            /**
             * @return {Object}
             */
            getInfo: function () {
                return {
                    longname: 'Amasty Product Tab Attribute Manager Plugin',
                    author: 'Amasty Team',
                    authorurl: 'http://amasty.com',
                    infourl: 'http://amasty.com',
                    version: '1.0'
                };
            }
        });

        /**
         * Register plugin
         */
        tinymce.PluginManager.add('amcustomtabs_attribute', tinymce.plugins.amcustomtabs_attribute);
    };
});
