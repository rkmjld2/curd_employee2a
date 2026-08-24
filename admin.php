<?php
/*
============================================================
 CURD-EMPLOYEE2
 ADMIN USER MANAGEMENT
============================================================

Purpose:
    Administrator dashboard for managing app_users.

Authentication:
    admin_login.php
    ADMIN_PASSWORD environment variable

Database:
    app_users

Fields:
    id
    user_id
    user_name
    password_hash
    active
    start_time
    stop_time
    last_login
    created_at
    updated_at

Timezone:
    Asia/Kolkata

============================================================
*/

date_default_timezone_set("Asia/Kolkata");

session_start();

require_once __DIR__ . "/db.php";


/* =========================================================
   ADMIN LOGIN PROTECTION
========================================================= */

if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {

    header("Location: admin_login.php");

    exit;
}


/* =========================================================
   ADMIN LOGOUT
========================================================= */

if (
    isset($_GET["logout"]) &&
    $_GET["logout"] === "1"
) {

    /*
     * Remove administrator session.
     */

    unset($_SESSION["admin_logged_in"]);
    unset($_SESSION["admin_name"]);

    /*
     * Destroy complete session.
     */

    $_SESSION = [];

    session_destroy();

    header("Location: admin_login.php");

    exit;
}


/* =========================================================
   MESSAGE
========================================================= */

$message = "";

$message_type = "";


/* =========================================================
   ACTIVATE USER
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["activate_user"])
) {

    $user_id =
        trim($_POST["user_id"] ?? "");


    if ($user_id === "") {

        $message =
            "User ID is missing.";

        $message_type =
            "error";

    } else {

        $stmt = $conn->prepare("
            UPDATE app_users
            SET active = 1
            WHERE user_id = ?
        ");


        if ($stmt) {

            $stmt->bind_param(
                "s",
                $user_id
            );


            if ($stmt->execute()) {

                $message =
                    "User activated successfully.";

                $message_type =
                    "success";

            } else {

                $message =
                    "Could not activate user.";

                $message_type =
                    "error";
            }


            $stmt->close();

        } else {

            $message =
                "Activation preparation failed.";

            $message_type =
                "error";
        }
    }
}


/* =========================================================
   DEACTIVATE USER
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["deactivate_user"])
) {

    $user_id =
        trim($_POST["user_id"] ?? "");


    if ($user_id === "") {

        $message =
            "User ID is missing.";

        $message_type =
            "error";

    } else {

        $stmt = $conn->prepare("
            UPDATE app_users
            SET active = 0
            WHERE user_id = ?
        ");


        if ($stmt) {

            $stmt->bind_param(
                "s",
                $user_id
            );


            if ($stmt->execute()) {

                $message =
                    "User deactivated successfully.";

                $message_type =
                    "success";

            } else {

                $message =
                    "Could not deactivate user.";

                $message_type =
                    "error";
            }


            $stmt->close();

        } else {

            $message =
                "Deactivation preparation failed.";

            $message_type =
                "error";
        }
    }
}


/* =========================================================
   DELETE USER
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_user"])
) {

    $user_id =
        trim($_POST["user_id"] ?? "");


    if ($user_id === "") {

        $message =
            "User ID is missing.";

        $message_type =
            "error";

    } else {

        /*
         * Delete employee records belonging
         * to this user first.
         *
         * This prevents orphan records when
         * employee.user_id does not have
         * a cascading foreign key.
         */

        $stmt_employee = $conn->prepare("
            DELETE FROM employee
            WHERE user_id = ?
        ");


        if ($stmt_employee) {

            $stmt_employee->bind_param(
                "s",
                $user_id
            );

            $stmt_employee->execute();

            $stmt_employee->close();
        }


        /*
         * Now delete the user.
         */

        $stmt = $conn->prepare("
            DELETE FROM app_users
            WHERE user_id = ?
        ");


        if ($stmt) {

            $stmt->bind_param(
                "s",
                $user_id
            );


            if ($stmt->execute()) {

                if (
                    $stmt->affected_rows > 0
                ) {

                    $message =
                        "User and associated employee records deleted successfully.";

                    $message_type =
                        "success";

                } else {

                    $message =
                        "User not found.";

                    $message_type =
                        "error";
                }

            } else {

                $message =
                    "Delete failed.";

                $message_type =
                    "error";
            }


            $stmt->close();

        } else {

            $message =
                "Delete preparation failed.";

            $message_type =
                "error";
        }
    }
}


/* =========================================================
   READ ALL USERS
========================================================= */

