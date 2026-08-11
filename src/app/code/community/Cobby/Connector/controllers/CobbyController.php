<?php
/*
 * Copyright 2013 mash2 GbR https://www.cobby.io
 *
 * ATTRIBUTION NOTICE
 * Parts of this work are adapted from Branko Ajzele
 * Original title Inchoo_Api
 * The work can be found http://ext.2magento.com/Inchoo_Api.html
 *
 * ORIGINAL COPYRIGHT INFO
 *
 * author      Branko Ajzele, ajzele@gmail.com
 * category    Inchoo
 * package     Inchoo_Api
 * copyright   Copyright (c) Inchoo LLC (http://inchoo.net)
 * license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

/**
 * Json api controller
 */
class Cobby_Connector_CobbyController extends Mage_Api_Controller_Action
{
    /**
     * Access xml-rpc api as json
     */
    #[\Maho\Config\Route('/api/cobby/json', name: 'api.cobby.json')]
    public function jsonAction()
    {
        $this->_getServer()
            ->init($this, 'cobby_connector_json', 'cobby_connector_json')
            ->run();
    }
}
