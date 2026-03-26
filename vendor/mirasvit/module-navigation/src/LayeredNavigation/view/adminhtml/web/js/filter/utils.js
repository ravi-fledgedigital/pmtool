define([
    'jquery',
    'jquery/ui',
    'mage/translate',
    'mage/loader',
    'Magento_Ui/js/modal/confirm',
    'Mirasvit_LayeredNavigation/js/category-tree',
    'prototype'
], function ($, jQueryUI, $t, loader, confirm, categoryTree) {

    const $sidebar = $('#mst-filter-list');
    const $horizontal = $('#mst_filters_manager-horizontal-list');
    const documentationHtml = $('#attribute-edit-docs').prop('outerHTML');

    let currentEditingAttributeCode = '';

    function showMessage(type, message) {
        const box = $('#layered-message-box');
        box.removeClass('success error').addClass(type).text(message).fadeIn();
        setTimeout(() => {
            box.fadeOut();
        }, 3000);
    }

    function highlightEditingFilter(attributeCode) {
        removeEditingHighlight();

        const elem = $(`.mst-filters__item[data-code="${attributeCode}"]`);
        elem.addClass('editing');
        $('button.mst-filters__action-button--edit', elem).attr('disabled', true);
    }

    function removeEditingHighlight() {
        const elem = $('.mst-filters__item.editing');

        elem.removeClass('editing');
        $('button.mst-filters__action-button--edit', elem).removeAttr('disabled');
    }

    function reloadFilterBlocks() {
        const $sidebar = $('#mst-filter-list');
        const $horizontal = $('#mst_filters_manager-horizontal-list');

        showLoader();

        $.ajax({
            url: typeof window.mstNavFilterReloadUrl !== 'undefined'
                ? window.mstNavFilterReloadUrl
                : getBaseUrl() + 'filter/reload',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    $('#mst-filter-list').html(res.sidebarHtml);
                    $('#mst_filters_manager-horizontal-list').html(res.horizontalHtml);

                    restoreEditingHighlight();
                } else {
                    console.warn('Reload failed');
                }
                hideLoader();

                initFilterUI();
            },
            error: function () {
                hideLoader();
                console.error('Reload request failed');
            }
        });
    }

    function initFilterUI() {
        $('#mst-filter-list, #mst_filters_manager-horizontal-list').sortable({
            connectWith: '#mst-filter-list, #mst_filters_manager-horizontal-list',
            items: '> .mst-filters__item',
            placeholder: 'sortable-placeholder',
            forcePlaceholderSize: true,
            handle: '.filters__action-button--remdragve',
            scroll: false,

            receive: function (event, ui) {
                const $item = ui.item;
                const itemCode = $item.data('code');
                ui.sender.find(`.mst-filters__item[data-code="${itemCode}"]`).not($item).remove();
                $(this).find(`.mst-filters__item[data-code="${itemCode}"]`).not($item).remove();
            },

            update: function (event, ui) {
                if (this === ui.item.parent()[0]) {
                    saveFilterPositions();
                }
            }
        });

        initEditHandler();
        initRemoveHandler();

    }

    function initDocAddFilerButton() {
        $('#attribute-edit-docs .mst-filters__add-button').on('click', function () {
            $('.mst-filters__add-wrapper .mst-filters__add-button').click();
        })
    }

    function restoreEditingHighlight() {
        if (currentEditingAttributeCode) {
            const $filterItem = $(`.mst-filters__item[data-code="${currentEditingAttributeCode}"]`);
            if ($filterItem.length) {
                $filterItem.addClass('editing');
                const $editButton = $filterItem.find('.mst-filters__action-button--edit');
                $editButton.prop('disabled', true);
            }
        }
    }

    function initEditHandler() {
        $('.mst-filters__item .mst-filters__action-button--edit').off('click').on('click', function (event) {
            confirmDiscardIfChanged(() => {
                const attributeId = $(this).data('id');
                const attributeLabel = $(this).data('label') || '';
                const attributeCode = $(this).data('code') || '';
                const attributeFrom = $(this).data('from') || '';
                const container = $('#attribute-edit-form');
                currentEditingAttributeCode = attributeCode;
                container.loader({});
                container.loader('show');
                highlightEditingFilter(attributeCode);

                $.ajax({
                    url: getBaseUrl() + 'filter/form',
                    data: {
                        attribute_id: attributeId,
                        form_key: window.FORM_KEY
                    },
                    success: function (response) {
                        const headerHtml = `
                        <div class="attribute-edit-header">
                            <h2 class="attribute-edit-title">
                                <span class="label">${attributeLabel}</span><span class="code">${attributeCode}</span>
                            </h2>
                            <div class="attribute-edit-actions">
                                <button id="clear-attribute-edit" class="mst-action-secondary action-dismiss">
                                    ${$.mage.__('Close')}
                                </button>
                                <button id="save-layered-form" class="action-primary">
                                    ${$.mage.__('Save Configuration')}
                                </button>
                            </div>
                        </div>
                    `;

                        container.html(headerHtml + response);
                        trackFormChanges('#attribute-edit-form form');
                        container.loader('hide');
                        formSaveListener();

                        $('#attribute-edit-form').data('editing-attribute-id', attributeId);
                        
                        $('#category_tree_container').categoryTree({
                            url: getBaseUrl() + 'category/tree',
                            selectedCategories: $('#category_tree_container').data('selected-categories') || []
                        });

                        $('#clear-attribute-edit').on('click', function () {
                            confirm({
                                title: $.mage.__('Discard changes?'),
                                content: $.mage.__('Unsaved changes will be lost. Continue?'),
                                actions: {
                                    confirm: restoreDocumentation
                                },
                                buttons: [{
                                    text: $.mage.__('Yes'),
                                    class: 'action-primary',
                                    click: function () {
                                        this.closeModal();
                                        restoreDocumentation();
                                    }
                                }, {
                                    text: $.mage.__('No'),
                                    class: 'mst-action-secondary action-dismiss',
                                    click: function () {
                                        this.closeModal();
                                    }
                                }]
                            });
                        });

                        if (attributeFrom === 'sidebar') {
                            $('html, body').animate({
                                scrollTop: $('.attribute-edit-header').offset().top
                            }, 400);
                        }
                    },
                    error: function () {
                        container.html('<div class="message error">' + $.mage.__('Failed to load attribute form') + '</div>');
                        container.loader('hide');
                    }
                });
            });
        });
    }

    function initRemoveHandler() {
        $('.mst-filters__item .filters__action-button--remove').off('click').on('click', function () {
            const $btn = $(this);
            const attributeId = $btn.data('id');
            const attributeCode = $btn.data('code');
            const attributeLabel = $btn.data('label');
            const attributeFrom = $btn.data('from') || 'sidebar';
            const filterName = attributeLabel ? `${attributeLabel} (${attributeCode})` : attributeCode;

            confirm({
                title: $.mage.__('Remove Filter?'),
                content: $.mage.__(`Are you sure you want to remove "${filterName}" filter from layered navigation?`),
                actions: {
                    confirm: function () {
                        $.ajax({
                            url: getBaseUrl() + 'filter/remove',
                            method: 'POST',
                            data: {
                                attribute_id: attributeId,
                                from: attributeFrom,
                                form_key: window.FORM_KEY
                            },
                            showLoader: true,
                            success: function (res) {
                                if (res.success) {
                                    reloadFilterBlocks();

                                    const currentlyEditingId = $('#attribute-edit-form').data('editing-attribute-id');

                                    if (parseInt(attributeId) === parseInt(currentlyEditingId) && res.removed_completely) {
                                        restoreDocumentation();
                                    }

                                    if (currentEditingAttributeCode === attributeCode && res.removed_completely) {
                                        currentEditingAttributeCode = '';
                                    }
                                    showMessage('success', res.message || `Attribute "${attributeCode}" was removed!`);
                                } else {
                                    alert($.mage.__('Error: ') + res.message);
                                }
                            },
                            error: function () {
                                alert($.mage.__('Unexpected error occurred.'));
                            }
                        });
                    },
                    cancel: function () {
                    }
                }
            });
        });
    }

    function restoreDocumentation() {
        const container = $('#attribute-edit-form');
        container.html(documentationHtml);
        removeEditingHighlight();
        initDocAddFilerButton();
    }

    function saveFilterPositions() {
        const horizontalOrder = [];
        const sidebarOrder = [];

        $horizontal.find('.mst-filters__item').each(function () {
            horizontalOrder.push($(this).data('code'));
        });
        $sidebar.find('.mst-filters__item').each(function () {
            sidebarOrder.push($(this).data('code'));
        });

        $.ajax({
            url: getBaseUrl() + 'filter/save',
            method: 'POST',
            data: {
                horizontal: horizontalOrder,
                sidebar: sidebarOrder,
                form_key: window.FORM_KEY
            },
            showLoader: true,
            // success: () => console.log('[Layered Nav] Filter positions saved'),
            // error: () => console.warn('[Layered Nav] Save error')
        });
    }

    function getBaseUrl() {
        const baseUrl = BASE_URL;
        if (baseUrl.includes('/layered_navigation/index/index/key/')) {
            return baseUrl.split('/layered_navigation')[0] + '/layered_navigation/';
        }
        return baseUrl;
    }


    function formSaveListener() {
        $('#save-layered-form').off('click').on('click', function () {
            const form = $('#attribute-edit-form').find('form');
            const formData = new FormData(form[0]);

            $('.mst-nav__image-field .image img.product-image').each(function (index) {
                const $img = $(this);
                formData.append(`attribute_images[${index}][url]`, $img.attr('src'));
                formData.append(`attribute_images[${index}][position]`, $img.data('position') || index);
                formData.append(`attribute_images[${index}][label]`, $img.attr('alt') || '');
            });

            const disabledFields = form.find(':input:disabled').prop('disabled', false);
            disabledFields.each(function () {
                formData.append(this.name, this.value);
            });
            disabledFields.prop('disabled', true);

            $.ajax({
                url: getBaseUrl() + 'filter/saveConfig',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                showLoader: true,
                success: function (res) {
                    if (res.success) {
                        showMessage('success', `Attribute "${res.attribute_code}" saved successfully!`);
                        reloadFilterBlocks();
                        resetFormChangeTracking();
                    } else {
                        showMessage('error', 'Error: ' + res.message);
                    }
                },
                error: function () {
                    showMessage('Unexpected error occurred.');
                }
            });
        });
    }


    function showLoader() { $('body').loader({ show: true }); }
    function hideLoader() { $('body').loader({ show: false }); }

    let isFormChanged = false;
    let originalFormState = '';

    function trackFormChanges(formSelector = '#attribute-edit-form form') {
        const $form = $(formSelector);
        if (!$form.length) return;

        originalFormState = JSON.stringify($form.serializeArray());
        isFormChanged = false;

        $(document).off('.formChangeWatcher');

        $(document).on('change.formChangeWatcher input.formChangeWatcher', `${formSelector} :input`, function () {
            const currentState = JSON.stringify($form.serializeArray());
            isFormChanged = currentState !== originalFormState;
        });
    }

    function resetFormChangeTracking() {
        isFormChanged = false;
        originalFormState = '';
        $(document).off('.formChangeWatcher');
    }

    function confirmDiscardIfChanged(onConfirm) {
        if (!isFormChanged) {
            onConfirm();
            return;
        }

        require(['Magento_Ui/js/modal/confirm'], function (confirm) {
            confirm({
                title: $.mage.__('Discard changes?'),
                content: $.mage.__('You have unsaved changes. Are you sure you want to continue?'),
                buttons: [{
                    text: $.mage.__('Yes, discard'),
                    class: 'action-primary',
                    click: function () {
                        this.closeModal();
                        resetFormChangeTracking();
                        onConfirm();
                    }
                }, {
                    text: $.mage.__('Cancel'),
                    class: 'mst-action-secondary',
                    click: function () {
                        this.closeModal();
                    }
                }]
            });
        });
    }

    return {
        showLoader,
        hideLoader,
        showMessage,
        reloadFilterBlocks,
        initFilterUI,
        trackFormChanges,
        resetFormChangeTracking,
        confirmDiscardIfChanged,
        getBaseUrl
    };
});
