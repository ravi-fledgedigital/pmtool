<?php
declare(strict_types=1);

namespace OnitsukaTiger\Favorite\Ui\Component\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class FavoriteActions extends Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /** Url Path */
    const FAVORITE_DETAIL_URL_PATH = 'onitsukatiger_favorite/favorite/details';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,        
        UrlInterface $urlBuilder,
        array $data = array(),
        array $components = array()) 
    {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return void
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');   
                if (isset($item['entity_id'])) {                    
                    $item[$name] = html_entity_decode('<a href="'.$this->urlBuilder->getUrl(self::FAVORITE_DETAIL_URL_PATH, ['id' => $item['entity_id'], 'type_id' => $item['type_id']]).'">'.__('View').'</a>');
                }
            }
        }
        return $dataSource;
    }
}