<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\App;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Framework\Stdlib\CookieDisablerInterface;

/**
 * Prevents Cookies from being sent
 */
class CookieDisabler implements CookieDisablerInterface, ResetAfterRequestInterface
{
    /** @var bool */
    private bool $cookiesDisabled = false;

    /**
     * @inheritDoc
     */
    public function setCookiesDisabled(bool $disabled) : void
    {
        $this->cookiesDisabled = $disabled;
    }

    /**
     * Get Cookies Disabled
     *
     * @return bool
     */
    public function isCookiesDisabled() : bool
    {
        return $this->cookiesDisabled;
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->cookiesDisabled = false;
    }
}
