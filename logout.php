<?php
/*
============================================================
CURD-EMPLOYEE2
USER LOGOUT
============================================================
*/

session_start();

/*
 * Destroy the current login session.
 */
$_SESSION = array();

session_destroy();

/*
 * Return to login page.
 */
header("Location: login.php");
exit;

?>