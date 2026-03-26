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

namespace Mirasvit\CatalogLabel\Controller\Adminhtml;

use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Ui\Component\MassAction\Filter;
use Mirasvit\CatalogLabel\Api\Data\LabelInterface;
use Magento\Backend\App\Action;
use Mirasvit\CatalogLabel\Model\Indexer;
use Mirasvit\CatalogLabel\Model\Label\RuleFactory;
use Mirasvit\CatalogLabel\Model\ConfigProvider;
use Mirasvit\CatalogLabel\Repository\LabelRepository;
use Mirasvit\Core\Helper\Cron;
use Magento\Framework\Registry;
use Magento\Framework\Json\Helper\Data;
use Magento\Backend\App\Action\Context;

abstract class Label extends Action
{
    protected $labelRepository;

    protected $labelRuleFactory;

    protected $label;

    protected $config;

    protected $timezone;

    protected $registry;

    protected $jsonEncoder;

    protected $context;

    protected $backendSession;

    protected $resultFactory;

    protected $cronHelper;

    protected $indexer;

    protected $indexerRegistry;

    protected $filter;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        LabelRepository $labelRepository,
        RuleFactory $labelRuleFactory,
        LabelInterface $label,
        ConfigProvider $config,
        Cron $cronHelper,
        TimezoneInterface $timezone,
        Registry $registry,
        Data $jsonEncoder,
        IndexerRegistry $indexerRegistry,
        Indexer $indexer,
        Context $context,
        Filter $filter
    ) {
        $this->labelRepository  = $labelRepository;
        $this->labelRuleFactory = $labelRuleFactory;
        $this->label            = $label;
        $this->config           = $config;
        $this->cronHelper       = $cronHelper;
        $this->timezone         = $timezone;
        $this->registry         = $registry;
        $this->jsonEncoder      = $jsonEncoder;
        $this->context          = $context;
        $this->indexer          = $indexer;
        $this->indexerRegistry  = $indexerRegistry;
        $this->backendSession   = $context->getSession();
        $this->resultFactory    = $context->getResultFactory();
        $this->filter           = $filter;

        parent::__construct($context);
    }

    protected function initPage($resultPage)
    {
        $resultPage->setActiveMenu('Mirasvit_CatalogLabel::cataloglabel');

        $resultPage->getConfig()->getTitle()->prepend((string)__('Product Labels'));
        $resultPage->getConfig()->getTitle()->prepend((string)__('Manage Labels'));

        return $resultPage;
    }

    protected function getModel(): LabelInterface
    {
        $model = $this->labelRepository->create();

        if ($id = $this->getRequest()->getParam('id')) {
            $model = $this->labelRepository->get((int)$id);
        }

        $this->registry->register('current_model', $model);

        return $model;
    }

    protected function _isAllowed(): bool
    {
        return $this->context->getAuthorization()
            ->isAllowed('Mirasvit_CatalogLabel::cataloglabel_labels');
    }
}
