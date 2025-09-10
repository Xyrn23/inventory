<?php

$users = ["lenny", "jersey", "aaron", "winalyn", "janvyn"];
$password = "admin123";

foreach ($users as $u) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "INSERT INTO users (username, password) VALUES ('$u', '$hash');\n";
}
