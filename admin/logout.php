<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/auth.php';

Auth::logout();
header('Location: index?msg=logged_out');
exit;
