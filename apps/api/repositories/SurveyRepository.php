<?php
// Survey Repository

class SurveyRepository {
    private $db;

    public function __construct() {
        $this->db = DB::getConnection();
    }

    /**
     * Find survey by alumni_id.
     */
    public function findByAlumniId($alumniId) {
        $stmt = $this->db->prepare("SELECT * FROM surveys WHERE alumni_id = ?");
        $stmt->execute([$alumniId]);
        $row = $stmt->fetch();
        if ($row) {
            $row['lanjut_s2s3'] = (bool)$row['lanjut_s2s3'];
            $row['lanjut_ppg'] = (bool)$row['lanjut_ppg'];
        }
        return $row ?: null;
    }

    /**
     * Check if survey exists for alumni_id.
     */
    public function existsByAlumniId($alumniId) {
        $stmt = $this->db->prepare("SELECT 1 FROM surveys WHERE alumni_id = ? LIMIT 1");
        $stmt->execute([$alumniId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Create survey for an alumni.
     */
    public function create($alumniId, $data) {
        $sql = "INSERT INTO surveys (
                    alumni_id, tahun_lulus_konfirmasi, status_pekerjaan, status_pekerjaan_detail, nama_instansi, nomor_hp,
                    lanjut_s2s3, jurusan_s2s3, universitas_s2s3,
                    lanjut_ppg, tahun_ppg, universitas_ppg, pesan_saran
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $alumniId,
            $data['tahun_lulus_konfirmasi'],
            $data['status_pekerjaan'],
            $data['status_pekerjaan_detail'] ?? null,
            $data['nama_instansi'],
            $data['nomor_hp'],
            $data['lanjut_s2s3'] ? 1 : 0,
            $data['jurusan_s2s3'] ?? null,
            $data['universitas_s2s3'] ?? null,
            $data['lanjut_ppg'] ? 1 : 0,
            $data['tahun_ppg'] ?? null,
            $data['universitas_ppg'] ?? null,
            $data['pesan_saran'] ?? null
        ]);

        return $this->findByAlumniId($alumniId);
    }

    /**
     * Update survey by alumni_id.
     */
    public function updateByAlumniId($alumniId, $data) {
        $sql = "UPDATE surveys SET
                    tahun_lulus_konfirmasi = ?,
                    status_pekerjaan = ?,
                    status_pekerjaan_detail = ?,
                    nama_instansi = ?,
                    nomor_hp = ?,
                    lanjut_s2s3 = ?,
                    jurusan_s2s3 = ?,
                    universitas_s2s3 = ?,
                    lanjut_ppg = ?,
                    tahun_ppg = ?,
                    universitas_ppg = ?,
                    pesan_saran = ?
                WHERE alumni_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['tahun_lulus_konfirmasi'],
            $data['status_pekerjaan'],
            $data['status_pekerjaan_detail'] ?? null,
            $data['nama_instansi'],
            $data['nomor_hp'],
            $data['lanjut_s2s3'] ? 1 : 0,
            $data['jurusan_s2s3'] ?? null,
            $data['universitas_s2s3'] ?? null,
            $data['lanjut_ppg'] ? 1 : 0,
            $data['tahun_ppg'] ?? null,
            $data['universitas_ppg'] ?? null,
            $data['pesan_saran'] ?? null,
            $alumniId
        ]);

