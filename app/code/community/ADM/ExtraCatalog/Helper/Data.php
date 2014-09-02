<?php
class ADM_ExtraCatalog_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_NAVIGATION_ENABLED = 'catalog/product_navigation/enabled';

    const XML_PATH_NAVIGATION_CACHE_LIFETIME = 'catalog/product_navigation/lifetime';

    public function isProductNavigationEnabled()
    {
        return true;
        return Mage::getStoreConfigFlag(self::XML_PATH_NAVIGATION_ENABLED);
    }

    public function getNavCacheLifeTime()
    {
        $lifetime = Mage::getStoreConfig(self::XML_PATH_NAVIGATION_CACHE_LIFETIME);
        if (empty($lifetime)) {
            $lifetime = Mage::getSingleton('core/session')->getCookieLifetime();
        }

        //By default set 10mn lifetime
        if (empty($lifetime)) {
            $lifetime = 600;
        }

        return $lifetime;
    }

    /**
     * Get cache key
     *
     * @param string $requestUrlFull
     *
     * @return string;
     */
    public function buildNavProductCacheKey($requestUrlFull=''){

        if(empty($requestUrlFull)) {
            $requestUrlFull= Mage::helper('core/url')->getCurrentUrl();
        }

        $cacheKey  = 'ADM_EXTRA_PRODUCT_NAVIGATION_' .
                     'STORE_' . Mage::app()->getStore()->getId() .
                     '_' . md5($requestUrlFull);

        return $cacheKey;
    }

}