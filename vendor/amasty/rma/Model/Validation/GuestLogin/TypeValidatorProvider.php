<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Validation\GuestLogin;

use Amasty\Rma\Model\Validation\GuestLogin\Validation\TypeValidatorInterface;

class TypeValidatorProvider
{
    /**
     * @var TypeValidatorInterface[]
     */
    private $validators = [];

    public function __construct(
        array $validatorTypes = []
    ) {
        $this->initializeFileTypes($validatorTypes);
    }

    /**
     * @return TypeValidatorInterface[]
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

    /**
     * @param TypeValidatorInterface[] $validatorTypes
     * @return void
     */
    private function initializeFileTypes(array $validatorTypes): void
    {
        foreach ($validatorTypes as $validatorType) {
            if (!$validatorType instanceof TypeValidatorInterface) {
                throw new \LogicException(
                    sprintf('Validator type must implement %s', TypeValidatorInterface::class)
                );
            }
            $this->validators[] = $validatorType;
        }
    }
}
