<?php
session_start();

/*
============================================================
CRUD3 LOGOUT
============================================================
Logout from CRUD3 and return to the main login page.

CRUD3 location:
    C:\xampp\htdocs\crud3\

Login location:
    C:\xampp\htdocs\curd_employee2\login.php
============================================================
*/

// Clear all session variables
$_SESSION = array();

// Destroy current session
session_destroy();

// Prevent browser from displaying cached protected pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Return to the main login page
header("Location: ../curd_employee2b/login.php");
exit;
?>
```
