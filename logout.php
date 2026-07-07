<?php
// Manager Logout Handler
// Almighty Driving School Management System
session_start();
session_unset();
session_destroy();
header("Location: login.php");
exit;
