<?php

namespace Pagnany\Kcp;

class DatabaseConnect
{
    private const HOST = 'database-5003899544.webspace-host.com';
    private const DBNAME = 'DB4342384';
    private const USER = 'U4342384';
    private const PASSWORD = 'KC_Anliegerweg123';

    private $conn;

    public function __construct()
    {
        try {
            $this->conn = new \PDO(
                "mysql:host=" . self::HOST . ";dbname=" . self::DBNAME,
                self::USER,
                self::PASSWORD,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        } catch (\PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function getConnection(): \PDO
    {
        return $this->conn;
    }
}