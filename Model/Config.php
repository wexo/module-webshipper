<?php

namespace Wexo\Webshipper\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Serialize\Serializer\Base64Json;

class Config
{
    public $configurationToken = null;
    public $carrierConfig = false;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var Base64Json
     */
    private $base64Json;

    /**
     * @param \Magento\Framework\Serialize\Serializer\Json
     */
    private $json;

    /**
     * @param \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @param \Magento\Customer\Model\Session
     */
    private $customerSession;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Base64Json $base64Json,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->base64Json = $base64Json;
        $this->json = $json;
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
    }

    public function getConfigurationToken()
    {
        if ($this->configurationToken === null) {
            $configurationToken = (string) $this->scopeConfig->getValue(
                'carriers/webshipper/configuration_token',
                ScopeInterface::SCOPE_STORE
            ) ?? '';
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
            'webshipper/settings/export_order_status',
            ScopeInterface::SCOPE_STORE
        );
        if(is_string($value)) {
            $value = explode(',', $value);
        }
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

    public function getStoreEmail(): string
    {
        return (string) $this->scopeConfig->getValue(
            'trans_email/ident_general/email',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    public function getStoreContact(): string
    {
        return (string) $this->scopeConfig->getValue(
            'trans_email/ident_general/name',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    public function getStorePhone(): string
    {
        return (string) $this->scopeConfig->getValue(
            'general/store_information/phone',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    public function getStoreName(): string
    {
        return (string) $this->scopeConfig->getValue(
            'general/store_information/name',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    public function getStoreStreet(): string
    {
        return (string) $this->scopeConfig->getValue(
            'general/store_information/street_line1',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    public function getStoreSecondaryStreet(): string
    {
        return (string) $this->scopeConfig->getValue(
            'general/store_information/street_line2',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    public function getStoreCity(): string
    {
        return (string) $this->scopeConfig->getValue(
            'general/store_information/city',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    public function getStoreCountry(): string
    {
        return (string) $this->scopeConfig->getValue(
            'general/store_information/country_id',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    public function getStoreZip(): string
    {
        return (string) $this->scopeConfig->getValue(
            'general/store_information/postcode',
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    public function getStorePostCode(): string
    {
        return $this->getStoreZip();
    }

    public function getCarrierConfig(): array
    {
        if ($this->carrierConfig === false) {
            $this->carrierConfig = $this->scopeConfig->getValue(
                'carriers/webshipper',
                ScopeInterface::SCOPE_STORE
            );
            if (!is_array($this->carrierConfig)) {
                $this->carrierConfig = explode(',', $this->carrierConfig);
            }
        }
        return $this->carrierConfig;
    }

    public function resolveStaticField($mapping, $type)
    {
        $useStatic = isset($mapping[$type]) && $mapping[$type] === 'static';
        $staticNotEmpty = isset($mapping['static_field']) && !empty($mapping['static_field']);
        if ($useStatic && $staticNotEmpty) {
            return $mapping['static_field'];
        }
    }
    public function resolveWebshipperValue($mapping)
    {
        $static = $this->resolveStaticField($mapping, 'webshipper_field');
        if (!empty($static)) {
            return $static;
        }
        return $mapping['webshipper_field'];
    }

    public function resolveMagentoValue($mapping)
    {
        $static = $this->resolveStaticField($mapping, 'magento_field');
        if (!empty($static)) {
            return $static;
        }

        $fieldArray = explode('_', (string) $mapping['magento_field'] ?? '');
        $type = array_shift($fieldArray);
        $field = implode('_', $fieldArray);

        switch ($type) {
            case 'shipping':
                return $this->resolveMagentoShippingAddressValue($field);
                break;
            case 'billing':
                return $this->resolveMagentoBillingAddressValue($field);
                break;
            case 'session':
                return $this->resolveMagentoSessionValue($field);
                break;
            case 'store':
                return $this->resolveMagentoStoreValue($field);
                break;
                // case 'product':
                //     return $this->resolveMagentoProductValue($field);
                //     break;
            case 'order':
                return $this->resolveMagentoOrderValue($field);
                break;
            default:
                return false;
        }
    }

    public function getQuoteFromSession()
    {
        return $this->checkoutSession->getQuote();
    }

    public function resolveMagentoShippingAddressValue($field)
    {
        $quote = $this->getQuoteFromSession();
        $address = $quote->getShippingAddress();
        if ($field === 'name') {
            return $address->getFirstname() . ' ' . $address->getLastname();
        }
        return $address->getData($field);
    }

    public function resolveMagentoBillingAddressValue($field)
    {
        $quote = $this->getQuoteFromSession();
        $address = $quote->getBillingAddress();
        if ($field === 'name') {
            return $address->getFirstname() . ' ' . $address->getLastname();
        }
        return $address->getData($field);
    }

    public function resolveMagentoSessionValue($field)
    {
        return $this->customerSession->getData($field);
    }

    public function resolveMagentoStoreValue($field)
    {
        $storeInformation = $this->scopeConfig->getValue(
            'general/store_information',
            ScopeInterface::SCOPE_STORE
        );
        return $storeInformation[$field] ?? false;
    }


    public function resolveMagentoOrderValue($field)
    {
        $quote = $this->getQuoteFromSession();
        return $quote->getData($field);
    }


    // public function resolveMagentoProductValue($field)
    // {
    //     return false;
    // }

    public function updateAddressFromConfig(&$attributes, $attribute)
    {
        $attributeValue = isset($attributes[$attribute]) ? $attributes[$attribute] : [];
        $attributeValueMapper = $this->getMapper($attribute);
        foreach ($attributeValueMapper as $mapping) {
            $webshipperValue = $this->resolveWebshipperValue($mapping);
            $magentoValue = $this->resolveMagentoValue($mapping);
            if ($webshipperValue !== false && $magentoValue !== false) {
                $attributeValue[$webshipperValue] = $magentoValue;
            }
        }
        $attributes[$attribute] = $attributeValue;
    }

    public function updateOrderFromConfig($attributes)
    {
        $attributeValueMapper = $this->getMapper('order');
        foreach ($attributeValueMapper as $mapping) {
            $webshipperValue = $this->resolveWebshipperValue($mapping);
            $magentoValue = $this->resolveMagentoValue($mapping);
            if ($webshipperValue !== false && $magentoValue !== false) {
                $attributes[$webshipperValue] = $magentoValue;
            }
        }
    }

    // TODO add orderline mapper
    // public function updateOrderLinesFromConfig(&$orderLine)
    // {
    //     // $orderLines = $attributes['items'];
    //     $orderLineMapper = $this->getMapper('order_lines');
    //     foreach ($orderLineMapper as $mapping) {
    //         $webshipperValue = $this->resolveWebshipperValue($mapping);
    //         $magentoValue = $this->resolveMagentoValue($mapping);
    //         if ($webshipperValue !== false && $magentoValue !== false) {
    //             $orderLine[$webshipperValue] = $magentoValue;
    //         }
    //     }
    // }

    public function getMapper($type = false)
    {
        if (!$type) {
            return [];
        }
        if (!$this->carrierConfig) {
            $this->getCarrierConfig();
        }
        $mapper = isset($this->carrierConfig[$type]) ? $this->carrierConfig[$type] : false;
        if ($mapper) {
            return $this->decodeMapper($mapper);
        }
        return [];
    }

    public function decodeMapper($json)
    {
        try {
            return $this->json->unserialize($json) ?? [];
        } catch (\Exception $e) {
            $this->logger->debug(
                __METHOD__ . ' Mapper Decode Exception :: ' . $e->getMessage(),
                [
                    'json' => $json,
                    'trace' => $e->getTraceAsString()
                ]
            );
            return [];
        }
    }

    public function showButtonOnOrder()
    {
        return $this->scopeConfig->getValue(
            'webshipper/settings/show_button_on_order',
            ScopeInterface::SCOPE_STORE
        ) === '1';
    }

    public function getCreateShipmentAutomatically()
    {
        return $this->scopeConfig->getValue(
            'webshipper/settings/create_shipment_automatically',
            ScopeInterface::SCOPE_STORE
        ) === '1';
    }

    public function getValueFromOrder($order, $field)
    {
        $configValue = $this->scopeConfig->getValue(
            'webshipper/order/' . $field,
            ScopeInterface::SCOPE_STORE
        );
        if (!empty($configValue) && $configValue !== '0' && $configValue !== null) {
            $orderValue = $order->getData($configValue);
            if(!empty($orderValue) && $orderValue !== null) {
                return $orderValue;
            }
        }
        return false;
    }

    public function getExternalReferenceFromOrder($order)
    {
        $configValue = $this->getValueFromOrder($order, 'ext_ref');
        if ($configValue) {
            return $configValue;
        }
        return $order->getId();
    }

    public function getVisibleReferenceFromOrder($order)
    {
        $configValue = $this->getValueFromOrder($order, 'visible_ref');
        if ($configValue) {
            return $configValue;
        }
        return $order->getIncrementId();
    }

    public function getExternalCommentFromOrder($order)
    {
        $configValue = $this->getValueFromOrder($order, 'external_comment');
        if ($configValue) {
            return $configValue;
        }
        return false;
    }

    public function getInternalCommentFromOrder($order)
    {
        $configValue = $this->getValueFromOrder($order, 'internal_comment');
        if ($configValue) {
            return $configValue;
        }
        return false;
    }

    public function getValueFromOrderLine($item, $field)
    {
        $configValue = $this->scopeConfig->getValue(
            'webshipper/order_line/' . $field,
            ScopeInterface::SCOPE_STORE
        );
        if(is_string($configValue)){
            $configValue = explode(',', $configValue);
            $product = $item->getProduct();
            $returnValue = [];
            foreach($configValue as $value)
            {
                $returnValue[$value] = $product->getData($value);
            }
            if($field === 'additional_attributes'){
                return $returnValue;
            }else{
                return array_values($returnValue)[0] ?? false;
            }
        }
        return false;
    }

    public function getSkuForOrderLine($item)
    {
        $configValue = $this->getValueFromOrderLine($item, 'sku');
        if ($configValue) {
            return $configValue;
        }
        return $item->getSku();
    }

    public function getDescriptionForOrderLine($item)
    {
        $configValue = $this->getValueFromOrderLine($item, 'description');
        if ($configValue) {
            return $configValue;
        }
        return $item->getName();
    }

    public function getExternalReferenceForOrderLine($item)
    {
        $configValue = $this->getValueFromOrderLine($item, 'external_reference');
        if ($configValue) {
            return $configValue;
        }
        return $item->getProductId();
    }

    public function getWeightForOrderLine($item)
    {
        $configValue = $this->getValueFromOrderLine($item, 'weight');
        if ($configValue) {
            return $configValue;
        }
        return $item->getWeight();
    }

    public function getTarifForOrderLine($item)
    {
        $configValue = $this->getValueFromOrderLine($item, 'tarif');
        if ($configValue) {
            return $configValue;
        }
        return false;
    }

    public function getManufacturerForOrderLine($item)
    {
        $configValue = $this->getValueFromOrderLine($item, 'manufacturer');
        if ($configValue) {
            return $configValue;
        }
        return false;
    }

    public function getLocationForOrderLine($item)
    {
        $configValue = $this->getValueFromOrderLine($item, 'location');
        if ($configValue) {
            return $configValue;
        }
        return false;
    }

    public function getDangerousGoodsForOrderLine($item)
    {
        $configValue = $this->getValueFromOrderLine($item, 'dangerous_goods');
        if ($configValue) {
            return $configValue;
        }
        return false;
    }

    public function getAdditionalAttributesForOrderLine($item)
    {
        $configValue = $this->getValueFromOrderLine($item, 'additional_attributes');
        if ($configValue) {
            return $configValue;
        }
        return [];
    }

    public function isExportEnabled()
    {
        return $this->scopeConfig->getValue(
            'webshipper/settings/enabled',
            ScopeInterface::SCOPE_STORE
        ) === '1';
    }
}
