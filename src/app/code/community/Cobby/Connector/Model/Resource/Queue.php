<?php
/*
 * @copyright Copyright (c) 2021 mash2 GmbH & Co. KG. All rights reserved.
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0).
 */

class Cobby_Connector_Model_Resource_Queue extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('cobby_connector/queue', 'queue_id');
    }

    /**
     * @param Mage_Core_Model_Abstract $object
     * @return Cobby_Connector_Model_Resource_Queue
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        parent::_beforeSave($object);
        
        if ($object->isObjectNew()) {
            $object->setData('created_at', Mage::app()->getLocale()->formatDateForDb('now'));
        }
        
        return $this;
    }

    public function reset()
    {
        $this->_getWriteAdapter()->delete($this->getMainTable());
        return $this;
    }

}
