<?php
// Base configuration
define('BASE_URL', 'http://localhost/website');
define('DOMAIN_PATH', $_SERVER['DOCUMENT_ROOT'] . '/website');
define('CONNECT_PATH', DOMAIN_PATH . '/config/connect.php');
define('GLOBAL_FUNC', DOMAIN_PATH . '/config/global_func.php');
define('CL_SESSION_PATH', DOMAIN_PATH . '/config/session.php');
define('VALIDATOR_PATH', DOMAIN_PATH . '/config/validator.php');
define('FOOTER_PATH', DOMAIN_PATH . '/global/footer.php');
define('ISLOGIN', DOMAIN_PATH . '/config/islogin.php');
define('QUERY_LIMIT', 10);

// System Access Configuration
define('SYSTEM_ACCESS', [
    'E-ENROLL' => [
        'role' => [
            'ADMIN' => 'Administrator',
            'REGISTRAR' => 'Registrar',
            'DEAN' => 'Dean',
            'FACULTY' => 'Faculty',
            'STUDENT' => 'Student'
        ]
    ]
]);
?>