$result = $conn->query("
    SELECT
        id,
        user_id,
        user_name,
        active,
        start_time,
        stop_time,
        last_login,
        created_at,
        updated_at
    FROM app_users
    ORDER BY id ASC
");


/* =========================================================
   ADMIN NAME
========================================================= */

$admin_name =
    $_SESSION["admin_name"] ?? "Administrator";

?>
<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>CURD-EMPLOYEE2 - User Management</title>


<style>

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    padding: 20px;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f2f4f7;

    color: #222;
}


.container {

    width: 95%;

    max-width: 1400px;

    margin: 30px auto;
}


/* =========================================================
   HEADER
========================================================= */

.header {

    background: white;

    padding: 20px;

    border-radius: 10px;

    box-shadow:
        0 3px 10px
        rgba(0,0,0,0.12);

    margin-bottom: 20px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    flex-wrap: wrap;

    gap: 15px;
}


.header h1 {

    margin: 0;

    color: #1d3557;
}


.header-subtitle {

    color: #666;

    margin-top: 5px;
}


.admin-info {

    text-align: right;
}


.admin-name {

    font-weight: bold;

    color: #6f42c1;

    margin-bottom: 8px;
}


.logout-button {

    background: #6c757d;

    color: white;

    padding: 8px 14px;

    border-radius: 5px;

    text-decoration: none;

    font-size: 14px;
}


.logout-button:hover {

    opacity: 0.85;
}


/* =========================================================
   MESSAGE
========================================================= */

.message {

    padding: 12px;

    margin-bottom: 20px;

    border-radius: 6px;

    font-weight: bold;

    text-align: center;
}


.success {

    background: #d1e7dd;

    color: #0f5132;
}


.error {

    background: #f8d7da;

    color: #842029;
}


/* =========================================================
   ACTION BAR
========================================================= */

.action-bar {

    background: white;

    padding: 15px;

    border-radius: 10px;

    box-shadow:
        0 3px 10px
        rgba(0,0,0,0.12);

    margin-bottom: 20px;

    display: flex;

    gap: 10px;

    flex-wrap: wrap;
}


.button {

    display: inline-block;

    padding: 9px 15px;

    border: none;

    border-radius: 5px;

    text-decoration: none;

    cursor: pointer;

    font-size: 14px;
}


.create-button {

    background: #198754;

    color: white;
}


.refresh-button {

    background: #0d6efd;

    color: white;
}


.button:hover {

    opacity: 0.85;
}


/* =========================================================
   TABLE CARD
========================================================= */

.card {

    background: white;

    padding: 20px;

    border-radius: 10px;

    box-shadow:
        0 3px 10px
        rgba(0,0,0,0.12);
}


.card h2 {

    margin-top: 0;

    color: #1d3557;
}


.table-container {

    overflow-x: auto;
}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1100px;
}


th,
td {

    border: 1px solid #ddd;

    padding: 10px;

    text-align: center;

    vertical-align: middle;
}


th {

    background: #1d3557;

    color: white;
}


tr:nth-child(even) {

    background: #f8f9fa;
}


/* =========================================================
   STATUS
========================================================= */

.status-active {

    display: inline-block;

    background: #d1e7dd;

    color: #0f5132;

    padding: 5px 10px;

    border-radius: 15px;

    font-weight: bold;
}


.status-inactive {

    display: inline-block;

    background: #f8d7da;

    color: #842029;

    padding: 5px 10px;

    border-radius: 15px;

    font-weight: bold;
}


/* =========================================================
   ACTION BUTTONS
========================================================= */

.edit-button {

    background: #0d6efd;

    color: white;
}


.activate-button {

    background: #198754;

    color: white;
}


.deactivate-button {

    background: #fd7e14;

    color: white;
}


.delete-button {

    background: #dc3545;

    color: white;
}


.action-form {

    display: inline;
}


.action-cell {

    white-space: nowrap;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 700px) {

    body {

        padding: 10px;
    }


    .container {

        width: 100%;

        margin: 10px auto;
    }


    .header {

        text-align: center;

        justify-content: center;
    }


    .admin-info {

        text-align: center;
    }

}

</style>

</head>


<body>


<div class="container">


<!-- ======================================================
     HEADER
====================================================== -->

<div class="header">


<div>

<h1>
User Management
</h1>

<div class="header-subtitle">
CURD-EMPLOYEE2 Administrator Panel
</div>

</div>


<div class="admin-info">

<div class="admin-name">

Logged in as:
<?php

