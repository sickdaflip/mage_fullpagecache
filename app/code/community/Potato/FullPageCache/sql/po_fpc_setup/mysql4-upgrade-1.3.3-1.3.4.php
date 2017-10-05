<?php
$installer = $this;
$installer->startSetup();
$installer->run("
    TRUNCATE TABLE {$this->getTable('po_fpc/popularity')};
");
$installer->endSetup();