<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Setup\Operation;

use Amasty\CustomTabs\Api\TabsRepositoryInterface;
use Amasty\CustomTabs\Model\Tabs\TabsFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;

class AddPredefinedTab
{
    /**
     * @var TabsFactory
     */
    private $tabsFactory;

    /**
     * @var TabsRepositoryInterface
     */
    private $repository;

    /**
     * @var DateTime
     */
    private $date;

    /**
     * @var string[]
     */
    private $requiredFields = [
        'tab_title',
        'tab_name',
        'content',
        'stores'
    ];

    public function __construct(
        TabsFactory $tabsFactory,
        TabsRepositoryInterface $repository,
        DateTime $date
    ) {
        $this->tabsFactory = $tabsFactory;
        $this->repository = $repository;
        $this->date = $date;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $paths = $this->getTemplates();
        foreach ($paths as $path) {
            $xmlDoc = simplexml_load_file($path);
            $tabData = $this->parseNode($xmlDoc);
            try {
                $this->repository->getByName($tabData['tab_name']);
            } catch (NoSuchEntityException $e) {
                $this->createTab($tabData);
            }
        }
    }

    /**
     * @return array
     */
    protected function getTemplates(): array
    {
        $p = strrpos(__DIR__, DIRECTORY_SEPARATOR);
        $directoryPath = $p ? substr(__DIR__, 0, $p) : __DIR__;
        $directoryPath .= '/../etc/predifined/';

        //phpcs:ignore
        return glob($directoryPath . '*.xml');
    }

    /**
     * @param array $data
     */
    protected function createTab(array $data): void
    {
        if ($this->isTemplateDataValid($data)) {
            $model = $this->tabsFactory->create();
            $model->addData($data);
            $model->setCreatedAt($this->date->gmtDate());
            $this->repository->save($model);
        }
    }

    /**
     * @param \SimpleXMLElement $node
     * @return string[]
     */
    protected function parseNode(\SimpleXMLElement $node): array
    {
        $data = [];
        foreach ($node as $keyNode => $childNode) {
            $data[$keyNode] = (string)$childNode;
        }

        return $data;
    }

    /**
     * @param array $data
     * @return bool
     */
    private function isTemplateDataValid(array $data = []): bool
    {
        $result = true;
        foreach ($this->requiredFields as $fieldName) {
            if (!array_key_exists($fieldName, $data)) {
                $result = false;
            }
        }

        return $result;
    }
}
