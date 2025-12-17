# GasGuard IoT System

**GasGuard** is an IoT-based safety monitoring system designed to detect gas leaks in real-time. It utilizes an **ESP32** microcontroller and an **MQ-2 Gas Sensor** to monitor air quality, providing immediate local alerts (buzzer/LEDs) and remote monitoring via a web dashboard with email notifications for critical events.

## Features

* [cite_start]**Real-Time Monitoring:** Continuous tracking of gas concentration levels and raw sensor data[cite: 46].
* **Web Dashboard:** A responsive interface (built with Bootstrap 5) displaying:
    * [cite_start]Gas Concentration (%) & Raw Sensor Values[cite: 58].
    * [cite_start]System Status (Safe, Critical, Offline)[cite: 60].
    * [cite_start]Sensor Health (Voltage) & WiFi Signal Strength[cite: 47, 48].
* **Immediate Alerts:**
    * [cite_start]**Local:** Loud buzzer and Red LED activation on the device when thresholds (Raw > 2000) are breached[cite: 36, 61].
    * **Remote:** Automated email alerts sent via PHPMailer when "DANGER" status is detected.
    * **Browser:** Audio siren simulation on the dashboard during critical states.
* [cite_start]**Connectivity:** Uses WiFi and HTTPS to securely transmit data to a central server[cite: 35, 52].

## Hardware Requirements

* **Microcontroller:** ESP32 Development Board
* **Sensor:** MQ-2 Gas/Smoke Sensor
* [cite_start]**Display:** 0.96" OLED Display (128x64, I2C) 
* **Indicators:**
    * Red LED (Danger)
    * Green LED (Safe)
    * Active Buzzer
* **Misc:** Breadboard, Jumper Wires, Micro-USB cable.

### Pinout Configuration (ESP32)

| Component | Pin Type | ESP32 Pin |
| :--- | :--- | :--- |
| **MQ-2 Sensor** | Analog | [cite_start]`GPIO 34`  |
| **Buzzer** | Digital Output | [cite_start]`GPIO 23`  |
| **Red LED** | Digital Output | [cite_start]`GPIO 15`  |
| **Green LED** | Digital Output | [cite_start]`GPIO 32`  |
| **OLED SDA** | I2C Data | Default (Usually `21`) |
| **OLED SCL** | I2C Clock | Default (Usually `22`) |

## Software & Stack

* **Firmware:** C++ (Arduino IDE)
* **Backend:** PHP 7.4+
* **Database:** MySQL / MariaDB
* **Frontend:** HTML5, CSS3, JavaScript (Fetch API), Bootstrap 5

## Installation Guide

### 1. Database Setup
1.  Create a MySQL database named `gas_monitoring`.
2.  Execute the following SQL command to create the required table:

```sql
CREATE TABLE sensor_logs (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_name VARCHAR(50) NOT NULL,
    gas_percent INT(11) DEFAULT 0,
    gas_raw INT(11) DEFAULT 0,
    sensor_voltage DOUBLE DEFAULT 0,
    wifi_signal VARCHAR(10) DEFAULT '0',
    status VARCHAR(20) DEFAULT 'SAFE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
