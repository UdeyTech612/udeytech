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
 * Class Mapping
 * @package Udeytech\FacebookPixel\Model\Config
 */
class Mapping extends Value
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
            ['pattern' => '/^[\p{L}\p{N}_,;:!&#\=\+\*\$\?\|\'\.\-]*$/iu']
        );

        if (!$validator) {
            $message = __(
                'Product Parameters to Attributes Mapping is not valid.'
            );
            throw new LocalizedException($message);
        }

        return $this;
    }
}
