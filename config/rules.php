<?php
/** @noinspection SpellCheckingInspection */
return [
    'error' => 'site/error',
    'iolj10zj1dj4sgaj45ijtse96y8wnnkubdyp5i3fg66bqhd5c8' => 'site/iolj10zj1dj4sgaj45ijtse96y8wnnkubdyp5i3fg66bqhd5c8',
    'test' => 'site/test',
    'execution/add' => 'administrator/add-execution',
    'person/<executionNumber:[0-9a-zA-Z]+>' => 'site/index',
    'availability/check' => 'site/availability-check',
    'patients/check' => 'administrator/patients-check',
    'check/files/<executionNumber:[0-9a-zA-Z]+>' => 'administrator/files-check',
    'conclusion/<href:A?\d+-?\d*\.pdf>' => 'download/conclusion',
    'auto-print/<fileName:A?\d+-?\d*\.pdf>' => 'administrator/auto-print',
    'print-conclusion/<href:A?\d+-?\d*\.pdf>' => 'download/print-conclusion',
    'check' => 'site/check',
    'delete-unhandled-folder' => 'administrator/delete-unhandled-folder',
    'rename-unhandled-folder' => 'administrator/rename-unhandled-folder',
    'print-missed-conclusions-list' => 'administrator/print-missed-conclusions-list',
    'download/temp/<link:.+>' => 'download/download-temp',
    'drop' => 'download/drop',
    'mail/add/<id:\d+>' => 'management/handle-mail',
    'mail/add' => 'management/add-mail',
    'mail/change' => 'management/change-mail',
    'mail/delete' => 'management/delete-mail',
    'send-info-mail/<id:\d+>' => 'administrator/send-info-mail',
    'next/<center:nv|aurora>' => 'administrator/register-next-patient',
    'delete/conclusions/<executionNumber:[0-9a-zA-Z]+>' => 'management/delete-conclusions',
    '/dicom-viewer' => 'site/dicom-viewer',
    'api' => 'api/do',
    'rated' => 'user/rate-link-clicked',
    'rate' => 'user/rate',
    'show-changes' => 'administrator/show-notifications',
    'delete/conc/<filename:.+>' => 'administrator/delete-conclusion-file',
    'review' => 'user/review'
];