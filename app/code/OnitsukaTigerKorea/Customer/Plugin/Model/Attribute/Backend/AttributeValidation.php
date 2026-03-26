<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTigerKorea\Customer\Plugin\Model\Attribute\Backend;

use Closure;
use OnitsukaTigerKorea\Customer\Helper\Data;
use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use Magento\Framework\DataObject;

/**
 * Skip validate attributes used for create configurable product.
 */
class AttributeValidation
{

    /**
     * Address Helper
     *
     * @var Data
     */
    protected $dataHelper;

    protected $_storeId = null;

    /**
     * @param Data $dataHelper
     */
    public function __construct(
        Data $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param AbstractBackend $subject
     * @param Closure $proceed
     * @param DataObject $entity
     * @return bool|mixed
     */
    public function aroundValidate(AbstractBackend $subject, Closure $proceed, DataObject $entity)
    {
        if ($this->_storeId == null) {
            $this->_storeId = $entity->getStoreId();
        }
        if ($this->dataHelper->isCustomerEnabled($this->_storeId)) {
            $attribute = $subject->getAttribute();
            $attrCode = $attribute->getAttributeCode();
            if ($attrCode == 'lastname') {
                return true;
            }
        }
        return $proceed($entity);
    }
}
