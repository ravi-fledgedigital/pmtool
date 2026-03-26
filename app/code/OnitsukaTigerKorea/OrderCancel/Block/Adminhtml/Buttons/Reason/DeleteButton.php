<?php

namespace OnitsukaTigerKorea\OrderCancel\Block\Adminhtml\Buttons\Reason;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton implements ButtonProviderInterface
{

    /**
     * @var UrlInterface
     */
    protected UrlInterface $urlBuilder;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    public function __construct(
        Context $context
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
        $this->request = $context->getRequest();
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return  string
     */
    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->urlBuilder->getUrl($route, $params);
    }

    /**
     * @return array
     */
    public function getButtonData(): array
    {
        if (!$this->getReasonId()) {
            return [];
        }
        $alertMessage = __('Are you sure you want to do this?');
        $onClick = sprintf('deleteConfirm("%s", "%s")', $alertMessage, $this->getDeleteUrl());

        return [
            'label' => __('Delete'),
            'class' => 'delete',
            'id' => 'condition-edit-delete-button',
            'on_click' => $onClick,
            'sort_order' => 20,
        ];
    }

    /**
     * @return string
     */
    public function getDeleteUrl(): string
    {
        return $this->getUrl('*/*/delete', ['reason_id' => $this->getReasonId()]);
    }

    /**
     * @return null|int
     */
    public function getReasonId(): ?int
    {
        return (int)$this->request->getParam('reason_id');
    }
}
