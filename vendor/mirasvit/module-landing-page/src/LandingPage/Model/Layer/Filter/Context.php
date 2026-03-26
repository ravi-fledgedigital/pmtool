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

namespace Mirasvit\LandingPage\Model\Layer\Filter;

use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder as ItemDataBuilder;
use Magento\Catalog\Model\Layer\Filter\ItemFactory as FilterItemFactory;
use Magento\Store\Model\StoreManagerInterface;

class Context
{
    public $filterItemFactory;

    public $storeManager;

    public $itemDataBuilder;

    public function __construct(
        FilterItemFactory     $filterItemFactory,
        StoreManagerInterface $storeManager,
        ItemDataBuilder       $itemDataBuilder
    ) {
        $this->filterItemFactory = $filterItemFactory;
        $this->storeManager      = $storeManager;
        $this->itemDataBuilder   = $itemDataBuilder;
    }
}
