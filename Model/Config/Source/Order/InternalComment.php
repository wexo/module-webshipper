<?php
namespace Wexo\Webshipper\Model\Config\Source\Order;

class InternalComment extends AbstractOrderAttributes
{

    public function getDefaultOption()
    {
        return [
            'value' => null,
            'label' => __('-- Use Default (none) --')
        ];
    }
}
