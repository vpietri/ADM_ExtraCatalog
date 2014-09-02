<?php

class ADM_ExtraCatalog_Block_Product_List_Bestseller extends ADM_ExtraCatalog_Block_Product_List_Abstract
{
    protected $_subTitle = 'Best Sellers Products';

    protected $_cacheKeyPrefix = 'ADM_CATALOG_PRODUCT_LIST_BESTSELLERS';


    /**
     * Prepare and return product collection
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection|Object|Varien_Data_Collection
     */
    protected function _getProductCollection()
    {

        $storeId    = Mage::app()->getStore()->getId();
//         $collection = Mage::getResourceModel('sales/report_bestsellers_collection')
//         ->setModel('catalog/product')
//         ->addStoreFilter($storeId)
//         ;
//         $collection = Mage::getResourceModel('sales/report_bestsellers_collection')
//                             ->setModel('catalog/product')
//                             ->addStoreFilter($storeId)
//                             ->setOrder('ordered_qty', 'desc')
//                             ->setPageSize($this->getProductsCount())
//                             ->setCurPage(1);

        $collection = Mage::getResourceModel('reports/product_collection')
                            ->addAttributeToSelect('*')
                            ->addOrderedQty()
//                             ->setStoreId($storeId)
                            ->addStoreFilter($storeId)
                            ->setOrder('ordered_qty', 'desc')
                            ->setPageSize($this->getProductsCount())
                            ->setCurPage(1);

//         var_dump($collection->getSelect()->__toString());
//         exit;

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
        Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);


        return $collection;
    }
}
