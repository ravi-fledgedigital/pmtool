<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Validation\GuestLogin\Validation;

interface TypeValidatorInterface
{
    public function isValid(array $data): bool;

    public function getWarningMessage(): string;
}
