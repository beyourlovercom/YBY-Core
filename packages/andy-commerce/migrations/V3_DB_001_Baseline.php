<?php
/** Keep the historical BYL V3 baseline migration ID. No business-data changes. */
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; }
return array(
    'id' => 'V3_DB_001',
    'description' => 'Register V3 migration baseline; no business data transformation.',
    'up' => static function (): void {},
    'verify' => static function (): bool { return true; },
);
