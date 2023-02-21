<?php

namespace Wexo\Webshipper\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Export extends Action
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Wexo\Webshipper\Model\Api
     */
    private $webshipperApi;

    public function __construct(
        Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Wexo\Webshipper\Model\Api $webshipperApi,
    ) {
        $this->orderRepository = $orderRepository;
        $this->webshipperApi = $webshipperApi;
        parent::__construct($context);
    }
    /**
     * @inheritDoc
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $order = $this->orderRepository->get($orderId);
        $status = $this->webshipperApi->exportOrder($order);
        if ($status == 'error') {
            $order->addCommentToStatusHistory('Order not exported to Webshipper, Check Webshipper Logs');
            $this->messageManager->addErrorMessage(__('Order not exported to Webshipper, Check Webshipper Logs'));
        } else {
            $order->addCommentToStatusHistory('Order exported to Webshipper: ' . $status);
            $this->messageManager->addSuccessMessage(__('Order exported to Webshipper - (note: %1)', $status));
        }
        $this->orderRepository->save($order);
        $this->_redirect('sales/order/view', ['order_id' => $orderId]);
    }

    public function _isAllowed()
    {
        return true;
    }
}
