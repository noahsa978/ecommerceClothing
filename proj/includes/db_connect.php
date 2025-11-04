<?php
$host = 'localhost';
$user = 'root';       
$pass = '';            
$db   = 'clothing_store101';

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

