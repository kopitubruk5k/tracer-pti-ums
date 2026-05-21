<?php
// Admin Auth Controller

class AdminAuthController {
    private $adminRepo;

    public function __construct() {
        $this->adminRepo = new AdminRepository();
    }

    /**
     * POST /admin/auth/login
     */
    public function login($requestData) {
        $username = trim($requestData['username'] ?? '');
        $username = preg_replace('/@(ums\.ac\.id|student\.ums\.ac\.id)$/i', '', $username);
        $password = $requestData['password'] ?? '';

        if (empty($username) || empty($password)) {
            throw new HttpException('Username dan password wajib diisi', 400);
        }

        $admin = $this->adminRepo->findByUsername($username);
        if (!$admin) {
            throw new HttpException('Username atau password salah', 401);
        }

        if (!password_verify($password, $admin['password_hash'])) {
            throw new HttpException('Username atau password salah', 401);
        }

        // Store in session
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_username'] = $admin['username'];

        sendJson([
            'id' => (int)$admin['id'],
            'username' => $admin['username'],
            'nama' => $admin['nama']
        ], 200, 'Login berhasil');
    }

    /**
     * POST /admin/auth/logout
     */
    public function logout($requestData) {
        // Destroy session
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        
        sendJson(null, 200, 'Logout berhasil');
    }

    /**
     * GET /admin/auth/me
     */
    public function me($requestData) {
        if (!isset($_SESSION['admin_id'])) {
            sendJson(null, 200, 'Not logged in');
        }

        $adminId = $_SESSION['admin_id'];
        $admin = $this->adminRepo->findById($adminId);
        if (!$admin) {
            throw new HttpException('Admin tidak ditemukan', 404);
        }

        sendJson([
            'id' => (int)$admin['id'],
            'username' => $admin['username'],
            'nama' => $admin['nama']
        ], 200, 'Data admin');
    }

    /**
     * PUT /admin/auth/profile
     */
    public function updateProfile($requestData) {
        $adminId = $_SESSION['admin_id'];
        $nama = trim($requestData['nama'] ?? '');
        $username = trim($requestData['username'] ?? '');

        if (empty($nama) || empty($username)) {
            throw new HttpException('Nama dan username wajib diisi', 400);
        }

        // Check if username taken by another admin
        $existing = $this->adminRepo->findByUsername($username);
        if ($existing && (int)$existing['id'] !== $adminId) {
            throw new HttpException('Username sudah digunakan oleh admin lain', 400);
        }

        $this->adminRepo->updateProfile($adminId, $nama, $username);
        $_SESSION['admin_username'] = $username;

        sendJson(null, 200, 'Profil berhasil diperbarui');
    }

    /**
     * PUT /admin/auth/password
     */
    public function updatePassword($requestData) {
        $adminId = $_SESSION['admin_id'];
        $oldPassword = $requestData['old_password'] ?? '';
        $newPassword = $requestData['new_password'] ?? '';

        if (empty($oldPassword) || empty($newPassword)) {
            throw new HttpException('Password lama dan baru wajib diisi', 400);
        }

        if (strlen($newPassword) < 6) {
            throw new HttpException('Password baru minimal 6 karakter', 400);
        }

        $admin = $this->adminRepo->findById($adminId);
        if (!$admin) {
            throw new HttpException('Admin tidak ditemukan', 404);
        }

        if (!password_verify($oldPassword, $admin['password_hash'])) {
            throw new HttpException('Password lama salah', 400);
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->adminRepo->updatePassword($adminId, $newPasswordHash);

        sendJson(null, 200, 'Password berhasil diperbarui');
    }
}
