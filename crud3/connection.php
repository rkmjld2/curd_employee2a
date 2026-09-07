
<?php

$con = mysqli_init();

mysqli_ssl_set(
    $con,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL
);

mysqli_real_connect(
    $con,
    getenv("DB_HOST"),
    getenv("DB_USER"),
    getenv("DB_PASSWORD"),
    getenv("CRUD3_DB_NAME"),
    (int)getenv("DB_PORT"),
    NULL,
    MYSQLI_CLIENT_SSL
);

if (mysqli_connect_errno())
{
    echo 'Database Connection Error';
}

