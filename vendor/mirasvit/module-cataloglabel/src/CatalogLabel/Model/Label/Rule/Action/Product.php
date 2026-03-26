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

namespace Mirasvit\CatalogLabel\Model\Label\Rule\Action;

class Product extends \Magento\Rule\Model\Action\AbstractAction
{
    public function loadAttributeOptions(): self
    {
        $this->setAttributeOption([
            'rule_price' => (string)__('Rule price'),
        ]);

        return $this;
    }

    public function loadOperatorOptions(): self
    {
        $this->setOperatorOption([
            'to_fixed'   => (string)__('To Fixed Value'),
            'to_percent' => (string)__('To Percentage'),
            'by_fixed'   => (string)__('By Fixed value'),
            'by_percent' => (string)__('By Percentage'),
        ]);

        return $this;
    }

    public function asHtml(): string
    {
        $html = $this->getTypeElement()->getHtml().(string)__(
                "Update product's %1 %2: %3",
                $this->getAttributeElement()->getHtml(),
                $this->getOperatorElement()->getHtml(),
                $this->getValueElement()->getHtml()
            );
        $html .= $this->getRemoveLinkHtml();

        return $html;
    }
}
