<?php

namespace Wexo\Webshipper\Model\Config;

use Magento\Framework\UrlInterface;

class LinkToConfigurationsToken implements \Magento\Config\Model\Config\CommentInterface
{
    protected $urlInterface;

    public function __construct(
        UrlInterface $urlInterface
    ) {
        $this->urlInterface = $urlInterface;
    }

    public function getCommentText($elementValue)
    {
        $message = __('This will also syncronize your settings from webshipper.');
        $url = $this->urlInterface->getUrl('*/*/*/section/carriers/');
        $url = '<a href="' . $url . '#carriers_webshipper_configuration_token">' . __('Configuration Token') . '</a>';
        $message .= '<br>' . __('Click here to see your Configuration Token: %1', $url);
        return $message;
    }
}
