<?php
/**
 * Copyright (c) 2021. Udeytech Technologies All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Udeytech\FacebookPixel\Block;

use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\ProductFactory;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Config;
use Udeytech\FacebookPixel\Model\Customer\Context;

/**
 * Class Code
 * @package Udeytech\FacebookPixel\Block
 */
class Code extends Template
{
    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var Registry
     */
    public $coreRegistry;

    /**
     * @var Data
     */
    public $catalogHelper;

    /**
     * @var Magento\Framework\App\Http\Context
     */
    public $httpContext;

    /**
     * @var ProductFactory
     */
    public $productFactory;

    /**
     * @var Session
     */
    public $checkoutSession;

    /**
     * @var CustomerFactory
     */
    public $customerFactory;

    /**
     * @var AddressFactory
     */
    public $addressFactory;

    /**
     * @var RegionFactory
     */
    public $regionFactory;

    /**
     * Tax config model
     *
     * @var Config
     */
    public $taxConfig;

    /**
     * Tax display flag
     *
     * @var null|int
     */
    public $taxDisplayFlag = null;

    /**
     * Tax catalog flag
     *
     * @var null|int
     */
    public $taxCatalogFlag = null;

    /**
     * Store object
     *
     * @var null|Store
     */
    public $store = null;

    /**
     * Store ID
     *
     * @var null|int
     */
    public $storeId = null;

    /**
     * Base currency code
     *
     * @var null|string
     */
    public $baseCurrencyCode = null;

