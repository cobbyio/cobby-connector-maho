<?php
abstract class Cobby_Connector_Model_Import_Product_Abstract extends Mage_Api_Model_Resource_Abstract
{
    /**
     * @var Mage_Core_Model_Resource
     */
    protected $resourceModel;

    /**
     * DB connection.
     *
     * @var Varien_Db_Adapter_Interface
     */
    protected $connection;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->resourceModel = Mage::getSingleton('core/resource');
        $this->connection  = $this->resourceModel->getConnection('write');
    }

    /**
     * load existing product Ids
     *
     * @param $productIds
     * @return array
     */
    protected function loadExistingProductIds($productIds)
    {
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToFilter('entity_id', array('in' => $productIds));

        return $collection->getAllIds();
    }

    /**
     * set updated_at to now
     *
     * @param $productIds
     * @return $this
     */
    protected function touchProducts($productIds){
        if (count($productIds) > 0) {
            Mage::getModel('cobby_connector/product')->updateHash($productIds);
            $productTable = $this->resourceModel->getTableName('catalog/product');
            // Plain UPDATE rather than insertOnDuplicate(): these rows always exist, and the
            // INSERT branch would omit attribute_set_id and break its foreign key.
            $this->connection->update(
                $productTable,
                array('updated_at' => Mage::app()->getLocale()->formatDateForDb('now')),
                array('entity_id IN (?)' => $productIds)
            );
        }

        return $this;
    }

    /**
     * @param array $rows
     * @return array
     */
    public abstract function import($rows);

    /**
     * @param array $array
     * @param $column
     * @return array
     */
    protected function getColumnValues(array $array, $column)
    {
        return array_map(function($element) use ($column) {
            return $element[$column];
        }, $array);
    }
}