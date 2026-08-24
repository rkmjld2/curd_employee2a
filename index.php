<?php
/*
============================================================
CURD-EMPLOYEE2
EMPLOYEE PAYMENT CRUD

USER LOGIN + LICENSE PROTECTION
============================================================
*/

date_default_timezone_set("Asia/Kolkata");

session_start();

/* =========================================================
   LICENSE PROTECTION
========================================================= */

require_once __DIR__ . "/license_guard.php";


/* =========================================================
   DATABASE
========================================================= */

require_once __DIR__ . "/db.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

if (
    !isset($_SESSION["app_user_id"]) ||
    $_SESSION["app_user_id"] === ""
) {

    header("Location: login.php");

    exit;
}


/* =========================================================
   CURRENT USER
========================================================= */

$current_user_id =
    mysqli_real_escape_string(
        $conn,
        $_SESSION["app_user_id"]
    );


$message = "";


/* =========================================================
   DELETE
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["delete_id"])
) {

    $id =
        intval($_POST["delete_id"]);


    if ($id > 0) {

        /*
         * Delete ONLY the record belonging
         * to the logged-in user.
         */

        $sql = "
            DELETE FROM employee
            WHERE id = $id
            AND user_id = '$current_user_id'
        ";


        if (mysqli_query($conn, $sql)) {

            if (mysqli_affected_rows($conn) > 0) {

                $message =
                    "Employee record deleted successfully.";

            } else {

                $message =
                    "Record not found or does not belong to this user.";
            }

        } else {

            $message =
                "Delete failed: "
                . mysqli_error($conn);
        }
    }
}


/* =========================================================
   ADD / UPDATE
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["save"])
) {

    $id =
        isset($_POST["id"])
        ? intval($_POST["id"])
        : 0;


    $employee_name =
        mysqli_real_escape_string(
            $conn,
            trim($_POST["employee_name"] ?? "")
        );


    $basic_pay =
        floatval(
            $_POST["basic_pay"] ?? 0
        );


    $da_percent =
        floatval(
            $_POST["da_percent"] ?? 0
        );


    $hra_percent =
        floatval(
            $_POST["hra_percent"] ?? 0
        );


    $pf_deduction =
        floatval(
            $_POST["pf_deduction"] ?? 0
        );


    $other_allowance =
        floatval(
            $_POST["other_allowance"] ?? 0
        );


    /* =====================================================
       CALCULATIONS
    ===================================================== */

    $da_amount =
        ($basic_pay * $da_percent) / 100;


    $hra_amount =
        ($basic_pay * $hra_percent) / 100;


    /*
     * Correct formula:
     *
     * Total =
     * Basic
     * + DA
     * + HRA
     * - PF
     * + Other Allowance
     */

    $total_payment =
        $basic_pay
        + $da_amount
        + $hra_amount
        - $pf_deduction
        + $other_allowance;


    /* =====================================================
       UPDATE EXISTING RECORD
    ===================================================== */

    if ($id > 0) {

        /*
         * Update ONLY this user's record.
         */

        $sql = "

            UPDATE employee SET

                Employee_name = '$employee_name',

                BASIC_PAY = $basic_pay,

                DA_PERCENT = $da_percent,

                DA_AMOUNT = $da_amount,

                HRA_PERCENT = $hra_percent,

                HRA_AMOUNT = $hra_amount,

                PF_DEDUCTION = $pf_deduction,

                ANY_OTHER_ALLOWANCE = $other_allowance,

                TOTAL_PAYMENT = $total_payment

            WHERE id = $id

            AND user_id = '$current_user_id'

        ";


        if (mysqli_query($conn, $sql)) {

            if (mysqli_affected_rows($conn) > 0) {

                $message =
                    "Employee record updated successfully.";

            } else {

                $message =
                    "Record not found or does not belong to this user.";
            }

        } else {

            $message =
                "Update failed: "
                . mysqli_error($conn);
        }


    } else {

        /* =================================================
           ADD NEW RECORD

           IMPORTANT:
           user_id is now included.
        ================================================= */


        $sql = "

            INSERT INTO employee

            (
                user_id,

                Employee_name,

                BASIC_PAY,

                DA_PERCENT,

                DA_AMOUNT,

                HRA_PERCENT,

                HRA_AMOUNT,

                PF_DEDUCTION,

                ANY_OTHER_ALLOWANCE,

                TOTAL_PAYMENT
            )

            VALUES

            (
                '$current_user_id',

                '$employee_name',

                $basic_pay,

                $da_percent,

                $da_amount,

                $hra_percent,

                $hra_amount,

                $pf_deduction,

                $other_allowance,

                $total_payment
            )

        ";


        if (mysqli_query($conn, $sql)) {

            $message =
                "Employee record added successfully.";

        } else {

            $message =
                "Insert failed: "
                . mysqli_error($conn);
        }
    }
}


