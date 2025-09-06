<?php
// auth/logout.php — вихід
declare(strict_types=1);
session_start();
session_destroy();
header('Location: /index.php');
