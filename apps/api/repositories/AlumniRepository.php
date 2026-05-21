<?php
// Alumni Repository

class AlumniRepository {
    private $db;
    private $columns = 'a.id, a.nama_lengkap, a.nim, a.tahun_lulus, a.tanggal_lahir, a.email, a.created_at, a.updated_at';

    public function __construct() {
        $this->db = DB::getConnection();
    }

    /**
     * Case-insensitive search alumni by name or NIM.
     */
    public function search($query, $limit, $sortBy, $sortOrder) {
        $allowedSorts = [
            'nama_lengkap' => 'a.nama_lengkap',
            'tahun_lulus' => 'a.tahun_lulus',
        ];
        $orderCol = $allowedSorts[$sortBy] ?? 'a.nama_lengkap';
        $order = strtolower($sortOrder) === 'desc' ? 'DESC' : 'ASC';

        $searchCondition = "(a.nama_lengkap LIKE ? OR a.nim LIKE ?)";
        $searchTerm = "%{$query}%";

        // Get total count
        $countSql = "SELECT COUNT(*) AS total FROM alumni a WHERE {$searchCondition}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute([$searchTerm, $searchTerm]);
        $total = (int)$stmt->fetchColumn();

        // Get paginated data
        $dataSql = "SELECT {$this->columns}, (s.id IS NOT NULL) AS survey_exists
                    FROM alumni a
                    LEFT JOIN surveys s ON s.alumni_id = a.id
                    WHERE {$searchCondition}
                    ORDER BY {$orderCol} {$order}
                    LIMIT " . (int)$limit; // PDO can execute this directly if cast to int

        $stmt = $this->db->prepare($dataSql);
        $stmt->execute([$searchTerm, $searchTerm]);
        $items = $stmt->fetchAll();

        // Convert tinyint/int survey_exists to boolean
        foreach ($items as &$item) {
            $item['survey_exists'] = (bool)$item['survey_exists'];
        }

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Find alumni by ID.
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT {$this->columns} FROM alumni a WHERE a.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find alumni by Email.
     */
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT {$this->columns} FROM alumni a WHERE a.email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find alumni by NIM.
     */
    public function findByNim($nim) {
        $stmt = $this->db->prepare("SELECT {$this->columns} FROM alumni a WHERE a.nim = ?");
        $stmt->execute([$nim]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Update alumni email.
     */
    public function updateEmail($id, $email) {
        $stmt = $this->db->prepare("UPDATE alumni SET email = ? WHERE id = ?");
        $stmt->execute([$email, $id]);
    }

    /**
     * Verify alumni birth date. Returns true if matches.
     */
    public function verifyBirthDate($alumniId, $tanggalLahir) {
        $stmt = $this->db->prepare("SELECT 1 FROM alumni WHERE id = ? AND tanggal_lahir = ?");
        $stmt->execute([$alumniId, $tanggalLahir]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * List alumni with pagination, sorting, and optional filters.
     */
    public function listAlumni($params) {
        $allowedSorts = [
            'nama_lengkap' => 'a.nama_lengkap',
            'nim' => 'a.nim',
            'tahun_lulus' => 'a.tahun_lulus',
            'created_at' => 'a.created_at',
        ];
        $orderCol = $allowedSorts[$params['sortBy']] ?? 'a.created_at';
        $order = strtolower($params['sortOrder']) === 'asc' ? 'ASC' : 'DESC';
        $offset = ($params['page'] - 1) * $params['limit'];

        $conditions = [];
        $values = [];

        if (!empty($params['search'])) {
            $conditions[] = "(a.nama_lengkap LIKE ? OR a.nim LIKE ?)";
            $searchTerm = "%{$params['search']}%";
            $values[] = $searchTerm;
            $values[] = $searchTerm;
        }

        if (!empty($params['tahunLulus'])) {
            $conditions[] = "a.tahun_lulus = ?";
            $values[] = $params['tahunLulus'];
        }

        if (!empty($params['duplicateType'])) {
            if ($params['duplicateType'] === 'nim') {
                $conditions[] = "a.nim IN (SELECT nim FROM alumni GROUP BY nim HAVING COUNT(*) > 1)";
            } elseif ($params['duplicateType'] === 'nama_lengkap') {
                $conditions[] = "a.nama_lengkap IN (SELECT nama_lengkap FROM alumni GROUP BY nama_lengkap HAVING COUNT(*) > 1)";
            }
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        // Count total
        $countSql = "SELECT COUNT(*) AS total FROM alumni a {$whereClause}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($values);
        $total = (int)$stmt->fetchColumn();

        // Get data
        $dataSql = "SELECT {$this->columns}
                    FROM alumni a
                    {$whereClause}
                    ORDER BY {$orderCol} {$order}
                    LIMIT ? OFFSET ?";

        // For LIMIT/OFFSET PDO execute requires integer binding or disabling emulate prepares
        $stmt = $this->db->prepare($dataSql);
        
        // Bind parameters manually to handle pagination limits in PDO correctly
        $idx = 1;
        foreach ($values as $val) {
            $stmt->bindValue($idx++, $val);
        }
        $stmt->bindValue($idx++, (int)$params['limit'], PDO::PARAM_INT);
        $stmt->bindValue($idx++, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $items = $stmt->fetchAll();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Create a new alumni.
     */
    public function create($data) {
        $tahunLulus = $data['tahun_lulus'] ?? date('Y');
        $tanggalLahir = $data['tanggal_lahir'] ?? null;
        $email = $data['email'] ?? null;

        $sql = "INSERT INTO alumni (nama_lengkap, nim, tahun_lulus, tanggal_lahir, email) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['nama_lengkap'],
            $data['nim'],
            $tahunLulus,
            $tanggalLahir,
            $email
        ]);

        $lastId = $this->db->lastInsertId();
        return $this->findById($lastId);
    }

    /**
     * Update an alumni record.
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];

        if (array_key_exists('nama_lengkap', $data)) {
            $fields[] = "nama_lengkap = ?";
            $values[] = $data['nama_lengkap'];
        }
        if (array_key_exists('nim', $data)) {
            $fields[] = "nim = ?";
            $values[] = $data['nim'];
        }
        if (array_key_exists('tahun_lulus', $data)) {
            $fields[] = "tahun_lulus = ?";
            $values[] = $data['tahun_lulus'];
        }
        if (array_key_exists('tanggal_lahir', $data)) {
            $fields[] = "tanggal_lahir = ?";
            $values[] = $data['tanggal_lahir'];
        }

        if (empty($fields)) {
            return $this->findById($id);
        }

        $values[] = $id;
        $sql = "UPDATE alumni SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);

        return $this->findById($id);
    }

    /**
     * Delete an alumni by ID.
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM alumni WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get all alumni for export without pagination.
     */
    public function listAll($filters = []) {
        $conditions = [];
        $values = [];

        if (!empty($filters['tahunLulus'])) {
            $conditions[] = "tahun_lulus = ?";
            $values[] = $filters['tahunLulus'];
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        $sql = "SELECT id, nama_lengkap, nim, tahun_lulus, tanggal_lahir, email, created_at, updated_at
                FROM alumni 
                {$whereClause} 
                ORDER BY tahun_lulus DESC, nama_lengkap ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchAll();
    }
}
