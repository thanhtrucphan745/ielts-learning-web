<?php
require_once __DIR__ . '/../auth.php';

require_teacher();
header('Location: dashboard.php');
exit;
