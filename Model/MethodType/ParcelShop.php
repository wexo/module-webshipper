<?php

namespace Wexo\Webshipper\Model\MethodType;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Data\ObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Wexo\Shipping\Api\Carrier\MethodTypeHandlerInterface;
use Wexo\Shipping\Model\MethodType\AbstractParcelShop;

class ParcelShop extends AbstractParcelShop implements MethodTypeHandlerInterface
{

    /**
     * @var Json
     */
    private $jsonSerializer;
    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;
    /**
     * @var ObjectFactory
     */
    private $objectFactory;
    /**
     * @var null
     */
    private $parcelShopClass;

    public function __construct(
        Json $jsonSerializer,
        DataObjectHelper $dataObjectHelper,
        ObjectFactory $objectFactory,
        $parcelShopClass = null
    ) {
        parent::__construct(
            $jsonSerializer,
            $dataObjectHelper,
            $objectFactory,
            $parcelShopClass
        );
        $this->jsonSerializer = $jsonSerializer;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->objectFactory = $objectFactory;
        $this->parcelShopClass = $parcelShopClass;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return __('Parcel Shop');
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'parcelshop';
    }
    
    /**
     * @param CartInterface $quote
     * @param OrderInterface $order
     * @throws LocalizedException
     */
    public function saveOrderInformation(CartInterface $quote, OrderInterface $order)
    {
        $shippingData = $this->jsonSerializer->unserialize($order->getData('wexo_shipping_data'));

        if (!isset($shippingData['parcelShop'])) {
            throw new LocalizedException(__('Service Point must be set!'));
        }

        /** @var ParcelShopInterface $parcelShop */
        $parcelShop = $this->objectFactory->create($this->parcelShopClass, []);
        $this->dataObjectHelper->populateWithArray($parcelShop, $shippingData['parcelShop'], $this->parcelShopClass);

        if ($parcelShop->getNumber()) {
            
            $shippingData['shipping_address'] = $order->getShippingAddress()->getData();
            $order->setData('wexo_shipping_data', $this->jsonSerializer->serialize($shippingData));

            $order->getShippingAddress()->addData([
                OrderAddressInterface::COMPANY => $parcelShop->getCompanyName(),
                OrderAddressInterface::STREET => [
                    $parcelShop->getStreetName(),
                    $parcelShop->getNumber()
                ],
                OrderAddressInterface::POSTCODE => $parcelShop->getZipCode(),
                OrderAddressInterface::CITY => $parcelShop->getCity(),
                OrderAddressInterface::REGION => '',
                OrderAddressInterface::FAX => '',
                'save_in_address_book' => 0,
            ]);
        } else {
            throw new LocalizedException(__('Service Point number was not found!'));
        }
    }
}
