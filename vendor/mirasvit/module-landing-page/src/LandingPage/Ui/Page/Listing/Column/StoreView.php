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
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Ui\Page\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\System\Store;
use Magento\Ui\Component\Listing\Columns\Column;

class StoreView extends Column
{

    protected $store;

    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        Store              $store,
        array              $components,
        array              $data
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->store = $store;
    }

    public function prepareDataSource(array $dataSource)
    {
        $dataSource = parent::prepareDataSource($dataSource);

        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!empty($item['store_view'])) {
                $item['store_view'] = $this->renderVisibilityStructure($item['store_view']) ? : __('All Store Views');
            }
        }

        return $dataSource;
    }


    protected function renderVisibilityStructure(array $storeIds)
    {
        $visibility = '';

        foreach ($this->store->getStoresStructure(false, $storeIds) as $website) {
            $visibility .= $website['label'] . '<br/>';
            foreach ($website['children'] as $group) {
                $visibility .= str_repeat('&nbsp;', 3) . $group['label'] . '<br/>';
                foreach ($group['children'] as $store) {
                    $visibility .= str_repeat('&nbsp;', 6) . $store['label'] . '<br/>';
                }
            }
        }

        return $visibility;
    }
}
