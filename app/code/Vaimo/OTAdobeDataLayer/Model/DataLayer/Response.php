<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTAdobeDataLayer\Model\DataLayer;

use Magento\Framework\DataObject;
use Vaimo\OTAdobeDataLayer\Api\Data\DataLayerResponseInterface;

class Response extends DataObject implements DataLayerResponseInterface
{
    private const USER_INFO_COMPONENT = 'userInfo';

    private const PAGE_INFO_COMPONENT = 'pageInfo';

    public function getUserInfo(): string
    {
        return $this->getData(self::USER_INFO_COMPONENT);
    }

    public function getPageInfo(): string
    {
        return $this->getData(self::PAGE_INFO_COMPONENT);
    }
}
