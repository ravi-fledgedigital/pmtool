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


namespace Mirasvit\Brand\Block\Product;


use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Mirasvit\Brand\Model\Config\Config;
use Mirasvit\Brand\Repository\BrandRepository;
use Mirasvit\Brand\Service\BrandUrlService;

class Breadcrumbs extends \Magento\Theme\Block\Html\Breadcrumbs
{
    private $brandRepository;

    private $brandUrlService;

    private $config;

    public function __construct(
        Template\Context $context,
        BrandRepository $brandRepository,
        BrandUrlService $brandUrlService,
        Config $config,
        array $data = [],
        ?Json $serializer = null
    ) {
        $this->brandRepository = $brandRepository;
        $this->brandUrlService = $brandUrlService;
        $this->config          = $config;

        parent::__construct($context, $data, $serializer);
    }

    public function getBrandsBreadcrumbsConfig(): array
    {
        $brandsCrumbsConfig = [];

        $brandsCrumbsConfig[] = [
            'label' => $this->config->getGeneralConfig()->getBrandLinkLabel() ? : __('Brands'),
            'url'   => $this->brandUrlService->getBaseBrandUrl()
        ];

        foreach ($this->brandRepository->getFullList() as $brand) {
            $brandsCrumbsConfig[] = [
                'label' => $brand->getLabel(),
                'url'   => $brand->getUrl()
            ];
        }

        return $brandsCrumbsConfig;
    }
}
