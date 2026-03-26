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

namespace Mirasvit\LayeredNavigation\Plugin\Backend;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Ui\DataProvider\CatalogEavValidationRules;

/**
 * Plugin changes validation rule for attributes with integer backend type to decimal 
 * @see \Magento\Catalog\Ui\DataProvider\CatalogEavValidationRules::build()
 */
class BuildAttributeValidationRulesPlugin
{
    /**
     * @param CatalogEavValidationRules $subject
     * @param ProductAttributeInterface $attribute
     * @return array
     */
    public function afterBuild($subject, array $rules, ProductAttributeInterface $attribute)
    {
        if (
            $attribute->getFrontendClass() == 'validate-digits'
            && $attribute->getBackendType() == 'decimal'
            && $attribute->getFrontendInput() == 'text'
        ) {
            unset($rules['validate-digits']);
            $rules['validate-number'] = 1;
        }

        return $rules;
    }
}
