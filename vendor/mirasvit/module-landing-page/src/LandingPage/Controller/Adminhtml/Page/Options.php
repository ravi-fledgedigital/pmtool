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
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Controller\Adminhtml\Page;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\Product\Attribute\Repository as AttributeRepository;
use Magento\Framework\Controller\Result\JsonFactory;
use Mirasvit\LandingPage\Repository\PageRepository;

class Options extends Action
{
    private $jsonFactory;

    private $repository;

    private $pageRepository;

    public function __construct(
        AttributeRepository $repository,
        PageRepository      $pageRepository,
        JsonFactory         $jsonFactory,
        Context             $context
    ) {
        $this->pageRepository = $pageRepository;
        $this->jsonFactory    = $jsonFactory;
        $this->repository     = $repository;

        parent::__construct($context);
    }

    public function execute()
    {
        $attributeId = $this->getRequest()->getParam('attributeId');
        $result      = $this->jsonFactory->create();
        $options     = [];
        if (!$attributeId) {
            return $result->setData(['success' => true, 'value' => $options]);
        }

        $attribute = $this->repository->get($attributeId);

        foreach ($attribute->getOptions() as $option) {
            $options[] = [
                'label' => (string)$option->getLabel(),
                'value' => $option->getValue(),
            ];
        }

        return $result->setData(['success' => true, 'options' => $options]);
    }
}
