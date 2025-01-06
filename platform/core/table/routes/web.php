<?php

use Botble\Base\Facades\AdminHelper;

AdminHelper::registerRoutes(function () {
    require __DIR__ . '/web-actions.php';
});
