<?php
// 1. LOAD PHPMAILER (Fixed Paths)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// 2. LOAD USER SETTINGS (Email Only)
$configFile = 'config.json';
$alertEmail = "";
if (file_exists($configFile)) {
    $json = file_get_contents($configFile);
    $data = json_decode($json, true);
    $alertEmail = $data['email'] ?? "";
}

// 3. DATABASE CONNECTION
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get Data
    $device = $_POST['device_name'] ?? "Unknown";
    $percent = $_POST['gasPercent'] ?? 0;
    $raw = $_POST['gasRaw'] ?? 0;
    
    // --- MAP INPUTS TO NEW VARIABLES ---
    $voltage = $_POST['temp'] ?? 0.0;    // ESP32 sends voltage as 'temp'
    $wifi_signal = $_POST['hum'] ?? 0.0; // ESP32 sends RSSI as 'hum'

    // HARDCODED THRESHOLD (Since we removed it from settings)
    $status = ($raw > 2000) ? "DANGER" : "SAFE";

    // --- CHECK PREVIOUS STATUS ---
    $result = $conn->query("SELECT status FROM sensor_logs ORDER BY id DESC LIMIT 1");
    $lastStatus = ($result->num_rows > 0) ? $result->fetch_assoc()['status'] : "SAFE";

    // --- SEND EMAIL IF STATUS CHANGED TO DANGER ---
    if ($lastStatus == "SAFE" && $status == "DANGER" && !empty($alertEmail)) {
        // Pass the new info (Voltage/WiFi) to the email function
        sendEmailAlert($alertEmail, $device, $percent, $raw, $voltage, $wifi_signal);
    }

    // Insert Data
    // We insert into the new column names 'sensor_voltage' and 'wifi_signal'
    $stmt = $conn->prepare("INSERT INTO sensor_logs (device_name, gas_percent, gas_raw, sensor_voltage, wifi_signal, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddds", $device, $percent, $raw, $voltage, $wifi_signal, $status);
    $stmt->execute();
    $stmt->close();
}

// --- UPDATED EMAIL FUNCTION ---
function sendEmailAlert($targetEmail, $device, $percent, $raw, $voltage, $wifi) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        
        // --- YOUR CREDENTIALS ---
        $mail->Username   = 'gasguard.3102@gmail.com';  
        $mail->Password   = 'eaez slpu jxcv nzzm';      
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('gasguard.3102@gmail.com', 'GasGuard Security'); 
        $mail->addAddress($targetEmail);  

        // --- NEW PRECISE TIME FORMAT ---
        $time = date("F j, Y, g:i:s a"); 
        
        $mail->isHTML(true);
        $mail->Subject = 'GAS LEAK DETECTED - IMMEDIATE ACTION REQUIRED';
        
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;'>
            
            <div style='background-color: #d32f2f; color: white; padding: 20px; text-align: center;'>
                <h1 style='margin: 0; font-size: 24px;'>⚠️ CRITICAL GAS ALERT</h1>
                <p style='margin: 5px 0 0 0; font-size: 16px;'>High levels of gas detected in your facility.</p>
            </div>

            <div style='padding: 30px; background-color: #ffffff;'>
                
                <p style='font-size: 16px; color: #333; line-height: 1.5;'>
                    <strong>Alert:</strong> GasGuard has detected a dangerous spike in gas concentration. 
                    Please evacuate the area and check the source immediately.
                </p>

                <table style='width: 100%; border-collapse: collapse; margin-top: 20px; background-color: #f9f9f9; border-radius: 5px; overflow: hidden;'>
                    <tr>
                        <td style='padding: 15px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;'>Device Name:</td>
                        <td style='padding: 15px; border-bottom: 1px solid #eee; color: #333;'>$device</td>
                    </tr>
                    <tr>
                        <td style='padding: 15px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;'>Concentration:</td>
                        <td style='padding: 15px; border-bottom: 1px solid #eee; color: #d32f2f; font-weight: bold; font-size: 18px;'>$percent%</td>
                    </tr>
                    <tr>
                        <td style='padding: 15px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;'>Sensor Raw:</td>
                        <td style='padding: 15px; border-bottom: 1px solid #eee; color: #333;'>$raw (Limit: 2000)</td>
                    </tr>
                    <tr>
                        <td style='padding: 15px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;'>Sensor Health:</td>
                        <td style='padding: 15px; border-bottom: 1px solid #eee; color: #333;'>$voltage V</td>
                    </tr>
                    <tr>
                        <td style='padding: 15px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;'>WiFi Signal:</td>
                        <td style='padding: 15px; border-bottom: 1px solid #eee; color: #333;'>$wifi dBm</td>
                    </tr>
                    <tr>
                        <td style='padding: 15px; font-weight: bold; color: #555;'>Time Detected:</td>
                        <td style='padding: 15px; color: #333;'>$time</td>
                    </tr>
                </table>

                <div style='text-align: center; margin-top: 30px;'>
                    <a href='https://gasguard.bentesais.store/dashboard.php'
                       style='background-color: #d32f2f; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>
                       OPEN DASHBOARD
                    </a>
                </div>
            </div>

            <div style='background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #888;'>
                GasGuard Safety System • Automated Alert<br>
                Please do not reply to this email.
            </div>
        </div>";

        $mail->AltBody = "CRITICAL ALERT: Gas Leak Detected!\nDevice: $device\nLevel: $percent%\nVoltage: $voltage V\nSignal: $wifi dBm\nTime: $time\n\nEVACUATE IMMEDIATELY.";

        $mail->send();
    } catch (Exception $e) {
        // Silent error
    }
}
$conn->close();
?>