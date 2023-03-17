<?php
namespace Wexo\Webshipper\Model\Config\Source\Order;

class AdditionalAttributes extends AbstractOrderAttributes
{
    public function getDefaultOption()
    {
        return [
            'value' => null,
            'label' => __('-- Please Select --')
        ];
    }
}
