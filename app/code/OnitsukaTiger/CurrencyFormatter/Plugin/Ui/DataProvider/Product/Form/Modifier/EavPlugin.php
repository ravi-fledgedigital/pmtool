<?php

namespace OnitsukaTiger\CurrencyFormatter\Plugin\Ui\DataProvider\Product\Form\Modifier;

use Closure;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Mageplaza\CurrencyFormatter\Ui\DataProvider\Product\Form\Modifier\Eav;
use OnitsukaTiger\CurrencyFormatter\Helper\Data;

class EavPlugin
{
    /**
     * @var LocatorInterface
     */
    protected LocatorInterface $locator;

    /**
     * @var Data
     */
    protected Data $helperData;

    /**
     * Eav Plugin
     * @param LocatorInterface $locator
     * @param Data $helperData
     */
    public function __construct(
        LocatorInterface $locator,
        Data $helperData
    ) {
        $this->locator = $locator;
        $this->helperData = $helperData;
    }

    /**
     * Disable Mageplaza currency format in BE/product detail
     * @param Eav $subject
     * @param Closure $proceed
     * @param array $data
     * @return array
     */
    public function aroundModifyData(
        Eav     $subject,
        Closure $proceed,
        array   $data
    ): array
    {
        $storeId = $this->locator->getStore()->getId();
        if (!$this->helperData->isEnableCurrencyFormatter($storeId)) {
            return $data;
        }
        return $proceed($data);
    }
}
