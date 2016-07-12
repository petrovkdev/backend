<?php

namespace Backend\UchetBundle\AppClass;

use Backend\UchetBundle\AppInterface\ScenarioInterface;
use Backend\UchetBundle\PDO\PdoSqliteConnect;
use Symfony\Component\Yaml\Yaml;

class UchetScenario implements ScenarioInterface
{
    
    public $limit;
    public $sum_cost        = 0;
    public $balance;
    public $msg             = 0;
    public $sum_next_month  = null;
    public $excess          = null;
    public $prevexcess      = null;

    public function getScenario($cfg, $container){

        $db        = new PdoSqliteConnect();
        $dbconnect = $db->connect($container);
        $month     = date('m-Y',time());

        switch ($cfg->scenario){
            
            case 0:

                $prev_month = date('m-Y', strtotime('01-' . $month . ' -1 month'));

                $this->limit = $cfg->limit;

                if(isset($cfg->prev_limit[$prev_month])){
                    $this->limit = $cfg->limit - ($cfg->limit - $cfg->prev_limit[$prev_month]);
                }

                $current = $dbconnect->prepare("
                  select strftime('%m-%Y', datetime(date, 'unixepoch', 'localtime')) month, 
                  sum(sum) fullsum 
                  from costs 
                  WHERE month = :month");
                $current->bindValue(':month', $month, \PDO::PARAM_STR);
                $current->execute();

                $obj_current = $current->fetch(\PDO::FETCH_OBJ);

                $prevent = $dbconnect->prepare("
                  select strftime('%m-%Y', datetime(date, 'unixepoch', 'localtime')) month, 
                  sum(sum) fullsum 
                  from costs 
                  WHERE month = :month");
                $prevent->bindValue(':month', $prev_month, \PDO::PARAM_STR);
                $prevent->execute();

                $obj_prevent = $prevent->fetch(\PDO::FETCH_OBJ);

                if($obj_prevent->fullsum > $this->limit)
                {
                    $this->prevexcess = $obj_prevent->fullsum - $this->limit;

                    $this->limit  = $cfg->limit - ($obj_prevent->fullsum - $this->limit);
                }

                $this->balance = $this->limit;

                if($obj_current->fullsum)
                {

                    //всего расходов;
                    $this->sum_cost = $obj_current->fullsum;

                    //остаток от предельной суммы;
                    $this->balance  = $this->limit - $this->sum_cost;

                    //сумма, которая будет доступна в следующем месяце
                    $this->sum_next_month = $this->balance + $cfg->limit;

                    //если сумма лимита на следующий месяц отрицательная, то
                    //устанавливаем значение 0
                    if($this->sum_next_month < 0)
                    {
                        $this->sum_next_month = 0;
                    }

                    //сумма превышения
                    $this->excess = $this->balance * -1;

                    //создание параметра для конфигурационного файла.
                    //Установка текущего лимита (может отличаться от основного).
                    $cfg->prev_limit = [$month => $this->limit];

                    $yaml = Yaml::dump($cfg, 2, 4, false, true);

                    //запись в конфигурационный файл
                    file_put_contents('../src/Backend/UchetBundle/Resources/config/appconfig.yml', $yaml);

                    //если остаток от предельной суммы < 0, то устанавливаем остатку значение 0;
                    //$msg = 1 означает показать уведомление о превышении лимита;
                    if($this->balance < 0)
                    {
                        $this->balance = 0;
                        $this->msg     = 1;
                    }
                }
                
                break;

            case 1:

                $this->limit = $cfg->limit;

                if(isset($cfg->raise_limit[date('m', time())])){
                    $this->limit = $cfg->limit + $cfg->raise_limit[date('m', time())];
                }

                $current = $dbconnect->prepare("
                  select strftime('%m-%Y', datetime(date, 'unixepoch', 'localtime')) month, 
                  sum(sum) fullsum 
                  from costs 
                  WHERE month = :month");
                $current->bindValue(':month', $month, \PDO::PARAM_STR);
                $current->execute();

                $obj_current = $current->fetch(\PDO::FETCH_OBJ);

                $this->balance = $this->limit;

                if($obj_current->fullsum)
                {

                    //всего расходов;
                    $this->sum_cost = $obj_current->fullsum;

                    //остаток от предельной суммы;
                    $this->balance  = $this->limit - $this->sum_cost;

                    //сумма превышения
                    $this->excess = $this->balance * -1;

                    //если остаток от предельной суммы < 0, то устанавливаем остатку значение 0;
                    //$msg = 1 означает показать уведомление о превышении лимита;
                    if($this->balance < 0)
                    {
                        $this->balance = 0;
                        $this->msg     = 1;
                    }
                }

                break;

        }
        
        return $this;
    }


}