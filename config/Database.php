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
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    padding: 20px;
                }
                .error-container {
                    background: white;
                    padding: 40px;
                    border-radius: 10px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                    max-width: 500px;
                    text-align: center;
                }
                .error-icon {
                    font-size: 60px;
                    margin-bottom: 20px;
                }
                h1 {
                    color: #333;
                    margin: 0 0 15px 0;
                    font-size: 24px;
                }
                .error-message {
                    color: #666;
                    margin: 15px 0;
                    line-height: 1.6;
                }
                .error-suggestion {
                    background: #f0f4ff;
                    padding: 15px;
                    border-radius: 5px;
                    color: #555;
                    margin-top: 20px;
                    border-left: 4px solid #667eea;
                }
                .btn {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 12px 30px;
                    background: #667eea;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    transition: background 0.3s;
                }
                .btn:hover {
                    background: #5568d3;
                }
            </style>
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
                <a href="../index.php" class="btn" style="background: #764ba2;">Home Page</a>
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
