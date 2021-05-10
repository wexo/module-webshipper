<?php

namespace Wexo\Webshipper\Model\Carrier;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Asset\Repository;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Wexo\Shipping\Api\Data\RateInterface;
use Wexo\Shipping\Api\Data\RateInterfaceFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Wexo\Shipping\Api\Carrier\MethodTypeHandlerInterface;
use Wexo\Shipping\Model\Carrier\AbstractCarrier;
use Wexo\Shipping\Model\Rate;
use Wexo\Shipping\Model\RateManagement;
use Wexo\Webshipper\Api\Carrier\WebshipperInterface;
use Wexo\Webshipper\Api\Data\ParcelShopInterface;
use Wexo\Webshipper\Model\Api;

class Webshipper extends AbstractCarrier implements WebshipperInterface
{
    public $_code = self::TYPE_NAME;

    protected static $logos = [];

    /**
     * @var Api
     */
    private $webshipperApi;
    /**
     * @var RateFactory
     */
    private $rateFactory;
    /**
     * @var \Wexo\Webshipper\Model\Config
     */
    private $config;
    /**
     * @var Json
     */
    private $json;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        RateManagement $rateManagement,
        MethodFactory $methodFactory,
        ResultFactory $resultFactory,
        Repository $assetRepository,
        StoreManagerInterface $storeManager,
        RateInterfaceFactory $rateFactory,
        \Wexo\Webshipper\Model\Config $config,
        Json $json,
        MethodTypeHandlerInterface $defaultMethodTypeHandler = null,
        array $methodTypeHandlers = [],
        array $data = []
    ) {
        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $rateManagement,
            $methodFactory,
            $resultFactory,
            $assetRepository,
            $storeManager,
            $defaultMethodTypeHandler,
            $methodTypeHandlers,
            $data
        );
        $this->rateFactory = $rateFactory;
        $this->config = $config;
        $this->json = $json;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return true;
    }

    /**
     * Type name that links to the Rate model
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return static::TYPE_NAME;
    }

    /**
     * @inheirtDoc
     */
    public function getParcelShops($country, $method = '', $postcode = null)
    {
        if (empty($postcode)) {
            return [];
        }

        try {
            $parcelShops = $this->getWebshipperApi()->getParcelShops($country, $method, $postcode);
        } catch (Exception $e) {
            return [];
        }
        if (empty($parcelShops) || !$parcelShops) {
            return [];
        }

        return $parcelShops;
    }

    /**
     * @return Api
     */
    public function getWebshipperApi()
    {
        if ($this->webshipperApi === null) {
            $this->webshipperApi = ObjectManager::getInstance()->create(
                Api::class
            );
        }
        return $this->webshipperApi;
    }

    public function getImageUrl(ShippingMethodInterface $shippingMethod, $rate, $typeHandler)
    {
        if (isset(static::$logos[$shippingMethod->getMethodCode()])) {
            return static::$logos[$shippingMethod->getMethodCode()];
        }
        return $this->assetRepository->createAsset('Wexo_Webshipper::images/webshipper.png', [
            'area' => Area::AREA_FRONTEND
        ])->getUrl();
    }

    public function collectRates(RateRequest $request)
    {
        $result = parent::collectRates($request);

        $shippingRates = $this->fetchWebshipperRates($request);
        if (!isset($shippingRates['data']['attributes']['quotes'])) {
            return $result;
        }

        foreach ($shippingRates['data']['attributes']['quotes'] as $shippingRate) {
            $rate = $this->createRateFromWebshipperRate($shippingRate);
            /** @var Method $method */
            $method = $this->methodFactory->create();
            $method->setData('carrier', $this->_code);
            $method->setData('carrier_title', 'Webshipper');
            $method->setData('method', $this->makeMethodCode($rate));
            $method->setData('method_title', $rate->getTitle());
            $method->setPrice(
                $request->getFreeShipping() && $rate->getAllowFree() ? 0 : $rate->getPrice()
            );
            $result->append($method);
        }
        return $result;
    }

    /**
     * @param RateInterface $rate
     * @return string
     */
    public function makeMethodCode(RateInterface $rate)
    {
        $dropPointIndicator = $rate->getMethodType() === 'parcelshop' ? '1' : '0';
        return "{$rate->getId()}_{$dropPointIndicator}";
    }

    /**
     * @param string $method
     * @return mixed
     */
    public function getMethodTypeByMethod(string $method)
    {
        $methodType = explode('_', $method)[1];
        return $methodType === '1' ? 'parcelshop' : 'address';
    }

    public function fetchWebshipperRates($request)
    {
        return $this->getWebshipperApi()->request(function ($client) use ($request) {
            $allItems = $request->getAllItems();
            $items = [];
            foreach ($allItems as $item) {
                $items[] = [
                    'quantity' => $item->getQty(),
                    'sku' => $item->getSku()
                ];
            }
            $data = [
                "data" => [
                    "type" => "rate_quotes",
                    "attributes" => [
                        "order_channel_id" => $this->config->getOrderChannelId(),
                        "price" => $request->getPackageValue(),
                        "weight" => $request->getPackageWeight(),
                        "delivery_address" => [
                            "zip" => $request->getDestPostcode(),
                            "country_code" => $request->getDestCountryId()
                        ],
                        "items" => $items
                    ]
                ]
            ];
            return $client->post(
                "/v2/rate_quotes",
                [
                    'json' => $data,
                    'headers' => [
                        'Accept' => 'application/vnd.api+json',
                        'Content-Type' => 'application/vnd.api+json'
                    ]
                ]
            );
        }, function ($response, $content) {
            return $content;
        });
    }

    public function createRateFromWebshipperRate($data)
    {
        $shippingRate = $data['shipping_rate'] ?? [];
        /** @var Rate $rate */
        $rate = $this->rateFactory->create();
        $methodType = $shippingRate['require_drop_point'] ? 'parcelshop' : 'address';
        $rate->setId($shippingRate['id']);
        $rate->setCarrierType($this->_code);
        $rate->setMethodType($methodType);
        $rate->setIsActive(true);
        $rate->setSortOrder(1);
        $rate->setTitle($shippingRate['name']);
        $rate->setPrice($data['price']);
        $rate->setAllowFree(true);
        static::$logos[$this->makeMethodCode($rate)] = $data['carrier_logo'];
        return $rate;
    }
}
