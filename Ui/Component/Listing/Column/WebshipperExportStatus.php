<?php

namespace Wexo\Webshipper\Ui\Component\Listing\Column;

use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Api\SearchCriteriaBuilder;

class WebshipperExportStatus extends Column
{
    protected $_orderRepository;
    protected $_searchCriteria;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $criteria,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        array $components = [],
        array $data = []
    ) {
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria  = $criteria;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $orderIds = [];
            foreach ($dataSource['data']['items'] as $item) {
                $orderIds[] = $item["entity_id"];
            }

            $query = $this->resourceConnection->getConnection()->select()->from('webshipper')->where('order_id IN (?)', $orderIds);
            $result = $this->resourceConnection->getConnection()->fetchAll($query);
            $lookup = [];
            foreach ($result as $entry) {
                $lookup[$entry['order_id']] = $entry['state'];
            }

            foreach ($dataSource['data']['items'] as &$item) {

                $order  = $this->_orderRepository->get($item["entity_id"]);
                $shippingMethod = (string)$order->getShippingMethod();
                if(strpos($shippingMethod, 'webshipper') === false) {
                    $item[$this->getData('name')] = 'n/a';
                    continue;
                }
                $status = $lookup[$item['entity_id']] ?? 'pending';
                $item[$this->getData('name')] = $status;
            }
        }

        return $dataSource;
    }
}
