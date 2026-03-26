<?php
/**
 * Copyright © a All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class AccessToken extends AbstractDb
{

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('seoulwebdesign_kakaosync_access_token', 'access_token_id');
    }
}
