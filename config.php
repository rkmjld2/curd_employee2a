<?php

/*
============================================================
        CURD_EMPLOYEE2 - config.php
============================================================

Purpose:
    Central configuration for CURD_EMPLOYEE2.

IMPORTANT:
    This file does NOT connect to MySQL.

Employee database:
    Handled by db.php

Local database:
    employeer

License:
    Checked by license_guard.php

Remote license server:
    license-commercial2-remote

Timezone:
    Asia/Kolkata
============================================================
*/


/* =========================================================
   TIMEZONE
========================================================= */

date_default_timezone_set("Asia/Kolkata");


/* =========================================================
   APPLICATION SETTINGS
========================================================= */

$APP_NAME = "CURD Employee 2";

$APP_TIMEZONE = "Asia/Kolkata";


/* =========================================================
   CUSTOMER LICENSE USER ID
=========================================================

IMPORTANT:

This is the customer ID used by the REMOTE license server.

For the moment we use:

    CURD_EMPLOYEE2

We can change this later if your remote license table
uses a different user_id.

========================================================= */

$LICENSE_USER_ID = "CURD_EMPLOYEE2";


/* =========================================================
   REMOTE LICENSE SERVER
========================================================= */

$LICENSE_SERVER_URL =
    "https://license-commercial2-remote.onrender.com/license_check.php";


/* =========================================================
   LICENSE SERVER TIMEOUT
========================================================= */

$LICENSE_TIMEOUT = 20;


/* =========================================================
   SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}

?>