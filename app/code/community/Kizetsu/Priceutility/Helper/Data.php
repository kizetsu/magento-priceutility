<?php
/**
 *
 * @author      Ralph Dittrich <kizetsu.rd@googlemail.com>
 * @name        Kizetsu_Priceutility_Helper
 * @package     Kizetsu_Priceutility
 * @category    Kizetsu
 * @github      Kizetsu http://github.com/kizetsu/magento-priceutility
 *
 */

class Kizetsu_Priceutility_Helper extends Mage_Core_Helper_Abstract {
{

    /**
     * @var array
     */
    protected $_productPriceGroups = array();

    /**
     * @var array
     */
    protected $_optionAttributes = null;

    /**
     * check if item is type configurable
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return bool
     */
    protected function isConfigurable($item) {
        return ($item->getProduct()->getTypeId() == "configurable");
    }

    /**
     * check if item is type bundle
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return bool
     */
    protected function isBundle($item) {
        return ($item->getProduct()->getTypeId() == "bundle");
    }

    /**
     * check if item is type simple
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return bool
     */
    protected function isSimple($item) {
        return ($item->getProduct()->getTypeId() == "simple");
    }

    /**
     * load comarision options
     *
     * @return array
     */
    protected function getOptionAttributes() {
        /**
         * @TODO: get this values from backend
         */

        return $this->_optionAttributes = array(
            'Size',
        );
    }

    /**
     * get selected product options by given item
     *
     * @param $item
     * @return array
     */
    protected function getSelectedOptions($item) {
        $options = array();
        foreach ($item->getProduct()->getTypeInstance(true)->getSelectedAttributesInfo($item->getProduct()) as $option) {
            $options[] = array ($option['label'] => $option['value']);
        }
        return $options;
    }

    /**
     * get selected compare option by given product
     *
     * @param $item
     * @return array
     */
    public function getCompareOption($item) {
        foreach ($item->getProduct()->getTypeInstance(true)->getSelectedAttributesInfo($item->getProduct()) as $option) {
            if(in_array($option['label'],$this->_optionAttributes)) {
                return array('label' => $option['label'], 'value' => $option['value']);
            }
        }
        return null;
    }

    /**
     * get array with product groups
     *
     * @param Mage_Checkout_Model_Cart $cart
     * @return array
     */
    public function getProductGroupsFromCart($cart) {

        if ($this->_productPriceGroups != NULL) {
            return $this->_productPriceGroups;
        }

        $this->getOptionAttributes();

        if (!is_a($cart, 'Mage_Checkout_Model_Cart')) {
            /** @var Mage_Checkout_Model_Cart $cart */
            $cart = Mage::getSingleton('checkout/cart');
        }

        /* get empty var for product group array */
        $productPriceGroup = null;

        if ($cart->getItemsCount()) {
            /** @var Mage_Sales_Model_Quote_Item $item */
            foreach ($cart->getQuote()->getAllVisibleItems() as $item) {
                /* do nothing if item is promo */
                if($item->getIsPromo()) {
                    continue;
                }
                /* append product group array with item data */
                $productPriceGroup = $this->_getProductGroups(
                    $item,
                    Mage::getModel('catalog/product')->load($item->getProductId()),
                    $productPriceGroup
                );
            }
        }
        /* set and return product group array */
        $this->_productPriceGroups = $productPriceGroup;
        return $this->_productPriceGroups;
    }

    /**
     * get array with product groups for given item / product
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @param Mage_Catalog_Model_Product $product
     * @param array|null $productGroup
     * @return array
     */
    protected function _getProductGroups($item, $product, $productGroup = null) {
        /* prepare array if not given */
        if($productGroup == null) {
            $productGroup = array(
                'bundle' => array(),
                'simple' => array(),
                'configurable' => array(),
            );
        }

        /* prepare subarray for later use */
        $productGroupItems = array(
            'items'             => array(),
            'product'           => '',
            'has_options'   => false,
            'qty'               => array(),
            'qty_by_sku'        => array(),
            'options'           => array(),
        );

        /* get product type and sku */
        $type = $product->getTypeId();
        $sku = $product->getSku();

        /* get comparision key (bundle items always have the same sku) */
        if($this->isBundle($item)) {
            $itemKey = $item->getId();
        } else {
            $itemKey = $item->getSku();
        }
        $compareOption = null;
        if($this->isConfigurable($item)) {
            $compareOption = $this->getCompareOption($item);
        }

        /* if product is not "registered" in array set keys and values */
        if(!array_key_exists($sku, $productGroup[$type])) {
            $productGroup[$type][$sku]                          = $productGroupItems;
            $productGroup[$type][$sku]['product']               = $sku;
            if($this->isConfigurable($item) && ($compareOption != null)) {
                $productGroup[$type][$sku]['has_options'] = true;
                $productGroup[$type][$sku]['qty'][$compareOption['value']] = floatval($item->getQty());
            } else {
                $productGroup[$type][$sku]['qty']               = floatval($item->getQty());
            }
        } else {
            if($this->isConfigurable($item) && ($compareOption != null)) {
                if(!array_key_exists($compareOption['value'], $productGroup[$type][$sku]['qty'])) {
                    $productGroup[$type][$sku]['qty'][$compareOption['value']] = floatval($item->getQty());
                } else {
                    $productGroup[$type][$sku]['qty'][$compareOption['value']] += floatval($item->getQty());
                }
            } else {
                $productGroup[$type][$sku]['qty']               += floatval($item->getQty());
            }
        }

        /* if item is not "registered" in array set values */
        if(!array_key_exists($itemKey, $productGroup[$type][$sku]['items'])) {
            $productGroup[$type][$sku]['items'][$itemKey]       = $item;
            $productGroup[$type][$sku]['qty_by_sku'][$itemKey]  = floatval($item->getQty());
            if($productGroup[$type][$sku]['has_options']) {
                $productGroup[$type][$sku]['options'][$item->getId()] = $compareOption['value'];
            }
        }

        return $productGroup;
    }
}