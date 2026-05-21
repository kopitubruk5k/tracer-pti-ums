<?php
// Alumni Controller

class AlumniController {
    private $alumniRepo;
    private $surveyRepo;

    public function __construct() {
        $this->alumniRepo = new AlumniRepository();
        $this->surveyRepo = new SurveyRepository();
    }

    /**
     * GET /health
     */
    public function health() {
        sendJson(['status' => 'OK', 'timestamp' => time()], 200, 'Health check passed');
    }

    /**
     * GET /alumni/search
     */
    public function search($requestData) {
        $query = $_GET['query'] ?? '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $sortBy = $_GET['sort_by'] ?? 'nama_lengkap';
        $sortOrder = $_GET['sort_order'] ?? 'asc';

        // Constrain limit
        $limit = min(max($limit, 1), 50);

        $result = $this->alumniRepo->search($query, $limit, $sortBy, $sortOrder);

        // Paginated metadata structure matching express app
        $meta = [
            'total_items' => $result['total'],
            'items_per_page' => $limit,
            'current_page' => 1,
            'total_pages' => ceil($result['total'] / $limit)
        ];

        // Send paginated response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Hasil pencarian alumni',
            'data' => [
                'items' => $result['items'],
                'meta' => $meta
            ]
        ]);
        exit();
    }

    /**
     * GET /alumni/:id/status
     */
    public function checkStatus($requestData, $alumniId) {
        $alumniId = (int)$alumniId;
        $alumni = $this->alumniRepo->findById($alumniId);
        if (!$alumni) {
            throw new HttpException('Alumni tidak ditemukan', 404);
        }

        $exists = $this->surveyRepo->existsByAlumniId($alumniId);

        sendJson([
            'survey_exists' => $exists,
            'alumni' => [
                'id' => (int)$alumni['id'],
                'nama_lengkap' => $alumni['nama_lengkap'],
                'nim' => $alumni['nim'],
                'tahun_lulus' => $alumni['tahun_lulus'] !== null ? (int)$alumni['tahun_lulus'] : null,
                'email' => $alumni['email']
            ]
        ], 200, 'Status verifikasi alumni');
    }

    /**
     * POST /auth/google
     */
    public function loginWithGoogle($requestData) {
        $credential = $requestData['credential'] ?? '';
        if (empty($credential)) {
            throw new HttpException('Google credential is required', 400);
        }

        $config = require dirname(__DIR__) . '/config/config.php';
        $payload = GoogleAuthHelper::verifyIdToken($credential, $config['google_client_id']);
        
        $email = $payload['email'];
        $name = $payload['name'];

        // Strategy 1: Find by exact email
        $alumni = $this->alumniRepo->findByEmail($email);

        // Strategy 2: If student email, extract NIM
        if (!$alumni && (str_ends_with($email, '@student.ums.ac.id') || str_ends_with($email, '@ums.ac.id'))) {
            $parts = explode('@', $email);
            $nimCandidate = strtoupper($parts[0]);
            $alumni = $this->alumniRepo->findByNim($nimCandidate);
        }

        // Strategy 3: Try searching by exact name
        if (!$alumni && !empty($name)) {
            $searchResult = $this->alumniRepo->search($name, 1, 'nama_lengkap', 'asc');
            if (!empty($searchResult['items']) && strcasecmp($searchResult['items'][0]['nama_lengkap'], $name) === 0) {
                // Fetch full alumni record (since search only returns partial/joined properties)
                $alumni = $this->alumniRepo->findById($searchResult['items'][0]['id']);
            }
        }

        // If not found, return needs registration
        if (!$alumni) {
            sendJson([
                'verified' => false,
                'needs_registration' => true,
                'google_email' => $email,
                'google_name' => $name,
                'message' => 'Akun Google belum terhubung. Silakan verifikasi data Anda.'
            ], 200, 'Alumni tidak ditemukan');
        }

        // If email not set in database, link it now
        if (empty($alumni['email'])) {
            $this->alumniRepo->updateEmail($alumni['id'], $email);
            $alumni['email'] = $email;
        }

        // Check survey status
        $surveyExists = $this->surveyRepo->existsByAlumniId($alumni['id']);

        sendJson([
            'verified' => true,
            'alumni' => [
                'id' => (int)$alumni['id'],
                'nama_lengkap' => $alumni['nama_lengkap'],
                'nim' => $alumni['nim'],
                'tahun_lulus' => $alumni['tahun_lulus'] !== null ? (int)$alumni['tahun_lulus'] : null,
            ],
            'survey_exists' => $surveyExists,
            'google_email' => $email
        ], 200, 'Login berhasil');
    }

    /**
     * POST /auth/google/register
     */
    public function registerWithGoogle($requestData) {
        $credential = $requestData['credential'] ?? '';
        $nim = $requestData['nim'] ?? '';

        if (empty($credential) || empty($nim)) {
            throw new HttpException('Credential dan NIM wajib diisi', 400);
        }

        $config = require dirname(__DIR__) . '/config/config.php';
        $payload = GoogleAuthHelper::verifyIdToken($credential, $config['google_client_id']);

        $email = $payload['email'];
        $name = $payload['name'];
        $upperNim = strtoupper(trim($nim));

        // Check if alumni with this NIM exists
        $alumni = $this->alumniRepo->findByNim($upperNim);

        if ($alumni) {
            // Update email
            $this->alumniRepo->updateEmail($alumni['id'], $email);
            $alumni['email'] = $email;
        } else {
            // Create new alumni
            $alumni = $this->alumniRepo->create([
                'nim' => $upperNim,
                'nama_lengkap' => $name,
                'email' => $email
            ]);
        }

        $surveyExists = $this->surveyRepo->existsByAlumniId($alumni['id']);

        sendJson([
            'verified' => true,
            'alumni' => [
                'id' => (int)$alumni['id'],
                'nama_lengkap' => $alumni['nama_lengkap'],
                'nim' => $alumni['nim'],
                'tahun_lulus' => $alumni['tahun_lulus'] !== null ? (int)$alumni['tahun_lulus'] : null,
            ],
            'survey_exists' => $surveyExists,
            'google_email' => $email
        ], 200, 'Berhasil menghubungkan akun');
    }
}
