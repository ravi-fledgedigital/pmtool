<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\ReturnRules\DataProvider;

use Amasty\Rma\Api\Data\ResolutionInterface;
use Amasty\Rma\Api\Data\ReturnRulesInterface;
use Amasty\Rma\Api\ReturnRulesRepositoryInterface;
use Amasty\Rma\Api\Data\ReturnRulesWebsitesInterface;
use Amasty\Rma\Api\Data\ReturnRulesResolutionsInterface;
use Amasty\Rma\Api\Data\ReturnRulesCustomerGroupsInterface;
use Amasty\Rma\Model\OptionSource\Status;
use Amasty\Rma\Model\ReturnRules\ResourceModel\CollectionFactory;
use Amasty\Rma\Model\Resolution\ResourceModel\CollectionFactory as ResolutionCollectionFactory;
use Amasty\Rma\Controller\Adminhtml\RegistryConstants;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;

class Form extends AbstractDataProvider
{
    /**
     * @var ResolutionCollectionFactory
     */
    private $resolutionCollectionFactory;

    /**
     * @var ReturnRulesRepositoryInterface
     */
    private $repository;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        CollectionFactory $collectionFactory,
        ResolutionCollectionFactory $resolutionCollectionFactory,
        ReturnRulesRepositoryInterface $repository,
        DataPersistorInterface $dataPersistor,
        RequestInterface $request,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->resolutionCollectionFactory = $resolutionCollectionFactory;
        $this->repository = $repository;
        $this->dataPersistor = $dataPersistor;
        $this->request = $request;
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        /** @var ReturnRulesInterface $rule */
        foreach ($this->collection->getData() as $rule) {
            $this->loadedData[$rule[ReturnRulesInterface::ID]] = $this->prepareRuleData($rule);
        }
        $data = $this->dataPersistor->get('amrma_returnrule');

        if (!empty($data)) {
            $rule = $this->repository->getEmptyRuleModel();
            $rule->setData($data);
            $this->loadedData[$rule->getId()] = $rule->getData();
            $this->dataPersistor->clear('amrma_returnrule');
        }

        return $this->loadedData;
    }

    /**
     * @param ReturnRulesInterface $rule
     *
     * @return array
     */
    private function prepareRuleData($item)
    {
        $ruleWebsites = [];
        $websites = $this->repository->getWebsitesByRuleId($item[RegistryConstants::RULE_ID]);

        foreach ($websites as $website) {
            $ruleWebsites[] = $website[ReturnRulesWebsitesInterface::WEBSITE_ID];
        }
        $item[ReturnRulesInterface::WEBSITES] = $ruleWebsites;

        $ruleGroups = [];
        $customerGroups = $this->repository->getCustomerGroupsByRuleId($item[RegistryConstants::RULE_ID]);

        foreach ($customerGroups as $group) {
            $ruleGroups[] = $group[ReturnRulesCustomerGroupsInterface::CUSTOMER_GROUP_ID];
        }
        $item[ReturnRulesInterface::CUSTOMER_GROUPS] = $ruleGroups;
        $resolutions = $this->resolutionCollectionFactory->create()
            ->addNotDeletedFilter()
            ->getItems();

        foreach ($resolutions as $resolution) {
            if ($resolution->getStatus() === Status::DISABLED) {
                continue;
            }
            $ruleResolutionValue = $this->repository->getRuleResolution(
                $resolution->getResolutionId(),
                $item[RegistryConstants::RULE_ID]
            )->getValue();
            $item['resolution_' . $resolution->getResolutionId()] =
                $ruleResolutionValue !== null
                    ? $ruleResolutionValue
                    : $item[ReturnRulesInterface::DEFAULT_RESOLUTION];
        }

        return $item;
    }

    public function getMeta()
    {
        $resolutions = $this->resolutionCollectionFactory->create()->getItems();
        $meta = [
            'resolutions' => [
                'children' => []
            ]
        ];
        $ruleId = (int)$this->request->getParam(RegistryConstants::RULE_ID);

        foreach ($resolutions as $resolution) {
            if ($resolution->getStatus() === Status::DISABLED) {
                continue;
            }
            $meta['resolutions']['children']['resolution_' . $resolution->getResolutionId()] =
                $this->createResolutionField(
                    $resolution->getResolutionId(),
                    $resolution->getTitle(),
                    $this->repository->getRuleResolution(
                        $resolution->getResolutionId(),
                        $ruleId
                    )->getValue() === null
                );
        }

        return $meta;
    }

    /**
     * @param int $resolutionId
     * @param string $label
     * @param bool $isDefault
     *
     * @return array
     */
    private function createResolutionField($resolutionId, $label, $isDefault)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'dataType' => 'text',
                        'formElement' => 'input',
                        'componentType' => 'field',
                        'label' => $label . ' Period (days)',
                        'notice' => __('Enter "0" or leave empty to disable this type of resolution.'),
                        'validation' => [
                            'validate-digits' => true
                        ],
                        'service' => [
                            'template' => 'ui/form/element/helper/service'
                        ],
                        'disabled' => $isDefault
                    ]
                ]
            ],
            'attributes' => [
                'class' => \Magento\Ui\Component\Form\Field::class,
                'name' => 'resolution_' . $resolutionId
            ]
        ];
    }
}
