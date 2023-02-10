<?php

namespace Wexo\Webshipper\Model;

use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Laminas\Db\Sql\Expression;
use Magento\Framework\Api\ObjectFactory;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Base64Json;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Wexo\Webshipper\Api\Data\ParcelShopInterface;

class Api
{
    const WEBSHIPPER_ORDER_STATE_PENDING = 'pending';
    const WEBSHIPPER_ORDER_STATE_PROCESSING = 'processing';
    const WEBSHIPPER_ORDER_STATE_EXPORTED = 'exported';
    const WEBSHIPPER_ORDER_STATE_FAILED = 'failed';

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var Client
     */
    private $client = null;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var ObjectFactory
     */
    private $objectFactory;

    /**
     * @var Config
     */
    private $config;
    /**
     * @var Base64Json
     */
    private $base64Json;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $json;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private $emulation;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;

    /**
     * Api constructor.
     * @param ClientFactory $clientFactory
     * @param UrlInterface $url
     * @param Json $jsonSerializer
     * @param ObjectFactory $objectFactory
     * @param Config $config
     * @param LoggerInterface $logger
     * @param Base64Json $base64Json
     */
    public function __construct(
        ClientFactory $clientFactory,
        UrlInterface $url,
        Json $jsonSerializer,
        ObjectFactory $objectFactory,
        \Wexo\Webshipper\Model\Config $config,
        LoggerInterface $logger,
        Base64Json $base64Json,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\App\Emulation $emulation,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->clientFactory = $clientFactory;
        $this->url = $url;
        $this->jsonSerializer = $jsonSerializer;
        $this->objectFactory = $objectFactory;
        $this->config = $config;
        $this->base64Json = $base64Json;
        $this->json = $json;
        $this->storeManager = $storeManager;
        $this->emulation = $emulation;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * @param $countryCode
     * @param $postalCode
     * @param $shipping_address
     * @return array|false
     */
    public function getParcelShops($countryCode, $method, $postalCode, $shipping_address)
    {
        return $this->request(function (Client $client) use ($countryCode, $method, $postalCode, $shipping_address) {
            $city = $shipping_address === null ? '' : $shipping_address['city'] ?? '';
            $company = $shipping_address === null ? '' : $shipping_address['company'] ?? '';
            $street = $shipping_address === null ? '' : $shipping_address['street'][0] ?? '';
            $storeCountry = $this->config->getStoreCountry();
            $countryCode = $shipping_address === null ? $storeCountry : $shipping_address['countryId'] ?? $storeCountry;
            $rate_id = (int)explode('_', $method ?? '')[0] ?? 0;
            $data = [
                'data' => [
                    'type' => 'drop_point_locators',
                    'attributes' => [
                        'shipping_rate_id' => $rate_id,
                        'delivery_address' => [
                            'zip' => $postalCode,
                            'city' => $city,
                            'address_1' => $street,
                            'street' => $street,
                            'company' => $company,
                            'country_code' => $countryCode
                        ]
                    ]
                ]
            ];
            $this->logger->debug(
                'Webshipper drop_point_locator Preflight',
                [
                    'body' => $data
                ]
            );
            return $client->post(
                "/v2/drop_point_locators",
                [
                    'json' => $data,
                    'headers' => [
                        'Accept' => 'application/vnd.api+json',
                        'Content-Type' => 'application/vnd.api+json'
                    ]
                ]
            );
        }, function (Response $response, $content) {
            $this->logger->debug('Webshipper drop_point_locator Resposne', ['content' => $content]);
            return isset($content['data']['attributes']['drop_points'])
                ? $this->mapParcelShops($content['data']['attributes']['drop_points'])
                : false;
        });
    }

    public function getShippingRateIdFromMethod($method)
    {
        return (int)explode('_', $method ?? '')[1] ?? 0;
    }

    public function isShippingRateDropPoint($method)
    {
        return (int)explode('_', $method ?? '')[2] === 1;
    }

    /**
     * @param callable $func
     * @param callable $transformer
     * @return mixed
     */
    public function request(callable $func, callable $transformer = null)
    {
        try {
            /** @var Response $response */
            $response = $func($this->getClient());

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() <= 299) {
                $content = $this->jsonSerializer->unserialize($response->getBody()->__toString());
                return $transformer === null ? $content : $transformer($response, $content);
            }
        } catch (ClientException $exception) {
            $body = $exception->getResponse()->getBody();
            $this->logger->error($exception->getMessage(), [
                'body' => $body
            ]);
            throw $exception;
        }

        return false;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        if ($this->client === null) {
            $baseUri = str_replace(
                '//',
                '//' . $this->config->getTenantName() . '.',
                $this->config->getEndpoint()
            );
            $this->client = $this->clientFactory->create([
                'config' => [
                    'base_uri' => $baseUri,
                    'time_out' => 5.0,
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->config->getToken(),
                        'Accept' => 'application/vnd.api+json',
                        'Content-Type' => 'application/vnd.api+json'
                    ]
                ]
            ]);
        }

