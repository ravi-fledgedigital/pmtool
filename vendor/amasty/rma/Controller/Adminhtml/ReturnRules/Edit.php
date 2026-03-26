<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\ReturnRules;

use Amasty\Rma\Controller\Adminhtml\AbstractReturnRules;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Amasty\Rma\Api\ReturnRulesRepositoryInterface;
use Amasty\Rma\Model\ReturnRules\ReturnRules;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;

class Edit extends AbstractReturnRules
{
    /**
     * @var ReturnRulesRepositoryInterface
     */
    private $repository;

    /**
     * @var Registry
     */
    private $registry;

    public function __construct(
        Action\Context $context,
        ReturnRulesRepositoryInterface $repository,
        Registry $registry
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->registry = $registry;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $ruleId = (int)$this->getRequest()->getParam(RegistryConstants::RULE_ID);
        $title = __('New Return Rule');

        if ($ruleId) {
            try {
                $model = $this->repository->getById($ruleId);
                $title = __('Edit Return Rule %1', $model->getName());
            } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                $this->messageManager->addErrorMessage(__('This rule no longer exists.'));

                return $this->resultRedirectFactory->create()->setPath('*/*/index');
            }
        } else {
            $model = $this->repository->getEmptyRuleModel();
        }
        $this->registry->register(ReturnRules::CURRENT_RETURN_RULE, $model);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Amasty_Rma::return_rules');
        $resultPage->addBreadcrumb(__('Return Rules'), __('Return Rules'));
        $resultPage->getConfig()->getTitle()->prepend($title);

        return $resultPage;
    }
}
