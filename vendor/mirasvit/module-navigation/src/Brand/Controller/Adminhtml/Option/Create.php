<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\Brand\Controller\Adminhtml\Option;


use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\Model\View\Result\Page;
use Mirasvit\Brand\Model\Brand\PostData\Processor as PostDataProcessor;
use Mirasvit\Brand\Model\Config\Config;
use Mirasvit\Brand\Repository\BrandPageRepository;

class Create extends Action
{
    protected $config;

    private   $context;

    public function __construct(
        Context $context,
        Config $config
    ) {
        $this->context = $context;
        $this->config  = $config;

        parent::__construct($context);
    }

    public function execute()
    {
        if (!$this->config->getGeneralConfig()->getBrandAttribute()) {
            $this->messageManager->addNoticeMessage(
                (string)__('Please add "Brand Attribute" in Store->Configuration->MIRASVIT EXTENSION->Brand')
            );
        }
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $this->initPage($resultPage)->getConfig()->getTitle()->prepend((string)__('New Brand'));

        return $resultPage;
    }

    protected function _isAllowed(): bool
    {
        return $this->context->getAuthorization()->isAllowed('Mirasvit_Brand::brand_brand');
    }

    protected function initPage(Page $resultPage): Page
    {
        $resultPage->setActiveMenu('Magento_Backend::content');
        $resultPage->getConfig()->getTitle()->prepend((string)__('Brand Pages'));

        return $resultPage;
    }
}
