<?php

namespace PayPal\Test\Functional\Api;

use PayPal\Api\Amount;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Refund;
use PayPal\Api\Sale;
use PayPal\Common\PayPalModel;
use PayPal\Rest\ApiContext;
use PayPal\Rest\IResource;
use PayPal\Api\CreateProfileResponse;
use PayPal\Test\Functional\Setup;
use PayPal\Transport\PayPalRestCall;
use PayPal\Api\WebProfile;

/**
 * Class WebProfile
 *
 * @package PayPal\Test\Api
 */
class PaymentsFunctionalTest extends \PHPUnit_Framework_TestCase
{

    public $operation;

    public $response;

    public $mockPayPalRestCall;

    public function setUp()
    {
        $className = $this->getClassName();
        $testName = $this->getName();
        $operationString = file_get_contents(__DIR__ . "/../resources/$className/$testName.json");
        $this->operation = json_decode($operationString, true);
        $this->response = true;
        if (array_key_exists('body', $this->operation['response'])) {
            $this->response = json_encode($this->operation['response']['body']);
        }
        Setup::SetUpForFunctionalTests($this);
    }

    /**
     * Returns just the classname of the test you are executing. It removes the namespaces.
     * @return string
     */
    public function getClassName()
    {
        return join('', array_slice(explode('\\', get_class($this)), -1));
    }

    public function testCreate()
    {
        $request = $this->operation['request']['body'];
        $obj = new Payment($request);
        $result = $obj->create(null, $this->mockPayPalRestCall);
        $this->assertNotNull($result);
        return $result;
    }

    public function testCreateWallet()
    {
        $request = $this->operation['request']['body'];
        $obj = new Payment($request);
        $result = $obj->create(null, $this->mockPayPalRestCall);
        $this->assertNotNull($result);
        return $result;
    }

    /**
     * @depends testCreate
     * @param $payment Payment
     * @return Payment
     */
    public function testGet($payment)
    {
        $result = Payment::get($payment->getId(), null, $this->mockPayPalRestCall);
        $this->assertNotNull($result);
        $this->assertEquals($payment->getId(), $result->getId());
        $this->assertEquals($payment, $result, "", 0, 10, true);
        return $result;
    }

    /**
     * @depends testGet
     * @param $payment Payment
     * @return Sale
     */
    public function testGetSale($payment)
    {
        $transactions = $payment->getTransactions();
        $transaction = $transactions[0];
        $relatedResources = $transaction->getRelatedResources();
        $resource = $relatedResources[0];
        $result = Sale::get($resource->getSale()->getId(), null, $this->mockPayPalRestCall);
        $this->assertNotNull($result);
        $this->assertEquals($resource->getSale()->getId(), $result->getId());
        return $result;
    }

    /**
     * @depends testGetSale
     * @param $sale Sale
     * @return Sale
     */
    public function testRefundSale($sale)
    {
        $refund = new Refund($this->operation['request']['body']);
        $result = $sale->refund($refund, null, $this->mockPayPalRestCall);
        $this->assertNotNull($result);
        $this->assertEquals('completed', $result->getState());
        $this->assertEquals($sale->getId(), $result->getSaleId());
        $this->assertEquals($sale->getParentPayment(), $result->getParentPayment());
    }

    /**
     * @depends testGet
     * @param $payment Payment
     * @return Payment
     */
    public function testExecute($payment)
    {
        if (Setup::$mode == 'sandbox') {
            $this->markTestSkipped('Not executable on sandbox environment. Needs human interaction');
        }
    }
}
