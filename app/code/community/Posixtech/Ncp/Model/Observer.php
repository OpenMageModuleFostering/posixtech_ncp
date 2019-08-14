<?php
/**
 *
 * Posixtech Ltd.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@posixtech.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * You can edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future.
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This package designed for Magento COMMUNITY edition version 1.5.0.0 to all upper version.
 * Posixtech Ltd. does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * Posixtech Ltd. does not provide extension support in case of
 * incorrect edition usage.
 * =================================================================
 *
 * @category   Posixtech
 * @package    Ncp
 * @copyright  Copyright (c) 2013 Posixtech Ltd. (http://www.posixtech.com)
 * @license    http://www.posixtech.com/POSIXTECH_LTD_LICENSE.txt
 *
 */
class Posixtech_Ncp_Model_Observer {
    /**
     * Add the gift product to cart
     * @param Varien_Event_Observer $obs
     */
	public function addAditionalProduct(Varien_Event_Observer $obs)
    {
        /**
         * Check the validity of this customer for the gift product validity
         */
    	if(!$this->_isValidForGift()){
        	return;
        }
        
        /**
			Check the module configuration for enable status, product id
         */
        $isActive = Mage::getStoreConfig('posixtech_options/posixtech_ncp/active');
        $configProductID = Mage::getStoreConfig('posixtech_options/posixtech_ncp/product_id');
        
        if($isActive) {
            $item = $obs->getProduct();
            $requestProductID = $item->getId();
            if($configProductID == $requestProductID) {
                return;
            } else {
                $cartObj = Mage::getSingleton('checkout/cart');
                $duplicateProductCounter = 0;
                foreach ($cartObj->getItems() as $cartItem) {
                    if($configProductID==$cartItem->getProductId()) {
                        $duplicateProductCounter++;
                    }
                }

                if($duplicateProductCounter>=1) {
                    return;
                }
                
                $productModel=Mage::getModel('catalog/product');
                $productObj=$productModel->load($configProductID);
                $params = array();
                if($productObj->getTypeId() == 'simple') {
                    $params['qty'] = 1;
                } else {
                    $links = Mage::getModel('downloadable/product_type')->getLinks($productObj);
                    $linkId = 0;
                    foreach ($links as $link) {
                        $linkId = $link->getId();
                    }

                    $params['product'] = $configProductID;
                    $params['qty'] = 1;
                    $params['links'] = array($linkId);
                }
                
                $request = new Varien_Object();
                $request->setData($params);
                
                $cart = Mage::getModel('checkout/cart');
                $cart->addProduct($productObj, $request);
                $cart->save();
                
                Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                return;
            }
        }
    }
    
    
    /**
     * Modify the gift product price as a $0.00 (if the product price is not Zero)
     * Assign the free gift message as a product option
     * @param Varien_Event_Observer $obs
     */
    public function modifyPrice(Varien_Event_Observer $obs)
    {
    /**
         * Check the validity of this customer for the gift product validity
         */
    	if(!$this->_isValidForGift()){
        	return;
        }
        
        $isActive = Mage::getStoreConfig('posixtech_options/posixtech_ncp/active');
        $configProductID = Mage::getStoreConfig('posixtech_options/posixtech_ncp/product_id');
        $configProductTitle = Mage::getStoreConfig('posixtech_options/posixtech_ncp/product_title');
        
        if($isActive) {
            $item = $obs->getQuoteItem();
            $requestProductID = $item->getProductId();
            if($configProductID == $requestProductID) {
                $lastAddedProduct = Mage::getSingleton('checkout/session')->getLastAddedProductId();
                if($lastAddedProduct) {
                    if($lastAddedProduct != $configProductID) {
                        
                        $infoRequest = unserialize($item->getOptionByCode('info_buyRequest')->getValue());
                        $item = ( $item->getParentItem() ? $item->getParentItem() : $item );
                        $price = 0;
                        $item->setCustomPrice($price);
                        $item->setQty(1);
                        $item->setOriginalCustomPrice($price);
                        $infoRequest['option'] = serialize(array(
                            'label' => 'Free Gift',
                            'value' => $configProductTitle
                        ));						
                        $item->getOptionByCode('info_buyRequest')->setValue(serialize($infoRequest));
                        $_options = array(
                            1 => array(
                                'label' => 'Free Gift',
                                'value' => $configProductTitle,
                                'print_value' => $configProductTitle,
                                'option_type' => 'text',
                                'custom_view' => true
                            )
                        );
                        $options  = array(
                            'code' => 'additional_options',
                            'value' => serialize($_options)
                        );
                        $item->addOption($options);

                        $item->getProduct()->setIsSuperMode(true);
                    }
                }
            }
        }
    }
    
