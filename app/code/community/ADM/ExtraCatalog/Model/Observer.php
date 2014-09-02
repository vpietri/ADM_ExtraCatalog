<?php
class ADM_ExtraCatalog_Model_Observer extends Varien_Event_Observer
{
    const XML_PATH_INDICATOR_SORT  = 'catalog/product_enhanced/indicator_enabled';

    const CACHE_TAG = 'NAVIGATION_PRODUCT';

    public function storeProductListIds($observer)
    {
        $block = $observer->getEvent()->getBlock();
        $controllerAction = Mage::app()->getFrontController()->getAction()->getFullActionName();

        $requestUrlFull = Mage::helper('core/url')->getCurrentUrl();

        //Dans le cas d'une recherche, il faut conserver l'URL avec ses filtres
        if($controllerAction=='catalogsearch_result_index') {
            Mage::getSingleton('core/session')->setNavProductReturnList($requestUrlFull);
        }

        if(Mage::helper('adm_extracatalog')->isProductNavigationEnabled()
                && in_array(strtolower($controllerAction), array('catalog_category_view', 'catalogsearch_result_index')))
        {

            $key = Mage::helper('adm_extracatalog')->buildNavProductCacheKey($requestUrlFull);


            //Inutile de flusher le cache il peut être réutilisé par un autre utilisateur
            //			$currentNavProductCacheKey= Mage::getSingleton('core/session')->getNavProductCacheKey();
            // 			if ($key != $currentNavProductCacheKey) {
            // 			  Mage::app()->removeCache($currentNavProductCacheKey);
            // 			}

            $cacheContent = Mage::app()->loadCache($key);

            if (!$cacheContent)	{
                $tabCatalogProduct = array();
                $navCatalogProduct = new Varien_Object();

                $collectionNav  = clone $observer->getCollection();
                $pageSize = $collectionNav->getPageSize();

                $collectionNav->clear();
                $collectionNav->setPageSize(false);
                $collectionNav->getSelect()->reset(Zend_Db_Select::LIMIT_COUNT);
                $collectionNav->getSelect()->reset(Zend_Db_Select::LIMIT_OFFSET);
                foreach($collectionNav as $item){
                    $tabCatalogProduct[] = array('id'=>$item->getId(),
                                                'name'=>$item->getName(),
                                                'url_path'=>$item->getRequestPath(),
                                                );
                }

                $lifetime = Mage::helper('adm_extracatalog')->getNavCacheLifeTime();

                $navCatalogProduct->setUrlReferrer($requestUrlFull);
                $navCatalogProduct->setCatalog($tabCatalogProduct);
                $navCatalogProduct->setPageSize($pageSize);

                Mage::app()->saveCache(serialize($navCatalogProduct), $key, array(self::CACHE_TAG), $lifetime );     // Mise en cache du résultat
            }
            Mage::getSingleton('core/session')->setNavProductCacheKey($key);
        }
    }



	/**
	 * Calcul des attributs produits
	 *
	 */
	public function computeIndicatorAttributes()
	{

	    if (Mage::getStoreConfig(self::XML_PATH_INDICATOR_SORT)) {
    	    $indicatorHelper= Mage::helper('adm_extracatalog/indicator');


    	    /**
    	     *  Total des ventes
    	     */
    	    $reportProductsSelect = $indicatorHelper->getReportsOrderedSelect();
    	    $indicatorHelper->updateIndicators($reportProductsSelect, array('ordered_qty'=>'ordered_qty', 'ordered_count'=>'ordered_count'));


    	    /**
    	     *  Produit le plus ajouté au panier
    	     */
    	    $reportProductsSelect = $indicatorHelper->getReportsCartsCountSelect();
    	    $indicatorHelper->updateIndicators($reportProductsSelect, array('carts'=>'carts_count'));

    	    /**
    	     *  Total des vus
    	     */
    	    $reportProductsSelect = $indicatorHelper->getReportsViewCountSelect();
    	    $indicatorHelper->updateIndicators($reportProductsSelect, array('views'=>'views_count'));

    	    /**
    	     * Taux de conversion d’un produit :
    	     * Le taux de conversion d’un produit est : Nombre de ventes / Nombre de vues * 100
    	     */
    	    $reportProductsSelect = $indicatorHelper->getReportsStockLevelSelect();
    	    $indicatorHelper->updateIndicators($reportProductsSelect, array('stock_rate'=>'stock_rate'));


    	    $indicatorHelper->applyIndicators();
	    }
	}

}