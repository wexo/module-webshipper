<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Order Statuses source model
 */

namespace Wexo\Webshipper\Model\Config\Source;

/**
 * Class Status
 * @api
 * @since 100.0.2
 */
class WeightUnits implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $weightUnits = [
            'g' => 'Gram',
            'kg' => 'Kilogram',
            'oz' => 'Ounce',
            'lbs' => 'Pounds',
        ];
        $options = [['value' => '', 'label' => __('-- Please Select --')]];
        foreach ($weightUnits as $code => $label) {
            $options[] = ['value' => $code, 'label' => $label];
        }
        return $options;
    }
}
