<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

define('STRIPE_SECRET_KEY'); // à changer
define('STRIPE_PUBLISHABLE_KEY'); // à changer

define('ENTRY_FEE_AMOUNT', 5000000); 
define('ENTRY_FEE_CURRENCY', 'eur');
