<?php
namespace Wexo\Webshipper\Model\Config\Source\Order;

class InternalComment extends AbstractOrderAttributes
{

    public function getDefaultOption()
    {
        return [
            'value' => '0',
            'label' => __('-- Use Default (none) --')
        ];
    }
}
