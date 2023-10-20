<?php

namespace Wexo\Webshipper\Model\Config\Source\Order;

class AbstractOrderAttributes implements \Magento\Framework\Data\OptionSourceInterface
{
    /** 
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $resourceConnection;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    public function getDefaultOption()
    {
        return [
            'value' => null,
            'label' => __('-- Use Default --')
        ];
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            $this->getDefaultOption()
        ];

        // Alternative to get all attributes, though might want to use DI to inject the class to get overrides
        // $reflectionClass = new \ReflectionClass(\Magento\Sales\Model\Order::class);
        // $constants = $reflectionClass->getConstants();
        // foreach($constants as $constant){
        //     $human = str_replace('_', ' ', $constant ?? '');
        //     $human = ucwords($human);
        //     $options[] = [
        //         'value' => $constant,
        //         'label' => $human
        //     ];
        // }

        $salesOrder = $this->resourceConnection->getConnection()->describeTable('sales_order');
        foreach (array_keys($salesOrder) as $attribute) {
            $human = str_replace('_', ' ', $attribute ?? '');
            $human = ucwords($human);
            $options[] = [
                'value' => $attribute,
                'label' => $human
            ];
        }
        usort($options, function ($a, $b) {
            return strcmp((string)$a['label'], (string)$b['label']);
        });
        return $options;
    }
}
