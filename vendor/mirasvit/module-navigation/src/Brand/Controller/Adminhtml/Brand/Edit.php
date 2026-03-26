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

namespace Mirasvit\Brand\Controller\Adminhtml\Brand;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Mirasvit\Brand\Api\Data\BrandPageInterface;
use Mirasvit\Brand\Controller\Adminhtml\Brand;
use Mirasvit\Brand\Model\Brand\PostData\Processor as PostDataProcessor;
use Mirasvit\Brand\Model\Config\Config;
use Mirasvit\Brand\Repository\BrandPageRepository;
use Mirasvit\Brand\Repository\BrandRepository;

class Edit extends Brand
{
    private $generalConfig;

    private $brandRepository;

    public function __construct(
        BrandRepository $brandRepository,
        BrandPageRepository $brandPageRepository,
        Context $context,
        PostDataProcessor $postDataProcessor,
        Config $config
    ) {
        $this->generalConfig   = $config->getGeneralConfig();
        $this->brandRepository = $brandRepository;

        parent::__construct(
            $brandPageRepository,
            $context,
            $postDataProcessor,
            $config
        );
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page\Interceptor $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $id = $this->getRequest()->getParam('id');
        $model = $this->initModel();

        if ($id && !$model->getId()) {
            $this->messageManager->addErrorMessage((string)__('This brand page no longer exists.'));

            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        if ($id && !$this->brandRepository->get($model->getAttributeOptionId())) {
            $this->messageManager->addWarningMessage(__(
                'Attribute option with ID: %1 removed or does not belong to the attribute with the code "%2"',
                $model->getAttributeOptionId(),
                $this->generalConfig->getBrandAttribute()
            ));
        }

        $this->initPage($resultPage)->getConfig()->getTitle()->prepend($model->getBrandTitle());

        return $resultPage;
    }
}
