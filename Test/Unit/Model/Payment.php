<?php
namespace Liftmode\PMCCoinGroup\Test\Unit\Model;
 
class Payment extends \PHPUnit\Framework\TestCase
{
    protected $_objectManager;
    protected $_model;
    /**
     * This function is called before the test runs.
     * Ideal for setting the values to variables or objects.
     */
    protected function setUp()
    {
        $this->_objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->_model = $this->_objectManager->getObject("Liftmode\PMCCoinGroup\Model\Payment");
    }

    /**
     * this function tests the result of the addition of two numbers
     *
     */
    public function testExtraHeaders()
    {
        $result = $this->_model->_getExtraHeaders();
        $this->assertEquals('Accept: application/json', $result[0]);
    }
}