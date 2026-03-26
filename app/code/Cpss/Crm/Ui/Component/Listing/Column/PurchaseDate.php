<?php

namespace Cpss\Crm\Ui\Component\Listing\Column;

use Magento\Framework\Locale\Bundle\DataBundle;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\BooleanUtils;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class PurchaseDate extends \Magento\Ui\Component\Listing\Columns\Date
{
    /**
     * @var BooleanUtils
     */
    protected $booleanUtils;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        TimezoneInterface $timezone,
        BooleanUtils $booleanUtils,
        private \OnitsukaTigerCpss\Pos\Helper\HelperData $helperData,
        array $components = [],
        array $data = [],
        ResolverInterface $localeResolver = null,
        DataBundle $dataBundle = null
    ) {
        $this->booleanUtils = $booleanUtils;
        parent::__construct($context, $uiComponentFactory, $timezone, $booleanUtils, $components, $data, $localeResolver, $dataBundle);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$this->getData('name')])
                    && $item[$this->getData('name')] !== "0000-00-00 00:00:00"
                ) {
                    $storeCode = $item['store_code'];
                    $convertedDate = $this->helperData->formatDateFromCpss($item['purchase_date'], $storeCode);
                    $item[$this->getData('name')] = $convertedDate;
                }
            }
        }

        return $dataSource;
    }
}
