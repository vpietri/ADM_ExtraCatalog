<?php
$installer = $this;
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();

$setup->removeAttribute('catalog_product', 'adm_indicator');
$setup->addAttribute('catalog_product', 'adm_indicator', array(
		'type'              => 'int',
    'visible' 	=> false,
    'label'		=> 'Indicator',
    'required'  => false,
    'default'   => '0',
    'global'       => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'note'		=> 'Indicator'
));

//Creation des tables specifiques pour les achats
$installer->run("
        CREATE TABLE IF NOT EXISTS {$this->getTable('adm_extracatalog_indicator')} (
        oci_id bigint(20) NOT NULL auto_increment,
        entity_id int(11) NOT NULL,
        ordered_count int(5) default 0,
        ordered_qty int(5) default 0,
        carts_count int(5) default 0,
        views_count int(5) default 0,
        stock_rate decimal(3,2) default 0,
        conversion_rate decimal(3,2) default 0,
        indicator int(11) default 0,
        store_id int(3) default 0,
        PRIMARY KEY  (oci_id),
        UNIQUE KEY `UNQ_ADM_CAT_INDIC_PROD_STORE` (`entity_id`,`store_id`),
        KEY `IDX_ADM_CAT_INDIC_STORE_INDICATOR` (`store_id`, `indicator`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");

$installer->endSetup();