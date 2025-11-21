<?php
class Database {
    private static $instance = null;
    private $conn;

    private $host = "localhost";
    private $db_name = "smartbarangaydb";
    private $username = "root";
    private $password = "";

    // Private constructor to prevent direct instantiation
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            // User-friendly error message
            $this->displayError(
                "Unable to Connect to the System",
                "We're having trouble connecting to our database. This might be temporary. Please try again in a few moments.",
                "If the problem continues, please contact the barangay office for assistance."
            );
        }
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Prevent unserialization of the instance
    public function __wakeup() {
        $this->displayError(
            "System Error",
            "An unexpected error occurred while processing your request.",
            "Please refresh the page and try again."
        );
    }

    // Display user-friendly error page
    private function displayError($title, $message, $suggestion) {
        http_response_code(503);
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars($title) . '</title>
            <link rel="stylesheet" href="../css/error.css">
        </head>
        <body>
            <div class="error-container">
                <div class="error-icon">⚠️</div>
                <h1>' . htmlspecialchars($title) . '</h1>
                <p class="error-message">' . htmlspecialchars($message) . '</p>
                <div class="error-suggestion">
                    💡 ' . htmlspecialchars($suggestion) . '
                </div>
                <a href="javascript:history.back()" class="btn">Go Back</a>
                <a href="../index.php" class="btn btn-secondary">Home Page</a>
            </div>
        </body>
        </html>';
        exit;
    }

    // Get the singleton instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Get the PDO connection
    public function getConnection() {
        return $this->conn;
    }

    // Legacy method for backward compatibility
    public function connect() {
        return $this->conn;
    }
}
