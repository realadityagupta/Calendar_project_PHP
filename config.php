<?php
$conn = new mysqli("localhost", "root", "", "calendar");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>