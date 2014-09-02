<?php

class ADM_ExtraCatalog_Model_Resource_Indicator extends Mage_Core_Model_Resource_Db_Abstract
{
    const CATALOG_PRODUCT_INDICATOR_ATTRIBUTE_CODE = 'adm_indicator';

    const XML_PATH_INDICATOR_FORMULA  = 'catalog/product_enhanced/indicator_formula';

    const XML_PATH_INDICATOR_SORT  = 'catalog/product_enhanced/indicator_sort';

    protected $_product_type_id;

    protected $_product_indicator_attributes;

    protected $_reference_indicator_default=0;

    protected $_default_sort='asc';

    protected function _construct()
    {
        // Note that the orders_id refers to the key field in your database table.
        $this->_init('adm_extracatalog/indicator', 'oci_id');

        $this->_product_type_id = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();


        $defaultSort= Mage::getStoreConfig(self::XML_PATH_INDICATOR_SORT);

        //Tri par défaut
        if (!empty($defaultSort) and $defaultSort!='asc') {
            $this->_default_sort= 'desc';
        }

    }

    protected function getProductIndicatorAttribute()
    {
        $attributeCode= self::CATALOG_PRODUCT_INDICATOR_ATTRIBUTE_CODE;

        if (empty($this->_product_indicator_attributes[$attributeCode])) {
            //Reset attributes value
            $attributeModel = Mage::getResourceModel('catalog/eav_attribute')
                                    ->setEntityTypeId($this->_product_type_id);

            $attributeModel->load($attributeCode, 'attribute_code');
            $this->_product_indicator_attributes[$attributeCode]= $attributeModel;
        }

        return $this->_product_indicator_attributes[$attributeCode];
    }

    public function updateConversionRate()
    {
        $sql= "UPDATE adm_extracatalog_indicator
               SET conversion_rate=ROUND(ordered_count/views_count,2)
               WHERE views_count>0";

        $statement = $this->_getWriteAdapter()->query($sql);
    }


