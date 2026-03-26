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


namespace Mirasvit\CatalogLabel\Ui\Label\Form\Control;


use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Mirasvit\CatalogLabel\Model\Indexer;
use Mirasvit\CatalogLabel\Ui\General\Form\Control\GenericButton;

class ApplyButton extends GenericButton implements ButtonProviderInterface
{
    protected $indexerRegistry;

    public function __construct(
        IndexerRegistry $indexerRegistry,
        Context $context
    ) {
        $this->indexerRegistry = $indexerRegistry;

        parent::__construct($context);
    }

    public function getButtonData()
    {
        $idxr = $this->indexerRegistry->get(Indexer::INDEXER_ID);

        if (!$idxr->isScheduled()) {
            return [];
        }

        if (!$this->getId()) {
            return [];
        }

        return [
            'label'      => __('Apply Label'),
            'class'      => 'apply',
            'on_click'   => 'window.location.href="' . $this->getApplyUrl() . '"',
            'sort_order' => 20,
        ];
    }

    private function getApplyUrl()
    {
        return $this->getUrl('*/*/apply', ['id' => $this->getId()]);
    }
}
