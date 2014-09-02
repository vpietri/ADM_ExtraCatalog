<?php

class ADM_ExtraCatalog_Model_Adminhtml_System_Config_Source_Indicator_Formula
{
    /**
     * Retrieve Options Array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            ADM_ExtraCatalog_Helper_Indicator::FORMULA_CONVERS_ORDER_ONLY_WITH_STOCK  => Mage::helper('adm_extracatalog')->__('Conversion order rate * Stock level rate'),
            ADM_ExtraCatalog_Helper_Indicator::FORMULA_CONVERS_ORDER_PONDERATED_WITH_STOCK => Mage::helper('adm_extracatalog')->__('Conversion order rate (smooth) * Stock level rate'),
            ADM_ExtraCatalog_Helper_Indicator::FORMULA_CONVERS_ORDER_CART_WITH_STOCK  => Mage::helper('adm_extracatalog')->__('Conversion order and cart rate * Stock level rate'),
            ADM_ExtraCatalog_Helper_Indicator::FORMULA_SALES_WITH_STOCK => Mage::helper('adm_extracatalog')->__('Stats order and cart (without views) * Stock level rate'),
            ADM_ExtraCatalog_Helper_Indicator::FORMULA_ONLY_WITH_STOCK  => Mage::helper('adm_extracatalog')->__('Stock level rate'),
            ADM_ExtraCatalog_Helper_Indicator::FORMULA_CONVERS_ORDER_STOCK_ADDITIONAL  => Mage::helper('adm_extracatalog')->__('Conversion order, stock rate, news ponderated')
        );
    }
}
