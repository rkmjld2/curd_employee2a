<?php

$con = mysqli_connect(
    getenv("DB_HOST"),
    getenv("DB_USER"),
    getenv("DB_PASSWORD"),
    getenv("DB_NAME"),
    (int)getenv("DB_PORT")
);

if (mysqli_connect_errno())
{
    echo 'Database Connection Error';
}
