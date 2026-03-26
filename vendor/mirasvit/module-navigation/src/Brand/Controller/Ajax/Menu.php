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

namespace Mirasvit\Brand\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Mirasvit\Brand\Service\BrandListService;
use Mirasvit\Brand\Model\Config\Config;

class Menu extends Action
{
    private $brandListService;

    private $config;

    private $resultJsonFactory;

    public function __construct(
        Context          $context,
        BrandListService $brandListService,
        Config           $config,
        JsonFactory      $resultJsonFactory
    ) {
        $this->brandListService  = $brandListService;
        $this->config            = $config;
        $this->resultJsonFactory = $resultJsonFactory;

        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $resultJson = $this->resultJsonFactory->create();
        $letter     = $this->getRequest()->getParam('letter');
        $brands     = $this->getBrandsByLetter($letter);

        $brandsArray = [];

        foreach ($brands as $brand) {
            $brandsArrayItem = [
                'label' => $brand->getLabel(),
                'url'   => $brand->getUrl(),
            ];

            if ($this->config->getAllBrandPageConfig()->isShowBrandLogo()) {
                $brandsArrayItem['image'] = $brand->getImage();
            }

            $brandsArray[] = $brandsArrayItem;
        }

        return $resultJson->setData(['brands' => $brandsArray]);
    }

    private function getBrandsByLetter(?string $letter = null): array
    {
        if (!$letter) {
            return $this->brandListService->getFeaturedBrands();
        }

        $brandsByLetters = $this->brandListService->getBrandsByLetters();

        if (isset($brandsByLetters[$letter])) {
            return $brandsByLetters[$letter];
        }

        return [];
    }
}
