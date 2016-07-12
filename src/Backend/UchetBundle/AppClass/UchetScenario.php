<?php

namespace Backend\UchetBundle\AppClass;

use Backend\UchetBundle\AppInterface\ScenarioInterface;
use Backend\UchetBundle\PDO\PdoSqliteConnect;
use Symfony\Component\Yaml\Yaml;

class UchetScenario implements ScenarioInterface
{
    
    public $limit;                    //предельная сумма
    public $sum_cost        = 0;      //всего расходов;
    public $balance;                  //сколько осталось от предельной суммы
    public $msg             = false;  //true - показать уведомление
    public $sum_next_month  = null;   //предельная сумма на след. месяц
    public $excess          = null;   //сумма превышения
    public $prevexcess      = null;   //сумма превышения в предыдущем месяце
    
    
    //$cfg конфигурационный файл (obj), 
    //$container - $this->container в контроллере
    public function getScenario($cfg, $container){

        $db        = new PdoSqliteConnect();
        $dbconnect = $db->connect($container);//подключение к базе
        $month     = date('m-Y',time());//текущий месяц
        
        //сценарий
        switch ($cfg->scenario){
            
            case 0://если адаптивный сценарий

                //предыдущий месяц
                $prev_month = date('m-Y', strtotime('01-' . $month . ' -1 month'));

                //лимит на месяц
                $this->limit = $cfg->limit;

                //если имеется превышение лимита в предыдущем месяце,
                //то перезаписываем значение $this->limit
                if(isset($cfg->prev_limit[$prev_month])){
                    $this->limit = $cfg->limit - ($cfg->limit - $cfg->prev_limit[$prev_month]);
                }

                //выборка из базы общей суммы расходов за текущий месяц
                $current = $dbconnect->prepare("
                  select strftime('%m-%Y', datetime(date, 'unixepoch', 'localtime')) month, 
                  sum(sum) fullsum 
                  from costs 
                  WHERE month = :month");
                $current->bindValue(':month', $month, \PDO::PARAM_STR);
                $current->execute();

                $obj_current = $current->fetch(\PDO::FETCH_OBJ);

                //выборка из базы общей суммы расходов за прошлый месяц
                $prevent = $dbconnect->prepare("
                  select strftime('%m-%Y', datetime(date, 'unixepoch', 'localtime')) month, 
                  sum(sum) fullsum 
                  from costs 
                  WHERE month = :month");
                $prevent->bindValue(':month', $prev_month, \PDO::PARAM_STR);
                $prevent->execute();

                $obj_prevent = $prevent->fetch(\PDO::FETCH_OBJ);
                
                //если сумма расходов в предыдущем месяце больше лимита текущего месяца
                if($obj_prevent->fullsum > $this->limit)
                {
                    //сумма, на сколько превышен предел в предыдущем месяце
                    $this->prevexcess = $obj_prevent->fullsum - $this->limit;

                    //перезапись лимита
                    $this->limit  = $cfg->limit - ($obj_prevent->fullsum - $this->limit);
                }

                //остаток от лимита
                $this->balance = $this->limit;

                //если есть за текущий месяц расходы
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
                    //$msg = true означает показать уведомление о превышении лимита;
                    if($this->balance < 0)
                    {
                        $this->balance = 0;
                        $this->msg     = true;
                    }
                }
                
                break;

            case 1://если сценарий на увеличение лимита

                //лимит на месяц
                $this->limit = $cfg->limit;

                //если есть увеличение лимита в текущем месяце,
                //то перезыписываем $this->limit
                if(isset($cfg->raise_limit[date('m', time())])){
                    $this->limit = $cfg->limit + $cfg->raise_limit[date('m', time())];
                }

                //выборка из базы общей суммы расходов за текущий месяц
                $current = $dbconnect->prepare("
                  select strftime('%m-%Y', datetime(date, 'unixepoch', 'localtime')) month, 
                  sum(sum) fullsum 
                  from costs 
                  WHERE month = :month");
                $current->bindValue(':month', $month, \PDO::PARAM_STR);
                $current->execute();

                $obj_current = $current->fetch(\PDO::FETCH_OBJ);

                //остаток от лимита
                $this->balance = $this->limit;

                //если есть за текущий месяц расходы
                if($obj_current->fullsum)
                {

                    //всего расходов;
                    $this->sum_cost = $obj_current->fullsum;

                    //остаток от предельной суммы;
                    $this->balance  = $this->limit - $this->sum_cost;

                    //сумма превышения
                    $this->excess = $this->balance * -1;

                    //если остаток от предельной суммы < 0, то устанавливаем остатку значение 0;
                    //$msg = true означает показать уведомление о превышении лимита;
                    if($this->balance < 0)
                    {
                        $this->balance = 0;
                        $this->msg     = true;
                    }
                }

                break;

        }
        
        return $this;
    }


}