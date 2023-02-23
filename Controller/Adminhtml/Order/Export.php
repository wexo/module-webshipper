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

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    private $layoutFactory;

    public function __construct(
        Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Wexo\Webshipper\Model\Api $webshipperApi,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->webshipperApi = $webshipperApi;
        $this->resultFactory = $resultFactory;
        $this->layoutFactory = $layoutFactory;
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
            $this->messageManager->addErrorMessage(__('Order not exported to Webshipper, Check Webshipper Logs'));
        } else if($status == 'already_shipped'){
            $this->messageManager->addErrorMessage(__('Order already exists in Webshipper'));
        } else {
            $this->messageManager->addSuccessMessage(__('Order exported to Webshipper sucessfully'));
            
        }
        $this->_redirect('sales/order/view', ['order_id' => $orderId]);
    }

    public function _isAllowed()
    {
        return true;
    }
}
