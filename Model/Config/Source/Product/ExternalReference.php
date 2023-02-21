<?php
namespace Wexo\Webshipper\Model\Config\Source\Product;

class ExternalReference extends \Wexo\Webshipper\Model\Config\Source\ProductAttributes
{
    public function getDefaultOption()
    {
        return [
            'value' => '0',
            'label' => __('-- Use Default (product_id) --')
        ];
    }
}