/* =========================================================
   EDIT
========================================================= */

$edit = NULL;


if (isset($_GET["edit"])) {

    $id =
        intval($_GET["edit"]);


    if ($id > 0) {

        $edit_result =
            mysqli_query(
                $conn,

                "
                SELECT *
                FROM employee
                WHERE id = $id
                AND user_id = '$current_user_id'
                "
            );


        if ($edit_result) {

            $edit =
                mysqli_fetch_assoc(
                    $edit_result
                );
        }
    }
}


/* =========================================================
   SQL SELECT SEARCH
========================================================= */

$search_sql = "";

$search_result = NULL;

$search_error = "";

$search_count = 0;

$search_fields = array();

$has_id_column = false;


if (isset($_GET["search"])) {

    $search_sql =
        trim($_GET["search"]);


    if ($search_sql !== "") {

        /* Allow one optional semicolon at end */

        $search_sql =
            rtrim($search_sql);

        $search_sql =
            rtrim(
                $search_sql,
                ";"
            );

        $search_sql =
            trim($search_sql);


        /* Must begin with SELECT */

        if (
            !preg_match(
                '/^SELECT\s/i',
                $search_sql
            )
        ) {

            $search_error =
                "Only SELECT statements are allowed.";


        /* Reject modification commands */

        } elseif (
            preg_match(
                '/\b(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|CREATE|RENAME|REPLACE|GRANT|REVOKE|CALL|LOAD|SET|USE)\b/i',
                $search_sql
            )
        ) {

            $search_error =
                "Only SELECT statements are allowed.";


        /* No multiple statements */

        } elseif (
            strpos(
                $search_sql,
                ";"
            ) !== false
        ) {

            $search_error =
                "Please enter only one SELECT statement.";


        } else {

            /*
             * IMPORTANT:
             *
             * The user can run SELECT statements,
             * but the query must be restricted to
             * this user's employee records.
             *
             * We therefore reject SELECT queries that
             * do not contain the current user restriction.
             */

            if (
                !preg_match(
                    '/\bFROM\s+employee\b/i',
                    $search_sql
                )
            ) {

                $search_error =
                    "Search is allowed only on the employee table.";

            } else {

                /*
                 * Add user restriction automatically.
                 *
                 * If WHERE already exists:
                 *    append AND user_id = ...
                 *
                 * Otherwise:
                 *    add WHERE user_id = ...
                 */

                if (
                    preg_match(
                        '/\bWHERE\b/i',
                        $search_sql
                    )
                ) {

                    $search_sql .=
                        " AND user_id = '"
                        . $current_user_id
                        . "'";

                } else {

                    $search_sql .=
                        " WHERE user_id = '"
                        . $current_user_id
                        . "'";
                }


                $search_result =
                    mysqli_query(
                        $conn,
                        $search_sql
                    );


                if (!$search_result) {

                    $search_error =
                        "SQL Error: "
                        . mysqli_error($conn);

                } else {

                    $search_count =
                        mysqli_num_rows(
                            $search_result
                        );


                    $search_fields =
                        mysqli_fetch_fields(
                            $search_result
                        );


                    foreach (
                        $search_fields
                        as $field
                    ) {

                        if (
                            strtolower(
                                $field->name
                            )
                            ===
                            "id"
                        ) {

                            $has_id_column = true;

                            break;
                        }
                    }
                }
            }
        }
    }
}


