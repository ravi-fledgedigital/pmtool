<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7AsicsIntegration\Model\Api;

use Magento\Framework\Exception\AuthenticationException;
use Vaimo\OTScene7AsicsIntegration\Model\ConfigProvider as AsicsConfigProvider;

class ImagesInfoProvider
{
    private const APPROVED_FLAG = '1';
    private const IS_LATEST = 'true';

    private Adapter $adapter;
    private AsicsConfigProvider $asicsConfigProvider;

    public function __construct(
        Adapter $adapter,
        AsicsConfigProvider $asicsConfigProvider
    ) {
        $this->adapter = $adapter;
        $this->asicsConfigProvider = $asicsConfigProvider;
    }

    /**
     * @param int $offset
     * @return mixed[]
     * @throws AsicsApiException
     * @throws AuthenticationException
     */
    public function getImagesInfo(int $offset, $item, $lastTimeUpdate): array
    {
        $data = [
            'offset' => $offset,
            'approvedflg' => self::APPROVED_FLAG,
            'latest' => self::IS_LATEST,
            'region' => $this->asicsConfigProvider->getRegionSuffix(),
        ];
        if ($item != '') {
            $data['material'] = $item;
        }
        if ($lastTimeUpdate != '') {
            $data['afterdate'] = $lastTimeUpdate;
        }
        return $this->adapter->requestGet('/info/feed/image', $data);
    }
}
