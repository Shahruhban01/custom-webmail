<?php
require_once __DIR__ . '/../config.php';
requireAuth();

// Reuse sent.php logic but locked to drafts filter
$_GET['filter'] = 'draft';
require __DIR__ . '/sent.php';
