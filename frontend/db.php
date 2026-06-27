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

// Simple in-memory caching functions
function getCache($key, $ttl = 300) {
    if (!isset($_SESSION['_cache'])) {
        $_SESSION['_cache'] = [];
    }
    if (isset($_SESSION['_cache'][$key])) {
        $item = $_SESSION['_cache'][$key];
        if (time() - $item['time'] < $ttl) {
            return $item['data'];
        }
        // Expired, remove it
        unset($_SESSION['_cache'][$key]);
    }
    return null;
}

function setCache($key, $data) {
    if (!isset($_SESSION['_cache'])) {
        $_SESSION['_cache'] = [];
    }
    $_SESSION['_cache'][$key] = ['data' => $data, 'time' => time()];
}

function clearCache($pattern = null) {
    if (!isset($_SESSION['_cache'])) {
        return;
    }
    if ($pattern) {
        foreach (array_keys($_SESSION['_cache']) as $key) {
            if (strpos($key, $pattern) !== false) {
                unset($_SESSION['_cache'][$key]);
            }
        }
    } else {
        $_SESSION['_cache'] = [];
    }
}
