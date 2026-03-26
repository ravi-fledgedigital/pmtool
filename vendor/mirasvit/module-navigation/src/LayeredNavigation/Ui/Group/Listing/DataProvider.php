<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\LayeredNavigation\Ui\Group\Listing;


use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\LayeredNavigation\Api\Data\GroupInterface;
use Mirasvit\LayeredNavigation\Block\Adminhtml\Group\OptionLabel;
use Mirasvit\LayeredNavigation\Model\ConfigProvider;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    private $storeManager;

    private $attributeRepository;

    private $configProvider;

    private $optionLabel;

    /** @SuppressWarnings(PHPMD.ExcessiveParameterList) */
    public function __construct(
        ConfigProvider $configProvider,
        StoreManagerInterface $storeManager,
        AttributeRepository $attributeRepository,
        OptionLabel $optionLabel,
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        array $meta = [],
        array $data = []
    ) {
        $this->storeManager        = $storeManager;
        $this->attributeRepository = $attributeRepository;
        $this->configProvider      = $configProvider;
        $this->optionLabel         = $optionLabel;

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    protected function searchResultToOutput(SearchResultInterface $searchResult): array
    {
        $result                 = [];
        $result['totalRecords'] = $searchResult->getTotalCount();

        /** @var GroupInterface $item */
        foreach ($searchResult->getItems() as $item) {
            $preparedTitles = '';
            $titles         = $item->getTitle();

            foreach ($titles as $title) {
                $preparedTitles .= '<p>' . $this->storeManager->getStore($title['store_id'])->getName()
                    . ': <b>' . $title['label'] . '</b></p>';
            }

            $item->setData('labels', $preparedTitles);

            $preparedValues = '';
            $attribute      = $this->attributeRepository->get($item->getAttributeCode());
            $options        = $attribute->getOptions();

            $item->setData('attribute', $attribute->getDefaultFrontendLabel());

            foreach ($options as $option) {
                if (!in_array($option->getValue(), $item->getAttributeValueIds())) {
                    continue;
                }

                $preparedValues .= $this->optionLabel->getOptionLabelHtml($option);
            }

            $item->setData('attribute_values', $preparedValues);

            $item->setData('swatch', $this->prepareSwatchHtml($item));

            $result['items'][] = $item->getData();
        }

        return $result;
    }

    private function prepareSwatchHtml(GroupInterface $item): string
    {
        $haveSwatch = $item->getSwatchType() !== GroupInterface::SWATCH_TYPE_NONE;

        $backgroundValue = '';

        switch ($item->getSwatchType()) {
            case GroupInterface::SWATCH_TYPE_COLOR:
                $backgroundValue = $item->getSwatchValue();
                break;
            case GroupInterface::SWATCH_TYPE_IMAGE:
                $backgroundValue = 'url(' . $this->configProvider->getMediaUrl($item->getSwatchValue()) . ') no-repeat center/100%';
                break;
            default:
                break;
        }

        $backgroud = $haveSwatch
            ? ' background: ' . $backgroundValue
            : '';

        $html = "
            <div
                class='mst-nav__swatch'
                style='" . $backgroud . "'
            ></div>
        ";

        return $html;
    }
}
