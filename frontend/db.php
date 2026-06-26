<?php

$db = null;

function db() {
    global $db;
    if ($db === null) {
        $path = __DIR__ . '/../database/database.sqlite';
        $db = new PDO('sqlite:' . $path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec('PRAGMA foreign_keys = ON');
    }
    return $db;
}
