<?php

class BitfinexAPI {

  const API_TIMER = 50000;
  const API_TIMEOUT = 15;
  const API_URL = 'https://api.bitfinex.com/v1/pubticker/btcusd';
  const API_DEFAULT_NAME = 'tradecore';
  const API_DEFAULT_VER = '1.0';
  const BOT_INFO = 'http://www.it-hobby.km.ua';
  private $app_name = '';
  private $app_ver = '';
  private $http_headers = array();

  function __construct($app_name = '', $app_ver = ''){
    if ($app_name != '' and $app_name != ''){
      $this->app_name = $app_name;
      $this->app_ver = $app_ver;
    } else {
      $this->app_name = self::API_DEFAULT_NAME;
      $this->app_ver = self::API_DEFAULT_VER;
    }
    $this->http_headers[] = 'Content-Type: application/json; charset=utf-8';
    $this->http_headers[] = 'User-Agent: ' . ucfirst(strtolower($this->app_name)) . '/' . $this->app_ver . ' (+' . self::BOT_INFO . ')';
  }

  private function apiCall(){
    $result = array();
    $ch = curl_init();
    $res = '';
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->http_headers);
    curl_setopt($ch, CURLOPT_URL, self::API_URL);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, self::API_TIMEOUT);
    usleep(self::API_TIMER);
    $res = curl_exec($ch);
    $result['status'] = false;
    $result['data'] = '';
    if(curl_errno($ch) == 0){
      if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200){
        $json_obj = json_decode($res, true);
        if ($json_obj != NULL){
          $result['status'] = true;
          $result['data'] = $json_obj;
        }
      }
    }
    curl_close($ch);
    return $result;
  }

  public function getTicker(){
    $result = array('status' => false,
                    'data' => array('bid' => 0, 'ask' => 0,
                                    'low' => 0, 'high' => 0,
                                    'volume' => 0, 'last_price' => 0)
    );
    $data = $this->apiCall();
    $bid = 0;
    $ask = 0;
    $low = 0;
    $high = 0;
    $volume = 0;
    $last_price = 0;
    if ($data['status']){
      if (isset($data['data']['bid']) and isset($data['data']['ask']) and
          isset($data['data']['low']) and isset($data['data']['high']) and
          isset($data['data']['volume']) and isset($data['data']['last_price'])){
		$bid = floatval($data['data']['bid']);
        $ask = floatval($data['data']['ask']);
        $low = floatval($data['data']['low']);
        $high = floatval($data['data']['high']);
        $volume = floatval($data['data']['volume']);
        $last_price = floatval($data['data']['last_price']);
        if ($bid > 0 and $ask > 0 and $low > 0 and $high > 0 and $volume > 0 and $last_price > 0){
          $result['data']['bid'] = $bid;
          $result['data']['ask'] = $ask;
          $result['data']['low'] = $low;
          $result['data']['high'] = $high;
          $result['data']['volume'] = $volume;
          $result['data']['last_price'] = $last_price;
          $result['status'] = true;
        }
      }
    }
    return $result;
  }

}

?>