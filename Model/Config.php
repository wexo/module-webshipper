<?php

namespace Wexo\Webshipper\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
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
}
