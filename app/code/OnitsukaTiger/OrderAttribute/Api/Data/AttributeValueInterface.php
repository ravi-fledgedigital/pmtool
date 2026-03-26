<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Api\Data;

use Magento\Framework\Api\AttributeInterface;

interface AttributeValueInterface extends AttributeInterface
{
    /**
     * @param string|null $label
     * @return $this
     */
    public function setLabel(?string $label);

    /**
     * @return string|null
     */
    public function getLabel(): ?string;
}
