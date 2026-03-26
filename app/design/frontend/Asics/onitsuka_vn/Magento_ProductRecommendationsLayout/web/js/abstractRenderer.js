define([
    "uiComponent",
    "dataServicesBase",
    "jquery",
    "Magento_Catalog/js/price-utils",
    "slick"
], function (Component, ds, $, priceUnits) {
    "use strict"
    return Component.extend({
        defaults: {
            template:
                "Magento_ProductRecommendationsLayout/recommendations.html",
            recs: [],
        },
        initialize: function (config) {
            this._super(config)
            this.pagePlacement = config.pagePlacement
            this.placeholderUrl = config.placeholderUrl
            this.priceFormat = config.priceFormat
            this.priceUnits = priceUnits
            this.currencyConfiguration = config.currencyConfiguration
            this.alternateEnvironmentId = config.alternateEnvironmentId
            return this
        },
        /**
         * @returns {Element}
         */
        initObservable: function () {
            return this._super().observe(["recs"])
        },

        //Helper function to add addToCart button & convert currency
        /**
         *
         * @param {@} response is type Array.
         * @returns type Array.
         */
        processResponse(response) {
            const units = []
            if (!response.length || response[0].unitId === undefined) {
                return units
            }

            for (let i = 0; i < response.length; i++) {
                response[i].products = response[i].products.slice(
                    0,
                    response[i].displayNumber,
                )

                for (let j = 0; j < response[i].products.length; j++) {

                    /*var productImageUrl = response[i].products[j].smallImage.url;
                    var productThumbnailImageUrl = response[i].products[j].thumbnailImage.url;




                    if(productImageUrl.indexOf("no_selection") === -1) {
                        var mediaUrl = productImageUrl.indexOf("media/catalog/product") + 20;
                        var result = productImageUrl.substr(mediaUrl + 1);
                        if(result && result.length > 0) {
                            response[i].products[j].image.url = result;
                        } else if(productThumbnailImageUrl.indexOf("no_selection") === -1) {
                            var thumbnailMediaUrl = productThumbnailImageUrl.indexOf("media/catalog/product") + 20;
                            var thumbNailResult = productThumbnailImageUrl.substr(thumbnailMediaUrl + 1);
                            if(thumbNailResult && thumbNailResult.length > 0) {
                                response[i].products[j].image.url = thumbNailResult;
                            } else {
                                response[i].products[j].image.url = 'no-image';
                            }
                        } else {
                            response[i].products[j].image.url = 'no-image';
                        }
                    } else if(productThumbnailImageUrl.indexOf("no_selection") === -1) {
                        var mediaUrl = productThumbnailImageUrl.indexOf("media/catalog/product") + 20;
                        var result = productThumbnailImageUrl.substr(mediaUrl + 1);
                        if(result && result.length > 0) {
                            response[i].products[j].image.url = result;
                        } else {
                            response[i].products[j].image.url = 'no-image';
                        }
                    }*/

                    if(response[i].products[j].attributes.google_shop_image_link) {
                        var productImageUrls = response[i].products[j].attributes.google_shop_image_link;
                        if (productImageUrls.length > 0 && 0 in productImageUrls) {

                            var productImageUrl = '';
                            var pSkuString = response[i].products[j].sku;
                            var pSku = pSkuString.toString().replace(".", "_");
                            $.each(productImageUrls, function( index, pImage ) {
                                if (pImage.indexOf(pSku) != -1) {
                                    productImageUrl = pImage;
                                    return false;
                                }
                            });

                            if (productImageUrl.length > 0) {
                                productImageUrl = productImageUrl+'?qlt=100&wid=240&hei=300&bgc=255,255,255&resMode=bisharp';
                            } else {
                                productImageUrl = 'no-image';
                            }
                            response[i].products[j].image.url = productImageUrl;
                        } else {
                            response[i].products[j].image.url = 'no-image';
                        }
                    } else {
                        response[i].products[j].image.url = 'no-image';
                    }

                    if(response[i].products[j].attributes.gender) {
                        response[i].products[j].gender = response[i].products[j].attributes.gender;
                    } else {
                        response[i].products[j].gender ='';
                    }

                    if (response[i].products[j].productId) {
                        const form_key = $.cookie("form_key")
                        const url = this.createAddToCartUrl(
                            response[i].products[j].productId,
                        )
                        const postUenc = this.encodeUenc(url)
                        const addToCart = {form_key, url, postUenc}
                        response[i].products[j].addToCart = addToCart
                    }

                    if (
                        this.currencyConfiguration &&
                        response[i].products[j].currency !==
                        this.currencyConfiguration.currency
                    ) {
                        if (response[i].products[j].prices === null) {
                            response[i].products[j].prices = {
                                minimum: {final: null},
                            }
                        } else {
                            response[i].products[j].prices.minimum.final =
                                response[i].products[j].prices &&
                                response[i].products[j].prices.minimum &&
                                response[i].products[j].prices.minimum.final
                                    ? this.convertPrice(
                                        response[i].products[j].prices.minimum
                                            .final,
                                    )
                                    : null
                        }
                        response[i].products[j].currency =
                            this.currencyConfiguration.currency
                    }
                }
                units.push(response[i])
            }
            units.sort((a, b) => a.displayOrder - b.displayOrder)
            return units
        },

        loadJsAfterKoRender: function (self, unit) {
            const renderEvent = new CustomEvent("render", {detail: unit})
            document.dispatchEvent(renderEvent)
            $('.catalog-product-view .home-page-recomended').each(function(num, elem) {
                var sliderId = '#' + $(this).attr('id');
                $(sliderId).not('.slick-initialized').slick({
                    infinite: false,
                    slidesToShow: 2,
                    slidesToScroll: 1,
                    autoplay: false,
                    dots: false,
                    arrows: true,
                    responsive: [{
                        breakpoint: 991,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 1
                        }
                    },
                        {
                            breakpoint: 767,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 1
                            }
                        },
                        {
                            breakpoint: 480,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 1
                            }
                        },
                    ]
                });
            });
            $('.home-page-recomended').each(function(num, elem) {
                var sliderId = '#' + $(this).attr('id');
                $(sliderId).not('.slick-initialized').slick({
                    infinite: false,
                    slidesToShow: 4,
                    slidesToScroll: 1,
                    autoplay: false,
                    dots: false,
                    arrows: true,
                    responsive: [{
                        breakpoint: 991,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 1
                        }
                    },
                        {
                            breakpoint: 767,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 1
                            }
                        },
                        {
                            breakpoint: 480,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 1
                            }
                        },
                    ]
                });
            });
        },

        convertPrice: function (price) {
            return parseFloat(price * this.currencyConfiguration.rate)
        },

        createAddToCartUrl(productId) {
            const currentLocationUENC = encodeURIComponent(
                this.encodeUenc(BASE_URL),
            )
            const postUrl =
                BASE_URL +
                "checkout/cart/add/uenc/" +
                currentLocationUENC +
                "/product/" +
                productId
            return postUrl
        },

        encodeUenc: function (value) {
            const regex = /=/gi
            return btoa(value).replace(regex, ",")
        }
    })
})
