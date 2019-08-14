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

class Posixtech_Ncp_Block_Sales_Order_Item_Renderer_Downloadable extends Mage_Downloadable_Block_Sales_Order_Item_Renderer_Downloadable
{
    /**
     * This function is responsible to display the gift option in front-end
     * Here the product option 'info_buyRequest' comes from Observer.php
     * @return multitype:
     */
	
	public function getItemOptions()
    {
        $infoBuyRequest = $this->getOrderItem()->getProductOptionByCode('info_buyRequest');
    		if(isset($infoBuyRequest['option']))
    		{
    			$option = unserialize($infoBuyRequest['option']);
    			$_options = array(
                    0=>array(
                        'label' => $option['label'],
                        'value' => $option['value'],
                        'print_value' => $option['value'],
                        'option_type' => 'text',
                        'custom_view' => true
                    )
				);
			    $options = array('additional_options'=>$_options);
			    
				$options = array_merge($options, $this->getOrderItem()->getProductOptions());
    		}else{
    			$options = $this->getOrderItem()->getProductOptions();
    		}
    		
    	$result = array();
        if ($options) {
            if (isset($options['options'])) {
                $result = array_merge($result, $options['options']);
            }
            if (isset($options['additional_options'])) {
                $result = array_merge($result, $options['additional_options']);
            }
            if (isset($options['attributes_info'])) {
                $result = array_merge($result, $options['attributes_info']);
            }
        }
        
        return $result;
    }
}
