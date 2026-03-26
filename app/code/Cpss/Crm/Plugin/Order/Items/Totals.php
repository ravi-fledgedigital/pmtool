<?php
namespace Cpss\Crm\Plugin\Order\Items;

/**
 * Customer Order Totals
 */
class Totals
{
    protected $usedPoints = 0;
    protected $acquiredPoints = 0;
    protected $request;
    protected $orderRepository;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->request = $request;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Append Data from CPSS
     *
     * @param  \Magento\Sales\Block\Order\Items $subject
     * @param  mixed $alias
     * @param  mixed $useCache
     * @return string
     */
    public function afterGetChildHtml(\Magento\Sales\Block\Order\Items $subject,  $alias = '', $useCache = true)
    {
        $this->process();

        $html = $this->getAdditionalHtml() . $alias;

        return $html;
    }

    public function getAdditionalHtml()
    {
        $html = '<tr class="used-points">
        <th colspan="4" class="mark" scope="row">'. __('Used Points') .'</th>
            <td class="amount" data-th="Used points">' . __('%1 points',number_format((int) $this->usedPoints)) . '</td>
        </tr>
        <tr class="acquired-points">
            <th colspan="4" class="mark" scope="row">' . __('Points to be earned') . '</th>
            <td class="amount" data-th="Acquired Points">' . __('%1 points', number_format((int) $this->acquiredPoints)) . '</td>
        </tr>';

        return $html;
    }

    public function process()
    {
        $order = $this->getOrder($this->request->getParam('order_id'));
        $this->usedPoints = $order->getUsedPoint()  ?? 0;
        $this->acquiredPoints = $order->getAcquiredPoint() ?? 0;
    }

    public function getOrder($id)
    {
        return $this->orderRepository->get($id);
    }
}