echo htmlspecialchars(
    $admin_name,
    ENT_QUOTES,
    "UTF-8"
);

?>

</div>


<a
    href="admin.php?logout=1"
    class="logout-button"
>
Admin Logout
</a>

</div>


</div>


<?php

if ($message !== "") {

?>

<div
    class="message
    <?php

    echo $message_type === "success"
        ? "success"
        : "error";

    ?>"
>

<?php

echo htmlspecialchars(
    $message,
    ENT_QUOTES,
    "UTF-8"
);

?>

</div>

<?php

}

?>


<!-- ======================================================
     ACTION BAR
====================================================== -->

<div class="action-bar">


<a
    href="create_user.php"
    class="button create-button"
>
+ Create New User
</a>


<a
    href="admin.php"
    class="button refresh-button"
>
Refresh
</a>


</div>


<!-- ======================================================
     USER TABLE
====================================================== -->

<div class="card">


<h2>
Registered Users
</h2>


<div class="table-container">


<table>


<thead>

<tr>

<th>ID</th>

<th>User ID</th>

<th>User Name</th>

<th>Status</th>

<th>Start Time</th>

<th>Stop Time</th>

<th>Last Login</th>

<th>Created At</th>

<th>Updated At</th>

<th>Action</th>

</tr>

</thead>


<tbody>


<?php

if (
    $result &&
    $result->num_rows > 0
) {

    while (
        $row =
        $result->fetch_assoc()
    ) {

?>


<tr>


<td>

<?php

echo intval(
    $row["id"]
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row["user_id"],
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row["user_name"],
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>


<td>

<?php

if (
    (int)$row["active"] === 1
) {

?>

<span class="status-active">
ACTIVE
</span>

<?php

} else {

?>

<span class="status-inactive">
INACTIVE
</span>

<?php

}

?>

</td>


<td>

<?php

echo !empty($row["start_time"])
    ? htmlspecialchars(
        $row["start_time"],
        ENT_QUOTES,
        "UTF-8"
    )
    : "-";

?>

</td>


<td>

<?php

echo !empty($row["stop_time"])
    ? htmlspecialchars(
        $row["stop_time"],
        ENT_QUOTES,
        "UTF-8"
    )
    : "-";

?>

</td>


<td>

<?php

echo !empty($row["last_login"])
    ? htmlspecialchars(
        $row["last_login"],
        ENT_QUOTES,
        "UTF-8"
    )
    : "Never";

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row["created_at"],
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $row["updated_at"],
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>


<td class="action-cell">


<!-- EDIT -->

<a
    href="edit_user.php?user_id=<?php
        echo urlencode(
            $row["user_id"]
        );
    ?>"
    class="button edit-button"
>
Edit
</a>


<!-- ACTIVATE / DEACTIVATE -->

<?php

if (
    (int)$row["active"] === 1
) {

?>

<form
    method="POST"
    action="admin.php"
    class="action-form"
>

<input
    type="hidden"
    name="user_id"
    value="<?php

        echo htmlspecialchars(
            $row["user_id"],
            ENT_QUOTES,
            "UTF-8"
        );

    ?>"
>

<button
    type="submit"
    name="deactivate_user"
    class="button deactivate-button"
    onclick="
        return confirm(
            'Deactivate this user?'
        );
    "
>
Deactivate
</button>

</form>

<?php

} else {

?>

<form
    method="POST"
    action="admin.php"
    class="action-form"
>

<input
    type="hidden"
    name="user_id"
    value="<?php

        echo htmlspecialchars(
            $row["user_id"],
            ENT_QUOTES,
            "UTF-8"
        );

    ?>"
>

<button
    type="submit"
    name="activate_user"
    class="button activate-button"
>
Activate
</button>

</form>

<?php

}

?>


<!-- DELETE -->

<form
    method="POST"
    action="admin.php"
    class="action-form"
>

<input
    type="hidden"
    name="user_id"
    value="<?php

        echo htmlspecialchars(
            $row["user_id"],
            ENT_QUOTES,
            "UTF-8"
        );

    ?>"
>

<button
    type="submit"
    name="delete_user"
    class="button delete-button"
    onclick="
        return confirm(
            'WARNING! This will delete the user and all employee records belonging to this user. Continue?'
        );
    "
>
Delete
</button>

</form>


</td>


</tr>


<?php

    }

} else {

?>


<tr>

<td
    colspan="10"
>
No users found.
</td>

</tr>


<?php

}

?>


</tbody>

</table>


</div>


</div>


</div>


</body>

</html>

<?php

mysqli_close($conn);

?>