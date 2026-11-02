
<?php

use Illuminate\Support\Facades\Route;

// Public website routes.
require __DIR__ . '/web/public.php';

// Admin authentication and dashboard routes.
require __DIR__ . '/web/admin.php';

// Partner authentication and dashboard routes.
require __DIR__ . '/web/partner.php';

// Partner Center authentication and dashboard routes.
require __DIR__ . '/web/partner-center.php';

// Placement Partner authentication and dashboard routes.
require __DIR__ . '/web/partner-placement.php';

// Field Agent authentication and dashboard routes.
require __DIR__ . '/web/field-agent.php';
