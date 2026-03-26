<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Model\ResourceModel;

use Magento\CommerceBackendUix\Api\Data\MassActionFailedRequestInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * @inheritDoc
 */
class MassActionFailedRequest extends AbstractDb
{
    /**
     * @inheritDoc
     */
    public function _construct(): void
    {
        $this->_init('admin_ui_sdk_mass_actions_failed_request', MassActionFailedRequestInterface::FIELD_ID);
    }
}
