<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Model\Entity\EntityData;

use OnitsukaTiger\OrderAttribute\Api\Data\AttributeValueInterface;

class AttributeValue extends \Magento\Framework\Api\AttributeValue implements AttributeValueInterface
{
    public const LABEL = 'label';

    public function setLabel(?string $label)
    {
        return $this->setData(self::LABEL, $label);
    }

    public function getLabel(): ?string
    {
        return $this->_get(self::LABEL);
    }
}
