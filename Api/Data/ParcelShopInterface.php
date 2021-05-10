<?php

namespace Wexo\Webshipper\Api\Data;

interface ParcelShopInterface extends \Wexo\Shipping\Api\Data\ParcelShopInterface
{
    const NUMBER = 'id';
    const COMPANY_NAME = 'name';
    const STREET_NAME = 'address';
    const ZIP_CODE = 'postal_code';
    const CITY = 'city';
    const COUNTRY_CODE = 'country_code';
    const LONGITUDE = 'longitude';
    const LATITUDE = 'latitude';
    const OPENING_HOURS = 'opening_hours';
}
