<?php

namespace Wexo\Webshipper\Model\Config;

use Magento\Framework\UrlInterface;

class LinkToWebshipperSettings implements \Magento\Config\Model\Config\CommentInterface
{
    protected $urlInterface;

    public function __construct(
        UrlInterface $urlInterface
    ) {
        $this->urlInterface = $urlInterface;
    }

    public function getCommentText($elementValue)
    {
        $url = $this->urlInterface->getUrl('*/*/*/section/webshipper');
        $url = '<a href="' . $url . '">'.__('Webshipper Order Settings').'</a>';
        $message = __('For Order Export settings click here: %1', $url);
        return $message;
    }
}