<?php
namespace OCFram;

class PDOFactory
{
  // on spécifie l'encodage pour éviter les problèmes d'accent à la lecture / à l'écriture dans la base de données
  const ENCODE_UTF8 = 'utf8';
  
  public static function getMysqlConnexion()
  {
		$db = new \PDO('mysql:host=localhost;dbname=news;charset='.self::ENCODE_UTF8, 'root', '', array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.self::ENCODE_UTF8));
		$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    
    return $db;
  }
}