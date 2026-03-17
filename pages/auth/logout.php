<?php

require_once '../includes/auth.php';
session_unset();
session_destroy();
header('Location: /e-commerce/pages/auth/login.php');
exit;