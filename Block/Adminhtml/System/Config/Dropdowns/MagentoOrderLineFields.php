<?php

namespace Wexo\Webshipper\Block\Adminhtml\System\Config\Dropdowns;

class MagentoOrderLineFields extends \Wexo\Webshipper\Block\Adminhtml\System\Config\Dropdowns\MagentoFields
    {
    public function getSourceOptions()
    {
        $magentoFields = [
            [
                'label' => 'Order Line Fields',
                'value' => [
                    'product_name' => 'Product Name',
                    'product_sku' => 'Product SKU',
                    'product_weight' => 'Product Weight',
                ]
            ]
        ];
        return [ ...$magentoFields ];
    }
}
