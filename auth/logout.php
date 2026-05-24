<?php
session_start();
session_destroy();
header('Location: /KrishiDisha/auth/login.php');
exit;
