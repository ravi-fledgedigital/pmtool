require([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'Mirasvit_LayeredNavigation/js/filter/utils',
    'mage/translate',
], function ($, modal, utils) {

    const addFiltersModal = $('#add-filters-modal');

    const addFiltersModalInstance = modal({
        type: 'slide',
        buttons: false,
        modalClass: 'add-filters-dialog',
        responsive: true,
        innerScroll: true,
        closeText: $.mage.__('Close'),
        opened: function () {
            const modalWrap = addFiltersModal.closest('.modal-inner-wrap');
            const modalHeader = modalWrap.find('.modal-header');

            $('#submit-add-filters').prop('disabled', true);

            if (!modalHeader.find('.add-filters-header').length) {
                const closeButton = modalHeader.find('.action-close');

                modalHeader.empty().append(`
            <div class="add-filters-header">
                <h1 class="modal-title">${$.mage.__('Add Filters')}</h1>
                <button id="submit-add-filters" disabled class="action-primary">${$.mage.__('Add Selected Filters')}</button>
            </div>
        `);
                modalHeader.append(closeButton);

                modalHeader.find('.action-close').on('click', function () {
                    addFiltersModalInstance.closeModal();
                });

                modalHeader.find('#submit-add-filters').on('click', function () {
                    $('#add-filters-form').submit();
                });
            }
        }
    }, addFiltersModal);

    // debounce helper
    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    $('.mst-filters__add-button').on('click', function () {
        utils.confirmDiscardIfChanged(() => {
            addFiltersModal.find('#mst-filters__available-list').html('<p>Loading...</p>');

            $.ajax({
                url: utils.getBaseUrl() + 'filter/available',
                data: { form_key: window.FORM_KEY },
                success: function (res) {
                    if (res.success && res.html) {
                        renderAttributeListHtml(res.html);
                    } else {
                        utils.showMessage('error', res.message || 'No available attributes found.');
                        addFiltersModal.find('#mst-filters__available-list').html('<p>No available attributes found.</p>');
                    }
                },
                error: function () {
                    addFiltersModal.find('#mst-filters__available-list').html('<p>Error loading attributes.</p>');
                }
            });
        });
    });

    $('#add-filters-modal').on('change', 'input[type="checkbox"]', function () {
        const $item = $(this).closest('.mst-filters__attribute-item');

        if ($(this).is(':checked')) {
            $item.addClass('selected');
        } else {
            $item.removeClass('selected');
        }
    });

    function toggleSubmitButtonState() {
        const hasChecked = $('#add-filters-modal input[type="checkbox"]:checked').length > 0;
        $('#submit-add-filters').prop('disabled', !hasChecked);
    }

    $('#add-filters-modal').on('change', 'input[type="checkbox"]', function () {
        const $item = $(this).closest('.mst-filters__attribute-item');

        if ($(this).is(':checked')) {
            $item.addClass('selected');
        } else {
            $item.removeClass('selected');
        }

        toggleSubmitButtonState();
    });
    // select on item click, but ignore clicks on input, label, select, option
    $('#add-filters-modal').on('click', '.attribute-info', function (e) {
        const $checkbox = $(this).closest('.mst-filters__attribute-item').find('input[type="checkbox"]');
        if ($checkbox.length) {
            $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
        }
    });

    $('#add-filters-form').on('submit', function (e) {
        e.preventDefault();

        const data = $(this).serialize();

        $.ajax({
            url: utils.getBaseUrl() + 'filter/add',
            method: 'POST',
            data: data + '&form_key=' + window.FORM_KEY,
            showLoader: true,
            success: function (res) {
                if (res.success) {
                    utils.reloadFilterBlocks();
                    addFiltersModalInstance.closeModal();
                } else {
                    alert('Error: ' + res.message);
                }
                utils.showMessage('success', res.message || 'Filters added successfully.');
            },
            error: function () {
                alert('Unexpected error while saving.');
            }
        });
    });

    function renderAttributeListHtml(html) {
        const wrapper = `
        <div id="attribute-list-items" class="attribute-grid">${html}</div>
    `;

        addFiltersModal.find('#mst-filters__available-list').html(wrapper);

        addFiltersModal.find('#attribute-search').on('input', debounce(function () {
            const term = $(this).val().toLowerCase();
            $('#attribute-list-items .mst-filters__attribute-item').each(function () {
                const headerText = $(this).find('.mst-filters__attribute-header').text().toLowerCase();
                $(this).toggle(headerText.includes(term));
            });
        }, 300));

        addFiltersModalInstance.openModal();
    }

});
