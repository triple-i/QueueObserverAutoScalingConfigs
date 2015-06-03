<?php


namespace Qoasc\Mock;

abstract class MockTestCase extends \PHPUnit_Framework_TestCase
{

    /**
     * インターフェイス
     *
     * @var array
     **/
    private $methods = array(
        'getAccessKeyId',
        'getSecretKey',
        'getSecurityToken',
        'getExpiration',
        'setAccessKeyId',
        'setSecretKey',
        'setSecurityToken',
        'setExpiration',
        'isExpired'
    );


    /**
     * コンストラクタ引数のモックを取得する
     *
     * @return array
     **/
    private function _getConstructArguments ()
    {
        $arguments = [
            $this->_getCredentialsInterfaceMock(),
            $this->_getSignatureInterfaceMock(),
            $this->_getCollectionMock()
        ];

        return $arguments;
    }


    /**
     * @return Collection
     **/
    private function _getCollectionMock ()
    {
        return $this->getMock('Guzzle\Common\Collection');
    }


    /**
     * @return CredentialsInterface
     **/
    private function _getCredentialsInterfaceMock ()
    {
        return $this->getMock('Aws\Common\Credentials\CredentialsInterface');
    }


    /**
     * @return EntityBody
     **/
    public function _getEntityBodyMock ()
    {
        $stream = 'stream';

        return $this->getMock('Guzzle\Http\EntityBody', [
        ], [$stream]);
    }


    /**
     * @return SignatureInterface
     **/
    private function _getSignatureInterfaceMock ()
    {
        return $this->getMock('Aws\Common\Signature\SignatureInterface');
    }


    /**
     * @return AutoScalingClient_Mock
     **/
    public function getAutoScalingMock ()
    {
        $arguments = $this->_getConstructArguments();
        $methods   = array_merge($this->methods, [
            'createLaunchConfiguration', 'describeLaunchConfigurations',
            'updateAutoScalingGroup', 'putScheduledUpdateGroupAction'
        ]);

        return $this->getMock('Aws\AutoScaling\AutoScalingClient', $methods, $arguments);
    }


    /**
     * @return Ec2Client_Mock
     **/
    public function getEc2Mock ()
    {
        $arguments = $this->_getConstructArguments();
        $methods   = array_merge($this->methods, [
            'describeImages'
        ]);

        return $this->getMock('Aws\Ec2\Ec2Client', $methods, $arguments);
    }


    /**
     * @return Guzzle\Service\Resource\Model
     **/
    public function getGuzzleModelMock ()
    {
        return $this->getMock('Guzzle\Service\Resource\Model', [
            'get', 'getPath'
        ]);
    }
}

