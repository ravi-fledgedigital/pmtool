<?php

namespace OnitsukaTiger\Webapi\Controller\Adminhtml\Integration;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Integration\Block\Adminhtml\Integration\Edit\Tab\Info;
use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Integration\Model\Integration as IntegrationModel;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Security\Model\SecurityCookie;
use Magento\Integration\Api\OauthServiceInterface as IntegrationOauthService;

class Save extends \Magento\Integration\Controller\Adminhtml\Integration\Save
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var \Magento\Integration\Api\IntegrationServiceInterface
     */
    private $integrationService;
    /**
     * @var IntegrationOauthService
     */
    private $oauthService;
    /**
     * @var \Magento\Integration\Helper\Data
     */
    private $integrationData;
    /**
     * @var \Magento\Integration\Model\ResourceModel\Integration\Collection
     */
    private $integrationCollection;
    /**
     * @var SecurityCookie
     */
    private $securityCookie;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Integration\Api\IntegrationServiceInterface $integrationService
     * @param IntegrationOauthService $oauthService
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Integration\Helper\Data $integrationData
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Integration\Model\ResourceModel\Integration\Collection $integrationCollection
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Integration\Api\IntegrationServiceInterface $integrationService,
        IntegrationOauthService $oauthService,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Integration\Helper\Data $integrationData,
        \Magento\Framework\Escaper $escaper,
        \Magento\Integration\Model\ResourceModel\Integration\Collection $integrationCollection
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->integrationService = $integrationService;
        $this->oauthService = $oauthService;
        $this->jsonHelper = $jsonHelper;
        $this->integrationData = $integrationData;
        $this->escaper = $escaper;
        $this->integrationCollection = $integrationCollection;
        parent::__construct(
            $context,
            $registry,
            $logger,
            $integrationService,
            $oauthService,
            $jsonHelper,
            $integrationData,
            $escaper,
            $integrationCollection
        );
    }

    /**
     * Save integration action.
     *
     * @return void
     */
    public function execute()
    {
        /** @var array $integrationData */
        $integrationData = [];
        try {
            $integrationId = (int)$this->getRequest()->getParam(self::PARAM_INTEGRATION_ID);
            if ($integrationId) {
                $integrationData = $this->getIntegration($integrationId);
                if (!$integrationData) {
                    return;
                }
                if ($integrationData[Info::DATA_SETUP_TYPE] == IntegrationModel::TYPE_CONFIG) {
                    throw new LocalizedException(__("The integrations created in the config file can't be edited."));
                }
            }
            $this->validateUser();
            $this->processData($integrationData);
        } catch (UserLockedException $e) {
            $this->_auth->logout();
            $this->getSecurityCookie()->setLogoutReasonCookie(
                \Magento\Security\Model\AdminSessionsManager::LOGOUT_REASON_USER_LOCKED
            );
            $this->_redirect('*');
        } catch (\Magento\Framework\Exception\AuthenticationException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_getSession()->setIntegrationData($this->getRequest()->getPostValue());
            $this->_redirectOnSaveError();
        } catch (IntegrationException $e) {
            $this->messageManager->addErrorMessage($this->escaper->escapeHtml($e->getMessage()));
            $this->_getSession()->setIntegrationData($this->getRequest()->getPostValue());
            $this->_redirectOnSaveError();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($this->escaper->escapeHtml($e->getMessage()));
            $this->_redirectOnSaveError();
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage($this->escaper->escapeHtml($e->getMessage()));
            $this->_redirectOnSaveError();
        }
    }


    /**
     * Save integration data.
     *
     * @param array $integrationData
     * @return void
     */
    private function processData($integrationData)
    {
        /** @var array $data */
        $data = $this->getRequest()->getPostValue();
        $data['store_ids'] = isset($data['store_ids']) ? implode(',', $data['store_ids']) : 0;
        if (!empty($data)) {
            if (!isset($data['resource'])) {
                $integrationData['resource'] = [];
            }
            $integrationData = array_merge($integrationData, $data);
            if (!isset($integrationData[Info::DATA_ID])) {
                $integration = $this->_integrationService->create($integrationData);
            } else {
                $integration = $this->_integrationService->update($integrationData);
            }
            if (!$this->getRequest()->isXmlHttpRequest()) {
                $this->messageManager->addSuccess(
                    __(
                        'The integration \'%1\' has been saved.',
                        $this->escaper->escapeHtml($integration->getName())
                    )
                );
                $this->_redirect('*/*/');
            } else {
                $isTokenExchange = $integration->getEndpoint() && $integration->getIdentityLinkUrl() ? '1' : '0';
                $this->getResponse()->representJson(
                    $this->jsonHelper->jsonEncode(
                        ['integrationId' => $integration->getId(), 'isTokenExchange' => $isTokenExchange]
                    )
                );
            }
        } else {
            $this->messageManager->addError(__('The integration was not saved.'));
        }
    }

    /**
     * Get security cookie
     *
     * @return SecurityCookie
     * @deprecated 100.1.0
     */
    private function getSecurityCookie()
    {
        if (!($this->securityCookie instanceof SecurityCookie)) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(SecurityCookie::class);
        } else {
            return $this->securityCookie;
        }
    }
}
