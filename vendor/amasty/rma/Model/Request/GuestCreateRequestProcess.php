<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Request;

use Amasty\Rma\Api\Data\GuestCreateRequestInterface;
use Amasty\Rma\Api\Data\GuestCreateRequestInterfaceFactory;
use Amasty\Rma\Api\GuestCreateRequestProcessInterface;
use Amasty\Rma\Model\Request\ResourceModel\GuestCreateRequest;
use Amasty\Rma\Model\Validation\GuestLogin\FieldValidatorProvider;
use Amasty\Rma\Model\Validation\GuestRequestDataValidator;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Math\Random;
use Magento\Sales\Api\OrderRepositoryInterface;

class GuestCreateRequestProcess implements GuestCreateRequestProcessInterface
{
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        private readonly GuestCreateRequestInterfaceFactory $createRequestFactory,
        private readonly GuestCreateRequest $guestCreateRequestResource,
        private readonly Random $mathRandom,
        ?FieldValidatorProvider $fieldValidatorProvider = null,
        private ?GuestRequestDataValidator $guestRequestDataValidator = null // TODO move to not optional
    ) {
        $this->guestRequestDataValidator ??= ObjectManager::getInstance()->get(GuestRequestDataValidator::class);
    }

    /**
     * @inheritdoc
     */
    public function process(GuestCreateRequestInterface $guestCreateRequest)
    {
        try {
            if (!$this->guestRequestDataValidator->isValid($guestCreateRequest)) {
                return false;
            }

            $guestCreateRequest->setSecretCode($this->mathRandom->getUniqueHash());
            $this->guestCreateRequestResource->save($guestCreateRequest);

            return $guestCreateRequest->getSecretCode();
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function getEmptyCreateRequest()
    {
        return $this->createRequestFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function getOrderIdBySecretKey($secretKey)
    {
        return $this->guestCreateRequestResource->findOrderBySecretKey($secretKey);
    }

    /**
     * @inheritdoc
     */
    public function deleteBySecretKey($secretKey)
    {
        $this->guestCreateRequestResource->deleteBySecretKey($secretKey);
    }
}
