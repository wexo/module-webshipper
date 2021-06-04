<?php

namespace Wexo\Webshipper\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Serialize\Serializer\Base64Json;

class Config
{
    public $configurationToken = null;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var Base64Json
     */
    private $base64Json;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Base64Json $base64Json
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->base64Json = $base64Json;
    }

    public function getConfigurationToken()
    {
        if ($this->configurationToken === null) {
            $configurationToken = $this->scopeConfig->getValue('carriers/webshipper/configuration_token');
            $this->configurationToken = $this->base64Json->unserialize($configurationToken);
        }
        return $this->configurationToken;
    }

    public function getEndpoint()
    {
        return $this->getConfigurationToken()['endpoint'] ?? false;
    }

    public function getOrderChannelId()
    {
        return $this->getConfigurationToken()['order_channel_id'] ?? false;
    }

    public function getTenantName()
    {
        return $this->getConfigurationToken()['tenant_name'] ?? false;
    }

    public function getToken()
    {
        return $this->getConfigurationToken()['token'] ?? false;
    }

    public function getExportOrderAtStatus()
    {
        $value = $this->scopeConfig->getValue(
            'carriers/webshipper/export_order_at_status',
            ScopeInterface::SCOPE_STORE
        );
        return $value ?? false;
    }

    public function showCarrierLogo()
    {
        return $this->scopeConfig->getValue(
            'carriers/webshipper/show_carrier_logo',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getWeightUnit()
    {
        return $this->scopeConfig->getValue(
            'carriers/webshipper/product_weight_unit',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProductAttributes()
    {
        return $this->scopeConfig->getValue(
            'carriers/webshipper/product_attributes',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getStoreCountry(): string
    {
        return $this->scopeConfig->getValue(
                'general/store_information/country_id',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
    }

}
