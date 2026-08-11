<?php
/*
 * @copyright Copyright (c) 2021 mash2 GmbH & Co. KG. All rights reserved.
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0).
 */

class Cobby_Connector_Model_Resource_Product extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('cobby_connector/product', 'entity_id');
    }

    public function resetHash($hash)
    {
        $this->_getWriteAdapter()->update($this->getMainTable(), array('hash' => $hash));
        return $this;
    }

    public function updateHash($ids, $hash)
    {
        $select = $this->_getWriteAdapter()
            ->select()
            ->from(array('cp' => $this->getTable('catalog/product')), array('entity_id', new Varien_Db_Expr($this->_getWriteAdapter()->quote($hash))))
            ->where('cp.entity_id IN (?)', $ids)
            ->insertFromSelect($this->getMainTable(), array('entity_id', 'hash'), Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE);

        $this->_getWriteAdapter()->query($select);
        return $this;
    }
}
