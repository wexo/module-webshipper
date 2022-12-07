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
            $exportAtOrderStatus = $this->config->getExportOrderAtStatus() ?? [];
            if (empty($exportAtOrderStatus) && is_array($exportAtOrderStatus)) {
                return false;
            }

            $orderStatusIsValid = in_array($order->getStatus(), $exportAtOrderStatus);
            $validShippingMethod = strpos((string) $order->getShippingMethod(), 'webshipper') !== false;
            if ($orderStatusIsValid || !$validShippingMethod) {
                $this->logger->debug(
                    'Webshipper Bulk Import Order Status Validation',
                    [
                        'order_status' => $order->getStatus(),
                        'export_at_order_status' => $exportAtOrderStatus,
                        'order_status_valid' => $orderStatusIsValid,
                        'valid_shipping_method' => $validShippingMethod
                    ]
                );
                return false;
            }
            $this->api->request(function ($client) use ($order) {
                $data = [
                    'data' => [
                        'type' => 'bulk_import_orders',
                        'attributes' => [
                            'ids' => [$order->getIncrementId()],
                            'order_channel_id' => $this->config->getOrderChannelId()
                        ]
                    ]
                ];
                $this->logger->debug('Webshipper Bulk Import Order Request', [
                    'data' => $data
                ]);
                return $client->post('/v2/bulk_import_orders', [
                    'json' => $data,
                    'headers' => [
                        'Accept' => 'application/vnd.api+json',
                        'Content-Type' => 'application/vnd.api+json'
                    ]
                ]);
            }, function ($response, $content) {
                $this->logger->debug(
                    'Webshipper Bulk Import Response: ',
                    [
                        'content' => $content
                    ]
                );
            });
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
