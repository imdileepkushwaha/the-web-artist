<?php
// Database configuration
$servername = "localhost";
$username = "root"; // Default MySQL username for local environments like XAMPP/WAMP
$password = ""; // Default password is often empty
$dbname = "thewebartist_db";

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create table if it doesn't exist to ensure the script works out-of-the-box
    $createTableSql = "CREATE TABLE IF NOT EXISTS enquiries (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        service VARCHAR(100) NOT NULL,
        message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($createTableSql);

    // Check if form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $service = $_POST['service'] ?? '';
        $message = $_POST['message'] ?? '';

        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO enquiries (name, email, phone, service, message) 
                                VALUES (:name, :email, :phone, :service, :message)");
        
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':service' => $service,
            ':message' => $message
        ]);

        // Success Page HTML
        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Success - The Web Artist</title>
            <link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap' rel='stylesheet'>
            <style>
                body { 
                    font-family: 'Inter', sans-serif; 
                    display: flex; justify-content: center; align-items: center; 
                    height: 100vh; background-color: #f4f7f6; margin: 0; color: #333;
                }
                .success-card { 
                    background: white; padding: 50px 40px; border-radius: 16px; 
                    box-shadow: 0 10px 40px rgba(0,0,0,0.08); text-align: center;
                    max-width: 400px; width: 90%;
                    animation: slideUp 0.6s ease-out;
                }
                @keyframes slideUp {
                    from { transform: translateY(30px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                .icon { font-size: 4rem; color: #4BB543; margin-bottom: 20px; }
                .success-card h1 { color: #0056b3; margin-bottom: 15px; font-weight: 700; }
                .success-card p { color: #666; margin-bottom: 30px; line-height: 1.6; }
                .btn { 
                    display: inline-block; padding: 12px 24px; background: #0056b3; 
                    color: white; text-decoration: none; border-radius: 8px; font-weight: 600;
                    transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0, 86, 179, 0.3);
                }
                .btn:hover { background: #004494; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 86, 179, 0.4); }
            </style>
        </head>
        <body>
            <div class='success-card'>
                <div class='icon'>✅</div>
                <h1>Thank You!</h1>
                <p>Your demo request has been successfully submitted. Our team will get back to you shortly.</p>
                <a href='index.php' class='btn'>Back to Home</a>
            </div>
        </body>
        </html>";
    }

} catch(PDOException $e) {
    // In a production environment, you should log the error and show a generic message
    // For this demonstration, we'll output the error to help debug database issues
    echo "<div style='color: red; text-align: center; padding: 50px; font-family: sans-serif;'>";
    echo "<h3>Database Connection Error</h3>";
    echo "<p>Please ensure your MySQL server is running and the database 'thewebartist_db' exists.</p>";
    echo "<p>Error Details: " . $e->getMessage() . "</p>";
    echo "</div>";
}

$conn = null;
?>
