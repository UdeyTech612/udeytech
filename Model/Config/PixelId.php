<?php
/**
 * Copyright (c) 2021. Udeytech Technologies All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Udeytech\FacebookPixel\Model\Config;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;
use Zend_Validate;

/**
 * Class PixelId
 * @package Udeytech\FacebookPixel\Model\Config
 */
class PixelId extends Value
{
    /**
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $validator = Zend_Validate::is(
            $value,
            'Regex',
            ['pattern' => '/^[0-9]+$/']
        );

        if (!$validator) {
            $message = __('Please correct Facebook Pixel ID: "%1".', $value);
            throw new LocalizedException($message);
        }

        return $this;
    }
}
