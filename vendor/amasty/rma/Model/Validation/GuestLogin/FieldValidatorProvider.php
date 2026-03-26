<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Validation\GuestLogin;

use Amasty\Rma\Model\Validation\GuestLogin\Validation\FieldValidatorInterface;

class FieldValidatorProvider
{
    /**
     * @var FieldValidatorInterface[]
     */
    private $validators;

    public function __construct(
        array $validatorTypes = []
    ) {
        $this->initializeFileTypes($validatorTypes);
    }

    public function getValidatorByType(string $type): ?FieldValidatorInterface
    {
        return $this->validators[$type] ?? null;
    }

    private function initializeFileTypes(array $validatorTypes): void
    {
        foreach ($validatorTypes as $type => $validator) {
            if (!$validator instanceof FieldValidatorInterface) {
                throw new \LogicException(
                    sprintf('Field validator must implement %s', FieldValidatorInterface::class)
                );
            }
            $this->validators[$type] = $validator;
        }
    }
}
