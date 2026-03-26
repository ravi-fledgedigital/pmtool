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


namespace Mirasvit\CatalogLabel\Model\System\Config\Source;


use Magento\Framework\Option\ArrayInterface;
use Mirasvit\CatalogLabel\Api\Data\TemplateInterface;
use Mirasvit\CatalogLabel\Repository\TemplateRepository;

class TemplateSource implements ArrayInterface
{
    private $templateRepository;

    public function __construct(
        TemplateRepository $templateRepository
    ) {
        $this->templateRepository = $templateRepository;
    }

    public function toOptionArray()
    {
        $result = [
            [
                'value'   => 0,
                'label'   => 'No Template',
                'content' => 'No Template'
            ]
        ];

        /** @var TemplateInterface $template */
        foreach ($this->templateRepository->getCollection() as $template) {
            $result[] = [
                'value'   => $template->getId(),
                'label'   => $template->getName(),
                'content' => $template->getHtmlTemplate()
                    . '<style>'
                    . $template->getStyle()
                    . '</style>'
            ];
        }

        return $result;
    }

    public function toArray(): array
    {
        $values = [];

        foreach ($this->toOptionArray() as $item) {
            $values[$item['value']] = $item['label'];
        }

        return $values;
    }
}