    public function computeIndicator()
    {


         $formula= Mage::getStoreConfig(self::XML_PATH_INDICATOR_FORMULA);

         switch ($formula) {
             case ADM_ExtraCatalog_Helper_Indicator::FORMULA_ONLY_WITH_STOCK:
                 $sql= "UPDATE adm_extracatalog_indicator
                 SET indicator= ROUND(stock_rate*100,0)";
             break;

             case ADM_ExtraCatalog_Helper_Indicator::FORMULA_CONVERS_ORDER_PONDERATED_WITH_STOCK:
                 //Average stock rate
                 $sql= "SELECT ROUND(AVG(stock_rate),2) FROM adm_extracatalog_indicator WHERE stock_rate>0;";
                 $avgStock= $this->_getReadAdapter()->fetchOne($sql);

                 //Average conversion rate
                 $sql= "SELECT ROUND(AVG(conversion_rate),2) FROM adm_extracatalog_indicator WHERE conversion_rate>0;";
                 $avgConversion= $this->_getReadAdapter()->fetchOne($sql);

                 //Average min conversion rate
                 $sql= "SELECT ROUND(AVG(conversion_rate),2) FROM adm_extracatalog_indicator WHERE conversion_rate>0 AND conversion_rate<".$avgConversion;
                 $avgMinConversion= $this->_getReadAdapter()->fetchOne($sql);


                 //New products AND dubbed products with a low convertion rate
                 // and a high stock rate have to be pushed up
                 $sql= "UPDATE adm_extracatalog_indicator
                 SET indicator= round( if(conversion_rate<=" . $avgMinConversion . " AND stock_rate>=". $avgStock
                 . ",". $avgConversion
                 . ",conversion_rate)"
                 . " *stock_rate*100000,0)";
             break;

             case ADM_ExtraCatalog_Helper_Indicator::FORMULA_CONVERS_ORDER_STOCK_ADDITIONAL:

                 //TODO: Make ponderation varariable configurable
                 $ponderateConversion= 1;
                 $ponderateStock= 2;
                 $ponderateIsNew= 3;

                 //TODO: Replace catalog_product_flat_1 with eav tables or store new dates in the indicator table
                 $sql= "UPDATE adm_extracatalog_indicator oci ".
                       " JOIN catalog_product_flat_1 AS cpf1 ON oci.entity_id=cpf1.entity_id ".
                       " SET indicator= ROUND( (".
                           $ponderateConversion."*conversion_rate + ".
                           $ponderateStock."*stock_rate + ".
                           $ponderateIsNew."*IF(cpf1.news_from_date IS NOT NULL AND NOW()>cpf1.news_from_date AND (cpf1.news_to_date IS NULL OR NOW()<cpf1.news_to_date), 1, 0) )*100,0)";
                 break;

             case ADM_ExtraCatalog_Helper_Indicator::FORMULA_CONVERS_ORDER_CART_WITH_STOCK:
                 $sql= "UPDATE adm_extracatalog_indicator
                 SET indicator= ROUND( if(views_count>0,( (0.01+ordered_count) * (0.01+carts_count)*0.5)/views_count,0.001)*100000*stock_rate,0)";
             break;


             case ADM_ExtraCatalog_Helper_Indicator::FORMULA_SALES_WITH_STOCK:
                 $sql= "UPDATE adm_extracatalog_indicator
                 SET indicator= ROUND(  (  (0.01+ordered_count) + (0.01+carts_count)*0.5 ) *100 *stock_rate,0)";
             break;

             case ADM_ExtraCatalog_Helper_Indicator::FORMULA_CONVERS_ORDER_ONLY_WITH_STOCK:
             default:
                //Simple algorithm by jaymard
                $sql= "UPDATE adm_extracatalog_indicator
                                SET indicator= (1+conversion_rate)*stock_rate*100000";
             break;
         }



        $statement = $this->_getWriteAdapter()->query($sql);

        /**
         * Calcul de la reference
         * Les produits sans indicator doivent avoir un score élévé dans le cas d'un sort ascendant pour arriver en dernier
         */
        if ($this->_default_sort=='asc') {
            $sql= "SELECT 100*MAX(indicator)  FROM adm_extracatalog_indicator";
            $maxIndicator= $this->_getReadAdapter()->fetchOne($sql);
            $this->_reference_indicator_default= (!empty($maxIndicator)) ? $maxIndicator : 100000;
        } else {
            $this->_reference_indicator_default= 0;
        }
    }


    public function resetIndicatorProductAttribute($storeId=0)
    {
       $attribute= $this->getProductIndicatorAttribute();

       $sql= "INSERT INTO catalog_product_entity_int (entity_type_id, attribute_id,store_id,entity_id,VALUE)
       SELECT 4 AS entity_type_id, " . $attribute->getId()
       . " AS attribute_id, 0 AS store_id, cpe.entity_id," . $this->_reference_indicator_default
       . " AS VALUE FROM catalog_product_entity cpe
       LEFT JOIN catalog_product_super_link cpsl ON cpsl.product_id=cpe.entity_id
       WHERE ( cpe.type_id='configurable' OR (cpe.type_id='simple' AND cpsl.link_id IS NULL) )
       ON DUPLICATE KEY UPDATE VALUE=".$this->_reference_indicator_default;

       return $this->_getWriteAdapter()->query($sql);
   }

   public function updateIndicatorProductAttribute($entityId,$value,$storeId=0)
   {

       $attributeModel = $this->getProductIndicatorAttribute();

       if ($this->_default_sort=='asc') {
           $value= $this->_reference_indicator_default - $value;
       }

       $data= array('entity_type_id'=> $this->_product_type_id
                    ,'entity_id'=>$entityId
                    ,'attribute_id'=>$attributeModel->getId()
                    ,'value'=> $value
                    ,'store_id'=> $storeId
               );

       return $this->_getWriteAdapter()->insertOnDuplicate($attributeModel->getBackend()->getTable()
                                                    , $data);
   }

   public function updateIndicatorOnFlat()
   {
       $attributeModel = $this->getProductIndicatorAttribute();
       $resourceFlat=  Mage::getResourceModel('catalog/product_flat');
       foreach (Mage::app()->getStores() as $store) {
           $resourceFlat->setStoreId($store->getId());
           $columns= $resourceFlat->getAllTableColumns();
           $searcCol= self::CATALOG_PRODUCT_INDICATOR_ATTRIBUTE_CODE;
           if (in_array($searcCol, $columns)) {
               $flatTable= $resourceFlat->getFlatTableName();

               $sql= 'UPDATE '.$flatTable.' AS flat
               JOIN  '.$attributeModel->getBackend()->getTable().' cpe ON flat.entity_id=cpe.entity_id
               SET flat.'.$searcCol.'=cpe.value
               WHERE cpe.attribute_id='.$attributeModel->getId().' AND store_id=0';

               $this->_getWriteAdapter()->query($sql);
           }
       }
   }

}