/* =========================================================
   NORMAL EMPLOYEE LIST
========================================================= */

$result =
    mysqli_query(
        $conn,

        "
        SELECT *
        FROM employee
        WHERE user_id = '$current_user_id'
        ORDER BY id DESC
        "
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
Employee Payment CRUD
</title>


<style>

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family:
        Arial,
        sans-serif;

    background: #f2f4f7;
}


.container {

    width: 95%;

    max-width: 1450px;

    margin: 30px auto;
}


h1 {

    text-align: center;

    color: #1d3557;

    margin-bottom: 10px;
}


.user-bar {

    text-align: center;

    background: #e7f1ff;

    color: #084298;

    padding: 12px;

    margin-bottom: 25px;

    border-radius: 6px;

    font-weight: bold;
}


.logout-button {

    display: inline-block;

    padding: 10px 20px;

    background: #dc3545;

    color: white;

    text-decoration: none;

    border-radius: 6px;

    margin-left: 10px;
}


.logout-button:hover {

    background: #b02a37;
}


h2 {

    color: #1d3557;
}


.card {

    background: white;

    padding: 25px;

    margin-bottom: 25px;

    border-radius: 10px;

    box-shadow:
        0 3px 10px
        rgba(0,0,0,0.12);
}


.message {

    background: #d1e7dd;

    color: #0f5132;

    padding: 12px;

    margin-bottom: 20px;

    border-radius: 5px;

    font-weight: bold;
}


.search-box textarea {

    width: 100%;

    min-height: 100px;

    padding: 12px;

    border: 1px solid #999;

    border-radius: 6px;

    font-family:
        Consolas,
        monospace;

    font-size: 15px;

    resize: vertical;
}


.search-buttons {

    margin-top: 10px;

    display: flex;

    gap: 10px;

    flex-wrap: wrap;
}


button,
.btn {

    padding: 9px 16px;

    border: none;

    border-radius: 5px;

    cursor: pointer;

    text-decoration: none;

    display: inline-block;

    font-size: 14px;
}


.search-button {

    background: #6f42c1;

    color: white;
}


.clear-button {

    background: #6c757d;

    color: white;
}


.print-button {

    background: #198754;

    color: white;
}


.search-info {

    background: #e7f1ff;

    padding: 12px;

    margin-top: 15px;

    margin-bottom: 15px;

    border-radius: 5px;

    color: #084298;
}


.search-error {

    background: #f8d7da;

    color: #842029;

    padding: 12px;

    margin-top: 15px;

    margin-bottom: 15px;

    border-radius: 5px;

    font-weight: bold;
}


.search-help {

    background: #fff3cd;

    padding: 15px;

    margin-top: 15px;

    border-radius: 5px;

    line-height: 1.7;
}


.search-help code {

    background: #eee;

    padding: 3px 6px;

    border-radius: 4px;

    font-family:
        Consolas,
        monospace;
}


.form-grid {

    display: grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(200px, 1fr)
        );

    gap: 15px;
}


.form-group {

    display: flex;

    flex-direction: column;
}


label {

    font-weight: bold;

    margin-bottom: 6px;
}


input {

    padding: 10px;

    border: 1px solid #aaa;

    border-radius: 5px;

    font-size: 15px;
}


.save {

    background: #198754;

    color: white;

    margin-top: 20px;
}


.cancel {

    background: #6c757d;

    color: white;

    margin-top: 20px;
}


.edit {

    background: #0d6efd;

    color: white;
}


.delete {

    background: #dc3545;

    color: white;
}


.table-container {

    overflow-x: auto;
}


table {

    width: 100%;

    border-collapse: collapse;
}


th,
td {

    border: 1px solid #ddd;

    padding: 10px;

    text-align: center;
}


th {

    background: #1d3557;

    color: white;
}


tr:nth-child(even) {

    background: #f8f9fa;
}


.total {

    font-weight: bold;

    color: green;
}


