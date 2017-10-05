<?php
$installer = $this;
$installer->startSetup();
$installer->run("
    ALTER TABLE {$this->getTable('po_fpc/storage')} ADD `store_id` SMALLINT(5) UNSIGNED NOT NULL;
    
    CREATE TABLE IF NOT EXISTS {$this->getTable('po_fpc/popularity')} (
    `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT,
    `url` VARCHAR( 255 ) NOT NULL,
    `request_url` VARCHAR( 255 ) NOT NULL,
    `views` INT( 10 ) UNSIGNED NOT NULL DEFAULT 0,
    `store_id` SMALLINT(5) UNSIGNED NOT NULL,
    `position` INT( 10 ) UNSIGNED NOT NULL DEFAULT 99999,
    `allow_to_cache` TINYINT (1) UNSIGNED NOT NULL DEFAULT 1,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY ( `id` )
    ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
    
    CREATE INDEX url ON {$this->getTable('po_fpc/popularity')} (url);
    CREATE INDEX views ON {$this->getTable('po_fpc/popularity')} (views);
    CREATE INDEX position ON {$this->getTable('po_fpc/popularity')} (position);
    
    CREATE TABLE IF NOT EXISTS {$this->getTable('po_fpc/statistics')} (
    `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT,
    `cached` INT( 10 ) UNSIGNED NOT NULL DEFAULT 0,
    `miss` INT( 10 ) UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY ( `id` )
    ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
    
    CREATE INDEX created_at ON {$this->getTable('po_fpc/statistics')} (created_at);
");
$installer->endSetup();