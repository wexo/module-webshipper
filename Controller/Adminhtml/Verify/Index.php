<?php

namespace Wexo\Webshipper\Controller\Adminhtml\Verify;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Wexo\Webshipper\Model\Api
     */
    private $api;

    /**
     * @var \Wexo\Webshipper\Model\Config
     */
    private $config;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private $emulation;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    private $configWriter;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param WebshipperHelper $webshipperHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Wexo\Webshipper\Model\Api $api,
        \Wexo\Webshipper\Model\Config $config,
        \Magento\Store\Model\App\Emulation $emulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->api = $api;
        $this->config = $config;
        $this->emulation = $emulation;
        $this->storeManager = $storeManager;
        $this->cacheTypeList = $cacheTypeList;
        $this->configWriter = $configWriter;
    }

    /**
     * Verify action.
     *
     * @return ResultInterface|ResponseInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $storeId = $this->getRequest()->getParam('store') ?? 0;
        $this->storeManager->setCurrentStore($storeId);
        $this->emulation->startEnvironmentEmulation($storeId, ScopeInterface::SCOPE_STORE);

        $orderChannelId = $this->config->getOrderChannelId();
        if (!$orderChannelId) {
            $this->messageManager->addErrorMessage(__('No order channel ID found. Please ensure you have set your configuration token.'));
            $this->messageManager->addNoticeMessage(__('You can find your Configuration Token under Order Channels => Information => Module Configuration.'));
            $resultRedirect->setUrl($this->_url->getUrl('adminhtml/system_config/edit', ['section' => 'webshipper']));
            return $resultRedirect;
        }

        $response = $this->api->orderChannel($orderChannelId);

        if (isset($response['error'])) {
            if ($response['code'] && $response['code'] == 403) {
                $this->messageManager->addErrorMessage(__('Invalid Configurations Token, Unauthorized'));
                $this->messageManager->addNoticeMessage(__('You need to refresh your Configuration Token under Order Channels => Information => Module Configuration.'));
                $this->messageManager->addNoticeMessage(__('After Refreshing your Configuration Token, remember to update your Configuration Token in the Webshipper Configuration.'));
                $resultRedirect->setUrl($this->_url->getUrl('adminhtml/system_config/edit', ['section' => 'carriers']));
                return $resultRedirect;
            } else {
                $this->messageManager->addErrorMessage(__('An error occurred while verifying your configuration token. [code: %1]', $response['code']));
            }
        }

        $orderChannelAttributes = $this->mapOrderChannelAttributes($response['data']['attributes']['attrs']);

        foreach ($orderChannelAttributes as $key => $attribute) {
            $this->handleOrderChannelAttribute($key, $attribute);
        }

        $this->configWriter->save(
            'webshipper/settings/enabled',
            1,
            $this->storeManager->getStore()->getId() === '0' ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT : ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        $this->emulation->stopEnvironmentEmulation();

        $this->cacheTypeList->cleanType('config');

        $this->messageManager->addSuccessMessage(__('Your configuration token has been verified successfully.'));

        // Redirect to the configuration page
        $resultRedirect->setUrl($this->_url->getUrl('adminhtml/system_config/edit', ['section' => 'webshipper']));
        return $resultRedirect;
    }

    public function handleOrderChannelAttribute($key, $data)
    {
        switch ($key) {
            case "transfer_status": // Transfer status
                $this->config->updateExportOrderAtStatus($data);
                break;
            case "weight_unit":
                $this->config->updateWeightUnit($data);
                break;
            case "additional_attributes":
                $this->config->updateOrderAdditionalData($data);
                break;
            case "additional_item_attributes":
                $this->config->updateOrderLineAdditionalData($data);
                break;
        }
    }

    public function mapOrderChannelAttributes($attributes)
    {
        $mappedAttributes = [];
        foreach ($attributes as $attribute) {
            $mappedAttributes[$attribute['attr_key']] = $attribute;
        }

        return $mappedAttributes;
    }

    /**
     * Check permission for the controller.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}
