<?php
/**
 * Copyright (c) 2021. Udeytech Technologies All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Udeytech\FacebookPixel\Block\Adminhtml;

use Magento\Backend\Block\AbstractBlock;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Udeytech\FacebookPixel\Helper\Data;

/**
 * Class About
 * @package Udeytech\FacebookPixel\Block\Adminhtml
 */
class About extends AbstractBlock implements
    RendererInterface
{
    /**
     * @var Data
     */
    public $helper;

    /**
     * Constructor
     *
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Retrieve element HTML markup.
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element = null;
        $version = $this->helper->getExtensionVersion();
        $logopath = 'https://mlvg4tboqvjn.i.optimole.com/CHFcVu4-diizB1IS/w:160/h:30/q:auto/https://udeytech.com/wp-content/uploads/eCommerce-web-development-company-logo-scaled.png';
        $html = <<<HTML
<div style="background: url('$logopath') no-repeat scroll 15px 15px #f8f8f8; 
border:1px solid #ccc; min-height:100px; margin:5px 0; 
padding:15px 15px 15px 140px;">
<p>
<strong>Udeytech Facebook Pixel Extension v$version</strong><br /><br />
Adds Facebook Pixel with Dynamic Ads code on appropriate pages. Passes W3C 
validation. Easy to install and use.
</p>
</div>
HTML;
        return $html;
    }
}
