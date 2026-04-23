<?php
// connecting local MySQL server using XAMPP or MAMPP
$username = "root";  // as a root likhlo yaa aise refecree use karlo 
$conn=new mysqli("localhost", $username, "", "calendar"); // databse name =course_calendar and the passowrd is none as ""
$conn->set_charset("utf8mb4"); // Set character encoding to UTF-8mb4 for better support of special characters       
