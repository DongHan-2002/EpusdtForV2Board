<?php

namespace App\Payments;

class Epusdt {
    public function __construct($config) {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'url' => [
                'label' => 'API接口的网址(包含最后的斜杠)',
                'description' => '',
                'type' => 'input',
            ],
            'key' => [
                'label' => 'API KEY',
                'description' => '输入设置的Key',
                'type' => 'input',
            ],
        ];
    }

    public function pay($order) {
        
        
        $param = [
            'trade_type'   => 'tron.trx',
            'amount' => $order['total_amount'] / 100,
            'order_id' => $order['trade_no'],
            'notify_url' => $order['notify_url'],
            'redirect_url' => $order['return_url'],
        ];
        
        $param['signature'] = self::generateSignature($param, $this->config['key']);
        $params_string = @json_encode($param);
        
        $request = self::_curlPost($this->config['url'] . 'api/v1/order/create-transaction', $params_string);
        $ret = @json_decode($request, true);
        
        if ($ret['status_code'] != 200) {
            abort(500, $ret['message']);
        }
        return [
            'type' => 1,
            'data' => $ret['data']['payment_url'],
        ];
       // return $payEntity;
    }
    
    public function notify($params)
    {
        
        $payload = trim(file_get_contents('php://input'));
        $data = json_decode($payload, true);
        $sign = $data['signature'];
        unset($data['signature']);
        $generateSignature = self::generateSignature($data, $this->config['key']);
        if($sign != $generateSignature){
            return false;
        }
        if($data['status'] != 2){
            return false;
        }
        return [
            'trade_no' => $data['order_id'],
            'callback_no' => $data['trade_id'],
        ];
    }
    
    private function _curlPost($url,$params=false){
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    /**
     * 生成签名
     * @param array $data
     * @param string $key
     * @return string
     */
    private function generateSignature(array $data, string $key): string
    {
        ksort($data);
        $sign = '';
        foreach ($data as $k => $v) {
            if ($v == '') continue;
            $sign .= $k . '=' . $v . '&';
        }
        $sign = trim($sign, '&');
        return md5($sign . $key);
    }
}