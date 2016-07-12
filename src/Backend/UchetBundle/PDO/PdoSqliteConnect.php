<?php

namespace Backend\UchetBundle\PDO;


class PdoSqliteConnect
{
    public function connect($data)
    {
        $sqlite =  $data->getParameter('pathsqlite');

        try {
            $db = new \PDO('sqlite:' . $sqlite);
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);            
        }
        catch(PDOException $e) {
            echo "Нет соединения с базой данных";
        }
        
        return $db;
    }
}