<?php

$hook['pre_system'][] = [
    'class' => 'InitAppCore',
    'function' => 'initialize',
    'filename' => 'InitAppCore.php',
    'filepath' => 'hooks'
];

// Enabling Errors Handler
$hook['pre_system'][] = [
    'class' => 'ErrorsHandler',
    'function' => 'index',
    'filename' => 'ErrorsHandler.php',
    'filepath' => 'hooks',
    'params' => []
];
