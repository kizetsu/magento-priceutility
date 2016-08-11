<?php
/**
 *
 * @author  	Ralph Dittrich <kizetsu.rd@googlemail.com>
 * @name    	Kizetsu_Priceutility_Model_Observer
 * @package 	Kizetsu_Priceutility
 * @category 	Kizetsu
 * @github  	Kizetsu http://github.com/kizetsu/magento-priceutility
 *
 */

class Kizetsu_Priceutility_Model_Observer extends Mage_Core_Model_Abstract {

    public function update_tierprices($observer)
    {
        Mage::dispatchEvent('update_tierprices_before', array('cart'=>$this));

        /* debug parameter */
        $debug = Mage::app()->getRequest()->getParam('debug');
        if(!$debug || $debug != 'true') {
            $debug = false;
        }

        /** @var Mage_Checkout_Model_Cart $cart */
        $cart = $observer->getEvent()->getCart();

        /** @var Kizetsu_Priceutility_Helper */
        $priceHelper = Mage::helper('kizetsu_priceutility');

        /* var to collect errors */
        $errors = array();

        if($debug) {
            Mage::log('items in cart: '.$cart->getItemsCount());
        }

        /** @var array */
        $productPriceGroup = $priceHelper->getProductGroupsFromCart($cart);

        /* check if empty to prevent warnings if cart is cleared */
        /* this is important because $productPriceGroup is empty if user set the last product to zero */
        if(!empty($productPriceGroup)) {
            /*  $productGroup[$type] */
            foreach ($productPriceGroup as $types) {
                /*  $types[$type] => $types[$sku] */
                foreach ($types as $type => $group) {
                    /*  $group[$sku] => $group[$itemKey] */
                    foreach ($group['items'] as $key => $item) {
                        if ($debug) {
                            Mage::log('parent sku:    '.$group['product']);
                            if ($type == 'bundle') {
                                Mage::log('item sku:      '.$item->getSku() . ' - ' . $item->getId());
                            } else {
                                Mage::log('item sku:      '.$item->getSku());
                            }
                        }

                        /* get final price for item and group quantity */
                        if($group['has_options']) {
                            $price = $item->getProduct()->getFinalPrice($group['qty'][$group['options'][$item->getId()]]);
                            if ($debug) {
                                Mage::log('quantity:      '.$group['qty'][$group['options'][$item->getId()]]);
                            }
                        } else {
                            $price = $item->getProduct()->getFinalPrice($group['qty']);
                            if ($debug) {
                                Mage::log('quantity:      '.$group['qty']);
                            }
                        }
                        if ($price != null && $price != 0) {
                            if ($debug) {
                                Mage::log('price:         '.$price);
                            }
                            try {
                                /* set custom price */
                                $item->setCustomPrice($price);
                                $item->setOriginalCustomPrice($price);
                                $item->getProduct()->setIsSuperMode(true);
                            } catch (Exception $e) {
                                $errors[] = $item->getId() . ' => ' . $e->getMessage();
                            }
                        }
                    }
                }
            }
        }

        /* write errors to system.log */
        if(!empty($errors)) {
            foreach ($errors as $error) {
                /* Log as Error (log state 3) */
                Mage::log('Kizetsu_Priceutility_Model_Observer :: '.$error,3);
            }
        }

        Mage::dispatchEvent('update_tierprices_after', array('cart'=>$this));

        return $this;
    }

}