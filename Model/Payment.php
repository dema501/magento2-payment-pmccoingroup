<?php
/**
 *
 * @category   Liftmode
 * @package    PMCCoinGroup
 * @copyright  Copyright (c) Dmitry Bashlov <dema50@gmail.com
 * @license    MIT
 */

namespace Liftmode\PMCCoinGroup\Model;

class Payment extends \Magento\Payment\Model\Method\Cc
{
    const CODE = 'pmccoingroup';

    const API_URL = 'payment/' . self::CODE . '/apiurl';
    const API_TOKEN  = 'payment/' . self::CODE . '/token';
    const API_TEAMID  = 'payment/' . self::CODE . '/team_id';
    const API_WALLETID  = 'payment/' . self::CODE . '/wallet_id';

    protected $_code = self::CODE;

    protected $_canAuthorize = true;
    protected $_canCapture = false;
    protected $_canRefund = false;
    protected $_canVoid = false;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $_curl;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\HTTP\Client\Curl $curl,
        array $data = []
    ) {
        $this->_scopeConfig      = $scopeConfig;
        $this->_curl             = $curl;
        $this->_encryptor        = $encryptor;

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null,
            $data
        );
    }


    /**
     * Authorize a payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $response = $this->_doSale($payment, $amount);

        if(isset($response['id'])) {            // Successful auth request.
            // Set the transaction id on the payment so the capture request knows auth has happened.
            $payment->setTransactionId($response['id']);
            $payment->setParentTransactionId($response['id']);
        }

        //processing is not done yet.
        $payment->setIsTransactionClosed(0);

        return $this;
    }


    /**
     * Set the payment action to authorize_and_capture
     *
     * @return string
     */
    private function _doSale(\Magento\Payment\Model\InfoInterface $payment, int $amount)
    {
        $order = $payment->getOrder();
        $billing = $order->getBillingAddress();

        $data = array(
            "amount" => (int) $amount * 100, // Yes Decimal Total cents amount with up to 2 decimal places.,
            "wallet_id" => $this->_scopeConfig->getValue(self::API_WALLETID),
            "order_id"=> $order->getIncrementId(),
        );

        $this->_curl->setOption(CURLOPT_HTTPHEADER, $this->_getExtraHeaders());

        $this->_curl->get($this->_scopeConfig->getValue(self::API_URL) . '/v1/customers' . '?' . http_build_query( array(
            'page' => 1,
            'perPage' => 3,
            'search' => strval($order->getCustomerEmail())
        )));

        $searchCustomerResData = json_decode($this->_curl->getBody(), TRUE);

        if (sizeof($searchCustomerResData["data"]) > 0 && $searchCustomerResData["data"][0]["email"] ===  strval($order->getCustomerEmail())) {
            $data["customer"] = array(
                "id" => $searchCustomerResData["data"][0]["id"]
            );
        } else {
            $state =  substr(strval($billing->getRegionCode()), 0, 3);
            if (empty($state)) {
                $state = "UNW";
            }

            $data["customer"] = array(
                "name"=> strval($billing->getFirstname()) . ' ' . strval($billing->getLastname()), // Yes String Account holder's first and last name
                "email"  => strval($order->getCustomerEmail()), // Yes String Customer's email address. Must be a valid address. Upon processing of the draft an email will be sent to this address.
                "phone" => strval($billing->getTelephone()),
                "address" => array(
                    "line1" => strval($billing->getStreetLine(1)),
                    "line2" => strval($billing->getStreetLine(2)),
                    "country" => strval($billing->getCountryId()),
                    "state" => $state,// Yes String The state portion of the mailing address associated with the customer's checking account. It must be a valid US state or territory
                    "city" => strval($billing->getCity()), // Yes String The city portion of the mailing address associated with the customer's checking
                    "zipcode" => substr(strval($billing->getPostcode()), 0, 5), // Yes String The zip code portion of the mailing address associated with the customer's checking account. Accepted formats: XXXXX,
                ),
                "card" => array(
                    "cardholder_name"=> strval($billing->getFirstname()) . ' ' . strval($billing->getLastname()), // Yes String Account holder's first and last name
                    "number" => $payment->getCcNumber(),
                    "cvc" => $payment->getCcCid(),
                    "expiration" => sprintf('%02d-%02d', $payment->getCcExpMonth(),  substr($payment->getCcExpYear(), -2)),
                    "default" => true,
                    "register" => true
                )
            );
        }


        // $this->debug  var_dump(array('_doPost--->', $this->_scopeConfig->getValue(self::API_URL) . '/v1/charges', json_encode($data)));
        $this->_curl->post($this->_scopeConfig->getValue(self::API_URL) . '/v1/charges', json_encode($data));

        return $this->_doValidate($this->_curl->getStatus(), json_decode($this->_curl->getBody(), TRUE), json_encode($data));
    }

    private function _doValidate($respStatus, $data)
    {
        if ((int) substr($respStatus, 0, 1) !== 2) {
            $message = $data['message'];
            foreach ($data['errors'] as $field => $error) {
                $message .= sprintf("\r\nthe issue is in %s field - %s\r\n", $field, array_shift($error));
            }

            // $this->debug(array('_doValidate--->', $respStatus, $message, $data));
            throw new \Magento\Framework\Exception\LocalizedException(__("Error during process payment: response: %s, %s", $respStatus, $message));
        }

        return $data;
    }

    private function _getExtraHeaders() {
        return array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Cache-Control: no-cache',
            'Authorization: ' . $this->_scopeConfig->getValue(self::API_TOKEN),
            'team-id: ' .  $this->_scopeConfig->getValue(self::API_TEAMID),
        );
    }
}
