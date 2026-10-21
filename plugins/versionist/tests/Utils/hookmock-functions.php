<?php

if (!function_exists('apply_filters')) {
    function apply_filters()
    {
        return call_user_func_array('\Versionist\Tests\Utils\HookMock::applyFilters', func_get_args());
    }
}

if (!function_exists('add_filter')) {
    function add_filter()
    {
        return call_user_func_array('\Versionist\Tests\Utils\HookMock::addFilter', func_get_args());
    }
}

if (!function_exists('do_action')) {
    function do_action()
    {
        return call_user_func_array('\Versionist\Tests\Utils\HookMock::doAction', func_get_args());
    }
}

if (!function_exists('add_action')) {
    function add_action()
    {
        return call_user_func_array('\Versionist\Tests\Utils\HookMock::addAction', func_get_args());
    }
}

// Request helpers used by plugin definition hooks (`.versionpress/hooks.php`), mirroring WordPress core.

if (!function_exists('wp_doing_ajax')) {
    function wp_doing_ajax()
    {
        return defined('DOING_AJAX') && DOING_AJAX;
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        return is_array($value) ? array_map('wp_unslash', $value) : (is_string($value) ? stripslashes($value) : $value);
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key)
    {
        return is_scalar($key) ? preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$key)) : '';
    }
}
