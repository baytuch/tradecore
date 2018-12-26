<?php

define('CONFIG', 'config.json');

require_once('db.php');
require_once('BitfinexAPI.php');

class tradecore {

  private $config = NULL;
  private $db = NULL;
  private $bitfinex = NULL;

  function __construct(){
    $this->config = json_decode(file_get_contents(CONFIG), true);
    $this->db = new db($this->config['db']['host'], $this->config['db']['name'], $this->config['db']['user'], $this->config['db']['pass']);
    $this->bitfinex = new BitfinexAPI($this->config['name'], $this->config['ver']);
  }

  private function update(){
    $orders = $this->bitfinex->getTicker();
    if ($orders['status']){
      $data['service_id'] = 1;
      $data['bid'] = $orders['data']['bid'];
      $data['ask'] = $orders['data']['ask'];
      $data['low'] = $orders['data']['low'];
      $data['high'] = $orders['data']['high'];
      $data['volume'] = $orders['data']['volume'];
      $data['last_price'] = $orders['data']['last_price'];
      $this->db->insert($data);
    }
  }

  private function MarketsUpdate(){
    $orders = $this->db->getMarkets(time() - $this->config['calc']['step'], time());
    $n = 0;
    if ($orders['status']){
      foreach ($orders['orders'] as $order){
        $bid_array[] = $order['bid'];
        $ask_array[] = $order['ask'];
        $low_array[] = $order['low'];
        $high_array[] = $order['high'];
        $volume_array[] = $order['volume'];
        $last_price_array[] = $order['last_price'];
        $n++;
      }
      if ($n > 0){
        $bid = array_sum($bid_array) / $n;
        $ask = array_sum($ask_array) / $n;
        $low = array_sum($low_array) / $n;
        $high = array_sum($high_array) / $n;
        $volume = array_sum($volume_array) / $n;
        $last_price = array_sum($last_price_array) / $n;
        $data['service_id'] = 0;
        $data['bid'] = $bid;
        $data['ask'] = $ask;
        $data['low'] = $low;
        $data['high'] = $high;
        $data['volume'] = $volume;
        $data['last_price'] = $last_price;
        $this->db->insert($data);
      }
    }
  }

  private function genChart(){
    $orders = $this->db->getMarkets(time() - $this->config['calc']['chart_period'], time(), 0);
    $n = 0;
    $high = 0;
    $open_price = 0;
    $close_price = 0;
    $low = PHP_INT_MAX;
    $time = 0;
    $time_last = 0;
    $chart['status'] = false;
    $chart['data'] = '';
    $chart_data = array();
    $chart_data_n = 0;
    if ($orders['status']){
      foreach ($orders['orders'] as $order){
        $time = ceil($order['time'] / $this->config['calc']['chart_step']) * $this->config['calc']['chart_step'];
        if ($time_last == 0) $time_last = $time;
        if ($time == $time_last){
          if ($high < $order['last_price']) $high = $order['last_price'];
          if ($low > $order['last_price']) $low = $order['last_price'];
          if ($open_price == 0) $open_price = $order['last_price'];
          $close_price = $order['last_price'];
        } else {
          $chart_data[$chart_data_n]['high'] = $high;
          $chart_data[$chart_data_n]['open_price'] = $open_price;
          $chart_data[$chart_data_n]['close_price'] = $close_price;
          $chart_data[$chart_data_n]['low'] = $low;
          //$chart_data[$chart_data_n]['time'] = date("Y-m-d H:i:s", $time_last);
          $chart_data[$chart_data_n]['time'] = $time_last;
          $time_last = $time;
          $high = 0;
          $open_price = 0;
          $close_price = 0;
          $low = PHP_INT_MAX;
          $chart_data_n++;
        }		  
      }
      if ($chart_data_n > 0){
        $chart['data'] = $chart_data;
        $chart['status'] = true;
      }
    }
    return $chart;
  }

  private function showChart(){
    $this->db->connect();
    $data = $this->genChart();
    $result['data'] = json_encode($data);
    $this->db->close();
    $result['content_type'] = 'Content-Type: text/json; charset=utf-8';
    return $result;
  }

  public function cron(){
    sleep(5);
    $this->db->connect();
    $this->update();
    $this->MarketsUpdate();
    $this->genChart();
    //$this->db->install();
    $this->db->close();
  }

  public function api(){
    $action = '';
    $data = array();
    $data['content_type'] = 'Content-Type: text/plain; charset=utf-8';
    $data['result'] = 'The action is not defined. Request execution failed.';
    if (isset($_GET['action'])) $action = $_GET['action'];
    switch ($action){
      case '':
      case 'charts':
        $action_result = $this->showChart();
        $data['content_type'] = $action_result['content_type'];
        $data['result'] = $action_result['data'];
        break;
    }
    header('Access-Control-Allow-Origin: *');
    header($data['content_type']);
    echo $data['result'];
  }
  
}

?>