    /**
     * Current currency code
     *
     * @var null|string
     */
    public $currentCurrencyCode = null;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param Registry $coreRegistry
     * @param Data $catalogHelper
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param ProductFactory $productFactory
     * @param Session $checkoutSession
     * @param CustomerFactory $customerFactory
     * @param AddressFactory $addressFactory
     * @param RegionFactory $regionFactory
     * @param Config $taxConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Registry $coreRegistry,
        Data $catalogHelper,
        \Magento\Framework\App\Http\Context $httpContext,
        ProductFactory $productFactory,
        Session $checkoutSession,
        CustomerFactory $customerFactory,
        AddressFactory $addressFactory,
        RegionFactory $regionFactory,
        Config $taxConfig,
        array $data = []
    )
    {
        $this->scopeConfig = $context->getScopeConfig();
        $this->storeManager = $context->getStoreManager();
        $this->coreRegistry = $coreRegistry;
        $this->catalogHelper = $catalogHelper;
        $this->httpContext = $httpContext;
        $this->productFactory = $productFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerFactory = $customerFactory;
        $this->addressFactory = $addressFactory;
        $this->regionFactory = $regionFactory;
        $this->taxConfig = $taxConfig;

        parent::__construct($context, $data);
    }

    /**
     * Used in .phtml file and returns array of data.
     *
     * @return array
     */
    public function getFacebookPixelData()
    {
        $data = [];

        $data['id'] = $this->getConfig(
            'udeytech_facebookpixel/general/pixel_id',
            $this->getStoreId()
        );

        $data['full_action_name'] = $this->getRequest()->getFullActionName();

        return $data;
    }

    /**
     * Based on provided configuration path returns configuration value.
     *
     * @param string $configPath
     * @param string|int $scope
     * @return string
     */
    public function getConfig($configPath, $scope = 'default')
    {
        return $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE,
            $scope
        );
    }

    /**
     * Returns Store Id
     *
     * @return int
     */
    public function getStoreId()
    {
        if ($this->storeId === null) {
            $this->storeId = $this->getStore()->getId();
        }

        return $this->storeId;
    }

    /**
     * Returns store object
     *
     * @return Store
     */
    public function getStore()
    {
        if ($this->store === null) {
            $this->store = $this->storeManager->getStore();
        }

        return $this->store;
    }

    /**
     * Returns custmer data needed for advanced matching.
     *
     * @return array
     */
    public function getCustomerData()
    {
        $data = [];
        $customerData = [];
        $address = null;
        $addressId = 0;
        $customer = null;
        $customerId = 0;

        $isLoggedIn = $this->httpContext->getValue(
            \Magento\Customer\Model\Context::CONTEXT_AUTH
        );

        if ($isLoggedIn) {
            $customerId = $this->httpContext->getValue(
                Context::CONTEXT_CUSTOMER_ID
            );
        }

        if (!$customerId) {
            return null;
        }

        $customer = $this->getCustomerById($customerId);

        $data['db'] = $this->stripNonNumeric(
            $this->datetimeToDate($customer->getDob())
        );
        $data['em'] = $customer->getEmail();
        $data['fn'] = $customer->getFirstname();

        // 1 male, 2 female, 3 not specified
        $ge = $customer->getGender();

        if ($ge == 1) {
            $data['ge'] = 'm';
        }

        if ($ge == 2) {
            $data['ge'] = 'f';
        }

        $data['ln'] = $customer->getLastname();

        // Get billing address
        $addressId = $customer->getDefaultBilling();

        // If there is no billing address get shipping address
        if (!$addressId) {
            $addressId = $customer->getDefaultShipping();
        }

        if ($addressId) {
            $address = $this->getCustomerAddressById($addressId);

            $data['ct'] = $address->getCity();
            $data['country'] = $address->getCountry();
            $data['ph'] = $this->stripNonNumeric($address->getTelephone());
            $data['st'] = $this->getRegionCodeOrNameById(
                $address->getRegionId()
            );
            $data['zp'] = $address->getPostcode();
        }

        foreach ($data as $key => $value) {
            if ($value) {
                $customerData[$key] = $this->formatData($value);
            }
        }

        return $customerData;
    }

    /**
     * Returns customer object loaded by customer ID.
     *
     * @param int $id
     * @return Customer
     */
    public function getCustomerById($id)
    {
        return $this->customerFactory->create()->load($id);
    }

    /**
     * Strips all non numeric characters.
     *
     * @param string $str
     * @return string
     */
    public function stripNonNumeric($str)
    {
        return preg_replace('/[^\p{N}]+/', '', $str);
    }

    /**
     * Converts datetime string to date string.
     *
     * @param string $datetimeString
     * @return string
     */
    public function datetimeToDate($datetimeString)
    {
        $date = '';

        if ($datetimeString) {
            $date = date('Y-m-d', strtotime($datetimeString));
        }

        return $date;
    }

    /**
     * Returns address object loaded by address ID.
     *
     * @param int $id
     * @return Address
     */
    public function getCustomerAddressById($id)
    {
        return $this->addressFactory->create()->load($id);
    }

    /**
     * Returns region 2 letter code or name based on provided region ID.
     *
     * @param int $id
     * @return string
     */
    public function getRegionCodeOrNameById($id)
    {
        if (!$id) {
            return '';
        }

        $region = $this->getRegionById($id);
        $code = $region->getCode();
        $name = $region->getDefaultName();

        // FB wants only 2 letter codes otherwise name
        if ($this->stringLength($code) == 2) {
            return $code;
        } else {
            return $name;
        }
    }

    /**
     * Returns region object loaded by region ID.
     *
     * @param int $id
     * @return Region
     */
    public function getRegionById($id)
    {
        return $this->regionFactory->create()->load($id);
    }

    /**
     * Returns string length.
     *
     * @param string $str
     * @return int
     */
    public function stringLength($str)
    {
        if (function_exists('mb_strlen')) {
            $length = mb_strlen($str, 'UTF-8');
        } else {
            $length = strlen($str);
        }

        return (int)$length;
    }

    /**
     * Strips all spaces and converts to lowercase.
     *
     * @param string $str
     * @return string
     */
    public function formatData($str)
    {
        return $this->escapeSingleQuotes(
            $this->convertToLowercase(
                preg_replace('/\s+/', '', $str)
            )
        );
    }

    /**
     * Add slashes to string and prepares string for javascript.
     *
     * @param string $str
     * @return string
     */
    public function escapeSingleQuotes($str)
    {
        $removedNewLines = str_replace(["\n", "\r\n", "\r"], '', $str);
        return str_replace("'", "\'", $removedNewLines);
    }

    /**
     * Converts string to lowercase.
     *
     * @param string $s
     * @return string
     */
    public function convertToLowercase($s)
    {
        if (function_exists('mb_strtolower')) {
            $str = mb_strtolower($s, 'UTF-8');
        } else {
            $str = strtolower($s);
        }

        return $str;
    }

    /**
     * Returns product data needed for dynamic ads tracking.
     *
     * @return array
     */
    public function getProductData()
    {
        $p = $this->coreRegistry->registry('current_product');

        $data = [];

        $data['content_name'] = $this->escapeSingleQuotes($p->getName());
        $data['content_ids'] = $this->escapeSingleQuotes($p->getSku());
        $data['content_type'] = 'product';

        // Custom Parameters
        $attributeValue = '';
        $map = $this->getParameterToAttributeMap();

        foreach ($map as $parameter => $attribute) {
            $attributeValue = $this->getAttributeValue($p, $attribute);

            if ($attributeValue) {
                $data[$parameter] = $this->escapeSingleQuotes($attributeValue);
            }
        }

        $data['currency'] = $this->getCurrentCurrencyCode();
        // 'value' must be last element of the array
        // so everytihng is automatic in phtml file
        $data['value'] = $this->formatPrice(
            $this->getProductPrice($p)
        );

        return $data;
    }

    /**
     * Returns array map from parameter mapping configuration.
     * Default is 'product' but you can specify for mapping of order items.
     *
     * @return array
     */
    public function getParameterToAttributeMap($type = 'product')
    {
        $map = [];

        $data = $this->getConfig(
            'udeytech_facebookpixel/general/mapping_' . $type,
            $this->getStoreId()
        );

        if (!$data) {
            return $map;
        }

        $pairs = explode('|', $data);

        foreach ($pairs as $pair) {
            $pairArray = explode('=', $pair);

            if (isset($pairArray[0]) && isset($pairArray[1])) {
                $cleanedParameter = trim($pairArray[0]);
                $cleanedAttribute = trim($pairArray[1]);

                if ($cleanedParameter && $cleanedAttribute) {
                    $map[$cleanedParameter] = $cleanedAttribute;
                }
            }
        }

        return $map;
    }

    /**
     * Returns product attribute value or values. Third param is optional, if
     * set to false it will return array of values for multiselect attributes.
     *
     * @param Product $product
     * @param string $attrCode
     * @param bool $toString
     * @return string
     */
    public function getAttributeValue($product, $attrCode, $toString = true)
    {
        $attrValue = '';

        if ($product->getData($attrCode)) {
            $attrValue = $product->getAttributeText($attrCode);

            if (!$attrValue) {
                $attrValue = $product->getData($attrCode);
            }
        }

        if ($toString && is_array($attrValue)) {
            $attrValue = implode(', ', $attrValue);
        }

        return $attrValue;
    }

    /**
     * Returns current currency code
     * (3 letter currency code like USD, GBP, EUR, etc.)
     *
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        if ($this->currentCurrencyCode === null) {
            $this->currentCurrencyCode = strtoupper(
                $this->getStore()->getCurrentCurrencyCode()
            );
        }

        return $this->currentCurrencyCode;
    }

    /**
     * Returns formated price.
     *
     * @param string $price
     * @param string $currencyCode
     * @return string
     */
    public function formatPrice($price, $currencyCode = '')
    {
        $formatedPrice = number_format($price, 2, '.', '');

        if ($currencyCode) {
            return $formatedPrice . ' ' . $currencyCode;
        } else {
            return $formatedPrice;
        }
    }

    /**
     * Returns product price.
     *
     * @param Product $product
     * @return string
     */
    public function getProductPrice($product)
    {
        $price = 0.0;

        switch ($product->getTypeId()) {
            case 'bundle':
                $price = $this->getBundleProductPrice($product);
                break;
            case 'configurable':
                $price = $this->getConfigurableProductPrice($product);
                break;
            case 'grouped':
                $price = $this->getGroupedProductPrice($product);
                break;
            default:
                $price = $this->getFinalPrice($product);
        }

        return $price;
    }

    /**
     * Returns bundle product price.
     *
     * @param Product $product
     * @return string
     */
    public function getBundleProductPrice($product)
    {
        $includeTax = (bool)$this->getDisplayTaxFlag();

        return $this->getFinalPrice(
            $product,
            $product->getPriceModel()->getTotalPrices(
                $product,
                'min',
                $includeTax,
                1
            )
        );
    }

    /**
     * Returns flag based on "Stores > Cofiguration > Sales > Tax
     * > Price Display Settings > Display Product Prices In Catalog"
     * Returns 0 or 1 instead of 1, 2, 3.
     *
     * @return int
     */
    public function getDisplayTaxFlag()
    {
        if ($this->taxDisplayFlag === null) {
            // Tax Display
            // 1 - excluding tax
            // 2 - including tax
            // 3 - including and excluding tax
            $flag = $this->taxConfig->getPriceDisplayType($this->getStoreId());

            // 0 means price excluding tax, 1 means price including tax
            if ($flag == 1) {
                $this->taxDisplayFlag = 0;
            } else {
                $this->taxDisplayFlag = 1;
            }
        }

        return $this->taxDisplayFlag;
    }

    /**
     * Returns final price.
     *
     * @param Product $product
     * @param string $price
     * @return string
     */
    public function getFinalPrice($product, $price = null)
    {
        if ($price === null) {
            $price = $product->getFinalPrice();
        }

        if ($price === null) {
            $price = $product->getData('special_price');
        }

        $productType = $product->getTypeId();

        // 1. Convert to current currency if needed

        // Convert price if base and current currency are not the same
        // Except for configurable products they already have currency converted
        if (($this->getBaseCurrencyCode() !== $this->getCurrentCurrencyCode())
            && $productType != 'configurable'
        ) {
            // Convert to from base currency to current currency
            $price = $this->getStore()->getBaseCurrency()
                ->convert($price, $this->getCurrentCurrencyCode());
        }

        // 2. Apply tax if needed

        // Simple, Virtual, Downloadable products price is without tax
        // Grouped products have associated products without tax
        // Bundle products price already have tax included/excluded
        // Configurable products price already have tax included/excluded
        if ($productType != 'configurable' && $productType != 'bundle') {
            // If display tax flag is on and catalog tax flag is off
            if ($this->getDisplayTaxFlag() && !$this->getCatalogTaxFlag()) {
                $price = $this->catalogHelper->getTaxPrice(
                    $product,
                    $price,
                    true,
                    null,
                    null,
                    null,
                    $this->getStoreId(),
                    false,
                    false
                );
            }
        }

        // Case when catalog prices are with tax but display tax is set to
        // to exclude tax. Applies for all products except for bundle
        if ($productType != 'bundle') {
            // If display tax flag is off and catalog tax flag is on
            if (!$this->getDisplayTaxFlag() && $this->getCatalogTaxFlag()) {
                $price = $this->catalogHelper->getTaxPrice(
                    $product,
                    $price,
                    false,
                    null,
                    null,
                    null,
                    $this->getStoreId(),
                    true,
                    false
                );
            }
        }

        return $price;
    }

    /**
     * Returns base currency code
     * (3 letter currency code like USD, GBP, EUR, etc.)
     *
     * @return string
     */
    public function getBaseCurrencyCode()
    {
        if ($this->baseCurrencyCode === null) {
            $this->baseCurrencyCode = strtoupper(
                $this->getStore()->getBaseCurrencyCode()
            );
        }

        return $this->baseCurrencyCode;
    }

    /**
     * Returns Stores > Cofiguration > Sales > Tax > Calculation Settings
     * > Catalog Prices configuration value
     *
     * @return int
     */
    public function getCatalogTaxFlag()
    {
        // Are catalog product prices with tax included or excluded?
        if ($this->taxCatalogFlag === null) {
            $this->taxCatalogFlag = (int)$this->getConfig(
                'tax/calculation/price_includes_tax',
                $this->getStoreId()
            );
        }

        // 0 means excluded, 1 means included
        return $this->taxCatalogFlag;
    }

    /**
     * Returns configurable product price.
     *
     * @param Product $product
     * @return string
     */
    public function getConfigurableProductPrice($product)
    {
        if ($product->getFinalPrice() === 0) {
            $simpleCollection = $product->getTypeInstance()
                ->getUsedProducts($product);

            foreach ($simpleCollection as $simpleProduct) {
                if ($simpleProduct->getPrice() > 0) {
                    return $this->getFinalPrice($simpleProduct);
                }
            }
        }

        return $this->getFinalPrice($product);
    }

    /**
     * Returns grouped product price.
     *
     * @param Product $product
     * @return string
     */
    public function getGroupedProductPrice($product)
    {
        $assocProducts = $product->getTypeInstance(true)
            ->getAssociatedProductCollection($product)
            ->addAttributeToSelect('price')
            ->addAttributeToSelect('tax_class_id')
            ->addAttributeToSelect('tax_percent');

        $minPrice = INF;
        foreach ($assocProducts as $assocProduct) {
            $minPrice = min($minPrice, $this->getFinalPrice($assocProduct));
        }

        return $minPrice;
    }

    /**
     * Returns data needed for purchase tracking.
     *
     * @return array|null
     */
    public function getOrderData()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $orderId = $order->getIncrementId();

        if ($orderId) {
            $data = [];
            $products = [];
            $items = $order->getAllVisibleItems();
            $i = 0;
            $currency = $order->getOrderCurrencyCode();
            $product = null;
            $sku = '';
            $storeId = '';

            foreach ($items as $item) {
                $sku = $item->getSku();
                $products[$i]['id'] = $this->escapeSingleQuotes($sku);
                $products[$i]['quantity'] = round($item->getQtyOrdered(), 0);

                // Custom Parameters
                $attributeValue = '';
                $map = $this->getParameterToAttributeMap('order');
                // Load Product in order to get product attributes
                $storeId = $item->getStoreId();
                $product = $this->getProductBySku($sku, $storeId);
                // If sku cannot be found fall back
                if (null === $product) {
                    $product = $this->getProductById(
                        $item->getProductId(),
                        $storeId
                    );
                }

                foreach ($map as $parameter => $attribute) {
                    $attributeValue = $this->getAttributeValue(
                        $product,
                        $attribute
                    );

                    if ($attributeValue) {
                        $products[$i][$parameter] = $this
                            ->escapeSingleQuotes($attributeValue);
                    }
                }

                $price = $item->getPrice();
                // 'item_price' must be last element of the array
                $products[$i]['item_price'] = $this->formatPrice($price);

                $i++;
            }

            $data['contents'] = $products;
            $data['content_type'] = 'product';
            $data['currency'] = $currency;
            $data['value'] = $this->formatPrice($order->getGrandTotal());

            return $data;
        } else {
            return null;
        }
    }

    /**
     * Returns product object loaded by SKU.
     * Used in getOrderData() method to retreive product attributes.
     *
     * @param string $sku
     * @param int|string $storeId
     * @return Product
     */
    public function getProductBySku($sku, $storeId = '')
    {
        if (!$storeId) {
            $storeId = $this->getStoreId();
        }

        $product = $this->productFactory->create();
        return $product->setStoreId($storeId)->load($product->getIdBySku($sku));
    }

    /**
     * Returns product object loaded by ID.
     * Used in getOrderData() method to retreive product attributes.
     *
     * @param int $id
     * @param int|string $storeId
     * @return Product
     */
    public function getProductById($id, $storeId = '')
    {
        if (!$storeId) {
            $storeId = $this->getStoreId();
        }

        return $this->productFactory->create()->setStoreId($storeId)->load($id);
    }
}
