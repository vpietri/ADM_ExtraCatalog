<?php

class ADM_ExtraCatalog_Model_Indicator extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('adm_extracatalog/indicator');
        return parent::_construct();
    }

    /**
     * Clearing object's data
     *
     * @return Mage_Catalog_Model_Product_Option
     */
    protected function _clearData()
    {
        $this->_data = array();
        $this->_values = array();
        return $this;
    }
}

