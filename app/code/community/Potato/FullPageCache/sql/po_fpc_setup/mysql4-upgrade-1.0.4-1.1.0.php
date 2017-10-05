<?php
$installer = $this;
$installer->startSetup();
$installer->run("
    CREATE TABLE IF NOT EXISTS {$this->getTable('po_fpc/storage')} (
    `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT,
    `cache_id` VARCHAR( 255 ) NOT NULL,
    `size` INT( 10 ) UNSIGNED NOT NULL,
    `tags` VARCHAR( 255 ) NOT NULL,
    `private_tag` VARCHAR( 255 ) NOT NULL,
    `expire` INT( 10 ) UNSIGNED NOT NULL,
    `request_url` VARCHAR( 255 ) NOT NULL DEFAULT '',
    PRIMARY KEY ( `id` )
    ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
    CREATE INDEX cache_id ON {$this->getTable('po_fpc/storage')} (cache_id);
    CREATE INDEX tags ON {$this->getTable('po_fpc/storage')} (tags);
    CREATE INDEX private_tag ON {$this->getTable('po_fpc/storage')} (private_tag);
");
$installer->endSetup();