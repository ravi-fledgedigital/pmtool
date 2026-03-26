<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Controller;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\GiftRegistry\Model\EntityFactory;
use Magento\GiftRegistry\Model\GiftRegistry\AddAddressToGiftRegistry;
use Magento\GiftRegistry\Model\GiftRegistry\GiftRegistryRegistrantsUpdater;
use Magento\GiftRegistry\Helper\Data as GiftRegistryDataHelper;
use Psr\Log\LoggerInterface;

/**
 * Base registry frontend controller
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * @var EntityFactory
     */
    protected EntityFactory $giftRegistryFactory;

    /**
     * @var GiftRegistryRegistrantsUpdater|mixed
     */
    protected GiftRegistryRegistrantsUpdater $registrantsUpdater;

    /**
     * @var AddAddressToGiftRegistry
     */
    protected AddAddressToGiftRegistry $giftRegistryAddress;

    /**
     * @var GiftRegistryDataHelper
     */
    protected GiftRegistryDataHelper $dataHelper;

    /**
     * @var LoggerInterface|mixed
     */
    protected LoggerInterface $logger;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param Validator $formKeyValidator
     * @param EntityFactory|null $giftRegistryFactory
     * @param GiftRegistryRegistrantsUpdater|null $registrantsUpdater
     * @param AddAddressToGiftRegistry|null $giftRegistryAddress
     * @param GiftRegistryDataHelper|null $dataHelper
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        ?EntityFactory $giftRegistryFactory = null,
        ?GiftRegistryRegistrantsUpdater $registrantsUpdater = null,
        ?AddAddressToGiftRegistry $giftRegistryAddress = null,
        ?GiftRegistryDataHelper $dataHelper = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->_formKeyValidator = $formKeyValidator;
        $this->_coreRegistry = $coreRegistry;
        $this->giftRegistryFactory = $giftRegistryFactory ?: ObjectManager::getInstance()->get(EntityFactory::class);
        $this->registrantsUpdater = $registrantsUpdater ?:
            ObjectManager::getInstance()->get(GiftRegistryRegistrantsUpdater::class);
        $this->giftRegistryAddress = $giftRegistryAddress ?:
            ObjectManager::getInstance()->get(AddAddressToGiftRegistry::class);
        $this->dataHelper = $dataHelper ?:
            ObjectManager::getInstance()->get(GiftRegistryDataHelper::class);
        $this->logger = $logger ?:
            ObjectManager::getInstance()->get(LoggerInterface::class);
        parent::__construct($context);
    }

    /**
     * Only logged users can use this functionality, this function checks if user is logged in before all other actions
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->_objectManager->get(\Magento\GiftRegistry\Helper\Data::class)->isEnabled()) {
            throw new NotFoundException(__('Page not found.'));
        }

        if (!$this->_objectManager->get(\Magento\Customer\Model\Session::class)->authenticate()) {
            $this->getResponse()->setRedirect(
                $this->_objectManager->get(\Magento\Customer\Model\Url::class)->getLoginUrl()
            );
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }
        return parent::dispatch($request);
    }

    /**
     * Get current customer session
     *
     * @return \Magento\Customer\Model\Session
     * @codeCoverageIgnore
     */
    protected function _getSession()
    {
        return $this->_objectManager->get(\Magento\Customer\Model\Session::class);
    }

    /**
     * Load gift registry entity model by request argument
     *
     * @param string $requestParam
     * @return \Magento\GiftRegistry\Model\Entity
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _initEntity($requestParam = 'id')
    {
        $entity = $this->_objectManager->create(\Magento\GiftRegistry\Model\Entity::class);
        $customerId = $this->_getSession()->getCustomerId();
        $entityId = $this->getRequest()->getParam($requestParam);

        if ($entityId) {
            $entity->load($entityId);
            if (!$entity->getId() || $entity->getCustomerId() != $customerId) {
                throw new LocalizedException(__('The gift registry ID is incorrect. Verify the ID and try again.'));
            }
        }
        return $entity;
    }
}
