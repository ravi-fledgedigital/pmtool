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


namespace Mirasvit\CatalogLabel\Ui\Placeholder;

use Magento\Framework\Api\Search\SearchResultInterface;
use Mirasvit\CatalogLabel\Api\Data\PlaceholderInterface;

class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    protected function searchResultToOutput(SearchResultInterface $searchResult): array
    {
        $arrItems          = [];
        $arrItems['items'] = [];

        /** @var PlaceholderInterface $item */
        foreach ($searchResult->getItems() as $item) {
            $itemData = $item->getData();

            $itemData['manual_code']     = $this->prepareManualCode($item);
            $itemData['is_code_visible'] = (bool)$itemData['manual_code'];

            $arrItems[$item->getId()] = $itemData;
            $arrItems['items'][]      = $itemData;
        }

        $arrItems['totalRecords'] = $searchResult->getTotalCount();

        return $arrItems;
    }

    private function prepareManualCode(PlaceholderInterface $item): string
    {
        if ($item->getPosition() !== 'MANUAL') {
            return '';
        }

        $manualCode = "<?php
    echo \$block->getLayout()->createBlock('\Mirasvit\CatalogLabel\Block\Product\Label\Placeholder')
            ->setProduct(\$_product)
            ->setPlaceholderByCode('{$item->getCode()}')
            ->setType(view_type)
            ->toHtml();
?>";

        return htmlspecialchars(trim($manualCode));
    }
}
