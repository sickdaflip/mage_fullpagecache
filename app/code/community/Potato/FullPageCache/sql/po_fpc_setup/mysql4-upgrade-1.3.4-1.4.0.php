<?php
$installer = $this;
$installer->startSetup();
$installer->run("
    CREATE TABLE IF NOT EXISTS {$this->getTable('po_fpc/crawler_queue')} (
    `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT,
    `url` VARCHAR( 255 ) NOT NULL,
    `options` TEXT NOT NULL DEFAULT '',
    PRIMARY KEY ( `id` )
    ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
    CREATE INDEX url ON {$this->getTable('po_fpc/crawler_queue')} (url);
");
$installer->endSetup();