.action-cell {

    white-space: nowrap;
}


.delete-form {

    display: inline;
}


.note {

    background: #fff3cd;

    padding: 12px;

    margin-bottom: 20px;

    border-radius: 5px;

    line-height: 1.7;
}


.print-button-area {

    margin: 15px 0;
}


/* =========================================================
   PRINT SEARCH RESULT ONLY
========================================================= */

@media print {

    body * {

        visibility: hidden !important;
    }


    #search-print-area,
    #search-print-area * {

        visibility: visible !important;
    }


    #search-print-area {

        position: absolute;

        left: 0;

        top: 0;

        width: 100%;
    }


    #search-print-area table {

        width: 100%;

        border-collapse: collapse;
    }


    #search-print-area th,
    #search-print-area td {

        border: 1px solid #000;

        padding: 6px;

        text-align: center;
    }


    #search-print-area th {

        background: #ddd !important;

        color: #000 !important;
    }


    .print-button-area {

        display: none !important;
    }
}

</style>

</head>


<body>


<div class="container">


<h1>
Employee Payment CRUD
</h1>


<div class="user-bar">

Logged in User:

<?php

echo htmlspecialchars(
    $_SESSION["app_user_name"]
    ?? $_SESSION["app_user_id"],
    ENT_QUOTES,
    "UTF-8"
);

?>

&nbsp;&nbsp;

User ID:

<?php

echo htmlspecialchars(
    $_SESSION["app_user_id"],
    ENT_QUOTES,
    "UTF-8"
);

?>


<a
    href="logout.php"
    class="logout-button"
>
Logout
</a>

</div>


<?php if ($message !== ""): ?>

<div class="message">

<?php

echo htmlspecialchars(
    $message,
    ENT_QUOTES,
    "UTF-8"
);

?>

</div>

<?php endif; ?>


<!-- =====================================================
     SQL SELECT SEARCH
===================================================== -->

<div class="card">

<h2>
SQL SELECT Search
</h2>


<div class="search-box">

<form
    method="GET"
    action=""
>

<textarea
    name="search"
    placeholder="Enter MySQL SELECT command here..."
><?php
echo htmlspecialchars(
    $search_sql,
    ENT_QUOTES
);
?></textarea>


<div class="search-buttons">

<button
    type="submit"
    class="btn search-button"
>
Search
</button>


<a
    href="index.php"
    class="btn clear-button"
>
Show All
</a>

</div>

</form>

</div>


<div class="search-help">

<strong>
Enter any valid single SELECT statement.
</strong>

<br>

Only SELECT statements are allowed.

<br><br>

<strong>
Examples:
</strong>

<br><br>

<code>
SELECT * FROM employee;
</code>

<br><br>

<code>
SELECT * FROM employee WHERE id = 1;
</code>

<br><br>

<code>
SELECT * FROM employee WHERE Employee_name LIKE '%Ravi%';
</code>

<br><br>

<code>
SELECT * FROM employee WHERE id BETWEEN 1 AND 10;
</code>

<br><br>

<code>
SELECT COUNT(*) AS TotalEmployees FROM employee;
</code>

<br><br>

<code>
SELECT SUM(TOTAL_PAYMENT) AS TotalPayment FROM employee;
</code>

<br><br>

<code>
SELECT AVG(BASIC_PAY) AS AverageBasicPay FROM employee;
</code>

<br><br>

<code>
SELECT MAX(TOTAL_PAYMENT) AS HighestPayment FROM employee;
</code>

<br><br>

<strong>
Do NOT use INSERT, UPDATE, DELETE, DROP, ALTER,
TRUNCATE, CREATE, etc. in the search box.
</strong>

</div>


<?php if ($search_error !== ""): ?>

<div class="search-error">

<?php

echo htmlspecialchars(
    $search_error,
    ENT_QUOTES,
    "UTF-8"
);

?>

</div>

<?php endif; ?>


<?php if (
    $search_sql !== ""
    &&
    $search_error === ""
    &&
    $search_result
): ?>

<div class="search-info">

<strong>
Search completed.
</strong>

