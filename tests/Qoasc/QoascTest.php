<?php


use Qoasc\Qoasc;
use Qoasc\Mock\MockTestCase;

class QoascTest extends MockTestCase
{

    /**
     * @var Qoasc
     **/
    private $qoasc;


    /**
     * @var Ec2Client
     **/
    private $ec2_client;


    /**
     * @var AutoScalingClient
     **/
    private $as_client;


    /**
     * @return void
     **/
    public function setUp ()
    {
        $this->qoasc = new Qoasc();

        $this->ec2_client = $this->getEc2Mock();
        $this->as_client  = $this->getAutoScalingMock();
    }


    /**
     * @test
     * @expectedException          \Exception
     * @expectedExceptionMessage   Ec2Clientクラスが指定されていません
     * @group qoasc-not-set-ec2-client
     * @group qoasc
     **/
    public function Ec2Clientクラスが指定されていない場合 ()
    {
        $this->qoasc->execute();
    }


    /**
     * @test
     * @expectedException          \Exception
     * @expectedExceptionMessage   AutoScalingClientクラスが指定されていません
     * @group qoasc-not-set-as-client
     * @group qoasc
     **/
    public function AutoScalingClientクラスが指定されていない場合 ()
    {
        $this->qoasc->setEc2Client($this->ec2_client);
        $this->qoasc->execute();
    }


    /**
     * @test
     * @group qoasc-execute
     * @group qoasc
     **/
    public function 正常な処理 ()
    {
        $ami_results = [[
            'ImageId' => 'ami-123',
            'Name'    => 'TripleI/Core 201505'
        ], [
            'ImageId' => 'ami-456',
            'Name'    => 'TripleI/Core 201503'
        ]];

        $amis = $this->getGuzzleModelMock();
        $amis->expects($this->any())
            ->method('get')
            ->will($this->returnValue($ami_results));

        $this->ec2_client->expects($this->any())
            ->method('describeImages')
            ->will($this->returnValue($amis));

        $config_results = [[
            'LaunchConfigurationName' => 'hoge-configVer2'
        ], [
            'LaunchConfigurationName' => 'QueueObserverConfigVer1'
        ], [
            'LaunchConfigurationName' => 'QueueObserverConfigVer2'
        ]];

        $configs = $this->getGuzzleModelMock();
        $configs->expects($this->any())
            ->method('get')
            ->will($this->returnValue($config_results));

        $this->as_client->expects($this->any())
            ->method('describeLaunchConfigurations')
            ->will($this->returnValue($configs));

        $group_results = [[
            'MinSize' => 1,
            'MaxSize' => 1
        ]];

        $groups = $this->getGuzzleModelMock();
        $groups->expects($this->any())
            ->method('get')
            ->will($this->returnValue($group_results));

        $this->as_client->expects($this->any())
            ->method('describeAutoScalingGroups')
            ->will($this->returnValue($groups));

        $this->qoasc->setEc2Client($this->ec2_client);
        $this->qoasc->setAutoScalingClient($this->as_client);
        $result = $this->qoasc->execute();

        $this->assertTrue($result);
    }

}