        return $this->findByAlumniId($alumniId);
    }

    /**
     * Update survey by survey ID.
     */
    public function updateById($id, $data) {
        $sql = "UPDATE surveys SET
                    tahun_lulus_konfirmasi = ?,
                    status_pekerjaan = ?,
                    status_pekerjaan_detail = ?,
                    nama_instansi = ?,
                    nomor_hp = ?,
                    lanjut_s2s3 = ?,
                    jurusan_s2s3 = ?,
                    universitas_s2s3 = ?,
                    lanjut_ppg = ?,
                    tahun_ppg = ?,
                    universitas_ppg = ?,
                    pesan_saran = ?
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['tahun_lulus_konfirmasi'],
            $data['status_pekerjaan'],
            $data['status_pekerjaan_detail'] ?? null,
            $data['nama_instansi'],
            $data['nomor_hp'],
            $data['lanjut_s2s3'] ? 1 : 0,
            $data['jurusan_s2s3'] ?? null,
            $data['universitas_s2s3'] ?? null,
            $data['lanjut_ppg'] ? 1 : 0,
            $data['tahun_ppg'] ?? null,
            $data['universitas_ppg'] ?? null,
            $data['pesan_saran'] ?? null,
            $id
        ]);

        return $this->findById($id);
    }

    /**
     * List surveys with pagination, sorting, and filters (joined with alumni).
     */
    public function findWithFilters($params) {
        $allowedSorts = [
            'nama_lengkap' => 'a.nama_lengkap',
            'tahun_lulus_konfirmasi' => 's.tahun_lulus_konfirmasi',
            'status_pekerjaan' => 's.status_pekerjaan',
            'created_at' => 'COALESCE(s.created_at, a.created_at)',
        ];
        $orderCol = $allowedSorts[$params['sortBy']] ?? 'COALESCE(s.created_at, a.created_at)';
        $order = strtolower($params['sortOrder']) === 'asc' ? 'ASC' : 'DESC';
        $offset = ($params['page'] - 1) * $params['limit'];

        $conditions = [];
        $values = [];

        if (!empty($params['search'])) {
            $conditions[] = "a.nama_lengkap LIKE ?";
            $values[] = "%{$params['search']}%";
        }
        if (!empty($params['tahunLulus'])) {
            $conditions[] = "a.tahun_lulus = ?";
            $values[] = $params['tahunLulus'];
        }
        if (!empty($params['statusPekerjaan'])) {
            $conditions[] = "s.status_pekerjaan = ?";
            $values[] = $params['statusPekerjaan'];
        }
        if (isset($params['lanjutPpg'])) {
            $conditions[] = "s.lanjut_ppg = ?";
            $values[] = $params['lanjutPpg'] ? 1 : 0;
        }
        
        // Status pengisian survey
        if (($params['statusPengisian'] ?? '') === 'sudah') {
            $conditions[] = "s.id IS NOT NULL";
        } elseif (($params['statusPengisian'] ?? '') === 'belum') {
            $conditions[] = "s.id IS NULL";
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        // Count total
        $countSql = "SELECT COUNT(*) AS total 
                    FROM alumni a 
                    LEFT JOIN surveys s ON s.alumni_id = a.id 
                    {$whereClause}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($values);
        $total = (int)$stmt->fetchColumn();

        // Get data
        $dataSql = "SELECT s.*, a.nama_lengkap, a.nim, a.tahun_lulus
                    FROM alumni a
                    LEFT JOIN surveys s ON s.alumni_id = a.id
                    {$whereClause}
                    ORDER BY {$orderCol} {$order}
                    LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($dataSql);
        
        // Bind manually for LIMIT/OFFSET support
        $idx = 1;
        foreach ($values as $val) {
            $stmt->bindValue($idx++, $val);
        }
        $stmt->bindValue($idx++, (int)$params['limit'], PDO::PARAM_INT);
        $stmt->bindValue($idx++, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $items = $stmt->fetchAll();

        foreach ($items as &$item) {
            if ($item['id'] !== null) {
                $item['lanjut_s2s3'] = (bool)$item['lanjut_s2s3'];
                $item['lanjut_ppg'] = (bool)$item['lanjut_ppg'];
            }
        }

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Get all surveys with filters (for export, no pagination).
     */
    public function listAll($filters = []) {
        $conditions = [];
        $values = [];

        if (!empty($filters['tahunLulus'])) {
            $conditions[] = "s.tahun_lulus_konfirmasi = ?";
            $values[] = $filters['tahunLulus'];
        }
        if (!empty($filters['statusPekerjaan'])) {
            $conditions[] = "s.status_pekerjaan = ?";
            $values[] = $filters['statusPekerjaan'];
        }
        if (isset($filters['lanjutPpg'])) {
            $conditions[] = "s.lanjut_ppg = ?";
            $values[] = $filters['lanjutPpg'] ? 1 : 0;
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        $sql = "SELECT s.*, a.nama_lengkap, a.nim
                FROM surveys s
                INNER JOIN alumni a ON a.id = s.alumni_id
                {$whereClause}
                ORDER BY a.nama_lengkap ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        $items = $stmt->fetchAll();

        foreach ($items as &$item) {
            $item['lanjut_s2s3'] = (bool)$item['lanjut_s2s3'];
            $item['lanjut_ppg'] = (bool)$item['lanjut_ppg'];
        }

        return $items;
    }

    /**
     * Delete a survey by ID.
     */
    public function deleteById($id) {
        $stmt = $this->db->prepare("DELETE FROM surveys WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Find a single survey by ID (with alumni join).
     */
    public function findById($id) {
        $stmt = $this->db->prepare(
            "SELECT s.*, a.nama_lengkap, a.nim, a.tahun_lulus
             FROM surveys s
             INNER JOIN alumni a ON a.id = s.alumni_id
             WHERE s.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $row['lanjut_s2s3'] = (bool)$row['lanjut_s2s3'];
            $row['lanjut_ppg'] = (bool)$row['lanjut_ppg'];
        }
        return $row ?: null;
    }

    // --- Dashboard Aggregations ---

    public function countTotal() {
        return (int)$this->db->query("SELECT COUNT(*) FROM surveys")->fetchColumn();
    }

    public function countByStatusPekerjaan() {
        $sql = "SELECT status_pekerjaan AS label, COUNT(*) AS value 
                FROM surveys 
                GROUP BY status_pekerjaan 
                ORDER BY value DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function countByUniversitasPpg() {
        $sql = "SELECT universitas_ppg AS label, COUNT(*) AS value 
                FROM surveys 
                WHERE lanjut_ppg = 1 AND universitas_ppg IS NOT NULL AND TRIM(universitas_ppg) <> ''
                GROUP BY universitas_ppg 
                ORDER BY value DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function countByUniversitasS2s3() {
        $sql = "SELECT universitas_s2s3 AS label, COUNT(*) AS value 
                FROM surveys 
                WHERE lanjut_s2s3 = 1 AND universitas_s2s3 IS NOT NULL AND TRIM(universitas_s2s3) <> ''
                GROUP BY universitas_s2s3 
                ORDER BY value DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function countPpgDistribution() {
        $sql = "SELECT 
                    CASE WHEN lanjut_ppg = 1 THEN 'Ya' ELSE 'Tidak' END AS label,
                    COUNT(*) AS value
                FROM surveys
                GROUP BY lanjut_ppg
                ORDER BY label";
        return $this->db->query($sql)->fetchAll();
    }

    public function countS2s3Distribution() {
        $sql = "SELECT 
                    CASE WHEN lanjut_s2s3 = 1 THEN 'Ya' ELSE 'Tidak' END AS label,
                    COUNT(*) AS value
                FROM surveys
                GROUP BY lanjut_s2s3
                ORDER BY label";
        return $this->db->query($sql)->fetchAll();
    }

    public function countByTahunLulus() {
        $sql = "SELECT CAST(tahun_lulus_konfirmasi AS CHAR) AS label, COUNT(*) AS value
                FROM surveys
                GROUP BY tahun_lulus_konfirmasi
                ORDER BY tahun_lulus_konfirmasi ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function countByJurusanS2s3() {
        $sql = "SELECT jurusan_s2s3 AS label, COUNT(*) AS value
                FROM surveys
                WHERE lanjut_s2s3 = 1 AND jurusan_s2s3 IS NOT NULL AND TRIM(jurusan_s2s3) <> ''
                GROUP BY jurusan_s2s3
                ORDER BY value DESC";
        return $this->db->query($sql)->fetchAll();
    }
}
