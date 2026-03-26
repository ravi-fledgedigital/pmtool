<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Attribute\InputType;

class InputType
{
    /**
     * @var string
     */
    private $label;

    /**
     * @var bool
     */
    private $manageOptions;

    /**
     * @var bool|string
     */
    private $optionDefault;

    /**
     * @var array
     */
    private $validateTypes;

    /**
     * @var array
     */
    private $validateFilters;

    /**
     * @var array
     */
    private $filterTypes;

    /**
     * @var bool|string
     */
    private $sourceModel;

    /**
     * @var bool|string
     */
    private $backendModel;

    /**
     * @var string
     */
    private $backendType;

    /**
     * @var bool|string
     */
    private $defaultValue;

    /**
     * @var string
     */
    private $frontendInputType;

    /**
     * @var string
     */
    private $frontendUiComponent;

    /**
     * @var string
     */
    private $frontendTmpl;

    /**
     * @var bool
     */
    private $displayEmptyOption;

    /**
     * @var string
     */
    private $dataModel;

    /**
     * @var bool
     */
    private $filterableInGrid;

    /**
     * @var string
     */
    private $columnUiComponent;

    /**
     * @var string
     */
    private $columnUiFilter;

    /**
     * @var string
     */
    private $columnDatatype;

    /**
     * @var bool
     */
    private $displayFutureCheckout;

    public function __construct(
        $label = '',
        $manageOptions = false,
        $optionDefault = false,
        $displayEmptyOption = true,
        $validateTypes = [],
        $validateFilters = [],
        $filterTypes = [],
        $sourceModel = false,
        $backendModel = false,
        $dataModel = false,
        $backendType = '',
        $defaultValue = false,
        $frontendInputType = '',
        $frontendUiComponent = '',
        $frontendTmpl = '',
        $filterableInGrid = false,
        $columnUiComponent = '',
        $columnUiFilter = '',
        $columnDatatype = '',
        $displayFutureCheckout = true
    ) {
        $this->label = $label;
        $this->manageOptions = $manageOptions;
        $this->optionDefault = $optionDefault;
        $this->validateTypes = $validateTypes;
        $this->validateFilters = $validateFilters;
        $this->filterTypes = $filterTypes;
        $this->sourceModel = $sourceModel;
        $this->backendModel = $backendModel;
        $this->dataModel = $dataModel;
        $this->backendType = $backendType;
        $this->defaultValue = $defaultValue;
        $this->frontendInputType = $frontendInputType;
        $this->frontendUiComponent = $frontendUiComponent;
        $this->frontendTmpl = $frontendTmpl;
        $this->displayEmptyOption = $displayEmptyOption;
        $this->filterableInGrid = $filterableInGrid;
        $this->columnUiComponent = $columnUiComponent;
        $this->columnUiFilter = $columnUiFilter;
        $this->columnDatatype = $columnDatatype;
        $this->displayFutureCheckout = $displayFutureCheckout;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return bool
     */
    public function isManageOptions()
    {
        return $this->manageOptions;
    }

    /**
     * @return bool|string
     */
    public function getOptionDefault()
    {
        return $this->optionDefault;
    }

    /**
     * @return array
     */
    public function getValidateTypes()
    {
        return $this->validateTypes;
    }

    /**
     * @return array
     */
    public function getValidateFilters()
    {
        return $this->validateFilters;
    }

    /**
     * @return array
     */
    public function getFilterTypes()
    {
        return $this->filterTypes;
    }

    /**
     * @return bool|string
     */
    public function getSourceModel()
    {
        return $this->sourceModel;
    }

    /**
     * @return bool|string
     */
    public function getBackendModel()
    {
        return $this->backendModel;
    }

    /**
     * @return bool|string
     */
    public function getDataModel()
    {
        return $this->dataModel;
    }

    /**
     * @return string
     */
    public function getBackendType()
    {
        return $this->backendType;
    }

    /**
     * @return bool|string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @return string
     */
    public function getFrontendInputType()
    {
        return $this->frontendInputType;
    }

    /**
     * @return string
     */
    public function getFrontendUiComponent()
    {
        return $this->frontendUiComponent;
    }

    /**
     * @return string
     */
    public function getFrontendTmpl()
    {
        return $this->frontendTmpl;
    }

    /**
     * @return bool
     */
    public function isDisplayEmptyOption()
    {
        return $this->displayEmptyOption;
    }

    /**
     * @return bool
     */
    public function isFilterableInGrid()
    {
        return $this->filterableInGrid;
    }

    /**
     * @return bool
     */
    public function isDisplayFutureCheckout(): bool
    {
        return $this->displayFutureCheckout;
    }

    /**
     * @return string
     */
    public function getColumnUiFilter()
    {
        return $this->columnUiFilter;
    }

    /**
     * @param string $columnUiFilter
     */
    public function setColumnUiFilter($columnUiFilter)
    {
        $this->columnUiFilter = $columnUiFilter;
    }

    /**
     * @return string
     */
    public function getColumnUiComponent()
    {
        return $this->columnUiComponent;
    }

    /**
     * @param string $columnUiComponent
     */
    public function setColumnUiComponent($columnUiComponent)
    {
        $this->columnUiComponent = $columnUiComponent;
    }

    /**
     * @return string
     */
    public function getColumnDatatype()
    {
        return $this->columnDatatype;
    }

    /**
     * @param string $columnDatatype
     */
    public function setColumnDatatype($columnDatatype)
    {
        $this->columnDatatype = $columnDatatype;
    }
}
