<?php namespace Academe\SagePay\Psr7\ServerRequest;

/**
 * The ACS POST response that the issuing bank’s Access Control System (ACS)
 * or their agent sends the user back with.
 * This will include the optional MD for finding the transaction again, and the hashed
 * PaRes result that is then sent to Sage Pay to complete the transaction.
 */

use Academe\SagePay\Psr7\Helper;
use Academe\SagePay\Psr7\ServerRequest\AbstractServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class Secure3DAcs extends AbstractServerRequest
{
    protected $cRes;
    protected $threeDSSessionData;
    protected $PaRes;
    protected $MD;

    /**
     * @param ServerRequestInterface  $message The 3DSecure resource callback from Sage Pay.
     */
    public function __construct(ServerRequestInterface $message = null)
    {
        if (isset($message)) {
            $this->setData($this->parseBody($message));
        }
    }

    /**
     * @param array|object $data The 3DSecure resource callback from Sage Pay; $_POST will work here.
     * @return $this
     */
    protected function setData($data)
    {
        $this->cRes = Helper::dataGet($data, 'cres', null);
        $this->threeDSSessionData = Helper::dataGet($data, 'threeDSSessionData', null);
        $this->PaRes = Helper::dataGet($data, 'PaRes', null);
        $this->MD = Helper::dataGet($data, 'MD', null);
        return $this;
    }

    /**
     * Only needed for debugging or logging.
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'cRes' => $this->getCRes(),
            'threeDSSessionData' => $this->getThreeDSSessionData(),
            'PaRes' => $this->getPaRes(),
            'MD' => $this->getMD(),
        ];
    }

    /**
     * @return string The optional Merchant Data (MD) to identify the transaction.
     */
    public function getMD()
    {
        return $this->MD;
    }

    /**
     * @return string The encrypted 3DSecure result (PaRes) to pass on to Sage Pay for validation.
     */
    public function getPaRes()
    {
        return $this->PaRes;
    }

    /**
     * @return string The encrypted 3DSecure result (cRes) to pass on to Sage Pay for validation.
     */
    public function getCRes()
    {
        return $this->cRes;
    }

    public function getThreeDSSessionData()
    {
        return $this->threeDSSessionData;
    }

    /**
     * Determine if this message is a valid 3D Secure ACS server request.
     * @return boolean
     */
    public function isValid()
    {
        if (!empty($this->getCRes())) {
            return true;
        }

        // If paRes is set, then this is [likely to be] the user returning from
        // the bank's 3D Secure password entry.
        return ! empty($this->getPaRes());
    }

    /**
     * Determine whether this message is active, i.e. has been sent to the application.
     * $data will be $request->getBody() for most implementations.
     *
     * @param array|object $data The ServerRequest body data.
     */
    public static function isRequest($data)
    {
        if (!empty(Helper::dataGet($data, 'cres'))) {
            return true;
        }

        return ! empty(Helper::dataGet($data, 'PaRes'));
    }
}
