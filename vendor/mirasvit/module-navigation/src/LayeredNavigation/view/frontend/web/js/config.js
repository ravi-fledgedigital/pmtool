define([
    'jquery'
], function ($) {
    'use strict';

    return {
        isAjax: function () {
            return window.mstNavAjax;
        },

        isInstantMode: function () {
            return window.mstInstantlyMode;
        },

        isConfirmationMode: function () {
            return window.mstNavConfirmationMode
                || (window.mstNavConfirmOnMobile && window.innerWidth <= window.mstNavModeSwitchBreakpoint);
        },

        isSeoFilterEnabled: function () {
            return window.mstSeoFilterEnabled;
        },

        getRelAttributeValue: function () {
            return window.mstRelAttributeValue;
        },

        isHighlightEnabled: function () {
            return window.mstHighlightEnabled;
        },

        preCalculationEnabled: function () {
            return window.mstPreCalculationEnabled;
        },

        isShowClearButton: function () {
            return window.mstIsShowClearButton;
        },

        getFriendlyClearUrl: function () {
            return window.mstFriendlyClearUrl;
        },

        getAjaxCallEvent: function () {
            return 'mst-nav__ajax-call';
        },

        getAjaxProductListWrapperId: function () {
            return '#m-navigation-product-list-wrapper';
        },

        isSearchFilterFulltext: function () {
            return window.mstSearchFilterFulltext;
        },

        isSearchFilterOptions: function () {
            return window.mstSearchFilterOptions;
        },

        // trustpilot compatibility
        loadTrustpilotWidget: function () {
            if(window.Trustpilot) {
                let trustboxes = document.querySelectorAll(this.getAjaxProductListWrapperId() + ' .trustpilot-widget');
                if(trustboxes.length !== 0) {
                    trustboxes.forEach((trustbox) => {
                        window.Trustpilot.loadFromElement(trustbox);
                    });
                }
            }
        },

        // yotpo compatibility
        loadYotpoWidget: function () {
            if(window.yotpo){
                window.yotpo.initWidgets();
            }
        },

        // lipscore compatibility
        loadLipscoreWidgets: function () {
            if(window.lipscore) {
                window.lipscore.initWidgets();
            }
        },

        load3rdPartyReviewWidgets: function () {
            this.loadTrustpilotWidget();
            this.loadYotpoWidget();
            this.loadLipscoreWidgets();
        },

        scrollToTopBehaviour: function () {
            return window.mstScrollToTopBehaviour;
        },

        mstStickySidebar: function () {
            return window.mstStickySidebar;
        },

        mstCategoryFilterModeLink: function () {
            return window.mstCategoryFilterModeLink;
        },
    };
});
