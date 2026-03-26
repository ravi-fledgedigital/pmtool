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

namespace Mirasvit\LayeredNavigation\Block\Adminhtml\Filter;

use Magento\Backend\Block\Template;

class AttributeItem extends Template
{
    protected $_template = 'Mirasvit_LayeredNavigation::filters_manager/add/item.phtml';

    private $attributeData;
    private $attributeId;

    public function setAttributeData(array $attribute): self
    {
        $this->attributeData = $attribute;
        return $this;
    }

    public function setAttributeId($id): self
    {
        $this->attributeId = $id;
        return $this;
    }

    public function getAttributeData()
    {
        return $this->attributeData;
    }

    public function getAttributeId()
    {
        return $this->attributeId;
    }
}
