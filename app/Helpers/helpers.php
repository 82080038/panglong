<?php

if (!function_exists('format_currency')) {
    function format_currency($amount)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

if (!function_exists('format_percentage')) {
    function format_percentage($value)
    {
        return number_format($value * 100, 2) . '%';
    }
}
