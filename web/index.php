<?php

// --- 1. CONFIGURATION AND UTILITY FUNCTIONS ---

// Database connection details from the .env file
$DB_HOST = 'db'; // Service name in docker-compose.yml
$DB_NAME = getenv('MYSQL_DATABASE');
$DB_USER = getenv('MYSQL_USER');
$DB_PASS = getenv('MYSQL_PASSWORD');

// Base URL for the shortener (used to display the final link)
// The HTTP_HOST variable already includes the port (e.g., 192.168.1.106:8080), 
// so we use it directly and skip adding the port explicitly. THIS IS THE FIX.
$BASE_URL = 'http://' . $_SERVER['HTTP_HOST'];

/**
 * Generates a random, unique short code of a specific length.
 * @param int $length The desired length of the code.
 * @return string The generated code.
 */
function generateUniqueCode($length = 7) {
    // Characters to use for the code (alphanumeric)
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    $chars_length = strlen($chars);
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[rand(0, $chars_length - 1)];
    }
    return $code;
}

// --- 2. DATABASE CONNECTION ---

$message = '';
$short_link = '';
$mysqli = null; // Initialize to null

try {
    // Connect to MySQL
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

    // Check connection
    if ($mysqli->connect_error) {
        // Throw an exception for centralized error handling
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }

} catch (Exception $e) {
    // Display connection error to the user if it's the root cause
    $message = "<div class='message error'>Database Connection Error: Cannot connect to the MySQL service.</div>";
    error_log("Database Error: " . $e->getMessage()); // Log error to PHP log
}

// --- 3. REDIRECTION LOGIC (Handles Short Codes) ---

// Get the path requested (e.g., '/abc1234')
$request_uri = $_SERVER['REQUEST_URI'];
// Remove the leading slash and sanitize the code
$short_code = trim(ltrim($request_uri, '/'));

// Check if we have a valid database connection AND a short code is present in the URL
if ($short_code && $mysqli) {
    // If a short code is provided, try to look it up in the database
    $stmt = $mysqli->prepare("SELECT long_url FROM links WHERE short_code = ?");
    
    if ($stmt) {
        $stmt->bind_param("s", $short_code);
        $stmt->execute();
        $stmt->bind_result($long_url);
        $stmt->fetch();
        $stmt->close();
    
        if ($long_url) {
            // SUCCESS: Redirect the user to the long URL
            header("Location: " . $long_url, true, 301);
            exit();
        } else {
            // ERROR: Code not found. Set an error message for the display section.
            $message = "<div class='message error'>Error: The short link '$short_code' was not found.</div>";
        }
    } else {
        $message = "<div class='message error'>Database Query Error during lookup.</div>";
    }
}


// --- 4. FORM SUBMISSION LOGIC (Handles New URL Creation) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mysqli) {
    $long_url = filter_input(INPUT_POST, 'long_url', FILTER_VALIDATE_URL); // Use filter_validate_url for better validation

    if ($long_url) {
        
        // 4a. Check if the URL already exists to avoid duplication
        $stmt = $mysqli->prepare("SELECT short_code FROM links WHERE long_url = ?");
        if ($stmt) {
            $stmt->bind_param("s", $long_url);
            $stmt->execute();
            $stmt->bind_result($existing_code);
            $stmt->fetch();
            $stmt->close();
        }

        if (isset($existing_code) && $existing_code) {
            // URL already exists, use the existing short code
            $short_link = $BASE_URL . '/' . $existing_code;
            $message = "<div class='message success'>This URL was already shortened! Reusing existing link.</div>";
        } else {
            // 4b. Generate a unique code and attempt to save it (with collision handling)
            $code_found = false;
            $attempts = 0;
            do {
                $new_code = generateUniqueCode();
                $stmt = $mysqli->prepare("INSERT INTO links (short_code, long_url) VALUES (?, ?)");
                
                if ($stmt && $stmt->bind_param("ss", $new_code, $long_url) && $stmt->execute()) {
                    // Success!
                    $short_link = $BASE_URL . '/' . $new_code;
                    $message = "<div class='message success'>URL shortened successfully!</div>";
                    $code_found = true;
                } elseif ($stmt) {
                    // Close the statement if it failed to execute but was prepared
                    $stmt->close();
                }
                
                $attempts++;
            // Continue if insertion failed (likely due to UNIQUE KEY constraint collision)
            } while (!$code_found && $attempts < 5); 

            if (!$code_found) {
                $message = "<div class='message error'>Error: Failed to generate a unique code after $attempts attempts. Database conflict may exist.</div>";
            }
        }
    } else {
        $message = "<div class='message error'>Error: Please enter a valid URL (must include http:// or https://).</div>";
    }
}

// Close the database connection
if ($mysqli && $mysqli->ping()) {
    $mysqli->close();
}

// --- 5. HTML OUTPUT ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Link Shortener</title>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .container { background-color: #ffffff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); max-width: 500px; width: 90%; }
        h1 { text-align: center; color: #333; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        input[type="url"] { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; font-size: 16px; transition: border-color 0.3s; }
        input[type="url"]:focus { border-color: #007bff; outline: none; }
        button { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 8px; font-size: 18px; cursor: pointer; transition: background-color 0.3s, transform 0.1s; }
        button:hover { background-color: #0056b3; }
        button:active { transform: scale(0.99); }
        .message { padding: 15px; border-radius: 8px; margin-top: 20px; font-size: 16px; font-weight: 500; text-align: center; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .result-box { background-color: #e9ecef; padding: 15px; border-radius: 8px; margin-top: 25px; text-align: center; }
        .result-box a { color: #007bff; font-weight: 700; word-break: break-all; text-decoration: none; font-size: 18px; }
        .result-box a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔗 Personal Link Shortener</h1>

        <!-- Display Messages (Success or Error) -->
        <?php echo $message; ?>

        <!-- Form for Submitting New URLs -->
        <form method="POST">
            <div class="form-group">
                <label for="long_url">Enter Long URL (must include http:// or https://):</label>
                <input type="url" id="long_url" name="long_url" placeholder="https://your-very-long-link.com/page?id=123..." required>
            </div>
            <button type="submit">Generate Short Link</button>
        </form>

        <!-- Display Short Link Result -->
        <?php if ($short_link): ?>
            <div class="result-box">
                Your Short Link: <br>
                <a href="<?php echo $short_link; ?>" target="_blank" onclick="document.execCommand('copy'); return false;">
                    <?php echo $short_link; ?>
                </a>
            </div>
        <?php endif; ?>

        <script>
            // Simple clipboard copy functionality for user convenience
            document.addEventListener('DOMContentLoaded', () => {
                const resultLink = document.querySelector('.result-box a');
                if (resultLink) {
                    resultLink.addEventListener('click', (e) => {
                        e.preventDefault();
                        const urlToCopy = resultLink.href;
                        // Use temporary textarea for reliable execCommand copy
                        const tempInput = document.createElement('textarea');
                        tempInput.value = urlToCopy;
                        document.body.appendChild(tempInput);
                        tempInput.select();
                        try {
                            document.execCommand('copy');
                            // Replace alert with a simple visual confirmation
                            const originalText = resultLink.innerHTML;
                            resultLink.innerHTML = 'COPIED!';
                            setTimeout(() => {
                                resultLink.innerHTML = originalText;
                            }, 1000);

                        } catch (err) {
                            console.error('Could not copy text: ', err);
                        }
                        document.body.removeChild(tempInput);
                    });
                }
            });
        </script>
    </div>
</body>
</html>
