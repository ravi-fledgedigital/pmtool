<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTigerCpss\Customer\Plugin\Customer\Controller\Account;

use Magento\Customer\Model\Registration;
use Magento\Framework\View\Result\PageFactory;
use OnitsukaTigerCpss\Crm\Helper\Data as DataHelper;

class Create
{
    /**
     * @var \Magento\Customer\Model\Registration
     */
    protected $registration;
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    protected $crmHelper;
    protected $session;
    /**
     * @param PageFactory $resultPageFactory
     * @param Registration $registration
     */
    public function __construct(
        PageFactory $resultPageFactory,
        Registration $registration,
        DataHelper $crmHelper,
        \Magento\Customer\Model\Session $customerSession,
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->registration = $registration;
        $this->crmHelper = $crmHelper;
        $this->session = $customerSession;
    }
    public function beforeExecute(
        \Magento\Customer\Controller\Account\Create $subject
    ) {
        try{

            if ( empty($request[DataHelper::PARAMS_REGISTERED_FROM_APP])){
                return [];
            }
            $request = $subject->getRequest()->getParams();
            $clientId = $subject->getRequest()->getParam('client_id',null);
            if(!empty($clientId)){
                $clientIds = explode(',', $subject->getRequest()->getParam('client_id',null));
                $request['client_id'] = $clientIds[0];
            }

            if ( !isset($request[DataHelper::PARAMS_REGISTERED_FROM_APP]) || !$this->crmHelper->validateAppLoginCredentials($request)) {

                http_response_code(403);
                echo 'Forbidden';
                exit;
            }

        }catch (\Exception $exception){
            $this->crmHelper->logCritical($exception->getMessage());
        }

        return [];
    }
}

