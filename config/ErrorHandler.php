<?php
/**
 * User-Friendly Error Handler
 * Converts technical errors into messages that regular users can understand
 */

class ErrorHandler {
    
    /**
     * Display a user-friendly error message
     * 
     * @param string $title The error title
     * @param string $message The main error message
     * @param string $suggestion Helpful suggestion for the user
     * @param int $httpCode HTTP status code (default: 500)
     */
    public static function showError($title, $message, $suggestion = '', $httpCode = 500) {
        http_response_code($httpCode);
        
        if (empty($suggestion)) {
            $suggestion = "Please try again or contact the barangay office for assistance.";
        }
        
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($title); ?></title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    padding: 20px;
                }
                .error-container {
                    background: white;
                    padding: 40px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    max-width: 600px;
                    width: 100%;
                    text-align: center;
                    animation: slideIn 0.3s ease-out;
                }
                @keyframes slideIn {
                    from {
                        opacity: 0;
                        transform: translateY(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                .error-icon {
                    font-size: 80px;
                    margin-bottom: 20px;
                    animation: bounce 1s ease-in-out;
                }
                @keyframes bounce {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-10px); }
                }
                h1 {
                    color: #333;
                    margin: 0 0 20px 0;
                    font-size: 28px;
                    font-weight: 600;
                }
                .error-message {
                    color: #666;
                    margin: 20px 0;
                    line-height: 1.8;
                    font-size: 16px;
                }
                .error-suggestion {
                    background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
                    padding: 20px;
                    color: #555;
                    margin-top: 25px;
                    border-left: 5px solid #667eea;
                    text-align: left;
                    line-height: 1.6;
                }
                .error-suggestion strong {
                    color: #667eea;
                    display: block;
                    margin-bottom: 8px;
                }
                .button-group {
                    margin-top: 30px;
                    display: flex;
                    gap: 15px;
                    justify-content: center;
                    flex-wrap: wrap;
                }
                .btn {
                    display: inline-block;
                    padding: 14px 30px;
                    background: #667eea;
                    color: white;
                    text-decoration: none;
                    transition: all 0.3s;
                    font-weight: 500;
                    border: 2px solid transparent;
                }
                .btn:hover {
                    background: #5568d3;
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
                }
                .btn-secondary {
                    background: white;
                    color: #667eea;
                    border: 2px solid #667eea;
                }
                .btn-secondary:hover {
                    background: #f0f4ff;
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
                }
                .contact-info {
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #e0e0e0;
                    color: #888;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-icon">⚠️</div>
                <h1><?php echo htmlspecialchars($title); ?></h1>
                <p class="error-message"><?php echo htmlspecialchars($message); ?></p>
                
                <div class="error-suggestion">
                    <strong>💡 What you can do:</strong>
                    <?php echo htmlspecialchars($suggestion); ?>
                </div>
                
                <div class="button-group">
                    <a href="javascript:history.back()" class="btn">Go Back</a>
                    <a href="/Final_Project_lmao/index.php" class="btn btn-secondary">Home Page</a>
                </div>
                
                <div class="contact-info">
                    Need help? Contact your barangay office during office hours.
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Get user-friendly error message for common database errors
     */
    public static function getDatabaseErrorMessage($exception) {
        $errorCode = $exception->getCode();
        $errorMessage = $exception->getMessage();
        
        // Check for common database errors
        if (strpos($errorMessage, 'Access denied') !== false) {
            return [
                'title' => 'Connection Problem',
                'message' => 'We cannot connect to our database right now.',
                'suggestion' => 'This is a temporary issue. Please try again in a few minutes. If the problem continues, please contact the barangay office.'
            ];
        }
        
        if (strpos($errorMessage, 'Unknown database') !== false) {
            return [
                'title' => 'System Configuration Error',
                'message' => 'Our system is not properly configured.',
                'suggestion' => 'Please contact the barangay office to report this issue. We apologize for the inconvenience.'
            ];
        }
        
        if (strpos($errorMessage, 'Duplicate entry') !== false) {
            return [
                'title' => 'Duplicate Information',
                'message' => 'This information already exists in our system.',
                'suggestion' => 'Please check if you have already registered or submitted this request before. If you need help, contact the barangay office.'
            ];
        }
        
        if ($errorCode == 1062) { // Duplicate entry
            return [
                'title' => 'Already Exists',
                'message' => 'The information you entered is already in our system.',
                'suggestion' => 'You may have already registered or submitted this before. Try logging in instead, or contact the barangay office for help.'
            ];
        }
        
        // Default database error
        return [
            'title' => 'Technical Difficulty',
            'message' => 'We are experiencing technical difficulties with our database.',
            'suggestion' => 'Please try again in a few minutes. If the problem persists, contact the barangay office for assistance.'
        ];
    }
    
    /**
     * Get user-friendly error message for file upload errors
     */
    public static function getFileUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return "The file you're trying to upload is too large. Please choose a file smaller than 5MB.";
            
            case UPLOAD_ERR_PARTIAL:
                return "The file upload was interrupted. Please try uploading again.";
            
            case UPLOAD_ERR_NO_FILE:
                return "No file was selected. Please choose a file to upload.";
            
            case UPLOAD_ERR_NO_TMP_DIR:
                return "We're having trouble saving your file. Please try again or contact the barangay office.";
            
            case UPLOAD_ERR_CANT_WRITE:
                return "We couldn't save your file. Please try again.";
            
            case UPLOAD_ERR_EXTENSION:
                return "The file type you're trying to upload is not allowed. Please use JPG, PNG, or PDF files.";
            
            default:
                return "There was a problem uploading your file. Please try again.";
        }
    }
    
    /**
     * Log error for developers while showing user-friendly message
     */
    public static function logAndShow($exception, $userTitle = null, $userMessage = null) {
        // Log the technical error for developers
        error_log("Error: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
        
        // Show user-friendly message
        if ($userTitle && $userMessage) {
            self::showError($userTitle, $userMessage);
        } else {
            $errorInfo = self::getDatabaseErrorMessage($exception);
            self::showError($errorInfo['title'], $errorInfo['message'], $errorInfo['suggestion']);
        }
    }
}

