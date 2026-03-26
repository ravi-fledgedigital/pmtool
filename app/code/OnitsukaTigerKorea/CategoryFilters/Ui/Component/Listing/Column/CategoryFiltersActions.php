<?php
namespace OnitsukaTigerKorea\CategoryFilters\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class CategoryFiltersActions extends Column
{
    /** Url path */
    public const CATEGORYFILTERS_URL_PATH_EDIT = "categoryfilters/categoryfilters/addrow";
    public const CATEGORYFILTERS_URL_PATH_DELETE = "categoryfilters/categoryfilters/delete";
    /** @var UrlInterface */
    protected $urlBuilder;
    /**
     * @var string
     */
    private $editUrl;
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     * @param string $editUrl
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = [],
        $editUrl = self::CATEGORYFILTERS_URL_PATH_EDIT
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->editUrl = $editUrl;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource["data"]["items"])) {
            foreach ($dataSource["data"]["items"] as &$item) {
                $name = $this->getData("name");
                if (isset($item["filter_id"])) {
                    $item[$name]["edit"] = [
                        "href" => $this->urlBuilder->getUrl($this->editUrl, [
                            "filter_id" => $item["filter_id"],
                        ]),
                        "label" => __("Edit"),
                    ];
                    $item[$name]["delete"] = [
                        "href" => $this->urlBuilder->getUrl(
                            self::CATEGORYFILTERS_URL_PATH_DELETE,
                            ["filter_id" => $item["filter_id"]]
                        ),
                        "label" => __("Delete"),
                        "confirm" => [
                            "title" => __(
                                "Delete " . "<b>{$item["filter_id"]}</b>"
                            ),
                            "message" => __(
                                'Are you sure you wan\'t to delete a ' .
                                    "<b>{$item["filter_id"]}</b>" .
                                    " record?"
                            ),
                        ],
                    ];
                }
            }
        }
        return $dataSource;
    }
}
