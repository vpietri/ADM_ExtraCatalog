<?php


class ADM_ExtraCatalog_Model_Adminhtml_System_Config_Source_Indicator_Sort
{
    /**
     * Retrieve Options Array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            'asc'  => Mage::helper('adm_extracatalog')->__('Ascending order'),
            'desc' => Mage::helper('adm_extracatalog')->__('Descending order')
        );
    }
}
