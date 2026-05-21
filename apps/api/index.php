<?php
// Router script compatibility for php -S built-in server
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (file_exists(__DIR__ . $path) && is_file(__DIR__ . $path)) {
        return false;
    }
}

// Front Controller & Router

// 1. Session Setup
// Set session cookie parameters for security
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// 2. Autoloader
spl_autoload_register(function ($class) {
    $dirs = [
        __DIR__ . '/config/',
        __DIR__ . '/controllers/',
        __DIR__ . '/repositories/',
        __DIR__ . '/libs/'
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// 3. Load Configurations
$config = require __DIR__ . '/config/config.php';

// 4. CORS Headers
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigin = $config['cors_origin'];

if ($origin === $allowedOrigin || $allowedOrigin === '*') {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    // Fallback default
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle CORS Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 5. Global Error Handler
set_exception_handler(function ($e) {
    header('Content-Type: application/json');
    $statusCode = 500;
    
    // Check if it's a custom application error
    if (method_exists($e, 'getStatusCode')) {
        $statusCode = $e->getStatusCode();
    }
    
    http_response_code($statusCode);
    
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    // Add validation errors if applicable
    if (method_exists($e, 'getErrors') && $e->getErrors()) {
        $response['errors'] = $e->getErrors();
    }
    
    // In dev mode, show trace
    if (getenv('NODE_ENV') === 'development' || $_SERVER['SERVER_NAME'] === 'localhost') {
        $response['trace'] = $e->getTraceAsString();
    }
    
    echo json_encode($response);
    exit();
});

// Helper class for throwing custom HTTP exceptions
class HttpException extends Exception {
    private $statusCode;
    private $errors;
    
    public function __construct($message, $statusCode = 400, $errors = null) {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }
    
    public function getStatusCode() {
        return $this->statusCode;
    }
    
    public function getErrors() {
        return $this->errors;
    }
}

// Helper function to send JSON response
function sendJson($data, $statusCode = 200, $message = '') {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode([
        'success' => $statusCode >= 200 && $statusCode < 300,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// 6. Request Body Parsing (JSON payload)
$requestData = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $decoded = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $requestData = $decoded;
        }
    }
    // Merge post data if form submit
    if (empty($requestData) && !empty($_POST)) {
        $requestData = $_POST;
    }
}

// 7. Router
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Resolve subpath relative to script location
$scriptName = $_SERVER['SCRIPT_NAME'];
$scriptDir = dirname($scriptName);
$pathInfo = '';

if (strpos($path, $scriptDir) === 0) {
    $pathInfo = substr($path, strlen($scriptDir));
} else {
    $pathInfo = $path;
}

$pathInfo = '/' . ltrim($pathInfo, '/');
// Remove index.php, api, and v1 prefix if present
$pathInfo = preg_replace('/^\/index\.php/', '', $pathInfo);
$pathInfo = '/' . ltrim($pathInfo, '/');
$pathInfo = preg_replace('/^\/api/', '', $pathInfo);
$pathInfo = '/' . ltrim($pathInfo, '/');
$pathInfo = preg_replace('/^\/v1/', '', $pathInfo);
$pathInfo = '/' . ltrim($pathInfo, '/');

// Definition of Routes
$routes = [
    // --- Public ---
    'GET' => [
        '/^\/health$/' => ['AlumniController', 'health'],
        '/^\/alumni\/search$/' => ['AlumniController', 'search'],
        '/^\/alumni\/([0-9]+)\/status$/' => ['AlumniController', 'checkStatus'],
        '/^\/alumni\/([0-9]+)\/survey$/' => ['SurveyController', 'getSurvey']
    ],
    'POST' => [
        '/^\/auth\/google$/' => ['AlumniController', 'loginWithGoogle'],
        '/^\/auth\/google\/register$/' => ['AlumniController', 'registerWithGoogle'],
        '/^\/alumni\/([0-9]+)\/survey$/' => ['SurveyController', 'createSurvey']
    ],
    'PUT' => [
        '/^\/alumni\/([0-9]+)\/survey$/' => ['SurveyController', 'updateSurvey']
    ],
    'DELETE' => []
];

// Admin Routes (with authentication checks)
$adminRoutes = [
    'POST' => [
        '/^\/admin\/auth\/login$/' => ['AdminAuthController', 'login'],
        '/^\/admin\/auth\/logout$/' => ['AdminAuthController', 'logout'],
        '/^\/admin\/alumni\/batch-delete$/' => ['AdminAlumniController', 'batchDelete'],
        '/^\/admin\/alumni$/' => ['AdminAlumniController', 'create'],
        '/^\/admin\/alumni\/import$/' => ['AdminAlumniController', 'import'],
        '/^\/admin\/alumni\/import\/confirm$/' => ['AdminAlumniController', 'confirmImport'],
        '/^\/admin\/surveys\/import$/' => ['AdminSurveyController', 'import'],
        '/^\/admin\/surveys\/import\/confirm$/' => ['AdminSurveyController', 'confirmImport']
    ],
    'GET' => [
        '/^\/admin\/auth\/me$/' => ['AdminAuthController', 'me'],
        '/^\/admin\/alumni\/export$/' => ['AdminAlumniController', 'export'],
        '/^\/admin\/alumni$/' => ['AdminAlumniController', 'index'],
        '/^\/admin\/surveys\/export$/' => ['AdminSurveyController', 'export'], // Must be matched BEFORE /admin/surveys/:id
        '/^\/admin\/surveys$/' => ['AdminSurveyController', 'index'],
        '/^\/admin\/surveys\/([0-9]+)$/' => ['AdminSurveyController', 'show'],
        '/^\/admin\/dashboard\/charts$/' => ['DashboardController', 'charts']
    ],
    'PUT' => [
        '/^\/admin\/alumni\/([0-9]+)$/' => ['AdminAlumniController', 'update'],
        '/^\/admin\/auth\/profile$/' => ['AdminAuthController', 'updateProfile'],
        '/^\/admin\/auth\/password$/' => ['AdminAuthController', 'updatePassword']
    ],
    'DELETE' => [
        '/^\/admin\/alumni\/([0-9]+)$/' => ['AdminAlumniController', 'delete']
    ]
];

// Merge admin routes into main routing table
foreach (['GET', 'POST', 'PUT', 'DELETE'] as $m) {
    $routes[$m] = array_merge($routes[$m] ?? [], $adminRoutes[$m] ?? []);
}

// Match the current route
$matched = false;
$matches = [];
$controller = '';
$action = '';

if (isset($routes[$method])) {
    foreach ($routes[$method] as $pattern => $handler) {
        if (preg_match($pattern, $pathInfo, $matches)) {
            $matched = true;
            array_shift($matches); // Remove full match
            $controller = $handler[0];
            $action = $handler[1];
            break;
        }
    }
}

if (!$matched) {
    throw new HttpException('Endpoint not found: ' . $method . ' ' . $pathInfo, 404);
}

// 8. Admin Authentication Middleware Check
$isAdminRoute = (strpos($pathInfo, '/admin/') === 0);
$isLoginRoute = ($pathInfo === '/admin/auth/login');

if ($isAdminRoute && !$isLoginRoute) {
    if (!isset($_SESSION['admin_id'])) {
        throw new HttpException('Sesi admin berakhir. Silakan login kembali.', 401);
    }
}

// 9. Execute Controller
$controllerInstance = new $controller();
call_user_func_array([$controllerInstance, $action], array_merge([$requestData], $matches));
