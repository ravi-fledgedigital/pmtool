<?php
/**
 * Copyright © a All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Model\ResourceModel\AccessToken;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'access_token_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Seoulwebdesign\KakaoSync\Model\AccessToken::class,
            \Seoulwebdesign\KakaoSync\Model\ResourceModel\AccessToken::class
        );
    }
}
