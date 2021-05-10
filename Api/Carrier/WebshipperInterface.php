<?php

namespace Wexo\Webshipper\Api\Carrier;

use Wexo\Shipping\Api\Carrier\CarrierInterface;

interface WebshipperInterface extends CarrierInterface
{
    const TYPE_NAME = 'webshipper';

    /**
     * @param string $country
     * @param string $method
     * @param string $postcode
     * @return \Wexo\Webshipper\Api\Data\ParcelShopInterface[]
     */
    public function getParcelShops($country, $method = '', $postcode = null);
}
