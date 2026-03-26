<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Model\Adminhtml\Balance;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\Model\Auth\Session;
use Magento\Customer\Helper\View;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\User\Api\Data\UserInterfaceFactory;
use Magento\User\Model\ResourceModel\User;

/**
 * Customerbalance history model for adminhtml area
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class History extends \Magento\CustomerBalance\Model\Balance\History
{
    /**
     * @var Session
     */
    protected Session $_authSession;

    /**
     * @var UserContextInterface
     */
    private UserContextInterface $userContext;

    /**
     * @var UserInterfaceFactory
     */
    private UserInterfaceFactory $userFactory;

    /**
     * @var User
     */
    private User $userResource;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param DesignInterface $design
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerRegistry $customerRegistry
     * @param View $customerHelperView
     * @param Session $authSession
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param UserContextInterface|null $userContext
     * @param UserInterfaceFactory|null $userFactory
     * @param User|null $userResource
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context               $context,
        Registry              $registry,
        TransportBuilder      $transportBuilder,
        StoreManagerInterface $storeManager,
        DesignInterface       $design,
        ScopeConfigInterface  $scopeConfig,
        CustomerRegistry      $customerRegistry,
        View                  $customerHelperView,
        Session               $authSession,
        AbstractResource      $resource = null,
        AbstractDb            $resourceCollection = null,
        array                 $data = [],
        UserContextInterface  $userContext = null,
        UserInterfaceFactory  $userFactory = null,
        User                  $userResource = null
    ) {
        $this->_authSession = $authSession;
        parent::__construct(
            $context,
            $registry,
            $transportBuilder,
            $storeManager,
            $design,
            $scopeConfig,
            $customerRegistry,
            $customerHelperView,
            $resource,
            $resourceCollection,
            $data
        );
        $this->userContext = $userContext ?? ObjectManager::getInstance()->get(UserContextInterface::class);
        $this->userFactory = $userFactory ?? ObjectManager::getInstance()->get(UserInterfaceFactory::class);
        $this->userResource = $userResource ?? ObjectManager::getInstance()->get(User::class);
    }

    /**
     * Add information about admin user who changed customer balance
     *
     * @return $this
     */
    public function beforeSave()
    {
        $balance = $this->getBalanceModel();
        if (in_array((int)$balance->getHistoryAction(), [self::ACTION_CREATED, self::ACTION_UPDATED])
            && !$balance->getUpdatedActionAdditionalInfo()
        ) {
            $user = $this->_authSession->getUser();

            if ($user === null) {
                $userId = $this->userContext->getUserId();
                if ($userId) {
                    $user = $this->userFactory->create();
                    $this->userResource->load($user, $userId);
                }
            }

            $username = $user ? $user->getUsername() : null;
            if ($username) {
                $comment = $balance->getComment();
                if ($comment === null || !trim($comment)) {
                    $this->setAdditionalInfo(__('By admin: %1.', $username));
                } else {
                    $this->setAdditionalInfo(__('By admin: %1. (%2)', $username, $comment));
                }
            }
        }

        return parent::beforeSave();
    }
}
