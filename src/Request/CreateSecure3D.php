<?php

namespace Academe\SagePay\Psr7\Request;

/**
 * The 3DSecure request sent to Sage Pay, after the user is returned
 * from entering their 3D Secure authentication details.
 * Creates a 3D Secure object and returns the status.
 * See https://test.sagepay.com/documentation/#3-d-secure
 */

use Academe\SagePay\Psr7\Model\Auth;
use Academe\SagePay\Psr7\Model\Endpoint;
use Academe\SagePay\Psr7\ServerRequest\Secure3DAcs;

class CreateSecure3D extends AbstractRequest
{
    protected $cRes;
    protected $threeDSSessionData;
    protected $paRes;
    protected $transactionId;

    protected $resource_path = ['transactions', '{transactionId}', '3d-secure'];

    /**
     * @param Endpoint $endpoint
     * @param Auth $auth
     * @param string|Secure3DAcsResponse $paRes The PA Result returned by the user's bank (or their agent)
     * @param string $transactionId The ID that Sage Pay gave to the transaction in its intial response
     */
    public function __construct(Endpoint $endpoint, Auth $auth, $paRes, $transactionId)
    {
        $this->setEndpoint($endpoint);
        $this->setAuth($auth);

        if ($paRes instanceof Secure3DAcs) {
            if (!empty($paRes->getCRes())) {
                $this->resource_path = ['transactions', '{transactionId}', '3d-secure-challenge'];
                $this->cRes = $paRes->getCRes();
                $this->threeDSSessionData = $paRes->getThreeDSSessionData();
            } else {
                $this->paRes = $paRes->getPaRes();
            }
        } else {
            $this->paRes = $paRes;
        }

        $this->transactionId = $transactionId;
    }

    /**
     * Get the message body data for serializing.
     * @return array
     */
    public function jsonSerialize()
    {
        if (!empty($this->cRes)) {
            return [
                'cRes' => $this->getCRes(),
                'threeDSSessionData' => $this->getThreeDSSessionData(),
            ];
        }

        return [
            'paRes' => $this->getPaRes(),
        ];
    }

    /**
     * @return Secure3DAcsResponse|string
     */
    public function getPaRes()
    {
        return $this->paRes;
    }

    /**
     * @return Secure3DAcsResponse|string
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
     * Getter used to construct the URL.
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }
}
