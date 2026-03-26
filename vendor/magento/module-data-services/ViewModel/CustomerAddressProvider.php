<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magento\DataServices\ViewModel;

use Magento\Customer\Model\Session as PersonalizationSession;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * ViewModel for CustomerAddressProvider
 */
class CustomerAddressProvider implements ArgumentInterface
{
    /**
     * @var PersonalizationSession
     */
    private $personalizationSession;

    /**
     * @param PersonalizationSession $personalizationSession
     */
    public function __construct(
      PersonalizationSession $personalizationSession
    ) {
        $this->personalizationSession = $personalizationSession;
    }

    /**
     * Return User Action
     *
     * @return string
     */
    public function getUserAction() : string
    {
        $userAction = $this->personalizationSession->getUserAction();
        $this->personalizationSession->unsUserAction();
        return $userAction ? $userAction : '';
    }
}
