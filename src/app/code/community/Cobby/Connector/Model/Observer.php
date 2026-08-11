<?php
/*
 * @copyright Copyright (c) 2021 mash2 GmbH & Co. KG. All rights reserved.
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0).
 */

/**
 * cobby settings observers
 */
class Cobby_Connector_Model_Observer extends Mage_Core_Model_Abstract
{
    const SUCCESS_MESSAGE = 'Registration was successful. Excel is now linked to your store. The service is now being set up for the first use. This process can take some time. Once done, you will receive an email with further information.';
    const CHARS_DIGITS                          = '0123456789';

    const SAVE = Mage_Index_Model_Event::TYPE_SAVE;
    const DELETE = Mage_Index_Model_Event::TYPE_DELETE;

    /**
     * @var Cobby_Connector_Helper_Data
     */
    protected $helper;

    /**
     * @var Cobby_Connector_Helper_Queue
     */
    protected $queueHelper;

    /**
     * @var Cobby_Connector_Helper_Cobbyapi
     */
    private $cobbyApi;

    /**
     * @var Cobby_Connector_Helper_Settings
     */
    private $settings;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->helper = Mage::helper('cobby_connector');
        $this->cobbyApi = Mage::helper('cobby_connector/cobbyapi');
        $this->queueHelper = Mage::helper('cobby_connector/queue');
        $this->settings = Mage::helper('cobby_connector/settings');
    }

    /**
     * store custom cobby settings
     * @param $observer
     * @return Cobby_Connector_Model_Observer
     */
    #[\Maho\Config\Observer('model_config_data_save_before', id: 'Cobby_Connector')]
    public function saveCobbyConfigData($observer)
    {
        $config = $observer->getObject();
        if($config->getSection() == "cobby") {

            $this->settings->setCobbyVersion(Mage::getStoreConfig(Cobby_Connector_Helper_Settings::XML_PATH_COBBY_VERSION));
            $this->settings->setCobbyUrl($this->settings->getDefaultBaseUrl());
        }

        return $this;
    }

        /**
     * run index for category deleted event
     * because the category delete event is not processed in indexer
     *
     * @param $observer
     * @return $this
     */
    #[\Maho\Config\Observer('catalog_category_delete_after', id: 'cobby_connector')]
    public function catalogCategoryDeleteAfter($observer)
    {
        $event = $observer->getEvent();
        $category = $event->getCategory();

        $this->queueHelper
            ->enqueueAndNotify('category', self::DELETE, $category->getId()); //constant has different value
    }

    #[\Maho\Config\Observer('catalog_category_save_after', id: 'cobby_connector')]
    public function catalogCategorySaveAfter($observer)
    {
        $event = $observer->getEvent();
        $category = $event->getCategory();

        $this->queueHelper
            ->enqueueAndNotify('category', self::SAVE, $category->getId()); //constant has different value

        $affectedProductIds = $category->getAffectedProductIds();
        if ($affectedProductIds) {
            Mage::getModel('cobby_connector/product')->updateHash($affectedProductIds);
            $this->queueHelper
                ->enqueueAndNotify('product', self::SAVE, $affectedProductIds);
        }
    }

    #[\Maho\Config\Observer('catalog_product_save_after', id: 'cobby_connector')]
    public function catalogProductSaveAfter($observer)
    {
        $event = $observer->getEvent();
        $product = $event->getProduct();

        Mage::getModel('cobby_connector/product')->updateHash($product->getId());
        $this->queueHelper
            ->enqueueAndNotify('product', self::SAVE, $product->getId()); //constant has different value
    }

    #[\Maho\Config\Observer('catalog_product_delete_after', id: 'cobby_connector')]
    public function catalogProductDeleteAfter($observer)
    {
        $event = $observer->getEvent();
        $product = $event->getProduct();

        Mage::getModel('cobby_connector/product')->updateHash($product->getId());
        $this->queueHelper
            ->enqueueAndNotify('product', self::DELETE, $product->getId()); //constant has different value
    }

    #[\Maho\Config\Observer('catalog_product_attribute_update_before', id: 'cobby_connector')]
    public function catalogProductAttributeUpdateBefore($observer)
    {
        $productIds = $observer->getData('product_ids');

        Mage::getModel('cobby_connector/product')->updateHash($productIds);
        $this->queueHelper
            ->enqueueAndNotify('product', self::SAVE, $productIds); //constant has different value
    }

    #[\Maho\Config\Observer('catalog_entity_attribute_save_after', id: 'cobby_connector')]
    public function catalogEntityAttributeSaveAfter($observer)
    {
        $event = $observer->getEvent();
        $attribute = $event->getAttribute();
        $this->queueHelper
            ->enqueueAndNotify('attribute', self::SAVE, $attribute->getId()); //constant has different value
    }

    private function _triggerObjectChanged($observer, $entity)
    {
        $event = $observer->getEvent();
        $object = $event->getObject();
        $this->queueHelper
            ->enqueueAndNotify($entity, self::SAVE, $object->getId());
    }

    private function _triggerSetReindexCobbyRequired($msg)
    {
        Mage::getModel('cobby_connector/product')->resetHash($msg);

        Mage::getSingleton('index/indexer')
            ->getProcessByCode('cobby_sync')
            ->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);

        return $this;
    }

    #[\Maho\Config\Observer('customer_group_save_after', id: 'Cobby_Connector')]
    public function triggerCustomerGroupChanged($observer)
    {
        $this->_triggerObjectChanged($observer, Mage_Customer_Model_Group::ENTITY);
    }

    #[\Maho\Config\Observer('admin_roles_save_after', id: 'Cobby_Connector')]
    public function triggerRoleChanged($observer)
    {
        $this->_triggerObjectChanged($observer, 'role');
    }

    #[\Maho\Config\Observer('admin_user_save_after', id: 'Cobby_Connector')]
    public function triggerUserChanged($observer)
    {
        $this->_triggerObjectChanged($observer, 'user');
    }

    #[\Maho\Config\Observer('fastsimpleimport_reindex_products_after', id: 'Cobby_Connector')]
    public function triggerAfterProductImport($observer)
    {
        $event = $observer->getEvent();
        $entityIds = $event->getEntityId();

        if(count($entityIds)) {
            Mage::getModel('cobby_connector/product')->updateHash($entityIds);
            $this->queueHelper->enqueueAndNotify('product', 'save', $entityIds);
        }
    }

    #[\Maho\Config\Observer('sales_model_service_quote_submit_success', area: 'frontend', id: 'Cobby_Connector')]
    public function triggerCatalogProductByStock($observer)
    {
        $event = $observer->getEvent();
        // Reindex quote ids
        $quote = $event->getQuote();
        $productIds = array();
        foreach ($quote->getAllItems() as $item) {
            $productIds[] = $item->getProductId();
            $children   = $item->getChildrenItems();
            if ($children) {
                foreach ($children as $childItem) {
                    $productIds[] = $childItem->getProductId();
                }
            }
        }

        if( count($productIds)) {
            Mage::getModel('cobby_connector/product')->updateHash($productIds);
            $this->queueHelper->enqueueAndNotify('stock', 'save', $productIds);
        }

        return $this;

    }

    #[\Maho\Config\Observer('eav_entity_attribute_set_save_after', id: 'cobby_connector')]
    public function eavEntityAttributeSetSaveAfter($observer)
    {
        $event = $observer->getEvent();
        $object = $event->getObject();
        $this->queueHelper
            ->enqueueAndNotify('attributeset', self::SAVE, $object->getId()); //constant has different value
    }

    #[\Maho\Config\Observer('eav_entity_attribute_set_delete_after', id: 'cobby_connector')]
    public function eavEntityAttributeSetDeleteAfter($observer)
    {
        $event = $observer->getEvent();
        $object = $event->getObject();
        $this->queueHelper->enqueueAndNotify('attributeset', self::DELETE, $object->getId()); //constant has different value
    }

    #[\Maho\Config\Observer('cataloginventory_stock_item_save_after', id: 'cobby_connector')]
    public function cataloginventoryStockItemSaveAfter($observer)
    {
        $event = $observer->getEvent();
        $item = $event->getItem();
        Mage::getModel('cobby_connector/product')->updateHash($item->getProductId());
        $this->queueHelper
            ->enqueueAndNotify('stock', self::SAVE, $item->getProductId()); //constant has different value
    }

    #[\Maho\Config\Observer('core_config_data_save_commit_after', id: 'cobby_connector')]
    public function coreConfigDataSaveCommitAfter($observer)
    {
        $relatedConfigSettings = array(
            Mage_Catalog_Helper_Data::XML_PATH_PRICE_SCOPE,
            Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK
        );

        $event = $observer->getEvent();
        $data = $event->getDataObject();

        if ($data && in_array($data->getPath(), $relatedConfigSettings) && $data->isValueChanged()){
            $this->_triggerSetReindexCobbyRequired();
        }
    }

    #[\Maho\Config\Observer('store_save_after', id: 'cobby_connector')]
    public function storeSaveAfter($observer)
    {
        $this->_triggerSetReindexCobbyRequired('store_changed');
    }

    #[\Maho\Config\Observer('store_delete_after', id: 'cobby_connector')]
    public function storeDeleteAfter($observer)
    {
        $this->_triggerSetReindexCobbyRequired('store_changed');
    }

    #[\Maho\Config\Observer('store_group_save_after', id: 'cobby_connector')]
    public function storeGroupSaveAfter($observer)
    {
        $this->_triggerSetReindexCobbyRequired('store_changed');
    }

    #[\Maho\Config\Observer('store_group_delete_after', id: 'cobby_connector')]
    public function storeGroupDeleteAfter($observer)
    {
        $this->_triggerSetReindexCobbyRequired('store_changed');
    }

    #[\Maho\Config\Observer('website_save_after', id: 'cobby_connector')]
    public function websiteSaveAfter($observer)
    {
        $this->_triggerSetReindexCobbyRequired('website_changed');
    }

    #[\Maho\Config\Observer('website_delete_after', id: 'cobby_connector')]
    public function websiteDeleteAfter($observer)
    {
        $this->_triggerSetReindexCobbyRequired('website_changed');
    }

    /**
     * set cobby sync status to running
     * @param $observer
     * @return $this
     */
    #[\Maho\Config\Observer('after_reindex_process_cobby_sync', id: 'Cobby_Connector')]
    public function updateCobbySyncStatus($observer)
    {
        Mage::getSingleton('index/indexer')
            ->getProcessByCode('cobby_sync')
            ->changeStatus(Mage_Index_Model_Process::STATUS_RUNNING);

        return $this;
    }

    /**
     * @param $observer
     */
    #[\Maho\Config\Observer('cobby_handle_changes', id: 'cobby_connector')]
    public function cobbyHandleChanges($observer)
    {
        $entity     = $observer->getEntity();
        $username   = $observer->getUsername();
        $action     = $observer->getAction();
        $context    = $observer->getContext();
        $ids        = $observer->getIds();

        if ($entity == 'product') {
            Mage::getModel('cobby_connector/product')->updateHash($observer->getIds());
        }

        $this->queueHelper->enqueueAndNotify($entity, $action, $ids, null, $context, $username);
    }
}
