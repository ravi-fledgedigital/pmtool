/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

/* eslint max-lines: off */
/* eslint complexity: off */
define([ // eslint-disable-line
    'jquery',
    'Magento_Catalog/js/price-utils',
    'mage/translate'
], function ($, priceUtils) {
    return function (widget) {
        $.widget('mage.SwatchRenderer', widget, {
            /**
             * Event listener
             *
             * @private
             */
            _init: function () {
                this._super();
                const $this = this;

                /*this.element.on('click', '.' + $this.options.classes.optionClass, function() {
                    return $this.updateAddToCartFormRelation($this);
                });*/

                if (!$this.options.jsonConfig.isPdpRelation) {
                    $this.RenderSwatchesColor($this);
                    let optionsLength = $this.element.find('.swatch-attribute.color .swatch-option').length; // eslint-disable-line
                    var message = $.mage.__('%1 Color').replace('%1', optionsLength); // eslint-disable-line
                    if (optionsLength > 1) { // eslint-disable-line
                        message = $.mage.__('%1 Colors').replace('%1', optionsLength); // eslint-disable-line
                    }
                    $this.element.closest('.product-item-info').find('.product.product-color-total').text(message); // eslint-disable-line
                }
            },
            RenderSwatchesColor: function ($widget) {
                var options = $widget.options.jsonSwatchConfig,
                    urlLoadProduct = $widget.options.jsonSwatchConfig['urlLoadProduct'],
                    moreClass = $widget.options.classes.moreButton,
                    moreText = $widget.options.moreButtonText,
                    elementSwatches = $widget.element.closest('.product-item-info').find('.product-item-photo'),
                    $html = '<div class="swatch-attribute color_code color" attribute-code="color_code" data-attribute-id="323" attribute-id="323" data-option-selected="2955"><div class="swatch-attribute-options clearfix">';
                var $countAttribute = 0;
                var $optionsLength = 0;
                $.each(options, function (index) {
                    var $colorOptions = $widget.options.jsonSwatchConfig[index];
                    if (typeof $colorOptions.product_sku != 'undefined') {
                        $optionsLength++;
                    }
                });
                $.each(options, function (index, $colorOptions) {
                    if (typeof $colorOptions.product_sku != 'undefined') {
                        var classOptions = '';
                        if ($countAttribute == ($optionsLength - 1)) {
                            classOptions = 'last';
                        }
                        if (elementSwatches.attr('data-sku') == $colorOptions.product_sku) {
                            classOptions += ' selected';
                        }
                        if ($countAttribute == $widget.options.numberToShow) {
                            $html += '<span class="' + moreClass + '"><span>' + moreText + '</span></span>';
                        }
                        $styleShow = '';
                        if ($countAttribute >= $widget.options.numberToShow) {
                            $styleShow = 'display:none !important;';
                        }
                        $html += '<div id="swatch_options_id_' + $colorOptions.swatches_attribute_id + '" ' +
                            'class="swatch-option swatches-image-disable hidden-stock image ' + classOptions + '" data-link="' + urlLoadProduct + '"' +
                            ' data-sku="' + $colorOptions.product_sku + '" ' +
                            'aria-label="' + $colorOptions.swatches_color + '" ' +
                            'style="' + $styleShow + ' background: url(' + $colorOptions.swatches_image + ') no-repeat center; ' +
                            'background-size: initial;width:105px; height:78px"></div>';
                        $countAttribute++;
                    }
                });
                $html += '</div></div></div>';
                $widget.element.append($html);
                $widget._EventListenerSwatches($widget);
            },
            _EventListenerSwatches: function ($widget) {
                $widget.element.on('click', '.swatches-image-disable', function (e) {
                    e.preventDefault();
                    if ($(this).hasClass('selected')) {
                        return;
                    }
                    $(this).closest('.product-item-info').find('.swatches-image-disable').removeClass('selected');
                    $(this).addClass('selected');
                    $(this).closest('.product-item-info').find('img').addClass('swatch-option-loading');
                    $(this).closest('.product-item-info').find('.amlabel-position-wrapper img').removeClass('swatch-option-loading');
                    $.ajax({
                        type: "GET",
                        dataType: 'json',
                        url: $(this).attr('data-link'),
                        data: {
                            'sku': $(this).attr('data-sku'),
                            'product_options': $(this).closest('.product-item-info').find('.product-options-list').attr('data-product'),
                        },
                        success: function ($res) {
                            // phpcs:disable PSR12.Operators.OperatorSpacing.NoSpaceAfter
                            // phpcs:disable PSR12.Operators.OperatorSpacing.NoSpaceBefore
                            var $currentTaget = $('.product-item-info').find('.product-options-list[data-product="' + $res.product_options + '"]').closest('.product-item-info');
                            var finalPriceValue = $res.final_price;
                            var basePriceValue = $res.old_price;
                            var basePriceFormatted = priceUtils.formatPrice(basePriceValue);

                            if($res.coming_soon === "1") {
                                $('.product-coming-soon').show();
                            } else {
                                $('.product-coming-soon').hide();
                            }

                            let elem = document.getElementById("html-body");
                            let isMainPresent = elem.classList.contains("catalogsearch-result-index");
                            if (isMainPresent) {
                                basePriceFormatted = $res.old_price_with_currency;
                            }
                            var priceBox = $currentTaget.find('.price-box');
                            let $relationUrl = $res.url_product;
                            let $attributeClick = "window.location.href='" + $relationUrl + "'"; // eslint-disable-line
                            /*$currentTaget.find('a').each(function() {
                                $(this).attr('href', $relationUrl);
                            });*/
                            $currentTaget.find('[onclick^="window"]').each(function () {
                                $(this).attr('onclick', $attributeClick);
                            });
                            if ($currentTaget.attr('onclick')) {
                                $currentTaget.attr('onclick', $attributeClick);
                            }
                            $currentTaget.find('.base-images .product-image-container img').attr('src', $res.base_image);
                            $currentTaget.find('.seconds-base img').attr('src', $res.base_mouse_over);
                            $currentTaget.find('.base-image-hover img').attr('src', $res.base_image);
                            $currentTaget.find('img').removeClass('swatch-option-loading');
                            if (finalPriceValue && basePriceValue !== finalPriceValue) {
                                // Special Price
                                let finalPriceFormatted = priceUtils.formatPrice(finalPriceValue);
                                if (isMainPresent) {
                                    finalPriceFormatted = $res.final_price_with_currency;
                                }

                                priceBox.html(`
                        <span class="old-price">
                            <span class="price">
                                ${basePriceFormatted}
                            </span>
                        </span>
                        <span class="special-price">
                            <span class="price">
                                ${finalPriceFormatted}
                            </span>
                        </span>
                    `);
                            } else {
                                // Normal Price
                                priceBox.html(`
                        <span class="normal-price">
                            <span class="price">
                                ${basePriceFormatted}
                            </span>
                        </span>
                    `);
                            }
                            // phpcs:enable PSR12.Operators.OperatorSpacing.NoSpaceAfter
                            // phpcs:enable PSR12.Operators.OperatorSpacing.NoSpaceBefore
                        }
                    });
                    return false;
                });
                $widget.element.on('click', '.product-item-photo', function (e) {
                    e.preventDefault();
                    return false;
                })
            },
            updateAddToCartFormRelation: function ($this) {
                var parentProSku = $('.swatch-opt').find('.swatch-attribute.color').find('.swatch-option.image.available-size.selected').data('option-style');
                var parentSelectColorSku = $('.swatch-opt').find('.swatch-attribute.color').find('.swatch-option.image.selected').data('option-style');
                var colorParentSelectColorSku = $('.swatch-opt').find('.swatch-attribute.color').find('.swatch-option.color.available-size').data('option-style');

                if (parentSelectColorSku) {
                    var parentSku = parentSelectColorSku;
                } else if (colorParentSelectColorSku) {
                    parentSku = parentSelectColorSku;
                } else {
                    var parentSku = parentProSku;
                }
                $('.product-sku-pdp.sku').html('');
                $('.product-sku-pdp.sku').html('<strong class="type">Style #:</strong><div class="value" itemprop="sku">' + parentSku + '</div>')
                var footwearSizeLabel = $('.footwear_size').find('.swatch-attribute-selected-option').html();
                var qaSizeLabel = $('.qa_size').find('.swatch-attribute-selected-option').html();
                var sizeLabel = $('.size').find('.swatch-attribute-selected-option').html();
                var oldSize = $('.block-detail').find('.selected-size-text-pdp').html();
                if (footwearSizeLabel) {
                    var sizeLabelData = footwearSizeLabel;
                } else if (qaSizeLabel) {
                    var sizeLabelData = qaSizeLabel;
                } else {
                    var sizeLabelData = sizeLabel;
                }
                if (oldSize == sizeLabelData) {
                    $('#add_item_wishlist_na').show();
                    $('#wishlist_normal_product').hide();
                    $('.block-detail').find('.selected-size-text-pdp').html('');
                    $('.footwear_size').find('.swatch-attribute-selected-option').html('');
                    $('.footwear_size').find('.container-swatch-attribute').find('.size.swatch-attribute-label').html('Pick a Size');
                    $('.qa_size').find('.swatch-attribute-selected-option').html('');
                    $('.qa_size').find('.container-swatch-attribute').find('.size.swatch-attribute-label').html('Pick a Size');
                    $('.size').find('.swatch-attribute-selected-option').html('');
                    $('.size').find('.container-swatch-attribute').find('.size.swatch-attribute-label').html('Pick a Size');
                    $('.block-detail').find('.selected-size-text-pdp').html('Not Selected');
                    $('.swatch-opt').find('.swatch-attribute.footwear_size').find('.available-size').removeClass('selected');
                    $('.swatch-opt').find('.swatch-attribute.footwear_size').find('.available-size').attr('aria-checked', false);

                    $('.swatch-opt').find('.swatch-attribute.qa_size').find('.available-size').removeClass('selected');
                    $('.swatch-opt').find('.swatch-attribute.qa_size').find('.available-size').attr('aria-checked', false);

                    $('.swatch-opt').find('.swatch-attribute.size').find('.available-size').removeClass('selected');
                    $('.swatch-opt').find('.swatch-attribute.size').find('.available-size').attr('aria-checked', false);

                    $('.swatch-opt').find('.swatch-attribute.footwear_size').find('.swatch-input.super-attribute-select').removeClass('mage-error');
                    $('.swatch-opt').find('.swatch-attribute.qa_size').find('.swatch-input.super-attribute-select').removeClass('mage-error');
                    $('.swatch-opt').find('.swatch-attribute.size').find('.swatch-input.super-attribute-select').removeClass('mage-error');
                    $('#error_footwear_size').remove();
                } else {
                    $('.block-detail').find('.selected-size-text-pdp').html(sizeLabelData);

                    if (sizeLabelData) {
                        $('#add_item_wishlist_na').hide();
                        $('#wishlist_normal_product').show();
                        $('.footwear_size').find('.container-swatch-attribute').find('.size.swatch-attribute-label').html('Size:');
                        $('.swatch-opt').find('.swatch-attribute.footwear_size').find('.swatch-input.super-attribute-select').removeClass('mage-error');
                        $('.qa_size').find('.container-swatch-attribute').find('.size.swatch-attribute-label').html('Size:');
                        $('.swatch-opt').find('.swatch-attribute.qa_size').find('.swatch-input.super-attribute-select').removeClass('mage-error');
                        $('.size').find('.container-swatch-attribute').find('.size.swatch-attribute-label').html('Size:');
                        $('.swatch-opt').find('.swatch-attribute.size').find('.swatch-input.super-attribute-select').removeClass('mage-error');
                        $('#error_footwear_size').remove();
                    } else {
                        $('#add_item_wishlist_na').show();
                        $('#wishlist_normal_product').hide();
                        $('.footwear_size').find('.container-swatch-attribute').find('.size.swatch-attribute-label').html('Pick a Size');
                        $('.qa_size').find('.container-swatch-attribute').find('.size.swatch-attribute-label').html('Pick a Size');
                        $('.size').find('.container-swatch-attribute').find('.size.swatch-attribute-label').html('Pick a Size');
                        $('.block-detail').find('.selected-size-text-pdp').html('Not Selected');
                        $('.swatch-opt').find('.swatch-attribute.footwear_size').find('.swatch-input.super-attribute-select').removeClass('mage-error');
                        $('.swatch-opt').find('.swatch-attribute.qa_size').find('.swatch-input.super-attribute-select').removeClass('mage-error');
                        $('.swatch-opt').find('.swatch-attribute.size').find('.swatch-input.super-attribute-select').removeClass('mage-error');
                        $('#error_footwear_size').remove();
                    }
                }

                let jsonConfig = $this.options.jsonConfig; // eslint-disable-line
                if (typeof $this.options.jsonConfig.currentSku == 'undefined') {
                    return;
                }
                let selectedStyleCode = $('.swatch-option.image.selected').data('option-style'); // eslint-disable-line
                let productId = jsonConfig.relationParents[selectedStyleCode] // eslint-disable-line
                    ?
                    jsonConfig.relationParents[selectedStyleCode] // eslint-disable-line
                    :
                    null; // eslint-disable-line
                if (!productId) {
                    return;
                }

                let $relationUrl = $this.options.jsonConfig.productUrl[productId][$this.getProduct()]; // eslint-disable-line
                if (typeof $relationUrl == 'undefined') {
                    $.each($this.options.jsonConfig.productUrl[productId], function () {
                        $relationUrl = this;
                    });
                }
                if (!jsonConfig.isPdpRelation && !jsonConfig.isPdpRelationQuickView) {
                    let $parentElement = $this.element.closest('.item.product.product-item'); // eslint-disable-line
                    let $attributeClick = "window.location.href='" + $relationUrl + "'"; // eslint-disable-line
                    $parentElement.find('a').each(function () {
                        $(this).attr('href', $relationUrl);
                    });
                    $parentElement.find('[onclick^="window"]').each(function () {
                        $(this).attr('onclick', $attributeClick);
                    });
                    if ($parentElement.attr('onclick')) {
                        $parentElement.attr('onclick', $attributeClick);
                    }
                    return;
                }
                let form = $('#product_addtocart_form'); // eslint-disable-line
                let url = form.attr('action'); // eslint-disable-line
                var dataPostWishlist = $('.product-addto-links .action.towishlist').data('post'); // eslint-disable-line
                dataPostWishlist['data']['product'] = productId; // eslint-disable-line
                $('.swatch-attribute .super-attribute-select').each(function () { // eslint-disable-line
                    dataPostWishlist['data'][$(this).attr('name')] = $(this).val(); // eslint-disable-line
                });
                $('.product-addto-links .action.towishlist').data('post', dataPostWishlist); // eslint-disable-line
                form.find('input[name="product"]').val(productId);
                form.find('input[name="item"]').val(productId);
                form.attr('action', url.replace(/\/product\/\d+\//, '/product/' + productId + '/'));
                var $productChildrenId = $this.getProductDetails();
                $('.product-add-form').attr('data-product-id', $productChildrenId);
                $('.product-add-form').attr('data-product-parent-id', productId);
                history.replaceState({}, null, $relationUrl);
            }
        });
        return $.mage.SwatchRenderer;
    };
});