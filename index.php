<?php

// Shared hosting setups often point the domain to the project root instead of /public.
// Delegate the request to Laravel's real public front controller.
require __DIR__ . '/public/index.php';
