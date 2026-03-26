<?php

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Auto-stub Magento auto-generated factory classes (*Factory) that don't exist
 * outside of a full Magento installation with compiled DI.
 */
spl_autoload_register(function (string $class) {
    if (!str_ends_with($class, 'Factory') || class_exists($class, false)) {
        return;
    }

    $lastSep = strrpos($class, '\\');
    $ns = substr($class, 0, $lastSep);
    $shortName = substr($class, $lastSep + 1);

    eval("namespace {$ns}; class {$shortName} { public function create(array \$data = []) { return null; } }");
});
