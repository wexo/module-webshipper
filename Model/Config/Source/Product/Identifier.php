<?php
namespace Wexo\Webshipper\Model\Config\Source\Order;

class Identifier extends \Wexo\Webshipper\Model\Config\Source\ProductAttributes
{
    public function getDefaultOption()
    {
        return [
            'value' => '',
            'label' => __('-- Use Default (increment_id) --')
        ];
    }
}
