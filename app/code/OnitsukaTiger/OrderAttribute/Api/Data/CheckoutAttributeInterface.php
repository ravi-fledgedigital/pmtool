<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Api\Data;

/**
 * @SuppressWarnings(PHPMD.BooleanGetMethodName)
 */
interface CheckoutAttributeInterface extends \Magento\Eav\Api\Data\AttributeInterface
{
    /**
     * Is required value for do special algorithm - is_required set to 0, required_on_front_only set to 1
     */
    public const IS_REQUIRED_PROXY_VALUE = 2;

    /**#@+
     * Constants defined for keys of data array
     */
    public const IS_VISIBLE_ON_FRONT = 'is_visible_on_front';
    public const IS_HIDDEN_FROM_CUSTOMER = 'is_hidden_from_customer';
    public const IS_VISIBLE_ON_BACK = 'is_visible_on_back';
    public const MULTISELECT_SIZE = 'multiselect_size';
    public const SORTING_ORDER = 'sorting_order';
    public const CHECKOUT_STEP = 'checkout_step';
    public const SHOW_ON_GRIDS = 'show_on_grids';
    public const INCLUDE_IN_PDF = 'include_in_pdf';
    public const INCLUDE_IN_HTML_PRINT_ORDER = 'include_in_html_print_order';
    public const SAVE_TO_FUTURE_CHECKOUT = 'save_to_future_checkout';
    public const APPLY_DEFAULT_VALUE = 'apply_default_value';
    public const INCLUDE_IN_EMAIL = 'include_in_email';
    public const REQUIRED_ON_FRONT_ONLY = 'required_on_front_only';
    public const INPUT_FILTER = 'input_filter';
    /**#@-*/

    /**
     * @return int|null
     */
    public function getIsVisibleOnFront();

    /**
     * @param int|null $isVisibleOnFront
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface
     */
    public function setIsVisibleOnFront($isVisibleOnFront);

    /**
     * @param bool|null $isHidden
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface
     */
    public function setIsHiddenFromCustomer(?bool $isHidden);

    /**
     * @return bool
     */
    public function getIsHiddenFromCustomer(): bool;

    /**
     * @return int|null
     */
    public function getIsVisibleOnBack();

    /**
     * @param int|null $isVisibleOnBack
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface
     */
    public function setIsVisibleOnBack($isVisibleOnBack);

    /**
     * @return int|null
     */
    public function getMultiselectSize();

    /**
     * @param int|null $multiselectSize
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface
     */
    public function setMultiselectSize($multiselectSize);

    /**
     * @return int|null
     */
    public function getSortingOrder();

    /**
     * @param int|null $sortingOrder
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface
     */
    public function setSortingOrder($sortingOrder);

    /**
     * @return int|null
     */
    public function getCheckoutStep();

    /**
     * @param int|null $checkoutStep
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface
     */
    public function setCheckoutStep($checkoutStep);

    /**
     * @return int|null
     */
    public function isShowOnGrid();

    /**
     * @param int|null $showOnGrids
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface
     */
    public function setShowOnGrids($showOnGrids);

    /**
     * @return int|null
     */
    public function getIncludeInPdf();

    /**
     * @param int|null $includeInPdf
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface
     */
    public function setIncludeInPdf($includeInPdf);

    /**
     * @return int|null
     */
    public function getIncludeInHtmlPrintOrder();

    /**
     * @param int|null $includeInHtmlPrintOrder
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface
     */
    public function setIncludeInHtmlPrintOrder($includeInHtmlPrintOrder);

    /**
     * @return bool|null
     */
    public function isSaveToFutureCheckout();

    /**
     * @param int|bool $saveToFutureCheckout
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface
     */
    public function setSaveToFutureCheckout($saveToFutureCheckout);

    /**
     * @return int|null
     */
    public function getApplyDefaultValue();

    /**
     * @param int|null $applyDefaultValue
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface
     */
    public function setApplyDefaultValue($applyDefaultValue);

    /**
     * @return bool|null
     */
    public function isIncludeInEmail();

    /**
     * @param bool|null $includeInEmail
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface
     */
    public function setIsIncludeInEmail($includeInEmail);

    /**
     * @return int|null
     */
    public function getRequiredOnFrontOnly();

    /**
     * @param int|null $requiredOnFrontOnly
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface
     */
    public function setRequiredOnFrontOnly($requiredOnFrontOnly);

    /**
     * @return string|null
     */
    public function getInputFilter();

    /**
     * @param string|null $inputFilter
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface
     */
    public function setInputFilter($inputFilter);
}
