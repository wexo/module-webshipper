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
     * @var \Magento\Sales\Model\Order\AddressFactory
     */
    private $addressFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

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
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Sales\Model\Order\AddressFactory $addressFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
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
        $this->addressFactory = $addressFactory;
        $this->orderRepository = $orderRepository;
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
        $explode = explode('_', $method ?? '');
        if (count($explode) > 2) {
            return (int)explode('_', $method ?? '')[2] === 1;
        }
        return false;
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

    public function updateOrderChannel($orderChannelId, $data)
    {
        try {
            return $this->request(function (Client $client) use ($orderChannelId, $data) {
                return $client->patch(
                    "/v2/order_channels/" . $orderChannelId,
                    [
                        'json' => $data,
                        'headers' => [
                            'Accept' => 'application/vnd.api+json',
                            'Content-Type' => 'application/vnd.api+json'
                        ]
                    ]
                );
            }, function (Response $response, $content) {
                return $content;
            });
        } catch (\Throwable $t) {
            return false;
        }
    }

    public function orderChannel($orderChannelId)
    {
        try {
            return $this->request(function (Client $client) use ($orderChannelId) {
                return $client->get(
                    "/v2/order_channels/" . $orderChannelId,
                    [
                        'headers' => [
                            'Accept' => 'application/vnd.api+json',
                            'Content-Type' => 'application/vnd.api+json'
                        ]
                    ]
                );
            }, function (Response $response, $content) {
                return $content;
            });
        } catch (ClientException $e) {
            $this->logger->error(
                '\Wexo\Webshipper\Model\Api::OrderChannel :: Client Exception',
                [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
            return [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'type' => 'client',
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $this->logger->error(
                '\Wexo\Webshipper\Model\Api::OrderChannel :: Request Exception',
                [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
            return [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'type' => 'request',
            ];
        } catch (\Throwable $e) {
            $this->logger->error(
                '\Wexo\Webshipper\Model\Api::OrderChannel :: Throwable Exception',
                [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
            return [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'type' => 'throwable',
            ];
        }
    }

    public function exportOrder(\Magento\Sales\Model\Order $order)
    {
        if (!$this->config->isExportEnabled()) {
            return false;
        }
        $this->emulation->startEnvironmentEmulation($order->getStoreId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);
        try {
            $response = $this->request(function (Client $client) use ($order) {
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
                $this->logger->debug(
                    '\Wexo\Webshipper\Model\Api::exportOrder :: Response',
                    [
                        'reason' => $response->getReasonPhrase(),
                        'status' => $response->getStatusCode(),
                        'body' => $content
                    ]
                );
                $webshipperId = $content['data']['id'] ?? 0;

                if ($response->getStatusCode() === 201) {
                    $message = 'Success';
                    $state = self::WEBSHIPPER_ORDER_STATE_EXPORTED;
                    $order->addCommentToStatusHistory('Order exported to Webshipper: ' . $webshipperId);
                    $order->setData('webshipper_id', $webshipperId);
                } else {
                    $message = $content;
                    $state = self::WEBSHIPPER_ORDER_STATE_FAILED;
                    $order->addCommentToStatusHistory('Order not exported to Webshipper, Check Webshipper Logs');
                }

                $this->updateWebshipperLog(
                    $order->getId(),
                    $order->getIncrementId(),
                    $webshipperId,
                    $state,
                    $message
                );

                $this->orderRepository->save($order);

                return $content;
            });
        } catch (ClientException $e) {
            if ($e->getCode() === 422) {
                $message = 'Already Exported to Webshipper';
                $state = self::WEBSHIPPER_ORDER_STATE_EXPORTED;
                $adminMessage = 'already_shipped';
                $order->addCommentToStatusHistory('Order already exists in webshipper');
            } else {
                $message = $e->getMessage();
                $state = self::WEBSHIPPER_ORDER_STATE_FAILED;
                $adminMessage = 'error';
                $order->addCommentToStatusHistory('Order not exported to Webshipper: ' . $e->getMessage());
            }
            $this->orderRepository->save($order);

            $this->updateWebshipperLog(
                $order->getId(),
                $order->getIncrementId(),
                0,
                $state,
                $message
            );

            $this->logger->error(
                '\Wexo\Webshipper\Model\Api::exportOrder :: ERROR',
                [
                    'reason' => $e->getResponse()->getReasonPhrase(),
                    'message' => $e->getResponse()->getBody()->__toString(),
                    'status' => $e->getResponse()->getStatusCode(),
                    'body' => $e->getResponse()->getBody()->__toString()
                ]
            );

            return $adminMessage;
        } catch (\Throwable $e) {
            $order->addCommentToStatusHistory('Order not exported to Webshipper: ' . $e->getMessage());
            $this->orderRepository->save($order);
            $this->logger->error(
                '\Wexo\Webshipper\Model\Api::exportOrder :: ERROR',
                [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
            return 'error';
        }
        $this->emulation->stopEnvironmentEmulation();
        return $response;
    }

    public function updateWebshipperLog($order_id, $increment_id, $webshipper_id, $state, $message)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('webshipper');

        $query = "
            INSERT INTO $tableName (order_id, increment_id, webshipper_id, state, message, created_at) 
            VALUES (:order_id, :increment_id, :webshipper_id, :message, :state, NOW()) 
            ON DUPLICATE KEY UPDATE state = :state, updated_at = NOW(), message = CONCAT(:message, ' | ', message)
        ";
        $binds = [
            'order_id' => $order_id,
            'increment_id' => $increment_id,
            'webshipper_id' => $webshipper_id,
            'state' => $state,
            'message' => $message
        ];
        try {
            $connection->query($query, $binds);
        } catch (\Exception $e) {
            $this->logger->error(
                '\Wexo\Webshipper\Model\Api::updateWebshipperLog :: ERROR',
                [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
        }
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

        $shippingMethod = $order->getShippingMethod();
        $shippingRateId = $this->getShippingRateIdFromMethod($shippingMethod);
        $hasDropPoint = $this->isShippingRateDropPoint($shippingMethod);
        $dropPoint = [];
        $deliveryAddress = $this->mapDeliveryAddress($shippingAddress);
        if ($hasDropPoint) {
            $dropPoint = $this->extractDroppoint($order);

            // change delivery address to customer address to ensure trail is correct
            // Shipping address in magento is the parcelshop by default
            $shippingData = $this->jsonSerializer->unserialize($order->getData('wexo_shipping_data'));
            $customerShippingAddressData = $shippingData['shipping_address'] ?? [];
            if (!empty($customerShippingAddressData)) {
                $customerShippingAddress = $this->addressFactory->create();
                $customerShippingAddress->addData($customerShippingAddressData);
                $deliveryAddress = $this->mapDeliveryAddress($customerShippingAddress);
            }
        }

        $senderAddress = $this->mapSenderAddress();
        $billingAddress = $this->mapBillingAddress($billingAddress);

        $createShipmentAutomatically = $this->config->getCreateShipmentAutomatically();
        $externalReference = $this->config->getExternalReferenceFromOrder($order);
        $visibleReference = $this->config->getVisibleReferenceFromOrder($order);
        $externalComment = $this->config->getExternalCommentFromOrder($order);
        $internalComment = $this->config->getInternalCommentFromOrder($order);

        $dto = [
            "data" => [
                "type" => "orders",
                "attributes" => [
                    "status" => "pending", // pending, dispatched, partly_dispatched, cancelled, error, missing_rate, on_hold
                    "ext_ref" => $externalReference,
                    "visible_ref" => $visibleReference,
                    "delivery_address" => $deliveryAddress,
                    "sender_address" => $senderAddress,
                    "billing_address" => $billingAddress,
                    "currency" => $order->getOrderCurrencyCode(),
                    "order_lines" => $orderLines,
                    'external_comment' => $externalComment,
                    'internal_comment' => $internalComment
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

        $additionalAttributes = $this->config->getAdditionalAttributesForOrder($order);
        if (!empty($additionalAttributes)) {
            $dto['data']['attributes']['additional_attributes'] = $additionalAttributes;
        }
        if ($createShipmentAutomatically) {
            $dto['data']['attributes']['create_shipment_automatically'] = true;
        }
        if ($hasDropPoint && !empty($dropPoint)) {
            $dto['data']['attributes']['drop_point'] = $dropPoint;
        }

        $dto = $this->array_remove_empty($dto);

        // Reset Current Store
        $this->storeManager->setCurrentStore($oldStore->getId());
        return $dto;
    }

    public function array_remove_empty($haystack)
    {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = $this->array_remove_empty($haystack[$key]);
            }

            if (empty($haystack[$key])) {
                unset($haystack[$key]);
            }
        }

        return $haystack;
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
        $orderLines = [];

        /** @var \Magento\Sales\Api\Data\OrderItemInterface $item */
        foreach ($order->getItems() as $item) {
            $sku = $this->config->getSkuForOrderLine($item);
            $description = $this->config->getDescriptionForOrderLine($item);
            $externalReference = $this->config->getExternalReferenceForOrderLine($item);
            $weight = $this->config->getWeightForOrderLine($item);
            $tarif = $this->config->getTarifForOrderLine($item);
            $manufacturer = $this->config->getManufacturerForOrderLine($item);
            $location = $this->config->getLocationForOrderLine($item);
            $dangerousGoods = $this->config->getDangerousGoodsForOrderLine($item);
            $additionalAttributes = $this->config->getAdditionalAttributesForOrderLine($item);
            $orderLine = [
                "sku" => $sku,
                "description" => $description,
                "quantity" => $item->getQtyOrdered(),
                "location" => $location,
                "tarif_number" => $tarif,
                "country_of_origin" => $manufacturer,
                "unit_price" => $item->getPriceInclTax(),
                "vat_percent" => $item->getTaxPercent(),
                "status" => 'pending', // pending, dispatched or returned
                "ext_ref" => $externalReference,
                "weight" => $weight,
                "weight_unit" => $this->config->getWeightUnit(),
                "is_virtual" => $item->getIsVirtual(),
                "dangerous_goods_details" => $dangerousGoods,
                "additional_attributes" => $additionalAttributes
            ];
            $orderLines[] = $this->array_remove_empty($orderLine);
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
