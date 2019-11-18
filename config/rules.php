<?php
return [
    'error' => 'site/error',
    'administrate' => 'site/administrate',
    'test' => 'site/test',
    'execution/add' => 'administrator/add-execution',
    'person/<executionNumber:[0-9a-zA-Z]+>' => 'site/index',
    'availability/check' => 'site/availability-check',
    'patients/check' => 'administrator/patients-check',
    'check/files/<executionNumber:[0-9a-zA-Z]+>' => 'administrator/files-check',
    'download/conclusion/<part:[0-9]+>' => 'download/conclusion',
    'print/conclusion/<part:[0-9]+>' => 'download/print-conclusion',
];