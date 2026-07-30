<?php
/*
 * @copyright Copyright (c) 2021 mash2 GmbH & Co. KG. All rights reserved.
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0).
 */

/**
 * Webservice json server handler
 **/
class Cobby_Connector_Model_Api_Server_Handler_Json extends Mage_Api_Model_Server_Handler_Abstract
{
    public function processingMethodResult($result)
    {
        return $result;
    }

    /**
     * The parent only converts Exception into an API fault. Under PHP 8 a malformed
     * request (wrong argument count, wrong argument type) raises an Error instead,
     * which would escape as an HTML fatal page rather than a JSON-RPC fault.
     *
     * @param string $sessionId
     * @param string $apiPath
     * @param array $args
     * @return mixed
     */
    public function call($sessionId, $apiPath, $args = [])
    {
        try {
            return parent::call($sessionId, $apiPath, $args);
        } catch (Error $e) {
            Mage::logException($e);
            $this->_fault('internal', null, $e->getMessage());
        }
    }
}