        return $this->client;
    }

    public $dayMapper = [
        0 => 'Monday',
        1 => 'Tuesday',
        2 => 'Wednesday',
        3 => 'Thursday',
        4 => 'Friday',
        5 => 'Saturday',
        6 => 'Sunday'
    ];

    /**
     * @param $parcelShops
     * @return array
     */
    protected function mapParcelShops($parcelShops)
    {
        return array_map(function ($parcelShop) {
            $parcelShopData = [];
            if (!isset($parcelShop['opening_hours'])) {
                $parcelShop['opening_hours'] = [];
            }
            foreach ($parcelShop['opening_hours'] as $key => $item) {
                $item['day'] = $this->dayMapper[$item['day']];
                $parcelShop['opening_hours'][$key] = $item;
            }

            /** @var ParcelShopInterface $parcelShopObject */
            $parcelShopObject = $this->objectFactory->create(ParcelShopInterface::class, [
                'data' => $parcelShopData
            ]);
            $parcelShopObject->setNumber($parcelShop['drop_point_id'] ?? '');
            $parcelShopObject->setCompanyName($parcelShop['name'] ?? '');
            $parcelShopObject->setStreetName($parcelShop['address_1'] ?? '');
            $parcelShopObject->setZipCode($parcelShop['zip'] ?? '');
            $parcelShopObject->setCity($parcelShop['city'] ?? '');
            $parcelShopObject->setCountryCode($parcelShop['country_code'] ?? '');
            $parcelShopObject->setLongitude($parcelShop['longitude'] ?? '');
            $parcelShopObject->setLatitude($parcelShop['latitude'] ?? '');
            $parcelShopObject->setOpeningHours([$parcelShop['opening_hours']]);
            return $parcelShopObject;
        }, $parcelShops);
    }

    public function exportOrder(\Magento\Sales\Model\Order $order)
    {
        $this->emulation->startEnvironmentEmulation($order->getStoreId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);

        $this->request(function (Client $client) use ($order) {
            $data = $this->mapOrderTransferObject($order);

            $this->logger->debug(
                '\Wexo\Webshipper\Model\Api::exportOrder :: Preflight',
                [
                    'body' => $data
                ]
            );
            return $client->post(
                "/v2/orders",
                [
                    'json' => $data,
                    'headers' => [
                        'Accept' => 'application/vnd.api+json',
                        'Content-Type' => 'application/vnd.api+json'
                    ]
                ]
            );
        }, function (Response $response, $content) use ($order) {

            if($response->getStatusCode() === 201){

                $webshipperId = $content['data']['id'] ?? 0;
                // Update webshipper_log table with response and order status
                // $connection = $this->resourceConnection->getConnection();
                // $tableName = $this->resourceConnection->getTableName('webshipper_log');

                // $query = "
                //     INSERT INTO $tableName (order_id, webshipper_id, state, message, created_at) 
                //     VALUES (:order_id, :response, :status) 
                //     ON DUPLICATE KEY UPDATE response = :response, status = :status
                //     ";
                // $binds = [
                //     'order_id' => $order->getId(),
                //     'webshipper_id' => $webshipperId,
                //     'response' => $response->getBody()->__toString(),
                //     'state' => self::WEBSHIPPER_ORDER_STATE_EXPORTED,
                //     'created_at' => new \Magento\Framework\DB\Sql\Expression('NOW()')
                // ];
                // $connection->query($query, $binds);

            }
            $this->logger->debug(
                '\Wexo\Webshipper\Model\Api::exportOrder :: Response',
                [
                    'reason' => $response->getReasonPhrase(),
                    'status' => $response->getStatusCode(),
                    'body' => $content
                ]
            );
        });

        $this->emulation->stopEnvironmentEmulation();
    }

    public function mapOrderTransferObject(\Magento\Sales\Model\Order $order)
    {
        $oldStore = $this->storeManager->getStore();
        // Load order store to ensure configuration is loaded correctly
        $this->storeManager->setCurrentStore($order->getStoreId());

        $orderChannelId = $this->config->getOrderChannelId();
        $orderLines = $this->mapOrderLines($order);

        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();
        
        $deliveryAddress = $this->mapDeliveryAddress($shippingAddress);

        $shippingMethod = $order->getShippingMethod();
        $shippingRateId = $this->getShippingRateIdFromMethod($shippingMethod);
        $hasDropPoint = $this->isShippingRateDropPoint($shippingMethod);
        if ($hasDropPoint) {
            $dropPoint = $this->extractDroppoint($order);
        }
        
        $senderAddress = $this->mapSenderAddress();
        $billingAddress = $this->mapBillingAddress($billingAddress);


        // TODO: External Ref setting
        // TODO: visible ref setting
        // TODO: setting create_shipment_automatically

        $dto = [
            "data" => [
                "type" => "orders",
                "attributes" => [
                    "status" => "pending", // pending, dispatched, partly_dispatched, cancelled, error, missing_rate, on_hold
                    "ext_ref" => $order->getId(),
                    "visible_ref" => $order->getIncrementId(),
                    "delivery_address" => $deliveryAddress,
                    "sender_address" => $senderAddress,
                    "billing_address" => $billingAddress,
                    "currency" => $order->getOrderCurrencyCode(),
                    "order_lines" => $orderLines,
                    // TODO: setting for 'external_comment' =>
                    // TODO: setting for internal_comment
                ],
                "relationships" => [
                    "order_channel" => [
                        "data" => [
                            "id" => (int)$orderChannelId,
                            "type" => "order_channels"
                        ]
                    ],
                    "shipping_rate" => [
                        "data" => [
                            "id" => (int)$shippingRateId,
                            "type" => "shipping_rates"
                        ]
                    ]
                ]
            ]
        ];
        if ($hasDropPoint && !empty($dropPoint)) {
            $dto['data']['attributes']['drop_point'] = $dropPoint;
        }

        // Reset Current Store
        $this->storeManager->setCurrentStore($oldStore->getId());
        return $dto;
    }

    public function mapDeliveryAddress(\Magento\Sales\Model\Order\Address $shippingAddress)
    {
        $shippingFirstName = $shippingAddress->getFirstname() ?? '';
        $shippingLastName = $shippingAddress->getLastname() ?? '';
        $shippingFullName = $shippingFirstName . ' ' . $shippingLastName;
        $shippingFullName = str_replace('  ', '', trim($shippingFullName)); // issue with dobbelt space for some clients

        return [
            "att_contact" => $shippingFullName,
            "email" => $shippingAddress->getEmail() ?? '',
            "address_1" => $shippingAddress->getStreetLine(1) ?? '',
            "address_2" => $shippingAddress->getStreetLine(2) ?? '',
            "company_name" => $shippingAddress->getCompany() ?? '',
            "zip" => $shippingAddress->getPostcode() ?? 0,
            "city" => $shippingAddress->getCity() ?? '',
            "phone" => $shippingAddress->getTelephone() ?? '',
            "country_code" => $shippingAddress->getCountryId() ?? '',
        ];
    }

    public function mapBillingAddress(\Magento\Sales\Api\Data\OrderAddressInterface $billingAddress)
    {
        $billingFirstName = $billingAddress->getFirstname() ?? '';
        $billingLastName = $billingAddress->getLastname() ?? '';
        $billingFullName = $billingFirstName . ' ' . $billingLastName;
        $billingFullName = str_replace('  ', '', trim($billingFullName)); // issue with dobbelt space for some clients

        return [
            "att_contact" => $billingFullName,
            "email" => $billingAddress->getEmail() ?? '',
            "address_1" => $billingAddress->getStreetLine(1) ?? '',
            "address_2" => $billingAddress->getStreetLine(2) ?? '',
            "company_name" => $billingAddress->getCompany() ?? '',
            "zip" => $billingAddress->getPostcode() ?? 0,
            "city" => $billingAddress->getCity() ?? '',
            "phone" => $billingAddress->getTelephone() ?? '',
            "country_code" => $billingAddress->getCountryId() ?? '',
        ];
    }

    public function mapSenderAddress()
    {
        return [
            "att_contact" => $this->config->getStoreContact() ?? '',
            "email" => $this->config->getStoreEmail() ?? '',
            "address_1" => $this->config->getStoreStreet() ?? '',
            "address_2" => $this->config->getStoreSecondaryStreet() ?? '',
            "company_name" => $this->config->getStoreName(),
            "zip" => $this->config->getStoreZip() ?? '',
            "city" => $this->config->getStoreCity() ?? '',
            "phone" => $this->config->getStorePhone() ?? '',
            "country_code" => $this->config->getStoreCountry() ?? '',
        ];
    }

    public function mapOrderLines(\Magento\Sales\Model\Order $order)
    {
        // TODO: notes about settings for order lines:
        // minium weight
        // ext_ref source (product_id, sku, custom)
        // TODO: Dangerous_goods

        /** @var \Magento\Sales\Api\Data\OrderItemInterface $item */
        foreach ($order->getItems() as $item) {
            $orderLines[] = [
                "sku" => $item['sku'] ?? '',
                "description" => $item['name'] ?? '',
                "quantity" => $item->getQtyOrdered(),
                // TODO: Setting for location: "location" => null,
                // TODO: Setting for tarif: "tarif_number" => $this->getAttributeFromAdditionalData($item, 'maul_tarif') ?? null,
                // TODO: Setting for manufacturer: "country_of_origin" => ,
                "unit_price" => $item->getPriceInclTax(),
                "vat_percent" => $item->getTaxPercent(),
                "status" => 'pending', // pending, dispatched or returned
                "ext_ref" => $item->getProductId(),
                "weight" => $item->getWeight(),
                "weight_unit" => $this->config->getWeightUnit(),
                "is_virtual" => $item->getIsVirtual(),
                "additional_attributes" => [
                    // TODO: Additional Attributes
                ]
            ];
        }
        return $orderLines;
    }

    public function extractDroppoint(\Magento\Sales\Model\Order $order)
    {
        $extension_attributes = $order->getExtensionAttributes();
        $webshipperJson = $extension_attributes->getWexoShippingData();
        $webshipperData = $this->json->unserialize($webshipperJson);
        $parcelShop = isset($webshipperData['parcelShop']) && $webshipperData['parcelShop'] !== null
            ? $webshipperData['parcelShop']
            : null;

        $shippingRateId = $this->getShippingRateIdFromMethod($order->getShippingMethod());

        return [
            "drop_point_id" => $parcelShop['number'] ?? '',
            "address_1" => $parcelShop['street_name'] ?? '',
            "zip" => $parcelShop['zip_code'] ?? '',
            "city" => $parcelShop['city'] ?? '',
            "country_code" => $parcelShop['country_code'],
            "carrier_code" => $shippingRateId
        ];
    }

    public function getAttributeFromAdditionalData($item, $attribute_code)
    {
        $additional_data = collect($item['additional_data'])->map(function ($item) {
            return json_decode($item, true);
        });
        foreach ($additional_data as $data_item) {
            foreach ($data_item as $key => $value) {
                if ($key === $attribute_code) {
                    return $value;
                }
            }
        }
        return '';
    }
}
