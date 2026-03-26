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


namespace Mirasvit\CatalogLabel\Ui\Template;


use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\App\RequestInterface;
use Mirasvit\CatalogLabel\Block\Adminhtml\Template\Preview;
use Magento\Framework\View\Element\BlockFactory;
use Mirasvit\CatalogLabel\Repository\TemplateRepository;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    private $blockFactory;

    private $templateRepository;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        TemplateRepository $templateRepository,
        BlockFactory $blockFactory,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->templateRepository = $templateRepository;
        $this->blockFactory       = $blockFactory;

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

    protected function searchResultToOutput(SearchResultInterface $searchResult)
    {
        $arrItems          = [];
        $arrItems['items'] = [];

        foreach ($searchResult->getItems() as $item) {
            $itemData = $this->addExampleDataToItemData($item->getData());

            /** @var Preview $block */
            $block = $this->blockFactory->createBlock(Preview::class)
                ->setTemplate('Mirasvit_CatalogLabel::template/template-preview.phtml');

            $template = $this->templateRepository->get((int)$item->getId());

            $block->setLabelTemplate($template)->setLabelData($itemData);

            $itemData['preview'] = $block->toHtml();

            $arrItems[$item->getId()] = $itemData;
            $arrItems['items'][]      = $itemData;
        }

        $arrItems['totalRecords'] = $searchResult->getTotalCount();

        return $arrItems;
    }

    public function addExampleDataToItemData(array $itemData): array
    {
        $exampleData = [
            'test_title'       => (string)__('Example Text'),
            'test_description' => (string)__('Example Description'),
        ];

        return array_merge($itemData, $exampleData);
    }
}