&nbsp;&nbsp;

Rows returned:

<strong>
<?php echo $search_count; ?>
</strong>

</div>


<div class="print-button-area">

<button
    type="button"
    class="btn print-button"
    onclick="printSearchResult()"
>
Print Search Result
</button>

</div>


<div
    id="search-print-area"
    class="table-container"
>

<table>

<thead>

<tr>

<?php foreach (
    $search_fields
    as $field
): ?>

<th>

<?php

echo htmlspecialchars(
    $field->name,
    ENT_QUOTES,
    "UTF-8"
);

?>

</th>

<?php endforeach; ?>


<?php if ($has_id_column): ?>

<th>
Action
</th>

<?php endif; ?>

</tr>

</thead>


<tbody>


<?php if ($search_count > 0): ?>


<?php while (
    $row =
    mysqli_fetch_assoc(
        $search_result
    )
): ?>

<tr>


<?php foreach (
    $search_fields
    as $field
): ?>


<?php

$column =
    $field->name;

$value =
    isset($row[$column])
    ? $row[$column]
    : "";

?>


<td>

<?php

if (
    is_numeric($value)
    &&
    preg_match(
        '/(PAY|AMOUNT|BASIC|DEDUCTION|ALLOWANCE|PERCENT)/i',
        $column
    )
) {

    echo number_format(
        (float)$value,
        2
    );

} else {

    echo htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

</td>


<?php endforeach; ?>


<?php if ($has_id_column): ?>

<td class="action-cell">


<a
    href="?edit=<?php
        echo intval($row["id"]);
    ?>"
    class="btn edit"
>
Edit
</a>


<form
    method="POST"
    action=""
    class="delete-form"
>

<input
    type="hidden"
    name="delete_id"
    value="<?php
        echo intval($row["id"]);
    ?>"
>


<button
    type="submit"
    class="btn delete"
    onclick="return confirm('Are you sure you want to delete this employee record?');"
>
Delete
</button>

</form>


</td>

<?php endif; ?>


</tr>

<?php endwhile; ?>


<?php else: ?>

<tr>

<td
    colspan="<?php
        echo count($search_fields)
        + ($has_id_column ? 1 : 0);
    ?>"
>

No records found.

</td>

</tr>

<?php endif; ?>


</tbody>

</table>

</div>

<?php endif; ?>

</div>


<!-- =====================================================
     ADD / UPDATE EMPLOYEE
===================================================== -->

<div class="card">

<h2>

<?php

echo $edit
    ? "Edit Employee"
    : "Add Employee";

?>

</h2>


<div class="note">

<strong>
Calculation:
</strong>

<br><br>

DA Amount =

Basic Pay × DA % / 100

<br>

HRA Amount =

Basic Pay × HRA % / 100

<br><br>

<strong>

Total Payment =

Basic Pay + DA Amount + HRA Amount
- PF Deduction + Other Allowance

</strong>

</div>


<form
    method="POST"
    action=""
>

<input
    type="hidden"
    name="id"
    value="<?php

        echo $edit
            ? intval($edit["id"])
            : "";

    ?>"
>


<div class="form-grid">


<div class="form-group">

<label>
Employee Name
</label>

<input
    type="text"
    name="employee_name"
    maxlength="100"
    required
    value="<?php

        echo $edit
            ? htmlspecialchars(
                $edit["Employee_name"],
                ENT_QUOTES,
                "UTF-8"
            )
            : "";

    ?>"
>

</div>


<div class="form-group">

<label>
Basic Pay
</label>

<input
    type="number"
    step="0.01"
    min="0"
    name="basic_pay"
    required
    value="<?php

        echo $edit
            ? $edit["BASIC_PAY"]
            : "";

    ?>"
>

</div>


<div class="form-group">

<label>
DA %
</label>

<input
    type="number"
    step="0.01"
    min="0"
    name="da_percent"
    required
    value="<?php

        echo $edit
            ? $edit["DA_PERCENT"]
            : "";

    ?>"
>

</div>


<div class="form-group">

<label>
HRA %
</label>

<input
    type="number"
    step="0.01"
    min="0"
    name="hra_percent"
    required
    value="<?php

        echo $edit
            ? $edit["HRA_PERCENT"]
            : "";

    ?>"
>

</div>


<div class="form-group">

<label>
PF Deduction
</label>

<input
    type="number"
    step="0.01"
    min="0"
    name="pf_deduction"
    required
    value="<?php

        echo $edit
            ? $edit["PF_DEDUCTION"]
            : "0";

    ?>"
>

</div>


<div class="form-group">

<label>
Any Other Allowance
</label>

<input
    type="number"
    step="0.01"
    min="0"
    name="other_allowance"
    required
    value="<?php

        echo $edit
            ? $edit["ANY_OTHER_ALLOWANCE"]
            : "0";

    ?>"
>

</div>


</div>


<?php if ($edit): ?>


<button
    type="submit"
    name="save"
    class="btn save"
>
Update Employee
</button>


<a
    href="index.php"
    class="btn cancel"
>
Cancel
</a>


<?php else: ?>


<button
    type="submit"
    name="save"
    class="btn save"
>
Add Employee
</button>


<?php endif; ?>


</form>

</div>


<!-- =====================================================
     ALL EMPLOYEE RECORDS
===================================================== -->

<div class="card">

<h2>
All Employee Records
</h2>


<div class="table-container">

<table>

<thead>

<tr>

<th>
Employee Name
</th>

<th>
ID
</th>

<th>
Basic Pay
</th>

<th>
DA %
</th>

<th>
DA Amount
</th>

<th>
HRA %
</th>

<th>
HRA Amount
</th>

<th>
PF Deduction
</th>

<th>
Other Allowance
</th>

<th>
Total Payment
</th>

<th>
Action
</th>

</tr>

</thead>


<tbody>


<?php if (
    $result
    &&
    mysqli_num_rows($result) > 0
): ?>


<?php while (
    $row =
    mysqli_fetch_assoc($result)
): ?>

<tr>

<td>

<?php

echo htmlspecialchars(
    $row["Employee_name"],
    ENT_QUOTES,
    "UTF-8"
);

?>

</td>


<td>

<?php

echo intval(
    $row["id"]
);

?>

</td>


<td>

<?php

echo number_format(
    $row["BASIC_PAY"],
    2
);

?>

</td>


<td>

<?php

echo number_format(
    $row["DA_PERCENT"],
    2
);

?>

%

</td>


<td>

<?php

echo number_format(
    $row["DA_AMOUNT"],
    2
);

?>

</td>


<td>

<?php

echo number_format(
    $row["HRA_PERCENT"],
    2
);

?>

%

</td>


<td>

<?php

echo number_format(
    $row["HRA_AMOUNT"],
    2
);

?>

</td>


<td>

<?php

echo number_format(
    $row["PF_DEDUCTION"],
    2
);

?>

</td>


<td>

<?php

echo number_format(
    $row["ANY_OTHER_ALLOWANCE"],
    2
);

?>

</td>


<td class="total">

<?php

echo number_format(
    $row["TOTAL_PAYMENT"],
    2
);

?>

</td>


<td class="action-cell">


<a
    href="?edit=<?php
        echo intval($row["id"]);
    ?>"
    class="btn edit"
>
Edit
</a>


<form
    method="POST"
    action=""
    class="delete-form"
>

<input
    type="hidden"
    name="delete_id"
    value="<?php
        echo intval($row["id"]);
    ?>"
>


<button
    type="submit"
    class="btn delete"
    onclick="return confirm('Are you sure you want to delete this employee record?');"
>
Delete
</button>

</form>


</td>

</tr>

<?php endwhile; ?>


<?php else: ?>

<tr>

<td colspan="11">

No employee records found.

</td>

</tr>

<?php endif; ?>


</tbody>

</table>

</div>

</div>


</div>


<script>

/*
=========================================================
PRINT SEARCH RESULT
=========================================================
*/

function printSearchResult() {

    window.print();

}

</script>


</body>

</html>


<?php

mysqli_close($conn);

?>
