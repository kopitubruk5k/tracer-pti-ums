<?php
// Admin Repository

class AdminRepository {
    private $db;

    public function __construct() {
        $this->db = DB::getConnection();
    }

    /**
     * Find admin by username.
     */
    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT id, username, password_hash, nama, created_at FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find admin by ID.
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT id, username, password_hash, nama, created_at FROM admins WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Update admin profile.
     */
    public function updateProfile($id, $nama, $username) {
        $stmt = $this->db->prepare("UPDATE admins SET nama = ?, username = ? WHERE id = ?");
        $stmt->execute([$nama, $username, $id]);
    }

    /**
     * Update admin password.
     */
    public function updatePassword($id, $passwordHash) {
        $stmt = $this->db->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
        $stmt->execute([$passwordHash, $id]);
    }
}
