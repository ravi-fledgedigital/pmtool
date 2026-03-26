define(['jquery','Magento_Ui/js/modal/modal','mage/translate'], function($,modal) {
    'use strict';
    return function(widget) {
        $.widget('mage.SwatchRenderer', widget, {
            _RenderControls: function () {
                var $widget = this,
                    container = this.element,
                    classes = this.options.classes,
                    chooseText = this.options.jsonConfig.chooseText,
                    showTooltip = this.options.showTooltip,
                    selectedArray = [];

                $widget.optionsMap = {};

                $.each(this.options.jsonConfig.attributes, function () {
                    var item = this,
                        controlLabelId = 'option-label-' + item.code + '-' + item.id,
                        options = $widget._RenderSwatchOptions(item, controlLabelId),
                        select = $widget._RenderSwatchSelect(item, chooseText),
                        input = $widget._RenderFormInput(item),
                        listLabel = '',
                        label = '';

                    // Show only swatch controls
                    if ($widget.options.onlySwatches && !$widget.options.jsonSwatchConfig.hasOwnProperty(item.id)) {
                        return;
                    }

                    if ($widget.options.enableControlLabel) {
                        if($('.sizing-table').length) {
                            label +='<span class="size-guide-product ' + $('<i></i>').text(item.label).html().toLowerCase() +'">Size guide</span>';
                        }
                        label +='<span id="' + controlLabelId + '" class="' + $('<i></i>').text(item.label).html().toLowerCase() +" "+ classes.attributeLabelClass + '">' +
                            $('<i></i>').text(item.label).html() +
                            '</span>' +
                            '<span class="' + classes.attributeSelectedOptionLabelClass + '"></span>';
                    }

                    if ($widget.inProductList) {
                        $widget.productForm.append(input);
                        input = '';
                        listLabel = 'aria-label="' + $('<i></i>').text(item.label).html() + '"';
                    } else {
                        listLabel = 'aria-labelledby="' + controlLabelId + '"';
                    }
                    var labelColor = 'colors';
                    if(item.options.length==1){
                        labelColor =  'color'
                    }
                    if ($widget.element.parent().hasClass('product-options-list')) {
                        input ='';
                    }
                    // Create new control
                    container.append(
                        '<div class="variant_count">'+item.options.length+' <span>'+labelColor+'</span> </div><div class="' + classes.attributeClass + ' ' + item.code + '" ' +
                        'attribute-code="' + item.code + '" ' +
                        'attribute-id="' + item.id + '">' +
                        label +
                        '<div aria-activedescendant="" ' +
                        'tabindex="0" ' +
                        'aria-invalid="false" ' +
                        'aria-required="true" ' +
                        'role="listbox" ' + listLabel +
                        'class="' + classes.attributeOptionsWrapper + ' clearfix">' +
                        options + select +
                        '</div>' + input +
                        '<div class="size-product-tooltip '+$('<i></i>').text(item.label).html().toLowerCase()+'">'+$.mage.__('All mentioned sizes here are in (US)')+'</div></div>'
                    );

                    $widget.optionsMap[item.id] = {};
                    var defaultAttributeColor = false,
                        orderDefaultAttributeColor;
                    var colorDefault = $('.product-add-form').data('color');
                    // Aggregate options array to hash (key => value)
                    $.each(item.options, function (index, value) {
                        if(colorDefault == value.label){
                            orderDefaultAttributeColor = index;
                            defaultAttributeColor = true;
                        }
                        if (value.products.length > 0) {
                            $widget.optionsMap[item.id][value.id] = {
                                price: parseInt(
                                    $widget.options.jsonConfig.optionPrices[value.products[0]].finalPrice.amount,
                                    10
                                ),
                                products: value.products
                            };
                        }
                    });
                    if(defaultAttributeColor){
                        selectedArray.push($widget.element.find('[attribute-id=' + item.id + '] .swatch-option')[orderDefaultAttributeColor]);
                    }else{
                        selectedArray.push($widget.element.find('[attribute-id=' + item.id + '] .swatch-option')[0]);
                    }
                });
                if (showTooltip === 1) {
                    // Connect Tooltip
                    container
                        .find('[option-type="1"], [option-type="2"], [option-type="0"], [option-type="3"]')
                        .SwatchRendererTooltip();
                }

                // Hide all elements below more button
                $('.' + classes.moreButton).nextAll().hide();

                // Handle events like click or change
                $widget._EventListener();

                // Rewind options
                $widget._Rewind(container);

                // get Default Options First Product

                //Emulate click on all swatches from Request
                $widget._EmulateSelected($widget._getSelectedAttributes());
                $('.size-guide-product').off('click').click(function(){
                    var options = {
                        type: 'popup',
                        responsive: true,
                        innerScroll: true,
                        title: 'Size Guide',
                        modalClass: 'size-guide-modals',
                        buttons: [{
                            text: $.mage.__('Submit'),
                            click: function () {
                                this.closeModal();
                            }
                        }]
                    };

                    var popup = modal(options, $('.sizing-table'));

                    $('.sizing-table').modal('openModal');
                });

                // automatic choose first swatch option with quantity in stock
                if (!$widget.element.parent().hasClass('product-options-list')) {
                    $.each(selectedArray, function () {
                        if(!$(this).hasClass('disabled') && this != undefined){
                            this.click();
                        }else{
                            $(this).parent().find('.swatch-option:not(.disabled)')[0].click();
                        }
                    });
                }
            },
            /**
             * Event for swatch options
             *
             * @param {Object} $this
             * @param {Object} $widget
             * @private
             */
            _OnClick: function ($this, $widget) {
                var $parent = $this.parents('.' + $widget.options.classes.attributeClass),
                    $wrapper = $this.parents('.' + $widget.options.classes.attributeOptionsWrapper),
                    $label = $parent.find('.' + $widget.options.classes.attributeSelectedOptionLabelClass),
                    attributeId = $parent.attr('attribute-id'),
                    $input = $parent.find('.' + $widget.options.classes.attributeInput),
                    checkAdditionalData = JSON.parse(this.options.jsonSwatchConfig[attributeId]['additional_data']),
                    $productId = $this.parents('.product-options-list').data('product');

                if ($widget.inProductList) {
                    $input = $widget.productForm.find(
                        '.' + $widget.options.classes.attributeInput + '[name="super_attribute[' + attributeId + ']"]'
                    );
                }

                if ($this.hasClass('disabled')) {
                    return;
                }

                if ($this.hasClass('selected')) {
                    $parent.removeAttr('option-selected').find('.selected').removeClass('selected');
                    $input.val('');
                    $label.text('');
                    $this.attr('aria-checked', false);
                } else {
                    $parent.attr('option-selected', $this.attr('option-id')).find('.selected').removeClass('selected');
                    $label.text($this.attr('option-label'));
                    $input.val($this.attr('option-id'));
                    $input.attr('data-attr-name', this._getAttributeCodeById(attributeId));
                    $this.addClass('selected');
                    $widget._toggleCheckedAttributes($this, $wrapper);
                }

                $widget._Rebuild();

                if ($widget.element.parents($widget.options.selectorProduct)
                    .find(this.options.selectorProductPrice).is(':data(mage-priceBox)')
                ) {
                    $widget._UpdatePrice();
                }

                $(document).trigger('updateMsrpPriceBlock',
                    [
                        _.findKey($widget.options.jsonConfig.index, $widget.options.jsonConfig.defaultValues),
                        $widget.options.jsonConfig.optionPrices
                    ]);

                if (parseInt(checkAdditionalData['update_product_preview_image'], 10) === 1) {
                    $widget._loadMedia();
                }

                $input.trigger('change');
            },

        });
        return $.mage.SwatchRenderer;
    }
});
