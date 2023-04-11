<?php

namespace Wexo\Webshipper\Model\Config;

use Magento\Framework\UrlInterface;

class LinkToDeliveryMethodsSettings implements \Magento\Config\Model\Config\CommentInterface
{
    protected $urlInterface;

    public function __construct(
        UrlInterface $urlInterface
    ) {
        $this->urlInterface = $urlInterface;
    }

    public function getCommentText($elementValue)
    {
        $url = $this->urlInterface->getUrl('*/*/*/section/carriers/');
        $url = '<a href="' . $url . '#carriers_webshipper-link">'.__('Delivery Methods').'</a>';
        $message = __('For Rate Quotes settings click here: %1', $url);
        return $message;
    }
}