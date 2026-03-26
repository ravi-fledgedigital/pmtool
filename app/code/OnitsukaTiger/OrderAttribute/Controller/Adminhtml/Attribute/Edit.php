<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
namespace OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Attribute;

use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface;

class Edit extends \OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Attribute
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\Attribute\CheckoutAttributeRepository
     */
    private $attributeRepository;

    public function __construct(
        Context $context,
        \Magento\Eav\Model\Config $eavConfig,
        \OnitsukaTiger\OrderAttribute\Api\CheckoutAttributeRepositoryInterface $attributeRepository,
        \Magento\Framework\Registry $coreRegistry
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->eavConfig = $eavConfig;
        $this->attributeRepository = $attributeRepository;
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('attribute_id', 0);
        $checkoutStepPosition = $this->getRequest()->getParam('position');

        if ($id) {
            try {
                $model = $this->attributeRepository->getById($id);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage('This attribute no longer exists.');
                return $this->_redirect('*/*/');
            }
        } else {
            $model = $this->eavConfig->getAttribute(Entity::ENTITY_TYPE_CODE, null);
        }

        // get entered data if was error when we do save
        $data = $this->_session->getAttributeData(true);
        if (!empty($data)) {
            $model->addData($data);
        }
        $attributeData = $this->getRequest()->getParam('attribute');
        if (!empty($attributeData) && $id === null) {
            $model->addData($attributeData);
        }

        if ($checkoutStepPosition) {
            $model->addData([CheckoutAttributeInterface::CHECKOUT_STEP => $checkoutStepPosition]);
        }

        $this->coreRegistry->register('entity_attribute', $model);

        $title = $id ? __('Edit Order Attribute') : __('New Order Attribute');

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->addBreadcrumb(__('Order'), __('Order'))
            ->addBreadcrumb(__('Order Attributes'), __('Order Attributes'))
            ->setActiveMenu('OnitsukaTiger_OrderAttribute::attributes_list')
            ->addBreadcrumb($title, $title);

        $resultPage->getConfig()->getTitle()->prepend($id ? $model->getName() : $title);

        $resultPage->getLayout()
            ->getBlock('attribute_edit_js')
            ->setIsPopup((bool)$this->getRequest()->getParam('popup'));

        return $resultPage;
    }
}
