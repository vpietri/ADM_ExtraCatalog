<?php

class ADM_ExtraCatalog_Model_Resource_Indicator_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('adm_extracatalog/indicator');
        return parent::_construct();
    }

    public function toOptionsArray(){
        $tab=array();
        $ressource = $this->toArray();
        foreach($ressource['items'] as $item){
            $tab[$item['id']]=$item['name'];
        }
        return $tab;
    }
}