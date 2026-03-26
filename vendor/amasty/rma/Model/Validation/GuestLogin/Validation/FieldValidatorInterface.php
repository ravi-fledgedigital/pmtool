<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Validation\GuestLogin\Validation;

interface FieldValidatorInterface
{
    public const BILLING_FIELD = 'billing';

    public function isFieldAvailable(): bool;
}
