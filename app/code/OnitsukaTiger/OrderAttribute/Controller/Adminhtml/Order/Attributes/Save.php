<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Order\Attributes;

use Magento\Backend\App\Action;
use OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form;

class Save extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'OnitsukaTiger_OrderAttribute::attribute_value_edit';

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Entity\EntityResolver
     */
    private $entityResolver;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Value\Metadata\FormFactory
     */
    private $metadataFormFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Entity\Handler\Save
     */
    private $saveHandler;

    public function __construct(
        Action\Context $context,
        \OnitsukaTiger\OrderAttribute\Model\Entity\EntityResolver $entityResolver,
        \OnitsukaTiger\OrderAttribute\Model\Value\Metadata\FormFactory $metadataFormFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \OnitsukaTiger\OrderAttribute\Model\Entity\Handler\Save $saveHandler
    ) {
        parent::__construct($context);
        $this->entityResolver = $entityResolver;
        $this->metadataFormFactory = $metadataFormFactory;
        $this->orderRepository = $orderRepository;
        $this->saveHandler = $saveHandler;
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getParam('order');
        $attributesData = $data['extension_attributes']['onitsukatiger_order_attributes'] ?? [];
        if (!empty($attributesData) || !empty($this->getRequest()->getFiles()->toArray())) {
            try {
                /** @var \Magento\Sales\Model\Order $order */
                $order = $this->orderRepository->get($orderId);
                $entity = $this->entityResolver->getEntityByOrder($order);

                $form = $this->createEntityForm($entity, $order->getStoreId(), $order->getCustomerGroupId());
                // emulate request
                $request = $form->prepareRequest($attributesData);
                $data = $form->extractData($request);
                $entity->setCustomAttributes([]);
                $form->restoreData($data);
                $errors = $form->validateData($data);
                if (is_array($errors)) {
                    throw new \Magento\Framework\Exception\LocalizedException(__(implode($errors)));
                }
                $this->saveHandler->execute($entity);
                $this->messageManager->addSuccessMessage(__('The order attributes have been updated.'));
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('An error occurred while updating the order attributes: ' . $e->getMessage())
                );
            }
        }
        if ($orderId) {
            $resultRedirect->setPath(
                'sales/order/view',
                ['order_id' => $orderId, '_current' => true]
            );
        } else {
            $resultRedirect->setPath(
                'sales/order/',
                ['_current' => true]
            );
        }

        return $resultRedirect;
    }

    /**
     * Return Checkout Form instance
     *
     * @param \OnitsukaTiger\OrderAttribute\Model\Entity\EntityData $entity
     * @param int                                       $store
     * @param int                                       $customerGroup
     *
     * @return Form
     */
    protected function createEntityForm($entity, $store, $customerGroup)
    {
        /** @var Form $formProcessor */
        $formProcessor = $this->metadataFormFactory->create();
        $formProcessor->setFormCode('adminhtml_checkout')
            ->setEntity($entity)
            ->setStore($store)
            ->setCustomerGroupId($customerGroup);

        return $formProcessor;
    }
}
