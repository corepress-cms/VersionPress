<?php

namespace VersionPress\Tests\Utils;

use VersionPress\Utils\ArrayUtils;

/**
 * Class used for mocking hooks in tests.
 *
 * It simulates the behavior of WordPress (TRUE_HOOKS): you can call `add_filter` and `apply_filters`
 * and the registered filter will actually be executed. With no hooks registered, filters pass values through.
 *
 * Usage:
 *
 * Just call `HookMock::setUp` in the `setUp` method of your test case
 * and `HookMock::tearDown` in the `tearDown` method.
 *
 * Note:
 * For now, this class supports only filters.
 *
 */
class HookMock
{

    const TRUE_HOOKS = 'true-hooks';

    private static $type = null;

    private static $hooks = [];

    public static function setUp($type = HookMock::TRUE_HOOKS)
    {
        require_once __DIR__ . '/hookmock-functions.php';

        self::$type = $type;
        self::$hooks = [];
    }

    public static function tearDown()
    {
        self::$type = null;
    }

    public static function applyFilters($tag, $value)
    {
        $args = func_get_args();
        $args = array_slice($args, 1);
        $args[0] = $value;

        if (self::$type === HookMock::TRUE_HOOKS) {
            $relatedFilters = self::getRelatedHooks($tag, 'filters');

            foreach ($relatedFilters as $filter) {
                $fn = $filter['fn'];
                $acceptedArgs = $filter['args'];
                $args[0] = $value;

                $value = call_user_func_array($fn, array_slice($args, 0, $acceptedArgs));
            }
        }

        return $value;
    }

    public static function addFilter($tag, $fn, $priority = 10, $acceptedArgs = 1)
    {

        if (self::$type === HookMock::TRUE_HOOKS) {
            self::$hooks['filters'][$tag][] = ['fn' => $fn, 'priority' => $priority, 'args' => $acceptedArgs];
        }
    }

    public static function doAction($tag, $arg = '')
    {
        $args = func_get_args();
        $args = array_slice($args, 1);

        if (self::$type === HookMock::TRUE_HOOKS) {
            $relatedHooks = self::getRelatedHooks($tag, 'actions');

            foreach ($relatedHooks as $hook) {
                $fn = $hook['fn'];
                $acceptedArgs = $hook['args'];

                call_user_func_array($fn, array_slice($args, 0, $acceptedArgs));
            }
        }
    }

    public static function addAction($tag, $fn, $priority = 10, $acceptedArgs = 1)
    {

        if (self::$type === HookMock::TRUE_HOOKS) {
            self::$hooks['actions'][$tag][] = ['fn' => $fn, 'priority' => $priority, 'args' => $acceptedArgs];
        }
    }

    private static function getRelatedHooks($tag, $hookType)
    {
        $relatedHooks = isset(self::$hooks[$hookType][$tag]) ? self::$hooks[$hookType][$tag] : [];

        ArrayUtils::stablesort($relatedHooks, function ($hook1, $hook2) {
            return $hook1['priority'] - $hook2['priority'];
        });

        return $relatedHooks;
    }
}
