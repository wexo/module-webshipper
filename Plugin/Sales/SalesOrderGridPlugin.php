<?php

namespace Wexo\Webshipper\Plugin\Sales;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;

class SalesOrderGridPlugin {
    /**
     * @param Collection $subject
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array
     * @throws LocalizedException
     */
    public function beforeLoad(Collection $subject, bool $printQuery = false, bool $logQuery = false): array
    {
        if (!$subject->isLoaded()) {
            $primaryKey = $subject->getResource()->getIdFieldName();
            $tableName = $subject->getResource()->getTable('webshipper');
            $orderTableName = $subject->getResource()->getTable('sales_order');
            
            $subject->getSelect()->joinLeft(
                $tableName,
                $tableName . '.order_id = main_table.' . $primaryKey,
                []
            )->joinLeft(
                $orderTableName,
                $orderTableName . '.entity_id = main_table.' . $primaryKey,
                [
                    'state' => new \Zend_Db_Expr(
                        'IF(' . $tableName . '.state IS NULL, IF('.$orderTableName.'.wexo_shipping_data IS NULL, "N/A", "Pending"), ' . $tableName . '.state)'
                    )
                ]
            );
        }

        return [$printQuery, $logQuery];
    }
}
