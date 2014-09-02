<?php
class ADM_ExtraCatalog_Helper_Indicator extends Mage_Core_Helper_Abstract
{

    const FORMULA_ONLY_WITH_STOCK= 0;

    const FORMULA_CONVERS_ORDER_ONLY_WITH_STOCK= 10;

    const FORMULA_CONVERS_ORDER_CART_WITH_STOCK= 20;

    const FORMULA_CONVERS_ORDER_PONDERATED_WITH_STOCK= 30;

    const FORMULA_SALES_WITH_STOCK= 40;

    const FORMULA_CONVERS_ORDER_STOCK_ADDITIONAL= 50;


    /**
     * Positionnement de filtre attribut sur la collection
     *
     * @param unknown_type $reportProductsCollection
     * @param unknown_type $filter
     */
   public function setFilterOnReportCollection($reportProductsCollection, $filter=array())
   {

       //$filter= array('entity_id'=>2);

       if (!empty($filter)) {
           foreach ($filter as $attribute=>$value) {
               $reportProductsCollection->addFieldToFilter($attribute, $value);
           }
       }

       return $reportProductsCollection;
   }



   public function getIndicatorMinDate()
   {
       $lastmonth = mktime(0, 0, 0, date("m")-1, date("d"),   date("Y"));

       return date('Y-m-d', $lastmonth);
   }

   public function getTodayDate()
   {
       return date('Y-m-d');
   }

    /**
     *
     * @param unknown_type $filter
     */
   public function getReportsOrderedSelect($filter=array())
   {
       $reportProductsCollection = Mage::getResourceModel('reports/product_collection')
                                   ->addOrderedQty($this->getIndicatorMinDate(), $this->getTodayDate());


       $reportProductsCollection->getSelect()->columns('SUM(1) AS ordered_count');

       $reportProductsCollection = $this->setFilterOnReportCollection($reportProductsCollection, $filter);

       return $reportProductsCollection->getSelect()->__toString();
   }




   public function getReportsCartsCountSelect($filter=array())
   {
       /**
        *  Produit le plus ajouté au panier
        */
       $reportProductsCollection = Mage::getResourceModel('reports/product_collection');
                                   //->addCartsCount();


       //Récriture de la méthode addCartsCount qui ne prend pas en compte les dates
       $countSelect = clone $reportProductsCollection->getSelect();
       $countSelect->reset();

       $countSelect->from(array('quote_items' => $reportProductsCollection->getTable('sales/quote_item')), 'COUNT(*)')
       ->join(array('quotes' => $reportProductsCollection->getTable('sales/quote')),
               'quotes.entity_id = quote_items.quote_id AND quotes.is_active = 1',
               array())
               ->where("quote_items.product_id = e.entity_id")
               ->where("DATE_FORMAT(quotes.created_at,'%Y-%m-%d')>='".$this->getIndicatorMinDate()."'");

       $reportProductsCollection->getSelect()
       ->columns(array("carts" => "({$countSelect})"))
       ->group("e.entity_id")
       ->having('carts > ?', 0);


       $reportProductsCollection = $this->setFilterOnReportCollection($reportProductsCollection, $filter);

       return $reportProductsCollection->getSelect()->__toString();
   }


   public function getReportsViewCountSelect($filter=array())
   {
       /**
        *  Total des vus
        */
       $reportProductsCollection = Mage::getResourceModel('reports/product_collection')
                                   ->addViewsCount($this->getIndicatorMinDate(), $this->getTodayDate());

       $reportProductsCollection = $this->setFilterOnReportCollection($reportProductsCollection, $filter);

       return $reportProductsCollection->getSelect()->__toString();

   }

   public function getReportsStockLevelSelect()
   {
       $select= "SELECT cpe.entity_id, ROUND(sub_stock.nb_dispo / sub_stock.nb,2) AS stock_rate
       FROM catalog_product_entity cpe
       JOIN (
       SELECT SUM(qty) AS qty, (CASE WHEN cpsl.parent_id IS NOT NULL THEN cpsl.parent_id ELSE cpe.entity_id END) AS product_id, COUNT(1) AS nb, SUM(IF(qty>0, 1, 0)) AS nb_dispo
       FROM catalog_product_entity cpe
       JOIN cataloginventory_stock_item csi ON cpe.entity_id=csi.product_id AND csi.stock_id=1
       LEFT JOIN catalog_product_super_link cpsl ON cpsl.product_id=cpe.entity_id
       GROUP BY  CASE WHEN cpsl.parent_id IS NOT NULL THEN cpsl.parent_id ELSE cpe.entity_id END
       ) sub_stock ON sub_stock.product_id=cpe.entity_id
       LEFT JOIN catalog_product_super_link cpsl ON cpsl.product_id=cpe.entity_id
       WHERE ( cpe.type_id='configurable' OR (cpe.type_id='simple' AND cpsl.link_id IS NULL) )
       AND sub_stock.qty > 0";

       return $select;
   }


   public function updateIndicators($reportSelect, $reportIndicators, $reportKey, $indicatorKey)
   {

       $indicator= Mage::getModel('adm_extracatalog/indicator');

       $read = Mage::getSingleton('core/resource')->getConnection('core_read');

       $reportProducts= $read->fetchAll($reportSelect);

       foreach($reportProducts as $reportProduct) {
           $entityId= (isset($reportProduct['entity_id'])) ? $reportProduct['entity_id'] : false;
           if ($entityId) {
               $nbLoop++;
               $indicator->clearInstance();
               $indicator->load($entityId,'entity_id');
               $indicator->setData('entity_id', $entityId);
               foreach ($reportIndicators as $reportKey=>$indicatorKey) {
                   $indicatorValue= (isset($reportProduct[$reportKey])) ? $reportProduct[$reportKey] : 0;
                   $indicator->setData($indicatorKey, $indicatorValue);
               }
               $indicator->save();

           }
       }
   }


   public function applyIndicators()
   {
       $indicatorRessourceModel= Mage::getResourceModel('adm_extracatalog/indicator');

       $indicatorRessourceModel->updateConversionRate();

       $indicatorRessourceModel->computeIndicator();

       $indicatorRessourceModel->resetIndicatorProductAttribute();

       //Set new value
       $indicatorCollection= Mage::getModel('adm_extracatalog/indicator')->getCollection()
                                            ->addFieldToFilter('indicator', array('gt'=> 0));


       $productModel= Mage::getModel('catalog/product');
       foreach ($indicatorCollection as $indicator) {
               $indicatorRessourceModel->updateIndicatorProductAttribute($indicator->getEntityId(), $indicator->getIndicator());
       }

       $indicatorRessourceModel->updateIndicatorOnFlat();
   }



}