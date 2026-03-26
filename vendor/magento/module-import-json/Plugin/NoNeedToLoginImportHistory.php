<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportJson\Plugin;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Model\Import\EntityInterface;

/**
 * No need to update import history for import JSON API call.
 */
class NoNeedToLoginImportHistory
{
    /**
     * @var bool
     */
    private bool $isLoggingEnabled = false;

    /**
     * @var State
     */
    private State $appState;

    /**
     * @param State $appState
     */
    public function __construct(
        State $appState
    ) {
        $this->appState = $appState;
    }

    /**
     * No need to update import history for import CSV REST & SOAP API calls.
     *
     * @param EntityInterface $subject
     * @param bool $result
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsNeedToLogInHistory(
        EntityInterface $subject,
        bool $result
    ): bool {
        try {
            $areaCode = $this->appState->getAreaCode();

            if ($areaCode === Area::AREA_WEBAPI_REST || $areaCode === Area::AREA_WEBAPI_SOAP) {
                return $this->isLoggingEnabled;
            }
        } catch (LocalizedException $e) {
            return $this->isLoggingEnabled;
        }

        return $result;
    }
}
