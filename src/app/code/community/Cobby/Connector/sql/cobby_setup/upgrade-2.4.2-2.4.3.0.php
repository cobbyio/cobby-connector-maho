<?php
/*
 * @copyright Copyright (c) 2021 mash2 GmbH & Co. KG. All rights reserved.
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0).
 */

/**
 * Repair installs whose core_resource row survived without the module's tables.
 *
 * Stores upgraded from an older cobby release (the 1.x line reached 1.56.1.1, well
 * above every upgrade script shipped here) keep their `cobby_setup` version row, so
 * the 1.5 - 1.45 scripts below are skipped and the tables are never created. The same
 * happens on any install that recorded the version but failed before the schema ran.
 *
 * This script re-applies the cumulative schema and is a no-op when it is already in
 * place, so it is safe on a fresh install too, where it simply runs last.
 */

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$connection  = $installer->getConnection();
$queueTable  = $installer->getTable('cobby_connector/queue');
$productTable = $installer->getTable('cobby_connector/product');

/**
 * cobby_connector_queue - created by 1.8.1.0, extended by 1.25.1.0 and 1.45.1.0.
 */
if (!$connection->isTableExists($queueTable)) {
    $table = $connection->newTable($queueTable)
        ->addColumn('queue_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
        ), 'Queue ID')
        ->addColumn('object_ids', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
            'nullable'  => false,
        ), 'Object IDs')
        ->addColumn('object_entity', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            'nullable'  => false,
        ), 'Object Entity')
        ->addColumn('object_action', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            'nullable'  => false,
        ), 'Object Action')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable'  => false,
        ), 'Creation Time')
        ->addColumn('user_name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            'nullable'  => true,
            'default'   => null,
        ), 'User Name')
        ->addColumn('context', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            'nullable'  => true,
            'default'   => null,
        ), 'Context')
        ->addColumn('transaction_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
            'nullable'  => true,
            'default'   => null,
        ), 'Transaction ID')
        ->setComment('Cobby Queue Table');

    $connection->createTable($table);
} else {
    // Table predates 1.25.1.0 / 1.45.1.0 - add whatever is missing.
    foreach (array('user_name', 'context', 'transaction_id') as $column) {
        if (!$connection->tableColumnExists($queueTable, $column)) {
            $connection->addColumn($queueTable, $column, 'varchar(255) null default null');
        }
    }
}

/**
 * cobby_connector_product - created by 1.38.1.0, seeded from the product catalog.
 */
if (!$connection->isTableExists($productTable)) {
    $table = $connection->newTable($productTable)
        ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity'  => true,
            'unsigned'  => true,
            'nullable'  => false,
            'primary'   => true,
        ), 'Entity ID')
        ->addColumn('hash', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array(
        ), 'Hash')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        ), 'Creation Time')
        ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        ), 'Update Time')
        ->addForeignKey(
            $installer->getFkName(
                'cobby_connector/product',
                'entity_id',
                'catalog/product',
                'entity_id'
            ),
            'entity_id', $installer->getTable('catalog/product'), 'entity_id',
            Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->setComment('Cobby Product Table');

    $connection->createTable($table);

    $installer->run("
        INSERT INTO `{$productTable}`
        (`entity_id`, `hash`)
            SELECT `entity_id`, 'init'
                FROM `{$installer->getTable('catalog/product')}`;
    ");
}

$installer->endSetup();
