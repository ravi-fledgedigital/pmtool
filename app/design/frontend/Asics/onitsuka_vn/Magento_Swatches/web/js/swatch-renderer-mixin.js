/* phpcs:ignoreFile */
define([
    'jquery',
    'underscore',
    'mage/template',
    'mage/smart-keyboard-handler',
    'mage/translate',
    'priceUtils',
    'jquery-ui-modules/widget',
    'jquery/jquery.parsequery',
    'mage/validation/validation',
], function ($, _, mageTemplate, keyboardHandler, $t, priceUtils) {
    'use strict';
    return function (widget) {
        $.widget('mage.validation', $.mage.validation, {

            /**
             * Handle form with swatches validation. Focus on first invalid swatch block.
             *
             * @param {jQuery.Event} event
             * @param {Object} validation
             */
            listenFormValidateHandler: function (event, validation) {
                var swatchWrapper, firstActive, swatches, swatch, successList, errorList, firstSwatch;
                if (!$(event.currentTarget).parents('.mfp-wrap').length) {
                    this._superApply(arguments);
                }

                swatchWrapper = '.swatch-attribute-options';
                swatches = $(event.target).find(swatchWrapper);

                if (!swatches.length) {
                    return;
                }

                swatch = '.swatch-attribute';
                firstActive = $(validation.errorList[0].element || []);
                successList = validation.successList;
                errorList = validation.errorList;
                firstSwatch = $(firstActive).parent(swatch).find(swatchWrapper);

                keyboardHandler.focus(swatches);

                $.each(successList, function (index, item) {
                    $(item).parent(swatch).find(swatchWrapper).attr('aria-invalid', false);
                });

                $.each(errorList, function (index, item) {
                    $(item.element).parent(swatch).find(swatchWrapper).attr('aria-invalid', true);
                });

                if (firstSwatch.length) {
                    $(firstSwatch).trigger('focus');
                }
            }
        });
        $.widget('mage.SwatchRenderer', widget, {
            _getCookies: function (cname) {
                var name = cname + "=";
                var decodedCookie = decodeURIComponent(document.cookie);
                var ca = decodedCookie.split(';');
                for (var i = 0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ') {
                        c = c.substring(1);
                    }
                    if (c.indexOf(name) == 0) {
                        return c.substring(name.length, c.length);
                    }
                }
                return "";
            },
            _create: function () {
                var options = this.options,
                    gallery = $('[data-gallery-role=gallery-placeholder]', '.column.main'),
                    productData = this._determineProductData(),
                    $main = productData.isInProductView ?
                        this.element.parents('.column.main') :
                        this.element.parents('.product-item-info');

                if (productData.isInProductView) {
                    gallery.data('gallery') ?
                        this._onGalleryLoaded(gallery) :
                        gallery.on('gallery:loaded', this._onGalleryLoaded.bind(this, gallery));
                } else {
                    options.mediaGalleryInitial = [{
                        'img': $main.find('.product-image-photo').attr('src'),
                        'secondBase': $main.find('.seconds-base .product-image-photo-second').attr('src')
                    }];
                }
                var self = this;
                $('.box-tocart .input-text.qty').on("change", function (e) {
                    self.showMaximumQuantityQtySales(self);
                    self.showMaximumQuantityQty(self);
                })
                this.productForm = this.element.parents(this.options.selectorProductTile).find('form:first');
                this.inProductList = this.productForm.length > 0;
            },

            /**
             * Determine product id and related data
             *
             * @returns {{productId: *, isInProductView: bool}}
             * @private
             */
            _determineProductData: function () {
                // Check if product is in a list of products.
                var productId,
                    isInProductView = false;

                productId = this.element.parents('.product-item-details')
                    .find('.price-box.price-final_price').attr('data-product-id');

                if (!productId) {
                    productId = this.element.parents('.product-item-info').find('.product-item-details')
                        .find('.price-box.price-final_price').attr('data-product-id');
                }

                if (!productId) {
                    // Check individual product.
                    productId = $('[name=product]').val();
                    isInProductView = productId > 0;
                }

                return {
                    productId: productId,
                    isInProductView: isInProductView
                };
            },

            /**
             * Update [gallery-placeholder] or [product-image-photo]
             * @param {Array} images
             * @param {jQuery} context
             * @param {Boolean} isInProductView
             */
            updateBaseImage: function (images, context, isInProductView) {
                images = this.sortBaseImageFirst(images);
                var justAnImage = images[0],
                    initialImages = this.options.mediaGalleryInitial,
                    imagesToUpdate,
                    gallery = context.find(this.options.mediaGallerySelector).data('gallery'),
                    isInitial;

                if (isInProductView) {
                    imagesToUpdate = images.length ? this._setImageType($.extend(true, [], images)) : [];
                    isInitial = _.isEqual(imagesToUpdate, initialImages);

                    if (this.options.gallerySwitchStrategy === 'prepend' && !isInitial) {
                        imagesToUpdate = imagesToUpdate.concat(initialImages);
                    }

                    imagesToUpdate = this._setImageIndex(imagesToUpdate);

                    if (!_.isUndefined(gallery)) {
                        gallery.updateData(imagesToUpdate);
                    } else {
                        context.find(this.options.mediaGallerySelector).on('gallery:loaded', function (loadedGallery) {
                            loadedGallery = context.find(this.options.mediaGallerySelector).data('gallery');
                            loadedGallery.updateData(imagesToUpdate);
                        }.bind(this));
                    }
                    try {
                        if (isInitial) {
                            $(this.options.mediaGallerySelector).AddFotoramaVideoEvents();
                        } else {
                            $(this.options.mediaGallerySelector).AddFotoramaVideoEvents({
                                selectedOption: this.getProduct(),
                                dataMergeStrategy: this.options.gallerySwitchStrategy
                            });
                        }
                    } catch (e) {

                    }

                    if (gallery) {
                        gallery.first();
                    }
                } else if (justAnImage && justAnImage.img) {
                    context.find('.product-image-photo').attr('src', justAnImage.img);
                }
            },
            /**
             * Show Base Image first
             * @param images
             */
            sortBaseImageFirst: function (images) {
                var imageThumbnail = [];
                $.each(images, function (propName, propVal) {
                    if (propVal.isMain) {
                        imageThumbnail.unshift(propVal);
                    } else {
                        imageThumbnail.push(propVal);
                    }
                });
                return imageThumbnail;
            },
            /**
             * Render controls
             *
             * @private
             */
            _RenderControls: function () {
                var $widget = this,
                    container = this.element,
                    classes = this.options.classes,
                    chooseText = this.options.jsonConfig.chooseText,
                    showTooltip = this.options.showTooltip,
                    selectedArray = [];

                $widget.optionsMap = {};

                var colorAttrId = this.options.colorAttrId;
                var sizeAttrId = this.options.sizeAttrId;
                var ajaxUrl = this.options.ajaxUrl;
                var graphqlUrl = this.options.graphqlUrl;
                var productSku = this.options.productSku;
                var storeId = this.options.storeId;
                var currentPageUrl = $(location).attr('href');
                var styleCode = this.options.styleCode;
                let loaderImgPdp = this.options.mageLoaderImg;
                let relationSwatches = this.options.jsonConfig.relationSwatches;
                var self = this;

                $.each(this.options.jsonConfig.attributes, function () {
                    var item = this,
                        controlLabelId = 'option-label-' + item.code + '-' + item.id,
                        options = $widget._RenderSwatchOptions(item, controlLabelId, item.code),
                        select = $widget._RenderSwatchSelect(item, chooseText),
                        input = $widget._RenderFormInput(item),
                        listLabel = '',
                        label = '';

                    if ($widget.bindings.parents('.mfp-container').length) {
                        if (item.code == 'size' || item.code == 'qa_size') {
                            input = $widget._RenderFormInputQuickview(item);
                        }
                    }
                    // Show only swatch controls
                    if ($widget.options.onlySwatches && !$widget.options.jsonSwatchConfig.hasOwnProperty(item.id)) {
                        return;
                    }

                    if ($widget.options.enableControlLabel) {
                        if ($('body').hasClass('catalog-category-view')) {
                            if ((item.code == 'size' || item.code == 'qa_size') && $('.product.hidden.gender').length) {
                                label += '<a target="_blank" href="' + $('.quickview-size-guide-link.hidden a').attr('href') + '" class="size-guide-product ' + $('<i></i>').text(item.label).html().toLowerCase() + '">' + $t('Size guide') + '</a>' +
                                    '<span id="' + controlLabelId + '" class="' + $('<i></i>').text(item.label).html().toLowerCase() + " " + classes.attributeLabelClass + '">' +
                                    $t(item.label) + ' (' + $('.product.hidden.gender').text().trim() + ')' +
                                    '</span>' +
                                    '<span class="' + classes.attributeSelectedOptionLabelClass + '"></span>';
                            } else {
                                label += '<a target="_blank" href="' + $('.quickview-size-guide-link.hidden a').attr('href') + '" class="size-guide-product ' + $('<i></i>').text(item.label).html().toLowerCase() + '">' + $t('Size guide') + '</a>' +
                                    '<span id="' + controlLabelId + '" class="' + $('<i></i>').text(item.label).html().toLowerCase() + " " + classes.attributeLabelClass + '">' +
                                    $('<i></i>').text($t(item.label)).html() +
                                    '</span>' +
                                    '<span class="' + classes.attributeSelectedOptionLabelClass + '"></span>';
                            }
                        } else {
                            if ((item.code == 'size' || item.code == 'qa_size') && $('.product.hidden.gender').length) {
                                label += '<span class="text-size-custom">사이즈를 선택</span><span class="size-guide-product ' + $('<i></i>').text(item.label).html().toLowerCase() + '">' + $t('Size guide') + '</span>' +
                                    '<span id="' + controlLabelId + '" class="' + $('<i></i>').text(item.label).html().toLowerCase() + " " + classes.attributeLabelClass + '">' +
                                    $t(item.label) + ' (' + $('.product.hidden.gender').text().trim() + ')' +
                                    '</span>' +
                                    '<span class="' + classes.attributeSelectedOptionLabelClass + '"></span>';
                            } else {
                                label += '<span class="text-size-custom">사이즈를 선택</span><span class="size-guide-product ' + $('<i></i>').text(item.label).html().toLowerCase() + '">' + $t('Size guide') + '</span>' +
                                    '<span id="' + controlLabelId + '" class="' + $('<i></i>').text(item.label).html().toLowerCase() + " " + classes.attributeLabelClass + '">' +
                                    $('<i></i>').text($t(item.label)).html() +
                                    '</span>' +
                                    '<span class="' + classes.attributeSelectedOptionLabelClass + '"></span>';
                            }
                        }
                    }

                    if ($widget.inProductList) {
                        $widget.productForm.append(input);
                        input = '';
                        listLabel = 'aria-label="' + $('<i></i>').text(item.label).html() + '"';
                    } else {
                        listLabel = 'aria-labelledby="' + controlLabelId + '"';
                    }
                    var labelColor = 'colors';
                    if (item.options.length == 1) {
                        labelColor = 'color'
                    }
                    var styleAttribute = '';
                    if (item.code == 'width') {
                        styleAttribute = 'style="display:none"';
                    }
                    // Create new control
                    /*container.append(
                        '<div class="' + classes.attributeClass + ' ' + item.code + '" ' + styleAttribute +
                        'attribute-code="' + item.code + '" ' +
                        'data-attribute-id="' + item.id + '" ' +
                        'attribute-id="' + item.id + '"><div class="container-swatch-attribute">' +
                        label +
                        '<div aria-activedescendant="" ' +
                        'tabindex="0" ' +
                        'aria-invalid="false" ' +
                        'aria-required="true" ' +
                        'role="listbox" ' + listLabel +
                        'class="' + classes.attributeOptionsWrapper + ' clearfix">' +
                        options + select +
                        '</div><div class="size-product-tooltip ' + $('<i></i>').text(item.label).html().toLowerCase() + '">' + $.mage.__('All mentioned sizes here are in (US)') + '</div></div>' + input + '</div>'
                    );*/

                    if (item.id == colorAttrId) {
                        container.append(
                            '<div class="' + classes.attributeClass + ' color ' + item.code + '" ' + styleAttribute +
                            'attribute-code="' + item.code + '" ' +
                            'data-attribute-id="' + item.id + '" ' +
                            'attribute-id="' + item.id + '"><div class="container-swatch-attribute">' +
                            label +
                            '<div aria-activedescendant="" ' +
                            'tabindex="0" ' +
                            'aria-invalid="false" ' +
                            'aria-required="true" ' +
                            'role="listbox" ' + listLabel +
                            'class="' + classes.attributeOptionsWrapper + ' clearfix" id="color-swatches">' +
                            options + select +
                            /*colorOptionsData + select +*/
                            '</div><div class="size-product-tooltip ' + $('<i></i>').text(item.label).html().toLowerCase() + '">' + $.mage.__('All mentioned sizes here are in (US)') + '</div></div>' + input + '</div>'
                        );
                        //self.getOtherColorSwatchOptions(self, currentPageUrl, graphqlUrl, container, classes, item, listLabel, select, label, productSku, styleCode, storeId, options, input, ajaxUrl, $widget, styleAttribute);
                        self.getOtherColorSwatchOptions(graphqlUrl, productSku, styleCode, storeId, self, ajaxUrl, $widget, currentPageUrl);
                    } else if (item.id == sizeAttrId) {
                        $('.catalog-product-view').remove('.custom-pdp-loader');
                        let loaderOnBody = '<div class="loading-mask custom-pdp-loader" data-role="loader" style="display: block;"><div class="loader"><img alt="Loading..." src="' + loaderImgPdp + '"><p>Please wait...</p></div></div>';

                        if (relationSwatches && relationSwatches.length != 0) {
                            $('.catalog-product-view').append(loaderOnBody);
                        }
                        container.append(
                            '<div class="coming-soon-content"><span class="coming-soon-label"></span></div>'+
                            '<div class="ot-size-options ' + classes.attributeClass + ' ' + item.code + '" ' + styleAttribute +
                            'attribute-code="' + item.code + '" ' +
                            'data-attribute-id="' + item.id + '" ' +
                            'style="opacity: 0.5;" ' +
                            'id="swatch-qa-size-options" ' +
                            'attribute-id="' + item.id + '"><div class="container-swatch-attribute">' +
                            label +
                            '<div aria-activedescendant="" ' +
                            'tabindex="0" ' +
                            'aria-invalid="false" ' +
                            'aria-required="true" ' +
                            'role="listbox" ' + listLabel +
                            'class="' + classes.attributeOptionsWrapper + ' clearfix ot-size-swatches">' +
                            options + select +
                            '</div><div class="size-product-tooltip ' + $('<i></i>').text(item.label).html().toLowerCase() + '">' + $.mage.__('All mentioned sizes here are in (US)') + '</div></div>' + input + '</div>'
                        );

                    } else {
                        container.append(
                            '<div class="' + classes.attributeClass + ' ' + item.code + '" ' + styleAttribute +
                            'attribute-code="' + item.code + '" ' +
                            'data-attribute-id="' + item.id + '" ' +
                            'attribute-id="' + item.id + '"><div class="container-swatch-attribute">' +
                            label +
                            '<div aria-activedescendant="" ' +
                            'tabindex="0" ' +
                            'aria-invalid="false" ' +
                            'aria-required="true" ' +
                            'role="listbox" ' + listLabel +
                            'class="' + classes.attributeOptionsWrapper + ' clearfix">' +
                            options + select +
                            '</div><div class="size-product-tooltip ' + $('<i></i>').text(item.label).html().toLowerCase() + '">' + $.mage.__('All mentioned sizes here are in (US)') + '</div></div>' + input + '</div>'
                        );
                    }

                    var defaultAttributeColor = false,
                        orderDefaultAttributeColor;
                    var colorDefault = $widget._getCookies($('input[name="product"]').val());
                    if (!colorDefault) {
                        colorDefault = $('.product-add-form').data('color');
                        if ($widget.options.defaultChildColor) {
                            colorDefault = $widget.options.defaultChildColor;
                        }
                    }

                    $widget.optionsMap[item.id] = {};

                    // Aggregate options array to hash (key => value)
                    $.each(item.options, function (index, value) {
                        if (colorDefault == value.label) {
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
                    if (defaultAttributeColor) {
                        selectedArray.push($widget.element.find('[attribute-id=' + item.id + '] .swatch-option[data-option-label="' + colorDefault + '"]'));
                    } else {
                        selectedArray.push($widget.element.find('[attribute-id=' + item.id + '] .swatch-option')[0]);
                    }
                    /*$(".swatch-opt").appendTo("#product-options-wrapper");*/
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
                $('.size-guide-product').off('click').on("click", function (e) {
                    $('.product-size-guide-container button').trigger('click');
                });

                if (!$widget.element.parent().hasClass('product-options-list')) {
                    $.each(selectedArray, function () {
                        if ($('body').hasClass('web_kr_ko') && $(this).closest('.swatch-attribute').attr('attribute-code') == 'qa_size') {
                            $(this).closest('.swatch-attribute').find('.swatch-attribute-label').addClass('hidden-label');
                            $(this).closest('.swatch-attribute').find('.text-size-custom').addClass('visible-label');
                            return;
                        }
                        if ($(this).hasClass('other-color-options') && this != undefined) {
                            $(this).each(function () {
                                $(this).trigger('click');
                            });
                        } else if (!$(this).hasClass('disabled') && this != undefined) {
                            $(this).trigger('click');
                        } else {
                            if ($(this).parent().find('.swatch-option:not(.disabled)').length) {
                                $($(this).parent().find('.swatch-option:not(.disabled)')[0]).trigger('click');
                            }
                        }

                    });
                }

                $('.other-color-options.selected').trigger('click');

                $('.swatch-attribute').off('click').on("click", function (e) {
                    if (!$(this).hasClass('active')) {
                        $('.box-select-btn').parents().removeClass('active');
                    }
                    if (!$(this).hasClass('qa_size')) {
                        $(this).toggleClass('active-visible');
                    }
                });

                if ($('body').hasClass('catalog-product-view')) {
                    if ($(window).width() < 768) {
                        var e = document.querySelector(".block-btn-sp");
                        if ($(".box-tocart .actions").length > 0) {
                            document.querySelector(".box-tocart .actions").getBoundingClientRect().top - 50 < window.innerHeight ? e.classList.add("is-hidden") : e.classList.remove("is-hidden");
                        }
                    }
                }
                $(".catalog-product-view .page-wrapper .content-wrap .product-info-price-details").clone().addClass('product-price-desktop').insertAfter(".catalog-product-view .page-wrapper .content-wrap.item-detail .swatch-opt .swatch-attribute.color");
            },
            /**
             * Ajax call to update PDP page content
             *
             * @param self
             * @param ajaxUrl
             * @param $widget
             * @param currentPageUrl
             * @param productId
             * @param dataOptionId
             */
            callOtherColorOptions: function (self, ajaxUrl, $widget, currentPageUrl, productId, dataOptionId) {
                $('.other-color-options').on('click', function (event) {

                    var productId = $(this).attr('data-id');
                    var dataOptionId = $(this).attr('data-option-id');

                    $.ajax({
                        showLoader: true,
                        url: ajaxUrl,
                        data: {id: productId, coloroptionid: dataOptionId, productPageUrl: currentPageUrl},
                        type: "POST",
                        dataType: 'json'
                    }).always(function (data) {
                        $('.ot-size-swatches').html("");
                        $('.swatch-attribute.size').show();
                        $('.swatch-attribute.size .swatch-attribute-selected-option').html("");
                        var jsonResponse = data.jsonResponse;
                        if (jsonResponse) {
                            var responseData = JSON.parse(jsonResponse);
                            $('.ot-size-swatches').html(responseData.size_option);
                            $("#product_addtocart_form").attr('action', responseData.addtocart_url);
                            $('input[name=product]').val(productId);
                            $('input[name=item]').val(productId);
                            $('.page-title .base').text(responseData.product_name);
                            $('.sku .value').text(responseData.product_sku);
                            $('.product.attribute.description > .value').html(responseData.product_description);
                            $('#content_des').empty()
                            $('#content_des').html(responseData.product_description)

                            // updating product amasty label
                            if (responseData.amasty_pdp_label_data) {
                                var htmlObject = $(".product-details-label-container").html(responseData.amasty_pdp_label_data);
                                setTimeout(function () {
                                    htmlObject.find('.amasty-label-container').attr('data-mage-init');
                                    htmlObject.trigger('contentUpdated');
                                    $('.product-details-label').show();
                                }, 300);
                            } else {
                                $('.product-details-label').hide();
                            }

                            if (responseData.main_price) {
                                $('.product-info-price .price-final_price .price-wrapper .price').text(responseData.main_price);
                            }

                            var sizeAttr = $("div[data-attribute-code='qa_size']");
                            self.options.swatchPriceDetails = responseData.price;
                            self.options.mediaGalleryInitial = responseData.json_image;

                            $widget._OnChangeSize(sizeAttr, $widget);

                            // $widget._loadMedia();

                            var thumbPo = 1;

                            if (!$('body').hasClass('catalog-category-view')) {
                                history.pushState(null, '', responseData.product_url);
                            }

                            $.each(self.options.mediaGalleryInitial, function (index, jsonObject) {

                                $('.image_' + thumbPo + ' img').prop('src', jsonObject.full);
                                $('.image_' + thumbPo + ' img').attr('data-id', thumbPo);
                                if (thumbPo == 4) {
                                    return false;
                                }
                                thumbPo++;
                            });

                            if (!$('body').hasClass('web_kr_ko')) {
                                $('.size-swatch-option').first().click();
                                $('.swatch-attribute.qa_size ').removeClass('active-visible');
                            }
                            $('.catalog-product-view').find('.custom-pdp-loader').hide();
                            $('#swatch-qa-size-options').removeClass('ot-size-options')
                            $("#swatch-qa-size-options").css({"opacity": "1"});

                            if ($('body').hasClass('web_kr_ko')) {
                                let optionIds = [];
                                let i = 0;
                                var previesSze = $('.swatch-attribute.qa_size').attr('data-option-selected')
                                $(".size-swatch-option").each(function (position, sizeOption) {
                                    optionIds[i] = sizeOption.dataset.optionId;
                                    if (!$(this).hasClass('size-swatch-disabled')) {
                                        if (previesSze && previesSze == sizeOption.dataset.optionId) {
                                            sizeOption.click();
                                            return false;
                                        }
                                    }
                                    i++;
                                });
                                if (previesSze && !optionIds.includes(previesSze)) {
                                    $(".size-swatch-option").each(function (position, sizeOption) {
                                        if (!$(this).hasClass('size-swatch-disabled')) {
                                            $(this).first().click();
                                            return false;
                                        }
                                    });
                                }
                            }
                            if(responseData.enable_add_to_cart === false) {
                                $('.product-options-bottom').find('.box-tocart').find('#button-alert-custom').attr('style', 'display: none !important');
                                $('.box-quick-purchase').hide();
                                $('.product-options-bottom').find('.box-tocart').find('#product-addtocart-button').attr('style', 'display: none !important');
                                $('#button-oos-custom').attr('style', 'display: none !important');
                                $('.box-quick-purchase, #product-quick-purchase-button').attr('style', 'display: none !important');
                            }
                            if(responseData.coming_soon) {
                                $('.coming-soon-content .coming-soon-label').text(responseData.coming_soon_label);
                                $('.box-quick-purchase').hide();
                                $('.product-options-bottom').find('.box-tocart').find('.actions').find('.action.primary.tocart').hide();
                            } else {
                                $('.coming-soon-content .coming-soon-label').text('');
                                $('.box-quick-purchase').show();
                                $('.product-options-bottom').find('.box-tocart').find('.actions').find('.action.primary.tocart').show();
                            }
                        }
                    })

                })

                if (productId && dataOptionId) {
                    this.callAjaxForColorOptions(self, ajaxUrl, $widget, currentPageUrl, productId, dataOptionId);
                }
            },
            /**
             * Ajax call to update PDP page content
             *
             * @param self
             * @param ajaxUrl
             * @param $widget
             * @param currentPageUrl
             * @param productId
             * @param dataOptionId
             */
            callAjaxForColorOptions: function (self, ajaxUrl, $widget, currentPageUrl, productId, dataOptionId) {

                $.ajax({
                    showLoader: false,
                    url: ajaxUrl,
                    data: {id: productId, coloroptionid: dataOptionId, productPageUrl: currentPageUrl},
                    type: "POST",
                    dataType: 'json'
                }).done(function (data) {
                    $('.ot-size-swatches').html("");
                    $('.swatch-attribute.size').show();
                    $('.swatch-attribute.size .swatch-attribute-selected-option').html("");
                    var jsonResponse = data.jsonResponse;
                    if (jsonResponse) {
                        var responseData = JSON.parse(jsonResponse);
                        $('.ot-size-swatches').html(responseData.size_option);
                        $("#product_addtocart_form").attr('action', responseData.addtocart_url);
                        $('input[name=product]').val(productId);
                        $('input[name=item]').val(productId);
                        $('.page-title .base').text(responseData.product_name);
                        $('.sku .value').text(responseData.product_sku);
                        $('.product.attribute.description > .value').html(responseData.product_description);
                        $('#content_des').empty()
                        $('#content_des').html(responseData.product_description)
                        if (responseData.main_price) {
                            $('.product-info-price .price-final_price .price-wrapper .price').text(responseData.main_price);
                        }

                        var sizeAttr = $("div[data-attribute-code='qa_size']");
                        self.options.swatchPriceDetails = responseData.price;
                        self.options.mediaGalleryInitial = responseData.json_image;

                        $widget._OnChangeSize(sizeAttr, $widget);

                        // $widget._loadMedia();

                        var thumbPo = 1;
                        if (!$('body').hasClass('catalog-category-view')) {
                            history.pushState(null, '', responseData.product_url);
                        }

                        $.each(self.options.mediaGalleryInitial, function (index, jsonObject) {

                            $('.image_' + thumbPo + ' img').prop('src', jsonObject.full);
                            $('.image_' + thumbPo + ' img').attr('data-id', thumbPo);
                            if (thumbPo == 4) {
                                return false;
                            }
                            thumbPo++;
                        });

                        $(document).ajaxComplete(function (event, xhr, options) {
                            $('.swatch-attribute.qa_size ').removeClass('active-visible');
                            if (options.url === ajaxUrl) {
                                $('.swatch-option.size-swatch-option.out-of-stock').removeClass('disabled');
                                setTimeout(function () {
                                    $(".size-swatch-option").each(function (position, sizeOption) {
                                        if (!$(this).hasClass('size-swatch-disabled')) {
                                            if (!$('body').hasClass('web_kr_ko')) {
                                                $(this).first().click();
                                                return false;
                                            }
                                        }
                                    });
                                }, 300);
                            }
                        });
                    }
                })
            },
            /**
             * Get other color swatch options
             *
             * @param self
             * @param currentPageUrl
             * @param graphqlUrl
             * @param container
             * @param classes
             * @param item
             * @param listLabel
             * @param select
             * @param label
             * @param productSku
             * @param partNo
             * @param storeId
             * @param options
             * @param input
             * @param ajaxUrl
             * @param $widget
             */
            getOtherColorSwatchOptions: function (graphqlUrl, productSku, styleCode, storeId, self, ajaxUrl, $widget, currentPageUrl) {
                $.ajax({
                    showLoader: false,
                    url: graphqlUrl,
                    type: "POST",
                    data: JSON.stringify({query: `{othercoloroptionswatch(styleCode:"${styleCode}", productSku:"${productSku}", storeId:"${storeId}"){othercolordetail}}`}),
                    contentType: "application/json"
                }).done(function (data) {
                    if (data) {
                        $.each(data['data'], function (expkey, expvalue) {
                            if (expvalue.othercolordetail) {
                                $('#color-swatches').empty();
                                $('#color-swatches').append(expvalue.othercolordetail);
                            }
                        })
                    }
                });

                $(document).ajaxComplete(function (event, xhr, options) {
                    if (options.url === graphqlUrl) {
                        setTimeout(function () {
                            var targetOption = '.other-color-options[data-option-style="' + productSku + '"]';
                            var productId = $(targetOption).attr('data-id');
                            var dataOptionId = $(targetOption).attr('data-option-id');
                            self.callOtherColorOptions(self, ajaxUrl, $widget, currentPageUrl, productId, dataOptionId);
                            $(targetOption).click();
                        }, 300);
                    }
                });
            },
            /**
             * On change size
             *
             * @param $this
             * @param $widget
             * @private
             */
            _OnChangeSize: function ($this, $widget) {
                var $parent = $this,
                    attributeId = $parent.data('attribute-id'),
                    $input = $parent.find('.' + $widget.options.classes.attributeInput);

                if ($widget.productForm.length > 0) {
                    $input = $widget.productForm.find(
                        '.' + $widget.options.classes.attributeInput + '[name="super_attribute[' + attributeId + ']"]'
                    );
                }

                if ($this.val() > 0) {
                    $parent.attr('data-option-selected', $this.val());
                    $input.val($this.val());
                } else {
                    $parent.removeAttr('data-option-selected');
                    $input.val('');
                }

            },
            /**
             * Render swatch options by part of config
             *
             * @param {Object} config
             * @param {String} controlId
             * @returns {String}
             * @private
             */
            _RenderSwatchOptions: function (config, controlId, attributeCode) {
                var optionConfig = this.options.jsonSwatchConfig[config.id],
                    optionClass = this.options.classes.optionClass,
                    sizeConfig = this.options.jsonSwatchImageSizeConfig,
                    moreLimit = parseInt(this.options.numberToShow, 10),
                    moreClass = this.options.classes.moreButton,
                    moreText = this.options.moreButtonText,
                    countAttributes = 0,
                    restockDataColor = this.options.jsonConfig.restock_data_color,
                    restockDataSize = this.options.jsonConfig.restock_data_size,
                    restockProductData = this.options.jsonConfig.restock_product_data,
                    stockStatusSize = this.options.jsonConfig.stock_status_size,
                    productStatus = this.options.jsonConfig.product_status,
                    restockData = this.options.jsonConfig.restock_data,
                    colorOptionProductId = this.options.jsonConfig.color_option_product,
                    sizeForDisplay = this.options.jsonConfig.productSizeForDisplay,
                    html = '',
                    productUrl = this.options.productUrl,
                    styleCodes = this.options.jsonConfig.styleCodes,
                    styleCodesParent = this.options.jsonConfig.styleCodesParent,
                    relationSwatches = this.options.jsonConfig.relationSwatches,
                    isInStock = this.options.jsonConfig.isInStock;
                var relationProductColor = this.options.jsonConfig.relationProductColor;

                let selectedColorVariant = 0;
                let optionIsInStock = 0;
                let sizeStatus = [];

                if (!this.options.jsonSwatchConfig.hasOwnProperty(config.id)) {
                    return '';
                }

                var productId = this.options.productId;
                $.each(config.options, function (index) {
                    var id,
                        type,
                        value,
                        thumb,
                        label,
                        width,
                        height,
                        attr,
                        swatchImageWidth,
                        swatchImageHeight;

                    if (!optionConfig.hasOwnProperty(this.id)) {
                        return '';
                    }

                    // Add more button
                    if (moreLimit === countAttributes++) {
                        html += '<a href="#" class="' + moreClass + '"><span>' + moreText + '</span></a>';
                    }

                    var skuParent = '';
                    var relationImageSwatch = '';
                    var relationProductColorLabel = '';

                    if (attributeCode == "color" || attributeCode == "color_code") {
                        $.each(this.products, function (index, productId) {
                            skuParent = styleCodesParent[productId];
                            relationImageSwatch = relationSwatches[productId];
                            /*relationProductColorLabel = relationProductColor[productId];*/
                        });
                    }

                    id = this.id;
                    type = parseInt(optionConfig[id].type, 10);
                    value = optionConfig[id].hasOwnProperty('value') ?
                        $('<i></i>').text(optionConfig[id].value).html() : '';
                    thumb = optionConfig[id].hasOwnProperty('thumb') ? optionConfig[id].thumb : '';
                    width = _.has(sizeConfig, 'swatchThumb') ? sizeConfig.swatchThumb.width : 110;
                    height = _.has(sizeConfig, 'swatchThumb') ? sizeConfig.swatchThumb.height : 90;
                    label = this.label ? $('<i></i>').text(this.label).html() : '';

                    var sizeIdArr = [];
                    $.each(restockDataSize, function (index, size) {
                        if (size[id]) {
                            sizeIdArr.push(size[id]);
                        }
                    });

                    var sizeStockArr = [];
                    $.each(productStatus, function (index, sizeValue) {
                        if (sizeValue[id]) {
                            sizeStockArr.push(sizeValue[id]);
                        }
                    });

                    attr =
                        ' id="' + controlId + '-item-' + id + '"' +
                        ' index="' + index + '"' +
                        ' aria-checked="false"' +
                        ' aria-describedby="' + controlId + '"' +
                        ' tabindex="0"' +
                        ' data-option-type="' + type + '"' +
                        ' data-id="' + productId + '"' +
                        ' data-option-id="' + id + '"' +
                        ' data-option-style="' + skuParent + '"' +
                        ' data-option-label="' + label + '"' +
                        ' data-option-color-label="' + relationProductColorLabel + '"' +
                        ' aria-label="' + label + '"' +
                        ' role="option"' +
                        ' data-thumb-width="' + width + '"' +
                        ' data-size-product-id="' + restockProductData[id] + '"' +
                        ' data-size-option-id="' + sizeIdArr + '"' +
                        ' data-size-stock-status-id="' + sizeStockArr + '"' +
                        ' data-attribute-code="' + attributeCode + '"' +
                        ' data-thumb-height="' + height + '"';

                    swatchImageWidth = _.has(sizeConfig, 'swatchImage') ? sizeConfig.swatchImage.width : 30;
                    swatchImageHeight = _.has(sizeConfig, 'swatchImage') ? sizeConfig.swatchImage.height : 20;

                    if (attributeCode == 'qa_size') {
                        var restockColorProduct = [];
                        $.each(colorOptionProductId, function (key, sizeCode) {
                            if (sizeCode[id]) {
                                restockColorProduct.push(sizeCode[id]);
                            }
                        });
                        attr += ' data-re-stock-product-id="' + restockColorProduct + '"';
                    }

                    if (attributeCode == 'color_code') {
                        attr += ' data-out-of-stock-product-id="' + restockData[id] + '"';
                        attr += ' data-option-style="' + skuParent + '"';
                    }

                    attr += thumb !== '' ? ' data-option-tooltip-thumb="' + thumb + '"' : '';
                    attr += value !== '' ? ' data-option-tooltip-value="' + value + '"' : '';

                    swatchImageWidth = _.has(sizeConfig, 'swatchImage') ? sizeConfig.swatchImage.width : 30;
                    swatchImageHeight = _.has(sizeConfig, 'swatchImage') ? sizeConfig.swatchImage.height : 20;

                    if (!this.hasOwnProperty('products') || this.products.length <= 0) {
                        attr += ' data-option-empty="true"';
                    }

                    if (type === 0) {
                        // Text
                        if (sizeForDisplay && sizeForDisplay[id] && attributeCode === 'qa_size') {
                            html += '<div class="' + optionClass + ' text" ' + attr + '>' + sizeForDisplay[id] +
                                '</div>';
                        } else if (attributeCode === 'width') {
                            html += '<div class="' + optionClass + ' text option width-option" ' + attr + '>' + (value ? value : label) +
                                '</div>';
                        } else {
                            html += '<div class="' + optionClass + ' text" ' + attr + '>' + (value ? value : label) +
                                '</div>';
                        }
                    } else if (type === 1) {
                        if (value != relationImageSwatch) {
                            value = relationImageSwatch;
                        }
                        html += '<div class="' + optionClass + ' image" ' + attr + ' style="background: url(' + value + ') no-repeat center; background-size: initial;width:' +
                            swatchImageWidth + 'px; height:' + swatchImageHeight + 'px">' + '' + '</div>';
                    } else if (type === 2) {
                        if (value != relationImageSwatch) {
                            value = relationImageSwatch;
                        }
                        html += '<div data-id="' + productId + '" class="' + optionClass + ' image other-color-options" ' + attr + ' style="background: url(' + value + ') no-repeat center; background-size: initial;width:' +
                            swatchImageWidth + 'px; height:' + swatchImageHeight + 'px">' + '' + '</div>';
                    } else if (type === 3) {
                        // Clear
                        if (value != relationImageSwatch) {
                            value = relationImageSwatch;
                        }
                        html += '<div data-id="' + productId + '" class="' + optionClass + ' image other-color-options" ' + attr + ' style="background: url(' + value + ') no-repeat center; background-size: initial;width:' +
                            swatchImageWidth + 'px; height:' + swatchImageHeight + 'px">' + '' + '</div>';
                    } else if (sizeForDisplay && sizeForDisplay[id] && attributeCode === 'qa_size') {
                        html += '<div class="' + optionClass + '" ' + attr + '>' + sizeForDisplay[id] + '</div>';
                    } else {
                        // default
                        html += '<div class="' + optionClass + '" ' + attr + '>' + label + '</div>';
                    }
                });

                return html;
            },
            selectColor: function (option) {
                let optionId = option.data('option-id'),
                    attributeCode = option.data('attribute-code'),
                    productsIds = this.getProductsIds(attributeCode, optionId);
                this.setPriceForSelectedOption(option, productsIds);
                let $productId = option.parents('.product-options-list').data('product'),
                    productImageUrlElement = option.closest('.active-box-container').find('.product.photo.product-item-photo, .product-item-link'),
                    productTextUrlElement = productImageUrlElement.parent().find('.text-box .product-item-link'),
                    baseProductUrl = productImageUrlElement.attr('href'),
                    productListItem = option.closest('li'),
                    preselectMethod = this.options.jsonConfig.preselectMethod;
                if (preselectMethod === 'localStorage') {
                    customerData.set($productId, option.data('option-id'));
                } else {
                    if (baseProductUrl && baseProductUrl.indexOf('#') !== -1) {
                        baseProductUrl = baseProductUrl.substring(0, baseProductUrl.indexOf('#'));
                    }
                    if (baseProductUrl) {
                        let optionUrl = baseProductUrl + '#' + option.data('option-style');
                        productImageUrlElement.attr('href', optionUrl);
                        productTextUrlElement.attr('href', optionUrl);
                        productListItem.attr('onClick', 'window.location.href="' + optionUrl + '"');
                    }
                }
                if (option.closest('.qa_size').length === 0) {
                    option.addClass('selected');
                }

                if (option.closest('.footwear_size').length === 0) {
                    option.addClass('selected');
                }
            },
            setPriceForSelectedOption: function (option, productsIds) {
                let productId = _.first(productsIds),
                    optionPrices = this.options.jsonConfig.optionPrices[productId],
                    basePriceValue = optionPrices.oldPrice.amount,
                    finalPriceValue = optionPrices.finalPrice.amount,
                    basePriceFormatted = priceUtils.formatPrice(basePriceValue),
                    currentItem = $(option).closest('.product-item'),
                    priceBox = currentItem.find('.price-box');
                if (finalPriceValue && basePriceValue !== finalPriceValue) {
                    const finalPriceFormatted = priceUtils.formatPrice(finalPriceValue);
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
                    priceBox.html(`
                        <span class="normal-price">
                            <span class="price">
                                ${basePriceFormatted}
                            </span>
                        </span>
                    `);
                }
            },
            preselectColor: function () {
                let element = this.element,
                    productStyle = element.parents('li.item.product.product-item').find('[data-sku]').attr('data-sku'),
                    options = element.find('.swatch-option');
                _.each(options, function (element) {
                    let option = $(element);
                    if (option.data('option-style') === productStyle) {
                        this.selectColor(option);
                    }
                }, this);
            },
            _RenderFormInputQuickview: function (config) {
                return '<input class="' + this.options.classes.attributeInput + ' super-attribute-select" ' +
                    'name="super_attribute[' + config.id + ']" ' +
                    'type="text" ' +
                    'value="" ' +
                    'data-selector="super_attribute[' + config.id + ']" ' +
                    'data-validate="{required: true}" ' +
                    'aria-required="true" ' +
                    'data-msg-required="' + $t('Choose your size') + '" ' +
                    'aria-invalid="false">';
            },
            /**
             * Callback for product media
             *
             * @param {Object} $this
             * @param {String} response
             * @param {Boolean} isInProductView
             * @private
             */
            _ProductMediaCallback: function ($this, response, isInProductView) {
                var $main = isInProductView ? $this.parents('.column.main') : $this.parents('.product-item-info'),
                    $widget = this,
                    images = [],

                    /**
                     * Check whether object supported or not
                     *
                     * @param {Object} e
                     * @returns {*|Boolean}
                     */
                    support = function (e) {
                        return e.hasOwnProperty('large') && e.hasOwnProperty('medium') && e.hasOwnProperty('small');
                    };

                if (_.size($widget) < 1 || !support(response)) {
                    this.updateBaseImage(this.options.mediaGalleryInitial, $main, isInProductView);
                    this.updateSecondBaseImage(this.options.mediaGalleryInitial, $main, isInProductView);
                    return;
                }

                images.push({
                    full: response.large,
                    img: response.medium,
                    thumb: response.small,
                    isMain: true,
                    secondBase: response.base_mouseover_image
                });

                if (response.hasOwnProperty('gallery')) {
                    $.each(response.gallery, function () {
                        if (!support(this) || response.large === this.large) {
                            return;
                        }
                        images.push({
                            full: this.large,
                            img: this.medium,
                            thumb: this.small
                        });
                    });
                }

                this.updateBaseImage(images, $main, isInProductView);
                this.updateSecondBaseImage(images, $main, isInProductView);
            },
            /**
             * Update [gallery-placeholder] or [product-image-photo]
             * @param {Array} images
             * @param {jQuery} context
             * @param {Boolean} isInProductView
             */
            updateSecondBaseImage: function (images, context, isInProductView) {
                var justAnImage = images[0];

                if (justAnImage && justAnImage.img && !isInProductView) {
                    var image = justAnImage.secondBase
                    if (!justAnImage.secondBase) {
                        image = $('#amasty-shopby-product-list .products.wrapper').data('seconbase');
                    }
                    context.find('.seconds-base .product-image-photo-second').attr('src', image);
                    context.find('.base-image-hover .product-image-photo-second').attr('src', justAnImage.img);
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
                if ($this.hasClass('selected')) {
                    return false;
                }
                if ($this.hasClass('swatches-image-disable')) {
                    return false;
                }
                $('.content-wrap.item-detail .area-content .box-select .box-select-btn .text .js-select-text').css({'text-decoration': ''});

                var $parent = $this.parents('.' + $widget.options.classes.attributeClass),
                    $wrapper = $this.parents('.' + $widget.options.classes.attributeOptionsWrapper),
                    $label = $parent.find('.' + $widget.options.classes.attributeSelectedOptionLabelClass),
                    attributeId = $parent.attr('attribute-id'),
                    $input = $parent.find('.' + $widget.options.classes.attributeInput),
                    checkAdditionalData = JSON.parse(this.options.jsonSwatchConfig[attributeId]['additional_data']),
                    $productId = $this.parents('.product-options-list').data('product');

                let finalChildProductPrice = $this.data('final-child-product-price');
                let childProductId = $this.data('product-id');

                $('.product-info-price .price-final_price .price-wrapper .price').text(finalChildProductPrice);

                if ($widget.inProductList) {
                    $input = $widget.productForm.find(
                        '.' + $widget.options.classes.attributeInput + '[name="super_attribute[' + attributeId + ']"]'
                    );
                }

                if ($this.hasClass('size-swatch-disabled')) {
                    return;
                }

                $('.swatch-attribute-selected-option').removeClass('out-of-stock');
                $parent.find('.swatch-attribute-label').removeClass('hidden-label');
                $parent.find('.text-size-custom').removeClass('visible-label');

                if ($this.hasClass('selected')) {
                    $parent.removeAttr('option-selected').find('.selected').removeClass('selected').removeClass('selected-disable');
                    $input.val('');
                    $label.text('');
                    $this.attr('aria-checked', false);
                    this.addClassSwatchOption($parent);
                } else {
                    $parent.attr('data-option-selected', $this.data('option-id')).find('.selected').removeClass('selected');
                    $label.text($this.data('option-label'));
                    $input.val($this.data('option-id'));
                    $input.attr('data-attr-name', this._getAttributeCodeById(attributeId));
                    $this.addClass('selected');
                    $widget._toggleCheckedAttributes($this, $wrapper);
                }

                $widget._Rebuild();
                $(document).trigger('updateMsrpPriceBlock',
                    [
                        _.findKey($widget.options.jsonConfig.index, $widget.options.jsonConfig.defaultValues),
                        $widget.options.jsonConfig.optionPrices
                    ]);

                if (parseInt(checkAdditionalData['update_product_preview_image'], 10) === 1) {
                    if (!this.inProductList) {
                        $widget._loadMediaProduct($this.parent());
                    } else {
                        $widget._loadMedia();
                    }
                }

                $input.trigger('change');
                this.getColor($this, $widget);

                setTimeout(function () {
                    var selectedSizeiSOutOfStock = $('.swatch-attribute.qa_size').find('.swatch-option.text.selected').hasClass('out-of-stock');
                    var selectedProducId = $('.swatch-option.text.selected.out-of-stock').data('size-product-id');
                    if (selectedSizeiSOutOfStock) {
                        var restockProductId = $('.swatch-attribute.qa_size').find('.swatch-option.text.selected').data('product-id');
                        $('#button-alert-custom').data('product-id', restockProductId);
                        $('#button-alert-custom').show();
                        $('.box-quick-purchase').hide();
                        $('.preorder_note').empty();
                        $('.product-options-bottom').find('.box-tocart').find('#product-addtocart-button').attr('style', 'display: none !important');
                        $('#button-oos-custom').attr('style', 'display: none !important');
                    }
                }, 10);

                if ($this.hasClass('out-of-stock') && $this.hasClass('selected')) {
                    setTimeout(function () {
                        $('#button-alert-custom').show();
                        $('.box-quick-purchase').hide();
                        $('.preorder_note').empty();
                        $('.product-options-bottom').find('.box-tocart').find('#product-addtocart-button').attr('style', 'display: none !important');
                        $('#button-oos-custom').attr('style', 'display: none !important');
                        var restockPId = $this.data('product-id');
                        $('#button-alert-custom').attr('data-product-id', restockPId);
                    }, 10);
                }
                if ($this.hasClass('out-of-stock')) {
                    $('#button-alert-custom').show();
                    $('.box-quick-purchase').hide();
                    $('.preorder_note').empty();
                    $('.product-options-bottom').find('.box-tocart').find('#product-addtocart-button').attr('style', 'display: none !important');
                    $('#button-oos-custom').attr('style', 'display: none !important');
                    var rPId = $this.data('product-id');
                    $('#button-alert-custom').attr('data-product-id', rPId);
                } else {
                    $('.product-options-bottom').find('.box-tocart').find('#button-alert-custom').attr('style', 'display: none !important');
                    $('#product-addtocart-button').show();
                    $('.box-quick-purchase').show();
                    $('#button-oos-custom').attr('style', 'display: none !important');
                }

                var relationProductColorLabel = $('.swatch-option.image.selected').data('option-color-label');
                if (relationProductColorLabel) {
                    $('.swatch-attribute.color_code.color').find('.swatch-attribute-selected-option').text('');
                    $('.swatch-attribute.color_code.color').find('.swatch-attribute-selected-option').text(relationProductColorLabel);
                    $('#swatch-qa-size-options').removeClass('active-visible');
                }
                if (!$('body').hasClass('web_kr_ko')) {
                    setTimeout(function () {
                        if ($this.attr('data-child-products-stock-status') === "1" &&
                            $this.attr('data-child-products-notify-me') === "0") {
                            $('.product-options-bottom').find('.box-tocart').find('#product-addtocart-button').attr('style', 'display: none !important');
                            $('#button-oos-custom').attr('style', 'background-color: #e7e8ea !important; color: #9d9d9d !important')
                            $('#button-oos-custom').show();
                            $('#button-alert-custom').attr('style', 'opacity: 0; height: 0;')
                            $('.box-quick-purchase, #product-quick-purchase-button').attr('style', 'display: none !important');
                        } else if ($this.hasClass('out-of-stock') && $this.hasClass('selected')) {
                            $('#button-alert-custom').attr('style', 'opacity: 1; height: auto;')
                            $('.box-quick-purchase').hide();
                            $('.preorder_note').empty();
                            $('.product-options-bottom').find('.box-tocart').find('#product-addtocart-button').attr('style', 'display: none !important');
                            $('#button-oos-custom').attr('style', 'display: none !important');
                            var restockPId = $this.data('product-id');
                            $('#button-alert-custom').attr('data-product-id', restockPId);
                        }
                    }, 10);
                }

                setTimeout(function () {
                    if ($('body').hasClass('web_kr_ko') &&
                        $this.attr('data-child-products-stock-status') === "1" &&
                        $this.attr('data-child-products-notify-me') === "0") {
                        $('.product-options-bottom').find('.box-tocart').find('#product-addtocart-button').attr('style', 'display: none !important');
                        $('#button-oos-custom').attr('style', 'background-color: #e7e8ea !important; color: #9d9d9d !important')
                        $('#button-oos-custom').show();
                        $('#button-alert-custom').attr('style', 'opacity: 0; height: 0;');
                        $('.box-quick-purchase, #product-quick-purchase-button').attr('style', 'display: none !important');
                    } else if ($this.hasClass('out-of-stock') && $this.hasClass('selected')) {
                        $('#button-alert-custom').attr('style', 'opacity: 1; height: auto;')
                        $('.box-quick-purchase').hide();
                        $('.preorder_note').empty();
                        $('.product-options-bottom').find('.box-tocart').find('#product-addtocart-button').attr('style', 'display: none !important');
                        $('#button-oos-custom').attr('style', 'display: none !important');
                        var restockPId = $this.data('product-id');
                        $('#button-alert-custom').attr('data-product-id', restockPId);
                    } else if($this.hasClass('size-swatch-force-out-of-stock') && $this.hasClass('selected')){
                        $('.product-options-bottom').find('.box-tocart').find('#product-addtocart-button').attr('style', 'display: none !important');
                        //$('#button-so-custom').attr('style', 'background-color: #e7e8ea !important; color: #9d9d9d !important')
                        $('#button-so-custom').show();
                        $('#button-alert-custom').attr('style', 'display: none !important');
                        $('.box-quick-purchase, #product-quick-purchase-button').attr('style', 'display: none !important');
                        $('.content-wrap.item-detail .product-info-main').addClass("force-oos-product");
                    }else {
                        /*$('.product-options-bottom').find('.box-tocart').find('#product-addtocart-button').removeAttr('disabled');*/
                        $('.product-options-bottom').find('.box-tocart').find('#product-addtocart-button').removeClass('disabled');
                        $('#button-so-custom').attr('style', 'display: none !important');
                        $('.content-wrap.item-detail .product-info-main').removeClass("force-oos-product");
                    }
                    if ($this.hasClass('coming-soon-child-product')) {
                        $('.box-quick-purchase').hide();
                        $('.product-options-bottom').find('.box-tocart').find('#product-addtocart-button').attr('style', 'display: none !important');
                        $('.content-wrap.item-detail .block-btn-sp .btn-cart').css('display','none');
                    }
                }, 10);


                this.updateAddToCartForm();
                this.updateProductSku();

                return false;
            },
            _GetProductsIdsByAttributeId: function (attributeId, attributeType) {
                var result = [],
                    index = attributeType === 'color_code' ? 0 : 1;
                this.options.jsonConfig.attributes[index].options.forEach(function (element) {
                    if (String(element.id) === String(attributeId)) {
                        result = element.products;
                    }
                });
                return result;
            },
            validateField: function ($widget) {
                var selectedBox = $widget.element.find('.super-attribute-select'),
                    isValid = true;
                selectedBox.each(function () {
                    if (!$(this).val()) {
                        isValid = false;
                    }
                });
                return isValid;
            },
            updateProductSku: function () {
                let skuElement = $('.product-info-stock-sku .product.attribute.sku .value');
                var productSku = $('.swatch-option.image.selected').data('option-style');

                if (productSku) {
                    skuElement.text(productSku);
                }
            },
            updateAddToCartForm: function () {
                let jsonConfig = this.options.jsonConfig;
                if (!jsonConfig.isPdp) {
                    return;
                }
                let selectedStyleCode = $('.swatch-option.image.selected').data('option-style'),
                    productId = jsonConfig.relatedParents[selectedStyleCode] ? jsonConfig.relatedParents[selectedStyleCode] : null;
                if (!productId) {
                    return;
                }
                let form = $('#product_addtocart_form'),
                    url = form.attr('action');
                form.find('input[name="product"]').val(productId);
                form.find('input[name="item"]').val(productId);
                form.attr('action', url.replace(/\/product\/\d+\//, '/product/' + productId + '/'));
            },
            resetSizeForDisplay: function ($this, $widget) {
                $.each($widget.element.find('.swatch-attribute.qa_size .swatch-attribute-options .swatch-option'), function () {
                    $(this).text($(this).attr('aria-label'));
                })
            },
            getColor: function ($this, $widget) {
                $widget.element.find('.swatch-attribute.color_code .swatch-attribute-selected-option').text('');
                if ($widget.element.find('.swatch-attribute.color_code .selected').length) {
                    var productId = $widget.getProductDetails();
                    if (productId) {
                        if (typeof ($widget.options.jsonAttributeProduct) != 'undefined') {
                            if (typeof ($widget.options.jsonAttributeProduct[productId]) != 'undefined') {
                                $('.swatch-attribute.color_code .swatch-attribute-selected-option').text($widget.options.jsonAttributeProduct[productId].color);
                            }
                        }
                    }
                }
            },
            showMaximumQuantity: function ($widget) {
                var isPreOrder = this.checkProductIsPreOrder($widget);
                if (this.checkMaximumQty($widget)) {
                    $('.message-qty-validate.qty-stock').remove();
                    if ($('.product-info-main .box-tocart .message-qty-validate.qty-sales').length) {
                        return;
                    }

                    // return false;
                    if ($widget.options.maxQuantityPurchase[$widget.getProduct()] && $widget.options.maxQuantityPurchase[$widget.getProduct()].maxQty) {
                        // return;
                        var $maxQuantityPurchase = $widget.options.maxQuantityPurchase[$widget.getProduct()].maxQty;
                        $('.content-wrap.item-detail .area-content .box-select .box-select-options li').removeClass('disabled-select');
                        $('.content-wrap.item-detail .area-content .box-select .box-select-options li').each(function () {
                            if (parseInt($(this).text()) > $maxQuantityPurchase) {
                                $(this).addClass('disabled-select');
                            }
                        });
                        if (parseInt($('.box-tocart .input-text.qty').val()) > $maxQuantityPurchase && !isPreOrder) {
                            this.disabledButton();
                        }
                        if (!this.checkDropdownQty($widget)) {
                            $('.message-qty-validate.qty-stock').remove();
                            if (parseInt($('.box-tocart .input-text.qty').val()) > $maxQuantityPurchase) {
                                var messageHTML = '<div class="message-qty-validate qty-stock mage-error" generated="true">' + $t('Only %1 Left in Stock').replace('%1', $maxQuantityPurchase) + '</div>';
                                $(messageHTML).insertAfter($('.box-tocart .input-text.qty'));
                            }
                        }
                    }
                }
            },
            showMaximumQuantitySales: function ($widget) {
                if (this.checkMaximumQtySales($widget)) {
                    try {
                        if (this.validateField($widget)) {
                            $('.message-qty-validate.qty-sales').remove();
                            var $maxQuantityPurchase = '';
                            if ($widget.options.maxQuantityPurchase[$widget.getProduct()] && $widget.options.maxQuantityPurchase[$widget.getProduct()].maxQtySales) {
                                $maxQuantityPurchase = $widget.options.maxQuantityPurchase[$widget.getProduct()].maxQtySales;
                            }
                            if (!$maxQuantityPurchase) {
                                return;
                            }
                            if ($('.minicart-items .cart-item-qty[data-cart-item-id="' + $optionsSales.sku + '"]').length) {
                                $maxQuantityPurchase = parseInt($maxQuantityPurchase) - parseInt($('.minicart-items .cart-item-qty[data-cart-item-id="' + $optionsSales.sku + '"]').val())
                            }
                            if ($('.product-info-main .box-tocart .message-qty-validate.qty-stock').length) {
                                return;
                            }

                            $('.content-wrap.item-detail .area-content .box-select .box-select-options li').removeClass('disabled-select');
                            $('.content-wrap.item-detail .area-content .box-select .box-select-options li').each(function () {
                                if (parseInt($(this).text()) > $maxQuantityPurchase) {
                                    $(this).addClass('disabled-select');
                                }
                            });
                            if (parseInt($('.box-tocart .input-text.qty').val()) > $maxQuantityPurchase) {
                                this.disabledButton();
                            }
                            if (!this.checkDropdownQty($widget)) {
                                $('.message-qty-validate.qty-sales').remove();
                                if (parseInt($('.box-tocart .input-text.qty').val()) > $maxQuantityPurchase) {
                                    var messageHTML = '<div class="message-qty-validate qty-sales mage-error" generated="true">' + $t('Only %1 Left Can Purchase').replace('%1', $maxQuantityPurchase) + '</div>';
                                    $(messageHTML).insertAfter($('.box-tocart .input-text.qty'));
                                    return;
                                }
                            }
                        }
                    } catch (e) {
                        console.log(e);
                    }
                    $('#product_addtocart_form div.mage-error').remove();
                }
            },
            checkMaximumQtySales: function ($widget) {
                if ($widget.options.enableMaxQuantitySales) {
                    return true;
                }
                return false
            },
            checkMaximumQty: function ($widget) {
                if ($widget.options.enableMaxQuantity) {
                    return true;
                }
                return false
            },
            checkDropdownQty: function ($widget) {
                if ($widget.options.quantityDropdown) {
                    return true;
                }
                return false
            },
            showMaximumQuantityQty: function ($widget) {
                if (this.checkMaximumQty($widget)) {
                    $('.message-qty-validate.qty-stock').remove();
                    if ($('.product-info-main .box-tocart .message-qty-validate.qty-sales').length) {
                        return;
                    }

                    $('#product-addtocart-button,#instant-purchase .instant-purchase').prop("disabled", false);
                    $('.box-quick-purchase .quick-purchase').prop("disabled", false);
                    $('.content-wrap.item-detail .block-btn-sp .btn-cart').prop("disabled", false);
                    $('.content-wrap.item-detail .area-content .box-select .box-select-btn .text .js-select-text').css({'text-decoration': ''});
                    $('#product-addtocart-button').removeAttr('disabled');
                    if ($widget.options.maxQuantityPurchase[$widget.getProduct()] && $widget.options.maxQuantityPurchase[$widget.getProduct()].maxQty) {
                        var $maxQuantityPurchase = $widget.options.maxQuantityPurchase[$widget.getProduct()].maxQty;
                        $('.content-wrap.item-detail .area-content .box-select .box-select-options li').removeClass('disabled-select');
                        $('.content-wrap.item-detail .area-content .box-select .box-select-options li').each(function () {
                            if (parseInt($(this).text()) > $maxQuantityPurchase) {
                                $(this).addClass('disabled-select');
                            }
                        });
                        if ($('.swatch-attribute-selected-option.out-of-stock').length) {
                            this.disabledButton();
                        } else {
                            if (parseInt($('.box-tocart .input-text.qty').val()) > $maxQuantityPurchase) {
                                this.disabledButton();
                            }
                        }
                        if (!this.checkDropdownQty($widget)) {
                            $('.message-qty-validate.qty-stock').remove();
                            if (parseInt($('.box-tocart .input-text.qty').val()) > $maxQuantityPurchase) {
                                var messageHTML = '<div class="message-qty-validate qty-stock mage-error" generated="true">' + $t('Only %1 Left in Stock').replace('%1', $maxQuantityPurchase) + '</div>';
                                $(messageHTML).insertAfter($('.box-tocart .input-text.qty'));

                            }
                        }
                    }
                }
            },
            showMaximumQuantityQtySales: function ($widget) {
                try {
                    if (this.checkMaximumQtySales($widget) && $widget.options.maxQuantityPurchase[$widget.getProduct()] && $widget.options.maxQuantityPurchase[$widget.getProduct()].maxQtySales) {
                        if (this.validateField($widget)) {
                            $('.message-qty-validate.qty-sales').remove();
                            var $optionsSales = $widget.options.maxQuantityPurchase[$widget.getProduct()];
                            var $maxQuantityPurchase = $optionsSales.maxQtySales;
                            if (!$maxQuantityPurchase) {
                                return;
                            }
                            if ($('.minicart-items .cart-item-qty[data-cart-item-id="' + $optionsSales.sku + '"]').length) {
                                $maxQuantityPurchase = parseInt($maxQuantityPurchase) - parseInt($('.minicart-items .cart-item-qty[data-cart-item-id="' + $optionsSales.sku + '"]').val())
                            }

                            if ($('.product-info-main .box-tocart .message-qty-validate.qty-stock').length) {
                                return;
                            }
                            $('#product-addtocart-button,#instant-purchase .instant-purchase').prop("disabled", false);
                            $('.box-quick-purchase .quick-purchase').prop("disabled", false);
                            $('.content-wrap.item-detail .block-btn-sp .btn-cart').prop("disabled", false);
                            $('.content-wrap.item-detail .area-content .box-select .box-select-btn .text .js-select-text').css({'text-decoration': ''});
                            $('#product-addtocart-button').removeAttr('disabled');
                            $('.content-wrap.item-detail .area-content .box-select .box-select-options li').removeClass('disabled-select');
                            $('.content-wrap.item-detail .area-content .box-select .box-select-options li').each(function () {
                                if (parseInt($(this).text()) > $maxQuantityPurchase) {
                                    $(this).addClass('disabled-select');
                                }
                            });
                            if ($('.swatch-attribute-selected-option.out-of-stock').length) {
                                this.disabledButton();
                            } else {
                                if (parseInt($('.box-tocart .input-text.qty').val()) > $maxQuantityPurchase) {
                                    this.disabledButton();
                                }
                            }
                            if (!this.checkDropdownQty($widget)) {
                                $('.message-qty-validate.qty-sales').remove();
                                if (parseInt($('.box-tocart .input-text.qty').val()) > $maxQuantityPurchase) {
                                    var messageHTML = '<div class="message-qty-validate qty-sales mage-error" generated="true">' + $t('Only %1 Left Can Purchase').replace('%1', $maxQuantityPurchase) + '</div>';
                                    $(messageHTML).insertAfter($('.box-tocart .input-text.qty'));
                                    return;
                                }
                            }
                        }
                        $('#product_addtocart_form div.mage-error').remove();
                    }
                } catch (e) {
                    console.log(e);
                }
            },
            disabledButton: function () {
                $('.content-wrap.item-detail .block-btn-sp .btn-cart').attr('disabled', 'disabled');
                $('#product-addtocart-button,#instant-purchase .instant-purchase').attr('disabled', 'disabled');
                $('.box-quick-purchase .quick-purchase').attr('disabled', 'disabled');
                $('#product-addtocart-button').attr('disabled', 'disabled');
                $('.content-wrap.item-detail .area-content .box-select .box-select-btn .text .js-select-text').css({'text-decoration': 'line-through'});
            },

            addClassSwatchOption: function ($parent) {
                $parent.find('.swatch-attribute-label').addClass('hidden-label');
                $parent.find('.text-size-custom').addClass('visible-label');
            },
            /**
             * Event listener
             *
             * @private
             */
            _EventListener: function () {
                var $widget = this,
                    options = this.options.classes,
                    target;

                $widget.element.on('click', '.' + options.optionClass, function (e) {
                    e.preventDefault();
                    return $widget._OnClick($(this), $widget);
                });

                $widget.element.on('change', '.' + options.selectClass, function (e) {
                    e.preventDefault();
                    return $widget._OnChange($(this), $widget);
                });

                $widget.element.on('click', '.' + options.moreButton, function (e) {
                    e.preventDefault();

                    var attrOnClick = $(this).parents('.product-item-info').attr('onclick');
                    if (typeof attrOnClick !== 'undefined' && attrOnClick !== false) {
                        $(this).parents('.product-item-info').removeAttr('onclick');
                    }

                    return $widget._OnMoreClick($(this));
                });

                $widget.element.on('keydown', function (e) {
                    if (e.which === 13) {
                        target = $(e.target);

                        if (target.is('.' + options.optionClass)) {
                            return $widget._OnClick(target, $widget);
                        } else if (target.is('.' + options.selectClass)) {
                            return $widget._OnChange(target, $widget);
                        } else if (target.is('.' + options.moreButton)) {
                            e.preventDefault();

                            return $widget._OnMoreClick(target);
                        }
                    }
                });
            },
            /**
             * Load media gallery using ajax or json config.
             *
             * @private
             */
            _loadMediaProduct: function ($element) {
                var $main = this.inProductList ?
                        this.element.parents('.product-item-info') :
                        this.element.parents('.column.main'),
                    images;

                if (this.options.useAjax) {
                    this._debouncedLoadProductMedia();
                } else {
                    images = this.options.jsonConfig.images[this.getProductDetails()];
                    if (!$element.find('.selected').length) {
                        images = this.options.mediaGalleryInitial;
                    } else {
                        if (!images) {
                            images = this.options.mediaGalleryInitial;
                        }
                    }
                    this.updateBaseImage(this._sortImages(images), $main, !this.inProductList);
                }
            },
            /**
             * Get chosen product
             *
             * @returns int|null
             */
            getProductDetails: function () {
                var products = this._CalcProductsDetails();

                return _.isArray(products) ? products[0] : null;
            },
            /**
             * Check if images to update are initial and set their type
             * @param {Array} images
             */
            _setImageType: function (images) {
                images.map(function (img) {
                    if (!img.type) {
                        img.type = 'image';
                    }
                });

                return images;
            },
            /**
             * Rebuild container
             *
             * @private
             */
            _Rebuild: function () {
                var $widget = this,
                    stockMessage = '',
                    buttonTitle = '',
                    controls = $widget.element.find('.' + $widget.options.classes.attributeClass + '[data-attribute-id]'),
                    selected = controls.filter('[data-option-selected]');

                let selectedOptionValue = $('.swatch-attribute.qa_size').find('.size-swatch-option.selected');
                // Enable all options
                $widget._Rewind(controls);

                // done if nothing selected
                if (selected.length <= 0) {
                    return;
                }
                // Disable not available options
                controls.each(function () {
                    var $this = $(this),
                        id = $this.data('attribute-id'),
                        products = $widget._CalcProducts(id);

                    if (selected.length === 1 && selected.first().data('attribute-id') === id) {
                        return;
                    }
                    $this.find('[data-option-id]').each(function () {
                        var $element = $(this),
                            option = $element.data('option-id');

                        if (!$widget.optionsMap.hasOwnProperty(id) || !$widget.optionsMap[id].hasOwnProperty(option) ||
                            $element.hasClass('selected') ||
                            $element.is(':selected')) {
                            return;
                        }

                        if (_.intersection(products, $widget.optionsMap[id][option].products).length <= 0) {
                            /*$element.addClass('disabled');*/
                        }
                    });
                });

                var stockMessageSelector = '',
                    buttonLabelSelector = '#product-addtocart-button span',
                    productBlock = this.element.parents(this.options.selectorProduct);

                if (selectedOptionValue.hasClass('pre-order-item')) {

                }

                if (selectedOptionValue.hasClass('size-swatch-force-out-of-stock')) {
                    $('.product-options-bottom').find('.box-tocart').find('#product-addtocart-button').attr('style', 'display: none !important');
                    $('#button-so-custom').attr('style', 'background-color: #e7e8ea !important; color: #9d9d9d !important')
                    $('#button-so-custom').show();
                    $('#button-alert-custom').attr('style', 'opacity: 0; height: 0;')
                    //$('#button-alert-custom').attr('style', 'display: block !important');
                    $('.box-quick-purchase, #product-quick-purchase-button').attr('style', 'display: none !important');
                    $('.content-wrap.item-detail .product-info-main').addClass("force-oos-product");
                } else {
                    $('#button-so-custom').attr('style', 'display: none !important');
                    $('.content-wrap.item-detail .product-info-main').removeClass("force-oos-product");
                }

                if (productBlock.hasClass('product-info-main')) {
                    stockMessageSelector = '.product-info-price .product-info-stock-sku .stock span';
                } else {
                    stockMessageSelector = '.preorder_note'
                }
                this.stockMessageBlock = productBlock.find(stockMessageSelector).last();
                this.buttonLabelBlock = productBlock.find(buttonLabelSelector);
                this.originalStockMessage = this.stockMessageBlock.html();
                this.originalButtonTitle = this.buttonLabelBlock.html();

                buttonTitle = selectedOptionValue.attr('data-button-label');
                if (selectedOptionValue.hasClass('pre-order-item')) {
                    stockMessage = selectedOptionValue.attr('data-pre-order-note');
                }

                if (!buttonTitle) {
                    buttonTitle = $widget.originalButtonTitle;
                }

                $('.preorder_note').empty();
                if (typeof buttonTitle === 'string' && buttonTitle.toLowerCase() === 'pre-order') {
                    $('.box-quick-purchase, #product-quick-purchase-button').attr('style', 'display: none !important');
                } else {
                    $('.box-quick-purchase, #product-quick-purchase-button').attr('style', 'display: block !important');
                }

                if (selectedOptionValue.hasClass('pre-order-item')) {
                    $('.preorder_note').show();
                    $('.block-variation-container').addClass('pre-order-note-msg');
                    $('.area-content .inner').addClass('pre-order-note-msg');
                    $('.preorder_note').append(stockMessage);
                    $('.mfp-content.catalog-product-view .product-info-price .price-final_price .preorder_note').show()
                } else {
                    $('.block-variation-container').removeClass('pre-order-note-msg');
                    $('.area-content .inner').removeClass('pre-order-note-msg');
                    $('.preorder_note').hide();
                }

                if (selectedOptionValue.hasClass('coming-soon-child-product')) {
                    $('.box-quick-purchase').hide();
                    $('.product-options-bottom').find('.box-tocart').find('.actions').find('.action.primary.tocart').hide();
                    $('.content-wrap.item-detail .block-btn-sp .btn-cart').css('display','none');
                }
                this.stockMessageBlock.html(stockMessage);
                this.buttonLabelBlock.html(buttonTitle);
            },
            /**
             * Get selected product list
             *
             * @returns {Array}
             * @private
             */
            _CalcProductsDetails: function ($skipAttributeId) {
                var $widget = this,
                    selectedOptions = '.' + $widget.options.classes.attributeClass + '[data-option-selected]',
                    products = [];

                // Generate intersection of products
                $widget.element.find(selectedOptions).each(function () {
                    var id = $(this).data('attribute-id'),
                        option = $(this).attr('data-option-selected');

                    if ($skipAttributeId !== undefined && $skipAttributeId === id) {
                        return;
                    }

                    if (!$widget.optionsMap.hasOwnProperty(id) || !$widget.optionsMap[id].hasOwnProperty(option)) {
                        return;
                    }

                    if (products.length === 0) {
                        products = $widget.optionsMap[id][option].products;
                    } else {
                        products = _.intersection(products, $widget.optionsMap[id][option].products).length ? _.intersection(products, $widget.optionsMap[id][option].products) : products;
                    }
                });

                return products;
            },
            _CalcProductsDetailsColor: function ($skipAttributeId) {
                var $widget = this,
                    selectedOptions = '.' + $widget.options.classes.attributeClass + '[data-option-selected]',
                    products = [];

                // Generate intersection of products
                $widget.element.find(selectedOptions).each(function () {
                    var id = $(this).data('attribute-id'),
                        option = $(this).attr('data-option-selected');

                    if ($skipAttributeId !== undefined && $skipAttributeId === id) {
                        return;
                    }

                    if (!$widget.optionsMap.hasOwnProperty(id) || !$widget.optionsMap[id].hasOwnProperty(option)) {
                        return;
                    }

                    if (products.length === 0) {
                        products = $widget.optionsMap[id][option].products;
                    }
                });

                return products;
            },
            /**
             * Get chosen product
             *
             * @returns int|null
             */
            getProductDetailsColor: function () {
                var products = this._CalcProductsDetailsColor();

                return _.isArray(products) ? products[0] : null;
            },
            /**
             * Update Selected Color Code
             */
            updateColorCode: function () {
                const $allowedProductId = this.getProductDetailsColor(),
                    skuElement = $('.product-info-stock-sku .product.attribute.sku .value');
                if (!_.isEmpty($allowedProductId) && this.options.jsonConfig.productAdditionalData) {
                    const colorCode = this.options.jsonConfig.productAdditionalData[$allowedProductId].colorCode;
                    if (colorCode) {
                        skuElement.text(colorCode);
                    }
                }
            },

            /**
             * check product has pre order toggle is enabled
             */
            checkProductIsPreOrder: function ($widget) {

                var productId = $widget.getProduct()
                var preOrderData = $widget.options.jsonConfig.aitoc_pre_order_product_data;
                var isPreorder = '';
                if (preOrderData) {
                    isPreorder = preOrderData[productId].isPreOrder;
                }

                return isPreorder;
            },
        });
        return $.mage.SwatchRenderer;
    }
});
