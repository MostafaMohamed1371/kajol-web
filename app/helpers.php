<?php

if (!function_exists('format_price')) {
    function format_price($amount) {
        return number_format((float)$amount, 2);
    }
}