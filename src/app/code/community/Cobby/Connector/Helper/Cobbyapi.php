<?php
/*
 * @copyright Copyright (c) 2021 mash2 GmbH & Co. KG. All rights reserved.
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0).
 */

class Cobby_Connector_Helper_Cobbyapi extends Mage_Core_Helper_Abstract
{
    /**
     * cobby service url
     */
    const COBBY_API = 'https://api.cobby.mash2.com/';

    /**
     * @var Cobby_Connector_Helper_Settings
     */
    private $settings;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->settings = Mage::helper('cobby_connector/settings');
    }

    /**
     * Create a cobby request with required items
     *
     * @return array
     */
    private function createCobbyRequest()
    {
        $result = array();
        $result['LicenseKey']   = $this->settings->getLicenseKey();
        $result['ShopUrl']      = $this->settings->getDefaultBaseUrl();
        $result['CobbyVersion'] = $this->settings->getCobbyVersion();

        return $result;
    }

    /**
     * get http client
     *
     * Maho ships neither Zend_Rest_Client nor Zend_Http_Client (Zend Framework 1
     * was removed), so we use Symfony's HttpClient, the same stack Maho core uses
     * for outbound requests (e.g. Mage_Directory_Model_Currency_Import_Fixerio).
     *
     * @return \Symfony\Contracts\HttpClient\HttpClientInterface
     */
    private function getClient()
    {
        return \Symfony\Component\HttpClient\HttpClient::create(array('timeout' => 60));
    }

    /**
     *
     * Performs an HTTP POST request to cobby service
     *
     * @param $method
     * @param null $data
     * @return mixed
     * @throws Exception
     */
    public function restPost($method, $data = null)
    {
        $client = $this->getClient();
        $url = rtrim(self::COBBY_API, '/') . '/' . ltrim($method, '/');

        $response = $client->request('POST', $url, array(
            'body' => is_array($data) ? $data : array(),
        ));

        // Pass false so HTTP error status codes do not throw here, letting us read
        // the error body and surface the service message like the old client did.
        $status = $response->getStatusCode();
        $body = $response->getContent(false);
        $restResultAsObject = json_decode($body);

        if ($status != 200 && $status != 201) {
            $message = isset($restResultAsObject->message) ? $restResultAsObject->message : $body;
            throw new Exception($message);
        }

        return $restResultAsObject;
    }


    /**
     * Notify cobby about magento changes
     *
     * @param $objectType
     * @param $method
     * @param $objectIds
     * @throws Exception
     */
    public function notifyCobbyService($objectType, $method, $objectIds)
    {
        $request = $this->createCobbyRequest();
        if ($request['LicenseKey'] != '') {
            $request['ObjectType'] = $objectType;
            $request['ObjectId'] = $objectIds;
            $request['Method'] = $method;

            try {
                $this->restPost('notify', $request);
            } catch (Exception $e) { // Zend_Http_Client_Adapter_Exception
                if ($e->getCode() != 1000) { //Timeout
//                    throw $e; //TODO: throw
                }
            }
        }
    }
}
