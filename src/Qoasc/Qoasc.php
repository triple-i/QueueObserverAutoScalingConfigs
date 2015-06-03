<?php


namespace Qoasc;

class Qoasc
{

    /**
     * @var Ec2Client
     **/
    private $ec2_client;


    /**
     * @var AutoScalingClient
     **/
    private $as_client;


    /**
     * @param  Ec2Client $ec2_client
     * @return void
     **/
    public function setEc2Client (\Aws\Ec2\Ec2Client $ec2_client)
    {
        $this->ec2_client = $ec2_client;
    }


    /**
     * @param  AutoScalingClient $as_client
     * @return void
     **/
    public function setAutoScalingClient (\Aws\AutoScaling\AutoScalingClient $as_client)
    {
        $this->as_client = $as_client;
    }


    /**
     * @return void
     **/
    public function execute ()
    {
        try {
            $this->_validateParameters();

            $ami_id = $this->_getLatestTripleiCoreAmiId();
            $config_name = $this->_createLaunghConfiguration($ami_id);
            $this->_updateAutoScalingGroup($config_name);
            $this->_updateScheduledActions();

        } catch (\Exception $e) {
            throw $e;
        }

        return true;
    }


    /**
     * @return void
     **/
    private function _validateParameters ()
    {
        if (is_null($this->ec2_client)) {
            throw new \Exception('Ec2Clientクラスが指定されていません');
        }

        if (is_null($this->as_client)) {
            throw new \Exception('AutoScalingClientクラスが指定されていません');
        }
    }


    /**
     * 最新の TripleI/Core AMIイメージIDを取得する
     *
     * @return string
     **/
    private function _getLatestTripleiCoreAmiId ()
    {
        $results = $this->ec2_client->describeImages([
            'Filters' => [[
                'Name'   => 'tag:Name',
                'Values' => ['TripleI/Core']
            ]]
        ]);

        $images = [];
        foreach ($results->get('Images') as $result) {
            $img_id = $result['ImageId'];
            $timestamp = str_replace('TripleI/Core ', '', $result['Name']);

            $images[$timestamp] = $img_id;
        }

        krsort($images);
        $image = reset($images);

        return $image;
    }


    /**
     * @param  string $ami_id
     * @return string
     **/
    private function _createLaunghConfiguration ($ami_id)
    {
        $config_name = $this->_getLaunchConfigurationName();
        $user_data   = file_get_contents(LIB.'/Library/TripleI.ServerConfigs/queue-observer/user-data/production.sh');
        $user_data = base64_encode($user_data);

        $this->as_client->createLaunchConfiguration([
            'LaunchConfigurationName' => $config_name,
            'ImageId' => $ami_id,
            'KeyName' => 'a_1',
            'UserData' => $user_data,
            'SecurityGroups' => ['QueueService'],
            'InstanceType' => \Aws\Ec2\Enum\InstanceType::T1_MICRO
        ]);

        return $config_name;
    }


    /**
     * 生成する LaunchConfiguration の名前を取得する
     *
     * @return string
     **/
    private function _getLaunchConfigurationName ()
    {
        $configs   = $this->as_client->describeLaunchConfigurations();
        $base_name = 'QueueObserverConfigVer';
        $versions  = [];

        foreach ($configs->get('LaunchConfigurations') as $config) {
            $name = $config['LaunchConfigurationName'];
            if (preg_match('/'.$base_name.'([0-9]+)/', $name, $matches)) {
                $versions[] = $matches[1];
            }
        }

        // 配列の昇順化
        asort($versions);

        // LaunchConfiguration が三つ以上存在した場合は一番古いものを削除する
        if (count($versions) >= 3) {
            $config_name = $base_name.array_shift($versions);
            $this->as_client->deleteLaunchConfiguration([
                'LaunchConfigurationName' => $config_name
            ]);
        }

        // 生成したいLaunchConfiguration名を作る
        if (count($versions) == 0) {
            $config_name = $base_name.'1';
        } else {
            $ver = intval(array_pop($versions));
            $config_name = $base_name.strval($ver + 1);
        }

        return $config_name;
    }


    /**
     * @param  string $config_name
     * @return void
     **/
    private function _updateAutoScalingGroup ($config_name)
    {
        $groups = $this->as_client->describeAutoScalingGroups([
            'AutoScalingGroupNames' => ['QueueObserverAutoScalingGroup']
        ]);

        $this->as_client->UpdateAutoScalingGroup([
            'AutoScalingGroupName' => 'QueueObserverAutoScalingGroup',
            'LaunchConfigurationName' => $config_name,
            'MinSize' => $groups->get('AutoScalingGroups')[0]['MinSize'],
            'MaxSize' => $groups->get('AutoScalingGroups')[0]['MaxSize'],
            'AvailabilityZones' => [\Aws\Common\Enum\Region::AP_NORTHEAST_1.'a'],
        ]);
    }


    /**
     * @return void
     **/
    private function _updateScheduledActions ()
    {
        // スケールアウト用スケジュール
        $this->as_client->putScheduledUpdateGroupAction([
            'AutoScalingGroupName' => 'QueueObserverAutoScalingGroup',
            'ScheduledActionName' => 'QueueObserverScaleOutScheduledAction',
            'Recurrence' => '00 22 * * 0-5',
            'MinSize' => 1,
            'MaxSize' => 1
        ]);

        // スケールイン用スケジュール
        $this->as_client->putScheduledUpdateGroupAction([
            'AutoScalingGroupName' => 'QueueObserverAutoScalingGroup',
            'ScheduledActionName' => 'QueueObserverScaleInScheduledAction',
            'Recurrence' => '00 14 * * 0-6',
            'MinSize' => 0,
            'MaxSize' => 0
        ]);
    }

}

