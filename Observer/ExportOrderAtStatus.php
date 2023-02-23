<?php

namespace Wexo\Webshipper\Observer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Wexo\Webshipper\Model\Api;

class ExportOrderAtStatus implements ObserverInterface
{
    /**
     * @var Api
     */
    protected $api;
    /**
     * @var \Wexo\Webshipper\Model\Config
     */
    protected $config;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Api $api,
        LoggerInterface $logger,
        \Wexo\Webshipper\Model\Config $config
    ) {
        $this->api = $api;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();

            if($order->getWebshipperId() !== null){
                return false;
            }

            $exportAtOrderStatus = $this->config->getExportOrderAtStatus() ?? [];
            if (empty($exportAtOrderStatus) && is_array($exportAtOrderStatus)) {
                return false;
            }

            $oldStatus = $order->getOrigData('status');
            $newStatus = $order->getStatus();
            if ($oldStatus == $newStatus) {
                return false;
            }

            $orderStatusIsValid = in_array($order->getStatus(), $exportAtOrderStatus);
            $validShippingMethod = strpos((string) $order->getShippingMethod(), 'webshipper') !== false;

            if($this->config->isExportEnabled() && $validShippingMethod && $orderStatusIsValid){
                $this->api->exportOrder($order);
            }
        } catch (\Throwable $t) {
            $this->logger->debug(
                'Webhipper Bulk Import Error',
                [
                    'content' => $t->getMessage(),
                    'trace' => $t->getTraceAsString()
                ]
            );
        }
    }
}
