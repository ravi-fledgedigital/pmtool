<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ApplicationServer\App;

use Magento\Framework\Session\SessionStartChecker;

/**
 * Class to check if App Server Session can be started or not.
 */
class AppServerSessionStartChecker extends SessionStartChecker
{
    /**
     * @param bool $checkSapi
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod
     */
    public function __construct(bool $checkSapi = false)
    {
        parent::__construct(false);
    }
}
