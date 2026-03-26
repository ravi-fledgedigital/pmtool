<?php
namespace Seoulwebdesign\KakaoSync\Model\Config\Source;

use Magento\Customer\Api\Data\GroupSearchResultsInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Group.
 */
class CustomerGroup implements OptionSourceInterface
{
    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @param ModuleManager $moduleManager
     * @param GroupRepositoryInterface $groupRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ModuleManager $moduleManager,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->moduleManager = $moduleManager;
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toArray()
    {
        if (!$this->moduleManager->isEnabled('Magento_Customer')) {
            return [];
        }
        $customerGroups = [];
        /** @var GroupSearchResultsInterface $groups */
        $groups = $this->groupRepository->getList($this->searchCriteriaBuilder->create());
        foreach ($groups->getItems() as $group) {
            $customerGroups[$group->getId()] = $group->getCode();
        }

        return $customerGroups;
    }

    /**
     * Options getter
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toOptionArray()
    {
        $data = $this->toArray();
        $re = [];
        foreach ($data as $key => $val) {
            $re[]= ['value' =>$key, 'label' => __($val)];
        }
        return $re;
    }
}
