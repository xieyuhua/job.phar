<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Kcloze\Jobs\Message;

use Kcloze\Jobs\Config;
use Kcloze\Jobs\Logs;
use Kcloze\Jobs\Utils;

class DingMessage
{
    private $apiUrl='https://oapi.dingtalk.com/robot/send';

    public function init()
    {
        $this->logger  = Logs::getLogger(Config::getConfig()['logPath'] ?? '', Config::getConfig()['logSaveFileApp'] ?? '', Config::getConfig()['system'] ?? '');
    }

    public function request_by_curl($remote_server, $post_string)
        {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $remote_server);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // 不用开启curl证书验证
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $data = curl_exec($ch);
            //$info = curl_getinfo($ch);
            //var_dump($info);
            curl_close($ch);
            return $data;
        }

    public function send(string $content, string $token)
    {
        $this->init();
        if (!$token || !$content) {
            return false;
        }
        try {
            $apiUrl       = $this->apiUrl . '?access_token=' . $token;
            // text类型
            $textString = json_encode([
                'msgtype' => 'text',
                'text' => [
                    "content" => $content
                ],
                'at' => [
                    'atMobiles' => [
                    ],
                    'isAtAll' => false
                ]
            ]);
            $res          = $this->request_by_curl($apiUrl, $textString);
        } catch (\Throwable $e) {
            Utils::catchError($this->logger, $e);
        } catch (\Exception $e) {
            Utils::catchError($this->logger, $e);
        }

        $this->logger->log('[钉钉接口]请求自定义机器人消息接口,请求地址：' . json_encode($apiUrl) . ',请求参数:' . json_encode($message) . ',返回结果:' . $res['errmsg'] . '  httpcode: ' . $res['errcode'], 'info');

        return $res['errmsg'];
    }
}
