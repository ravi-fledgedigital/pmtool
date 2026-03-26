define([
    'jquery',
    'mage/backend/notification',
    'mage/translate',
    'uiRegistry',
    'mage/apply/main',
    'mageUtils',
    'Amasty_CustomTabs/js/variable/attribute-directive-generator',
    'Magento_Ui/js/lib/spinner',
    'jquery/ui',
    'prototype'
], function (jQuery, notification, $t, registry, mageApply, utils, attributeDirectiveGenerator, loader) {
    'use strict';

    window.AmastyProductTabsAttribute = {
        textareaElementId: null,
        attributesContent: null,
        dialogWindow: null,
        dialogWindowId: 'attributes-chooser',
        overlayShowEffectOptions: null,
        overlayHideEffectOptions: null,
        insertFunction: 'Attributes.insertAttribute',
        selectedPlaceholder: null,
        isEditMode: null,
        editor: null,

        /**
         * Initialize Attributes handler.
         *
         * @param {*} textareaElementId
         * @param {Function} insertFunction
         * @param {Object} editor
         * @param {Object} selectedPlaceholder
         */
        init: function (textareaElementId, insertFunction, editor, selectedPlaceholder) {
            if ($(textareaElementId)) {
                this.textareaElementId = textareaElementId;
            }

            if (insertFunction) {
                this.insertFunction = insertFunction;
            }

            if (selectedPlaceholder) {
                this.selectedPlaceholder = selectedPlaceholder;
            }

            if (editor) {
                this.editor = editor;
            }
        },

        /**
         * Reset data.
         */
        resetData: function () {
            this.attributesContent = null;
            this.dialogWindow = null;
        },

        /**
         * Open attributes chooser slideout.
         *
         * @param {Object} attributes
         */
        openAttributeChooser: function (attributes) {
            if (attributes) {
                this.openDialogWindow(attributes);
            }
        },

        /**
         * Close attributes chooser slideout dialog window.
         */
        closeDialogWindow: function () {
            jQuery('#' + this.dialogWindowId).modal('closeModal');
        },

        /**
         * Init ui component grid on the form
         *
         * @return void
         */
        initUiGrid: function () {
            mageApply.apply(document.getElementById(this.dialogWindow));
            jQuery('#' + this.dialogWindowId).applyBindings();
            loader.get('amcustomtabs_attributes_modal.amcustomtabs_attributes_modal.attributes').hide();
        },

        /**
         * Open slideout dialog window.
         *
         * @param {*} attributesContent
         * @param {Object} selectedElement
         */
        openDialogWindow: function (attributesContent, selectedElement) {

            var html = utils.copy(attributesContent),
                self = this;

            jQuery('<div id="' + this.dialogWindowId + '">' + html + '</div>').modal({
                title: self.isEditMode ? $t('Edit Attribute') : $t('Insert Attribute'),
                type: 'slide',
                buttons: self.getButtonsConfig(self.isEditMode),

                /**
                 * @param {jQuery.Event} e
                 * @param {Object} modal
                 */
                closed: function (e, modal) {
                    modal.modal.remove();
                }
            });

            this.selectedPlaceholder = selectedElement;

            this.addNotAvailableMessage(selectedElement);

            jQuery('#' + this.dialogWindowId).modal('openModal');
        },

        /**
         * Add message to slide out that attribute is no longer available
         *
         * @param {Object} selectedElement
         */
        addNotAvailableMessage: function (selectedElement) {
            var name,
                msg,
                attributePath,
                $wrapper,
                lostAttributeClass = 'magento-placeholder-error';

            if (
                this.isEditMode &&
                typeof selectedElement !== 'undefined' &&
                jQuery(selectedElement).hasClass(lostAttributeClass)
            ) {

                attributePath = AmastyProductTabsAttributePlugin.getElementAttributePath(selectedElement);
                name = attributePath.split(':');
                msg = $t('The attribute %1 is no longer available. Select a different attribute.')
                    .replace('%1', name[1]);

                jQuery('body').notification('clear')
                    .notification('add', {
                        error: true,
                        message: msg,

                        /**
                         * @param {String} message
                         */
                        insertMethod: function (message) {
                            $wrapper = jQuery('<div/>').html(message);

                            jQuery('.modal-header .page-main-actions').after($wrapper);
                        }
                    });
            }
        },

        /**
         * Get selected attribute directive.
         *
         * @returns {*}
         */
        getSelectedCheckboxes: function () {
            return jQuery('[name="attribute-select"]:checked');
        },

        /**
         * Get buttons configuration for slideout dialog.
         *
         * @param {Boolean} isEditMode
         *
         * @returns {Array}
         */
        getButtonsConfig: function (isEditMode) {

            var self = this,
                buttonsData;

            buttonsData = [
                {

                    text: $t('Cancel'),
                    'class': 'action-scalable cancel',

                    /**
                     * @param {jQuery.Event} event
                     */
                    click: function (event) {
                        this.closeModal(event);
                    }
                },
                {

                    text: isEditMode ? $t('Save') : $t('Insert Attribute'),
                    class: 'action-primary ' + (isEditMode ? '' : 'disabled'),
                    attr: {
                        'id': 'insert_attribute'
                    },

                    /**
                     * Insert Attribute
                     */
                    click: function () {
                        self.insertAttribute(self.getSelectedCheckboxes());
                    }
                }
            ];

            return buttonsData;
        },

        /**
         * Prepare attributes row.
         *
         * @param {String} varValue
         * @param {*} varLabel
         * @return {String}
         * @deprecated This method isn't relevant after ui changes
         */
        prepareAttributeRow: function (varValue, varLabel) {
            var value = varValue.replace(/"/g, '&quot;').replace(/'/g, '\\&#39;');

            return '<a href="#" onclick="' +
                this.insertFunction +
                '(\'' +
                value +
                '\');return false;">' +
                varLabel +
                '</a>';
        },

        /**
         * Insert attribute into WYSIWYG editor.
         *
         * @param selected
         * @return {Object}
         */
        insertAttribute: function (selected) {
            var windowId = this.dialogWindowId,
                textareaElm;

            jQuery('#' + windowId).modal('closeModal');
            textareaElm = $(this.textareaElementId);
            if (require.defined('wysiwygAdapter')) {
                require(['wysiwygAdapter'], function (wysiwyg) {
                    //to support switching between wysiwyg editors
                    var wysiwygEditorFocused = wysiwyg && wysiwyg.activeEditor();

                    if (wysiwygEditorFocused && wysiwyg.get(this.textareaElementId)) {
                        if (jQuery(this.selectedPlaceholder).hasClass('magento-placeholder')) {
                            wysiwyg.setCaretOnElement(this.selectedPlaceholder, 1);
                        }
                        selected.each(function () {
                            wysiwyg.insertContent(attributeDirectiveGenerator.processConfig(this.value), false);
                        });

                        if (this.selectedPlaceholder
                            && jQuery(this.selectedPlaceholder).hasClass('magento-placeholder')
                        ) {
                            this.selectedPlaceholder.remove();
                        }
                    } else if (textareaElm) {
                        var shouldToggle = textareaElm.visible() === false;
                        if (shouldToggle) {
                            wysiwygamcustomtabs_tabs_form_content.toggle.bind(wysiwygamcustomtabs_tabs_form_content)();
                        }

                        this.insertAttributeViaTextarea(textareaElm, selected);
                        if (shouldToggle) {
                            wysiwygamcustomtabs_tabs_form_content.toggle.bind(wysiwygamcustomtabs_tabs_form_content)();
                        }
                    }
                }.bind(this));
            } else if (textareaElm) {
                var shouldToggle = textareaElm.visible() === false;
                if (shouldToggle) {
                    wysiwygamcustomtabs_tabs_form_content.toggle.bind(wysiwygamcustomtabs_tabs_form_content)();
                }
                this.insertAttributeViaTextarea(textareaElm, selected);
                if (shouldToggle) {
                    wysiwygamcustomtabs_tabs_form_content.toggle.bind(wysiwygamcustomtabs_tabs_form_content)();
                }
            }

            return this;
        },

        insertAttributeViaTextarea: function (textareaElm, selected) {
            var scrollPos = textareaElm.scrollTop;
            selected.each(function () {
                updateElementAtCursor(textareaElm, attributeDirectiveGenerator.processConfig(this.value));
            });
            textareaElm.focus();
            textareaElm.scrollTop = scrollPos;
            jQuery(textareaElm).change();
            textareaElm = null;
        }
    };

    window.AmastyProductTabsAttributePlugin = {
        editor: null,
        attributes: null,
        textareaId: null,

        /**
         * Bind editor.
         *
         * @param {*} editor
         */
        setEditor: function (editor) {
            this.editor = editor;
        },

        /**
         * Load attributes chooser.
         *
         * @param {String} url
         * @param {*} textareaId
         * @param {Object} selectedElement
         *
         * @return {Object}
         */
        loadChooser: function (url, textareaId, selectedElement) {
            this.textareaId = textareaId;

            new Ajax.Request(url, {
                parameters: {},
                onComplete: function (transport) {
                    AmastyProductTabsAttribute.init(this.textareaId, 'AmastyProductTabsAttributePlugin.insertAttribute', this.editor);
                    AmastyProductTabsAttribute.isEditMode = !!this.getElementAttributePath(selectedElement);
                    this.attributesContent = transport.responseText;
                    AmastyProductTabsAttribute.openDialogWindow(this.attributesContent, selectedElement);
                    AmastyProductTabsAttribute.initUiGrid();
                }.bind(this)
            });

            return this;
        },

        /**
         * Open attributes chooser window.
         *
         * @param {*} attributes
         * @deprecated This method isn't relevant after ui changes
         */
        openChooser: function (attributes) {
            AmastyProductTabsAttribute.openAttributeChooser(attributes);
        },

        /**
         * Insert Attribute.
         *
         * @param {*} value
         *
         * @return {Object}
         */
        insertAttribute: function (value) {
            if (this.textareaId) {
                AmastyProductTabsAttribute.init(this.textareaId);
                AmastyProductTabsAttribute.insertAttribute(value);
            } else {
                AmastyProductTabsAttribute.closeDialogWindow();
                AmastyProductTabsAttribute.insertAttribute(value);
            }

            return this;
        },

        /**
         * Get element Attribute path.
         *
         * @param {Object} element
         * @returns {String}
         */
        getElementAttributePath: function (element) {
            var type, code;

            if (!element || !jQuery(element).hasClass('amcustomtabs-attribute')) {
                return '';
            }
            code = Base64.idDecode(element.getAttribute('id'));

            return code;
        }
    };
});
