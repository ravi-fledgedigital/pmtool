<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Entity;

use OnitsukaTiger\OrderAttribute\Api\CheckoutDataRepositoryInterface;
use OnitsukaTiger\OrderAttribute\Api\Data\EntityDataInterface;
use OnitsukaTiger\OrderAttribute\Model\Entity\EntityResolver;
use OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form;
use OnitsukaTiger\OrderAttribute\Model\Entity\Handler\Save;
use OnitsukaTiger\OrderAttribute\Model\Value\Metadata\FormFactory;
use Magento\Framework\Exception\InputException;
use Magento\Quote\Api\CartRepositoryInterface;

class CheckoutDataRepository implements CheckoutDataRepositoryInterface
{
    /**
     * @var Handler\Save
     */
    private $saveHandler;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Entity\EntityResolver
     */
    private $entityResolver;

    /**
     * @var FormFactory
     */
    private $metadataFormFactory;

    /**
     * @var array
     */
    private $allowedFormCodes = [
        'onitsukatiger_checkout_virtual',
        'onitsukatiger_checkout',
        'onitsukatiger_checkout_shipping'
    ];

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    public function __construct(
        Save $saveHandler,
        EntityResolver $entityResolver,
        FormFactory $metadataFormFactory,
        CartRepositoryInterface $cartRepository
    ) {
        $this->saveHandler = $saveHandler;
        $this->entityResolver = $entityResolver;
        $this->metadataFormFactory = $metadataFormFactory;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @inheritdoc
     */
    public function save(
        $onitsukatigerCartId,
        $checkoutFormCode,
        $shippingMethodCode,
        EntityDataInterface $entityData
    ) {
        if (!in_array($checkoutFormCode, $this->allowedFormCodes)) {
            throw new InputException(__('Unknown Form Code'));
        }

        $entityData->setParentId((int)$onitsukatigerCartId);
        $entity = $this->entityResolver->getEntityByQuoteId($entityData->getParentId());
        $entityData->setEntityId($entity->getEntityId());
        $entityData->setParentEntityType(EntityDataInterface::ENTITY_TYPE_QUOTE);

        $cart = $this->cartRepository->get($entityData->getParentId());

        $form = $this->createEntityForm(
            $entity,
            $checkoutFormCode,
            $shippingMethodCode,
            $cart->getCustomerGroupId(),
            $cart->getStore()
        );

        $request = $form->prepareRequest($entityData->getData());
        $data = $form
            ->setIsAjaxRequest($request->isAjax())
            ->extractData($request);

        $entity->setCustomAttributes([]);
        $form->restoreData($data);

        if (empty($form->getAllowedAttributes())) {
            /** No attributes for saving */
            return ['ok' => true];
        }

        $errors = $form->validateData($data);
        if (is_array($errors)) {
            foreach ($errors as &$error) {
                $error = __($error);
            }

            throw new InputException(__(implode(' ', $errors)));
        }
        try {
            $this->saveHandler->execute($entity);

            return ['ok' => true];
        } catch (\Exception $e) {
            throw new InputException(__('Something went wrong.'));
        }
    }

    /**
     * Return Checkout Form instance
     *
     * @param \OnitsukaTiger\OrderAttribute\Model\Entity\EntityData $entity
     * @param string                                    $checkoutFormCode
     * @param string                                    $shippingMethod
     * @param int                                       $customerGroupId
     * @param \Magento\Store\Model\Store                $store
     *
     * @return Form
     */
    protected function createEntityForm($entity, $checkoutFormCode, $shippingMethod, $customerGroupId, $store)
    {
        /** @var Form $formProcessor */
        $formProcessor = $this->metadataFormFactory->create();
        $formProcessor->setFormCode($checkoutFormCode)
            ->setShippingMethod($shippingMethod)
            ->setCustomerGroupId($customerGroupId)
            ->setStore($store)
            ->setEntity($entity);

        return $formProcessor;
    }
}
