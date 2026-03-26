define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/element/textarea',
    'Mirasvit_CatalogLabel/js/lib/codemirror/codemirror',
    'Mirasvit_CatalogLabel/js/lib/codemirror/css'
], function ($, _, Textarea, CodeMirror) {
    'use strict';

    return Textarea.extend({
        defaults: {
            elementTmpl: 'Mirasvit_CatalogLabel/ui/form/element/editor',
        },

        initEditor: function (textarea) {
            let self = this

            self.editor = CodeMirror.fromTextArea(
                textarea,
                {
                    lineNumbers: true,
                    matchBrackets: true,
                    mode: 'text/css',
                    indentUnit: 2,
                    indentWithTabs: false,
                    viewportMargin: Infinity,
                    styleActiveLine: true,
                    tabSize: 2
                }
            );

            self.editor.on(
                'changes',
                self.listenEditorChanges.bind(self)
            );

            return this;
        },

        listenEditorChanges: function (editor) {
            this.value(editor.getValue());
        },

        setEditorValue: function (newValue) {
            if (typeof this.editor !== 'undefined' &&
                newValue !== this.editor.getValue()
            ) {
                this.editor.setValue(newValue);
            }
        },

        initObservable: function () {
            this._super();
            this.value.subscribe(this.setEditorValue.bind(this));

            return this;
        },
    })
});
