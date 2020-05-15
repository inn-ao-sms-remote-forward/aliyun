<?php

declare(strict_types = 1);

namespace InnStudio\AliyunSms;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ServerException;

class App
{
    private $accessKeyId = '';

    private $accessSecret = '';

    private $SignName = '';

    private $TemplateCode = '';

    private $phoneNumber = 0;

    private $verificationCode = 0;

    private $configPath = '';

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;

        $this->setVerificationCode();
        $this->setPhoneNumber();
        $this->setConfig();

        if ($this->send()) {
            die(\json_encode([
                'code' => 0,
            ]));
        }

        die(\json_encode([
            'code' => 1,
        ]));
    }

    private function setVerificationCode(): void
    {
        $this->verificationCode = (int) \filter_input(\INPUT_GET, 'code', \FILTER_VALIDATE_INT);

        if ( ! $this->verificationCode) {
            die('Invalid verification code.');
        }
    }

    private function setPhoneNumber(): void
    {
        $this->phoneNumber = (int) \filter_input(\INPUT_GET, 'number', \FILTER_VALIDATE_INT);

        if ( ! $this->phoneNumber) {
            die('Invalid phone numbe');
        }
    }

    private function send(): bool
    {
        // Download：https://github.com/aliyun/openapi-sdk-php
        // Usage：https://github.com/aliyun/openapi-sdk-php/blob/master/README.md

        AlibabaCloud::accessKeyClient($this->accessKeyId, $this->accessSecret)
            ->regionId('cn-hangzhou') // replace regionId as you need
            ->asDefaultClient();

        try {
            $res = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId'      => 'cn-hangzhou',
                        'PhoneNumbers'  => $this->phoneNumber,
                        'SignName'      => $this->SignName,
                        'TemplateCode'  => $this->TemplateCode,
                        'TemplateParam' => \json_encode([
                            'code' => $this->verificationCode,
                        ]),
                    ],
                ])
                ->request();
            $data = $res->toArray();

            if ('OK' === ($data['code'])) {
                return true;
            }

            \error_log(\json_encode($data, \JSON_UNESCAPED_UNICODE));

            return false;
        } catch (ServerException $e) {
            \error_log($e->getErrorMessage());

            return false;
        }
    }

    private function setConfig(): void
    {
        if ( ! \is_readable($this->configPath)) {
            die('Invalid config file path.');
        }

        $config = \json_decode((string) \file_get_contents($this->configPath), true);

        if ( ! \is_array($config)) {
            die('Invalid config file content.');
        }

        [
            'accessKeyId'  => $this->accessKeyId,
            'accessSecret' => $this->accessSecret,
            'SignName'     => $this->SignName,
            'TemplateCode' => $this->TemplateCode,
        ] = $config;
    }
}
