<?php
class ADM_ExtraCatalog_Block_Product_Navigation extends Mage_Core_Block_Template
{

    protected $_cacheContent;

    protected function _beforeToHtml()
    {
        if ($this->canDisplayNavProduct()) {
            $this->_getNavProducts();
        }
        return parent::_beforeToHtml();
    }

    public function canDisplayNavProduct()
    {
        $key = Mage::getSingleton('core/session')->getNavProductCacheKey();
        if (Mage::helper('adm_extracatalog')->isProductNavigationEnabled() && $this->_getCacheContent())
            return true;
        else
          return false;
    }

    public function getNavProductsSize()
    {
        return count($this->_getCacheContent()->getCatalog());
    }

    protected function _getCacheContent()
    {
        if(is_null($this->_cacheContent)) {
            $key = Mage::getSingleton('core/session')->getNavProductCacheKey();
            $_cacheNav = unserialize(Mage::app()->loadCache($key));
            if(!empty($_cacheNav)) {
                $this->_cacheContent = $_cacheNav;
            } else {
                $this->_cacheContent = new Varien_Object(array('catalog'=>false, 'url_referrer'=>false));
            }

        }

        return $this->_cacheContent;
    }

    protected function _getNavProducts()
    {
        if (! $this->hasNavProducts()) {
            $tabProducts = $this->_getCacheContent()->getCatalog();
            if (empty($tabProducts)) {
                return false;
            }

            $_lenght = $this->getNavProductsSize();
            $_currentId = Mage::registry('product')->getId();
            $_position = 1;
            foreach ($tabProducts as $key=>$productData) {
                if ($_currentId == $productData['id']) {
                    if (($key - 1) >= 0)
                        $prevId = $tabProducts[$key - 1];
                    else
                     $prevId = $tabProducts[$_lenght - 1];

                    if (($key + 1) != $_lenght)
                        $nextId = $tabProducts[$key + 1];
                    else
                     $nextId = $tabProducts[0];

                    $_position = $key+1;
                    $this->setCurrentProductPosition($key+1);
                    $this->setPreviousProduct(new Varien_Object($prevId));
                    $this->setNextProduct(new Varien_Object($nextId));
                    break;
                }
            }


            $pageSize = $this->_getCacheContent()->getPageSize();
            $curPage = ceil($_position/$pageSize);

            $_currentCategoryUrl = $this->_getCacheContent()->getUrlReferrer();
            $_currentCategoryUrl = Mage::helper('core/url')->removeRequestParam($_currentCategoryUrl,'p');
            if ($curPage>1) {
                $_currentCategoryUrl = Mage::helper('core/url')->addRequestParam($_currentCategoryUrl,array('p'=>$curPage));
            }

            $this->setCategoryBackUrl($_currentCategoryUrl);


            if (Mage::registry('current_category')) {
                $_currentCategoryName = Mage::registry('current_category')->getName();
            } else {
                $_currentCategoryName = $this->__('Back list');
            }

            $this->setCategoryBackName($_currentCategoryName);


        }
    }
}