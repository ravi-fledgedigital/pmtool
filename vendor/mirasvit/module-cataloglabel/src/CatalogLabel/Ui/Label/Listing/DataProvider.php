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
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\CatalogLabel\Ui\Label\Listing;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\AttributeFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;
use Mirasvit\CatalogLabel\Api\Data\DisplayInterface;
use Mirasvit\CatalogLabel\Api\Data\LabelInterface;
use Mirasvit\CatalogLabel\Block\Product\Label\Display as DisplayBlock;
use Mirasvit\CatalogLabel\Model\System\Config\Source\ImageType;


/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    private $entityAttributeFactory;

    private $config;

    private $blockFactory;

    private $imageTypeSource;

    protected $type;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ImageType $imageTypeSource,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        \Magento\Framework\View\Element\BlockFactory $blockFactory,
        AttributeFactory $entityAttributeFactory,
        Config $config,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->entityAttributeFactory          = $entityAttributeFactory;
        $this->config                          = $config;
        $this->blockFactory                    = $blockFactory;
        $this->imageTypeSource                 = $imageTypeSource;

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
        $arrItems          = [];
        $arrItems['items'] = [];

        /** @var LabelInterface $item */
        foreach ($searchResult->getItems() as $item) {
            $item->load($item->getId());
            $this->preparePreviewImages($item);

            $this->type = $item->getType();

            $itemData = $item->getData();

            $arrItems['items'][] = $itemData;
        }

        $arrItems['totalRecords'] = $searchResult->getTotalCount();

        return $arrItems;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function preparePreviewImages(LabelInterface $label): void
    {
        $options = [];

        foreach ($this->imageTypeSource->toArray() as $type) {
            /** @var DisplayInterface $display */
            foreach ($label->getDisplaysByType($type) as $display) {
                /** @var DisplayBlock $previewBlock */
                $previewBlock = $this->blockFactory->createBlock(DisplayBlock::class);
                $previewBlock->setDisplay($display);

                $options[$type][] = ['html' => $previewBlock->toHtml() . $previewBlock->getMergedStylesOutput()];
            }

            $label->setData("preview_{$type}", $options[$type] ?? []);
        }
    }
}
