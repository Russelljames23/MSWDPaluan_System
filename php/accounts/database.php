<?php
class Database
{
    private $host = "localhost";
    private $db_name = "mswd_seniors";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            error_log("Database connected successfully to: " . $this->db_name);
        } catch (PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            error_log("Database: " . $this->db_name . ", Host: " . $this->host . ", Username: " . $this->username);

            // Check if database exists
            try {
                $temp_conn = new PDO("mysql:host=" . $this->host, $this->username, $this->password);
                $stmt = $temp_conn->query("SHOW DATABASES LIKE '" . $this->db_name . "'");
                if ($stmt->rowCount() > 0) {
                    error_log("Database exists but connection failed");
                } else {
                    error_log("Database does not exist");
                }
            } catch (PDOException $e) {
                error_log("Even basic connection failed: " . $e->getMessage());
            }

            throw new Exception("Database connection failed: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
