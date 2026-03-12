<?php
// config/database.php
class Database {
    private $host, $dbname, $user, $pass, $conn;
    
    public function __construct() {
        $this->loadEnv();
        $this->host = $_ENV['DB_HOST'];
        $this->dbname = $_ENV['DB_DATABASE'];
        $this->user = $_ENV['DB_USERNAME'];
        $this->pass = $_ENV['DB_PASSWORD'];
    }
    
    private function loadEnv() {
        $env = parse_ini_file(__DIR__.'/.env');
        $_ENV = array_merge($_ENV, $env);
    }
    
    public function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->user, $this->pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            return $this->conn;
        } catch(PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            die("Erro de conexão. Contate o suporte.");
        }
    }
    
    public function ping() {
        try {
            $this->connect()->query("SELECT 1");
            return true;
        } catch(Exception $e) {
            return false;
        }
    }
}
?>