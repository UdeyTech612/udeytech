<?php
/**
 * Copyright (c) 2021. Udeytech Technologies All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Udeytech\FacebookPixel\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\ModuleListInterface;

/**
 * Class Data
 * @package Udeytech\FacebookPixel\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var ModuleListInterface
     */
    public $moduleList;

    /**
     * Constructor
     *
     * @param Context $context
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList
    )
    {
        $this->moduleList = $moduleList;

        parent::__construct($context);
    }

    /**
     * Returns extension version.
     *
     * @return string
     */
    public function getExtensionVersion()
    {
        $moduleCode = 'Udeytech_FacebookPixel';
        $moduleInfo = $this->moduleList->getOne($moduleCode);
        return $moduleInfo['setup_version'];
    }
}
