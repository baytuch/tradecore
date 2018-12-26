<?php

class db {

  private $host = '';
  private $name = '';
  private $user = '';
  private $pass = '';
  private $link = NULL;

  function __construct($host, $name, $user, $pass){
    $this->host = $host;
    $this->name = $name;
    $this->user = $user;
    $this->pass = $pass;
  }

  private function num_format($num){
    $res = number_format(round($num, 6), 6, '.', '');
    return $res;
  }

  public function connect(){
    $this->link = mysqli_connect($this->host, $this->user, $this->pass, $this->name);
    if ($this->link === false){
      echo 'Error: Unable to connect to MySQL!';
      exit;
    }
  }

  public function install(){
    $query = NULL;
    $sql_q = "";
    $sql_q  = "CREATE TABLE `markets` (";
    $sql_q .= " `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,";
    $sql_q .= " `service_id` TINYINT(2) UNSIGNED NOT NULL,";
    $sql_q .= " `bid` DECIMAL(12, 6) UNSIGNED NOT NULL,";
    $sql_q .= " `ask` DECIMAL(12, 6) UNSIGNED NOT NULL,";
    $sql_q .= " `low` DECIMAL(12, 6) UNSIGNED NOT NULL,";
    $sql_q .= " `high` DECIMAL(12, 6) UNSIGNED NOT NULL,";
    $sql_q .= " `volume` DECIMAL(12, 6) UNSIGNED NOT NULL,";
    $sql_q .= " `last_price` DECIMAL(12, 6) UNSIGNED NOT NULL,";
    $sql_q .= " `time` TIMESTAMP NOT NULL";
    $sql_q .= ") ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
    mysqli_query($this->link, $sql_q);
  }

  public function insert($data){
    $query = NULL;
    $sql_q = "";
    $sql_q  = "INSERT INTO `markets` (`service_id`, `bid`, `ask`, `low`, `high`, `volume`, `last_price`)";
    $sql_q .= " VALUES";
    $sql_q .= " (" . $data['service_id'] . ", ";
    $sql_q .= $this->num_format($data['bid']) . ", " . $this->num_format($data['ask']) . ", ";
    $sql_q .= $this->num_format($data['low']) . ", " . $this->num_format($data['high']) . ", ";
    $sql_q .= $this->num_format($data['volume']) . ", " . $this->num_format($data['last_price']) . ")";
    mysqli_query($this->link, $sql_q);
  }

  public function getMarkets($time_min, $time_max, $service_id = NULL){
    $query = NULL;
    $sql_q = "";
    $sql_q  = "SELECT * FROM `markets` WHERE";
    $sql_q .= " (`time` >= '" . date('Y-m-d H:i:s', $time_min) . "' AND `time` <= '" . date('Y-m-d H:i:s', $time_max) . "')";
    if ($service_id === NULL){
      $sql_q .= " AND `service_id` > 0";
    } else {
      $sql_q .= " AND `service_id` = " . $service_id;
    }
    $query = mysqli_query($this->link, $sql_q);
    $result = array();
    $result['status'] = false;
    $result['orders'] = array();
    while (true){
      $row = mysqli_fetch_assoc($query);
      if (is_array($row)){
        if (isset($row['bid'])) $row['bid'] = floatval($row['bid']);
        if (isset($row['ask'])) $row['ask'] = floatval($row['ask']);
        if (isset($row['low'])) $row['low'] = floatval($row['low']);
        if (isset($row['high'])) $row['high'] = floatval($row['high']);
        if (isset($row['volume'])) $row['volume'] = floatval($row['volume']);
        if (isset($row['last_price'])) $row['last_price'] = floatval($row['last_price']);
        if (isset($row['time'])) $row['time'] = strtotime($row['time']);
        $result['orders'][] = $row;
      } else {
        break;
      }
    }
    mysqli_free_result($query);
    if (count($result['orders']) > 0) $result['status'] = true;
    return $result;
  }

  public function close(){
    mysqli_close($this->link);
  }

}

?>