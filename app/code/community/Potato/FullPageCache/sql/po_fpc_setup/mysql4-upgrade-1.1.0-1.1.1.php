<?php
$installer = $this;
$installer->startSetup();
$installer->run("
    ALTER TABLE {$this->getTable('po_fpc/storage')} ADD INDEX request_url (request_url);
");
$installer->endSetup();