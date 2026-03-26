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

namespace Mirasvit\AllProducts\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\ObjectManager;

/**
 * Product options data view model
 */
class OptionsData implements ArgumentInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getOptionsData(Product $product) : array
    {
        if (class_exists('Magento\Catalog\ViewModel\Product\OptionsData')) {
            $nativeOptionsData = ObjectManager::getInstance()->create('Magento\Catalog\ViewModel\Product\OptionsData');
            return $nativeOptionsData->getOptionsData($product);
        }

        return [];
    }
}
