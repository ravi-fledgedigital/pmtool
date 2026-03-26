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
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\CatalogLabel\Block;


use Magento\Framework\View\Element\Template;
use Mirasvit\CatalogLabel\Api\Data\DisplayInterface;
use Mirasvit\CatalogLabel\Repository\DisplayRepository;
use Mirasvit\CatalogLabel\Repository\TemplateRepository;
use Mirasvit\Core\Service\SecureOutputService;

class CommonCss extends Template
{
    private $templateRepository;

    private $displayRepository;

    public function __construct(
        TemplateRepository $templateRepository,
        DisplayRepository $displayRepository,
        Template\Context $context,
        array $data = []
    ){
        $this->templateRepository = $templateRepository;
        $this->displayRepository  = $displayRepository;

        parent::__construct($context, $data);
    }

    public function getCommonCss(): string
    {
        $css = '';

        foreach ($this->templateRepository->getCollection() as $template) {
            $css .= ' .cataloglabel-template-' . $template->getCode() . ' { ' . $template->getStyle() . ' } ';
        }

        /** @var DisplayInterface $display */
        foreach ($this->displayRepository->getByData() as $display) {
            if ($displayStyle = $display->getStyle()) {
                $css .= ' .cataloglabel-display-' . $display->getId() . ' { ' . $displayStyle . ' } ';
            }
        }

        $processor = new \Less_Parser;
        $processor->parse($css);

        $css = $processor->getCss();

        return SecureOutputService::cleanupOne($css);
    }
}
