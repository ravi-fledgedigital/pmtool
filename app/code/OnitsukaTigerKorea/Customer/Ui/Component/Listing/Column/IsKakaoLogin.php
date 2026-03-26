<?php

namespace OnitsukaTigerKorea\Customer\Ui\Component\Listing\Column;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class IsKakaoLogin
 * @package OnitsukaTigerKorea\Customer\Ui\Component\Listing\Column
 */
class IsKakaoLogin extends Column
{
    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        array              $components = [],
        array              $data = []
    )
    {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!empty($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item['is_kakao_login'] =
                    (!empty($item['is_kakao_login']) && $item['is_kakao_login'] == '1')
                        ? __('Yes')
                        : __('No');
            }
        }
        return $dataSource;
    }
}