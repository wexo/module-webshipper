<?php

namespace Wexo\Webshipper\Block\Adminhtml\System\Config\Dropdowns;

class MagentoOrderFields extends \Wexo\Webshipper\Block\Adminhtml\System\Config\Dropdowns\MagentoFields
{
    public function getSourceOptions()
    {
        $magentoFields = [
            [
                'label' => 'Order Fields', 
                'value' => [
                    // 'order_id' => 'Order ID',
                    // 'order_increment_id' => 'Order Increment ID',
                    'order_weight' => 'Weight',
                ]
            ]
        ];
        return [ ...$magentoFields, ...parent::getSourceOptions() ];
    }
}
