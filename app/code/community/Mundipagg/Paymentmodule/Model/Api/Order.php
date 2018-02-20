<?php
/**
 * This class is a Wrapper to the MundiPagg SDK
 *
 * @package Mundipagg/Paymentmodule
 */

require_once Mage::getBaseDir('lib') . '/autoload.php';

use MundiAPILib\MundiAPIClient;

class Mundipagg_Paymentmodule_Model_Api_Order
{
    public function createBoletoPayment(Varien_Object $paymentInformation)
    {
        $boleto = Mage::getModel('paymentmodule/api_boleto');
        $orderRequest = $boleto->getCreateOrderRequest($paymentInformation);
        $orderController = $this->getOrderController();

        $helperLog = Mage::helper('paymentmodule/log');
        $helperLog->info("Request");
        $helperLog->info(json_encode($orderRequest,JSON_PRETTY_PRINT));
        try {
            $response = $orderController->createOrder($orderRequest);
            $helperLog->info("Response");
            $helperLog->info(json_encode($response,JSON_PRETTY_PRINT));
            return $response;
        } catch (\Exception $e) {
            $helperLog->error("Exception: " . $e->getMessage());
            $helperLog->error(json_encode($e->errors,JSON_PRETTY_PRINT));
            return $e->getMessage();
        }
    }

    public function createCreditCardPayment(Varien_Object $paymentInformation)
    {
        $creditCard = Mage::getModel('paymentmodule/api_creditcard');
        $orderRequest = $creditCard->getCreateOrderRequest($paymentInformation);
        $orderController = $this->getOrderController();

        $helperLog = Mage::helper('paymentmodule/log');
        $helperLog->info("Request");
        $helperLog->info(json_encode($orderRequest,JSON_PRETTY_PRINT));

        try {
            $response = $orderController->createOrder($orderRequest);
            $helperLog->info("Response");
            $helperLog->info(json_encode($response,JSON_PRETTY_PRINT));
            return $response;
        } catch (\Exception $e) {
            $helperLog->error("Exception: " . $e->getMessage());
            $helperLog->error(json_encode($e->errors,JSON_PRETTY_PRINT));
            return $e->getMessage();
        }
    }

    private function getOrderController()
    {
        $client = $this->getMundiPaggApiClient();

        return $client->getOrders();
    }

    private function getMundiPaggApiClient()
    {
        $generalConfig = Mage::getModel('paymentmodule/config_general');

        $secretKey = $generalConfig->getSecretKey();
        $password = $generalConfig->getPassword();

        return new MundiAPIClient($secretKey, $password);
    }
}