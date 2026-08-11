<?php
/*
 * @copyright Copyright (c) 2021 mash2 GmbH & Co. KG. All rights reserved.
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0).
 */

/**
 * Json Xml Rpc webservice controller
 */
class Cobby_Connector_Model_Api_Server_Adapter_Json extends Varien_Object implements Mage_Api_Model_Server_Adapter_Interface
{
    /**
     * JSON-RPC Server
     *
     * Maho removed Zend Framework 1; its JSON-RPC server lives in
     * Laminas\Json\Server (the same component Maho core's
     * Mage_Api_Model_Server_Adapter_Jsonrpc uses).
     *
     * @var Laminas\Json\Server\Server
     */
    protected $_json = null;

    /**
     * Set handler class name for webservice
     *
     * @param string $handler
     * @return $this
     */
    public function setHandler($handler)
    {
        $this->setData('handler', $handler);
        return $this;
    }

    /**
     * Retrieve handler class name for webservice
     *
     * @return mixed
     */
    public function getHandler()
    {
        return $this->getData('handler');
    }

    /**
     *
     * Set webservice api controller
     *
     * @param Mage_Api_Controller_Action $controller
     * @return $this
     */
    public function setController(Mage_Api_Controller_Action $controller)
    {
        $this->setData('controller', $controller);
        return $this;
    }

    /**
     *
     * Retrieve webservice api controller
     *
     * @return mixed
     */
    public function getController()
    {
        return $this->getData('controller');
    }

    /**
     * Run webservice
     *
     * @return $this
     */
    public function run()
    {
        $apiConfigCharset = Mage::getStoreConfig("api/config/charset");

        $this->_json = new Laminas\Json\Server\Server();
        $this->_json->setClass($this->getHandler());
        // Return the response object from handle() instead of echoing it.
        $this->_json->setReturnResponse(true);

        $this->getController()->getResponse()
            ->clearHeaders()
            ->setHeader('Content-Type','application/json; charset='.$apiConfigCharset)
            ->setBody((string) $this->_json->handle());

        return $this;
    }

    /**
     * Dispatch webservice fault
     *
     * @param int $code
     * @param string $message
     * @throws Laminas\Json\Server\Exception\RuntimeException
     */
    public function fault($code, $message)
    {
        throw new Laminas\Json\Server\Exception\RuntimeException($message, $code);
    }
}