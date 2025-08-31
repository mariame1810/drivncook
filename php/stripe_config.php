<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

define('STRIPE_SECRET_KEY', 'sk_test_51S0wIdRpTaaDfFf74SQpEEuQ5kGWH0to7FIs7psrv7OY2nXJqh2gcnQN9XYiLlQkstEJHt6Hcj83SIBZScqQK9ZI00syfCsoTe'); // à changer
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51S0wIdRpTaaDfFf7PpkPZhiHao63owax3bIU754Ury3tL4u1uN63yJLIGMKkfcB5XewS5rFdPmhBGeBAAYiQvGSI00SwBb4oK1'); // à changer

define('ENTRY_FEE_AMOUNT', 5000000); 
define('ENTRY_FEE_CURRENCY', 'eur');
