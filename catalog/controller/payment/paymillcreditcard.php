<?php
require_once dirname(dirname(dirname(dirname(__FILE__)))).'/paymill/catalog/controller/paymill.php';

class ControllerPaymentPaymillcreditcard extends ControllerPaymentPaymill
{
    protected function getPaymentName()
    {
        return 'paymillcreditcard';
    }

    protected function getDatabaseName()
    {
        return 'paymill_cc_userdata';
    }
}
