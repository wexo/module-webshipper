<?php
namespace Wexo\Webshipper\Model\Config\Source\Product;

class DangerousGoods extends \Wexo\Webshipper\Model\Config\Source\ProductAttributes
{
    public function getDefaultOption()
    {
        return [
            'value' => null,
            'label' => __('-- Use Default (none) --')
        ];
    }
}
