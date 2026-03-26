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


namespace Mirasvit\LayeredNavigation\Controller\Adminhtml\Group;


use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Mirasvit\LayeredNavigation\Model\Config\FileProcessor;

class FileUpload extends Action
{
    private $fileProcessor;
    
    public function __construct(
        FileProcessor $fileProcessor,
        Context $context
    ) {
        $this->fileProcessor = $fileProcessor;
        
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->getRequest();

        $result = $this->fileProcessor->save('file');

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}