    /**
     * This function will ensure total number of gift product in cart
     * It should be always 1
     * So, customer will not able to update the qty of the gift product in cart
     * @param Varien_Event_Observer $obs
     */
    public function checkoutCartProductUpdateAfter(Varien_Event_Observer $obs)
    {
    	/**
    	 * Check the validity of this customer for the gift product validity
    	 */
    	if(!$this->_isValidForGift()){
    		return;
    	}
    	 
    	$isActive = Mage::getStoreConfig('posixtech_options/posixtech_ncp/active');
        $configProductID = Mage::getStoreConfig('posixtech_options/posixtech_ncp/product_id');
        
        if($isActive) {
            $cart    = $obs->getCart();
            foreach ($cart->getItems() as $cartItem) {
                if($configProductID==$cartItem->getProductId()) {
                    if($cartItem->getPrice() == '0.0000') {
                        $price = 0;
                        $cartItem->setCustomPrice($price);
                        $cartItem->setQty(1);
                        $cartItem->setOriginalCustomPrice($price);
                    }
                }
            }
        }
    }
    /**
     * If a gust customer(not registred Yet) add any number of products AND login into the checkout page,
     * the gift product will be added to his cart automatically
     * 
     * @param Varien_Event_Observer $obs
     */
    public function addAdditionalProductAfterLogin(Varien_Event_Observer $obs)
    {
    /**
         * Check the validity of this customer for the gift product validity
         */
    	if(!$this->_isValidForGift()){
        	return;
        }
        
        $isActive = Mage::getStoreConfig('posixtech_options/posixtech_ncp/active');
        $configProductID = Mage::getStoreConfig('posixtech_options/posixtech_ncp/product_id');
        
        if($isActive) {
            $cartObj = Mage::getSingleton('checkout/cart');
            if($cartObj->getItemsCount() == 0) {
                return;
            }
            
            $duplicateProductCounter = 0;
            foreach ($cartObj->getItems() as $cartItem) {
                if($configProductID==$cartItem->getProductId()) {
                    $duplicateProductCounter++;
                }
            }
            
            if($duplicateProductCounter>=1) {
                return;
            }
            
            $quoteObj = Mage::getSingleton('checkout/session')->getQuote();
            $productModel=Mage::getModel('catalog/product');
            $productObj=$productModel->load($configProductID);
            $params = array();
            if($productObj->getTypeId() == 'simple') {
                $params['qty'] = 1;
            } else {
                $links = Mage::getModel('downloadable/product_type')->getLinks($productObj);
                $linkId = 0;
                foreach ($links as $link) {
                    $linkId = $link->getId();
                }

                $params['product'] = $configProductID;
                $params['qty'] = 1;
                $params['links'] = array($linkId);
            }

            $request = new Varien_Object();
            $request->setData($params);
            
	    	$quoteObj->addProduct($productObj , $request);
            $quoteObj->save();
            
            $cartItems = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();
            $duplicateProductCounter = 0;
            foreach ($cartItems as $cartItem) {
                $cartItem = ( $cartItem->getParentItem() ? $cartItem->getParentItem() : $cartItem );
                if($configProductID==$cartItem->getProductId()) {
                    $configProductTitle = Mage::getStoreConfig('posixtech_options/posixtech_ncp/product_title');
                    $infoRequest = unserialize($cartItem->getOptionByCode('info_buyRequest')->getValue());
                    $price = 0;
                    $cartItem->setCustomPrice($price);
                    $cartItem->setQty(1);
                    $cartItem->setOriginalCustomPrice($price);
                    $infoRequest['option'] = serialize(array(
                        'label' => 'Free Gift',
                        'value' => $configProductTitle
                    ));						
                    $cartItem->getOptionByCode('info_buyRequest')->setValue(serialize($infoRequest));
                    $_options = array(
                        1 => array(
                            'label' => 'Free Gift',
                            'value' => $configProductTitle,
                            'print_value' => $configProductTitle,
                            'option_type' => 'text',
                            'custom_view' => true
                        )
                    );
                    $options  = array(
                        'code' => 'additional_options',
                        'value' => serialize($_options)
                    );
                    $cartItem->addOption($options);
                }
            }
            
            return;
        }
    }
    
    /**
     * 
     * @return number
     */
    public function _isValidForGift() 
    {
    	$customer = Mage::getSingleton('customer/session')->getCustomer();
    	
    	/**
    	 * check for guest customer
    	*/
    	if(!$customer->getId()) {
    		return 0;
    	}
    	
    	/**
    	 *	Load order list by customer id
    	 */
    	$orders = Mage::getResourceModel('sales/order_collection')
			    	->addFieldToSelect('*')
			    	->addFieldToFilter('customer_id', $customer->getId());
    	
    	/**
    	 * if $order > 0 means, this customer already place at least one order
    	 * That means, this is not a new customer
    	*/
    	if(count($orders) > 0) {
    		return 0;
    	}
    	
    	return 1;
    }
}
