<?php

namespace Versionist\Tests\Utils;

/**
 * Minimal stand-in for WordPress's `wpdb` exposing only the query methods used by `SqlQueryParser`,
 * so unit tests can mock it without loading WordPress (whose `wpdb` may be patched by Versionist).
 */
class WpdbStub
{
    public function get_results($query = null, $output = 'OBJECT')
    {
    }

    public function get_col($query = null, $x = 0)
    {
    }
}
