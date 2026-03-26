<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AEPRmaSync\Plugin;

use Vaimo\AepEventStreaming\Api\ConfigInterface;
use Vaimo\AEPRmaSync\Model\Rma\SynchronisePublisher;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;

class RmaReturnRepositoryPlugin
{
    private SynchronisePublisher $publisher;
    private ConfigInterface $config;

    public function __construct(
        SynchronisePublisher $publisher,
        ConfigInterface $config
    ) {
        $this->publisher = $publisher;
        $this->config = $config;
    }

    public function afterSave(
        RequestRepositoryInterface $subject,
        RequestInterface $result
    ): RequestInterface {
        if (!$this->config->isEnabled() || !$result->getCustomerId()) {
            return $result;
        }

        $this->publisher->publish($result);

        return $result;
    }
}