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


namespace Mirasvit\CatalogLabel\Model;


use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Mirasvit\CatalogLabel\Api\Data\TemplateInterface;
use Mirasvit\Core\Service\SecureOutputService;

class Template extends AbstractModel implements TemplateInterface
{
    public function getId(): ?int
    {
        return $this->getData(TemplateInterface::ID)
            ? (int)$this->getData(TemplateInterface::ID)
            : null;
    }

    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    public function setName(string $name): TemplateInterface
    {
        return $this->setData(self::NAME, $name);
    }

    public function getCode(): string
    {
        return (string)$this->getData(self::CODE);
    }

    public function setCode(string $code): TemplateInterface
    {
        return $this->setData(self::CODE, $code);
    }

    public function getHtmlTemplate(): string
    {
        return (string)SecureOutputService::cleanupOne($this->getData(self::HTML_TEMPLATE));
    }

    public function setHtmlTemplate(string $html): TemplateInterface
    {
        return $this->setData(self::HTML_TEMPLATE, $html);
    }

    public function getStyle(): string
    {
        $style = (string)$this->getData(self::STYLE);

        try {
            $processor = new \Less_Parser;
            $processor->parse($style);

            return $style;
        } catch (\Exception $e) {
            return '/* ' . $e->getMessage() . ' */';
        }
    }

    public function setStyle(string $styles = ''): TemplateInterface
    {
        return $this->setData(self::STYLE, $styles);
    }
}
