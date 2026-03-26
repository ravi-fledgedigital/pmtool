<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\ViewModel\MassAction;

use Magento\CommerceBackendUix\Model\Config as ModelConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Product Mass Action Config for view model
 */
class MassActionConfig
{
    /**
     * @param RequestInterface $request
     * @param Template $template
     * @param ModelConfig $modelConfig
     * @param MassActionConfigFactory $massActionFactory
     * @param MassActions $massActions
     * @param array $config
     */
    public function __construct(
        private RequestInterface $request,
        private Template $template,
        private ModelConfig $modelConfig,
        private MassActionConfigFactory $massActionFactory,
        private MassActions $massActions,
        private array $config = []
    ) {
    }

    /**
     * Get Mass Action Config
     *
     * @return array
     */
    public function getConfig(): array
    {
        $massActions = $this->massActions->getList();
        foreach ($massActions as $massAction) {
            if (($massActionId = $this->request->getParam($massAction['requestId']))) {
                $config = $this->massActionFactory->create($massAction['type'])->getConfig($massActionId);
                $this->config['massAction'] = $config;
                $this->addCommerceConfig();
            }
        }
        return $this->config;
    }

    /**
     * Get Selected Ids from Mass Action
     *
     * @return array
     */
    public function getSelectedIds(): array
    {
        $massActions = $this->massActions->getList();
        foreach ($massActions as $massAction) {
            if (isset($massAction['requestId']) && $this->request->getParam($massAction['requestId'])) {
                return $this->massActionFactory->create($massAction['type'])->getSelectedIds();
            }
        }
        return [];
    }

    /**
     * Add Commerce Data to Config
     *
     * @return void
     */
    private function addCommerceConfig(): void
    {
        $this->config['commerce']['baseUrl'] = $this->template->getBaseUrl();
        $this->config['commerce']['clientId'] = $this->modelConfig->getClientId();
    }
}
