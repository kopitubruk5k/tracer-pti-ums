<?php
// Admin Alumni Controller

class AdminAlumniController {
    private $alumniRepo;

    public function __construct() {
        $this->alumniRepo = new AlumniRepository();
    }

    /**
     * GET /admin/alumni
     */
    public function index($requestData) {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $sortBy = $_GET['sort_by'] ?? 'created_at';
        $sortOrder = $_GET['sort_order'] ?? 'desc';
        $search = $_GET['search'] ?? null;
        $tahunLulus = isset($_GET['tahun_lulus']) && $_GET['tahun_lulus'] !== '' ? (int)$_GET['tahun_lulus'] : null;
        $duplicateType = $_GET['duplicate_type'] ?? null;

        $params = [
            'page' => max($page, 1),
            'limit' => min(max($limit, 1), 1000000),
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'search' => $search,
            'tahunLulus' => $tahunLulus,
            'duplicateType' => $duplicateType
        ];

        $result = $this->alumniRepo->listAlumni($params);

        // Paginated metadata structure matching express app
        $meta = [
            'total_items' => $result['total'],
            'items_per_page' => $params['limit'],
            'current_page' => $params['page'],
            'total_pages' => ceil($result['total'] / $params['limit'])
        ];

        // Format items properly
        $formattedItems = [];
        foreach ($result['items'] as $item) {
            $formattedItems[] = [
                'id' => (int)$item['id'],
                'nama_lengkap' => $item['nama_lengkap'],
                'nim' => $item['nim'],
                'tahun_lulus' => $item['tahun_lulus'] !== null ? (int)$item['tahun_lulus'] : null,
                'tanggal_lahir' => $item['tanggal_lahir'],
                'email' => $item['email'],
                'created_at' => $item['created_at'],
                'updated_at' => $item['updated_at']
            ];
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Daftar alumni',
            'data' => [
                'items' => $formattedItems,
                'meta' => $meta
            ]
        ]);
        exit();
    }

    /**
     * POST /admin/alumni
     */
    public function create($requestData) {
        $namaLengkap = trim($requestData['nama_lengkap'] ?? '');
        $nim = strtoupper(trim($requestData['nim'] ?? ''));
        $tahunLulus = isset($requestData['tahun_lulus']) && $requestData['tahun_lulus'] !== '' ? (int)$requestData['tahun_lulus'] : null;
        $tanggalLahir = isset($requestData['tanggal_lahir']) && $requestData['tanggal_lahir'] !== '' ? $requestData['tanggal_lahir'] : null;
        $email = isset($requestData['email']) && $requestData['email'] !== '' ? trim($requestData['email']) : null;

        if (empty($namaLengkap) || empty($nim)) {
            throw new HttpException('Nama lengkap dan NIM wajib diisi', 400);
        }

        // Validate NIM format
        if (empty($nim)) {
            throw new HttpException('NIM wajib diisi', 400);
        }

        // Check unique NIM
        $existing = $this->alumniRepo->findByNim($nim);
        if ($existing) {
            throw new HttpException('Alumni dengan NIM tersebut sudah ada', 400);
        }

        // Check unique Email
        if ($email) {
            $existingEmail = $this->alumniRepo->findByEmail($email);
            if ($existingEmail) {
                throw new HttpException('Alumni dengan Email tersebut sudah ada', 400);
            }
        }

        if ($tahunLulus !== null && ($tahunLulus < 1950 || $tahunLulus > 2100)) {
            throw new HttpException('Tahun lulus harus antara 1950 dan 2100', 400);
        }

        $alumni = $this->alumniRepo->create([
            'nama_lengkap' => $namaLengkap,
            'nim' => $nim,
            'tahun_lulus' => $tahunLulus,
            'tanggal_lahir' => $tanggalLahir,
            'email' => $email
        ]);

        sendJson($alumni, 201, 'Alumni berhasil ditambahkan');
    }

    /**
     * PUT /admin/alumni/:id
     */
    public function update($requestData, $id) {
        $id = (int)$id;
        $alumni = $this->alumniRepo->findById($id);
        if (!$alumni) {
            throw new HttpException('Alumni tidak ditemukan', 404);
        }

        $namaLengkap = isset($requestData['nama_lengkap']) ? trim($requestData['nama_lengkap']) : null;
        $nim = isset($requestData['nim']) ? strtoupper(trim($requestData['nim'])) : null;
        $tahunLulus = isset($requestData['tahun_lulus']) && $requestData['tahun_lulus'] !== '' ? (int)$requestData['tahun_lulus'] : null;
        $tanggalLahir = isset($requestData['tanggal_lahir']) && $requestData['tanggal_lahir'] !== '' ? $requestData['tanggal_lahir'] : null;

        $updateData = [];
        if ($namaLengkap !== null) {
            if (empty($namaLengkap)) throw new HttpException('Nama lengkap wajib diisi', 400);
            $updateData['nama_lengkap'] = $namaLengkap;
        }

        if ($nim !== null) {
            if (empty($nim)) throw new HttpException('NIM wajib diisi', 400);
            $existing = $this->alumniRepo->findByNim($nim);
            if ($existing && (int)$existing['id'] !== $id) {
                throw new HttpException('NIM sudah digunakan oleh alumni lain', 400);
            }
            $updateData['nim'] = $nim;
        }

        if (array_key_exists('tahun_lulus', $requestData)) {
            if ($tahunLulus !== null && ($tahunLulus < 1950 || $tahunLulus > 2100)) {
                throw new HttpException('Tahun lulus harus antara 1950 dan 2100', 400);
            }
            $updateData['tahun_lulus'] = $tahunLulus;
        }

        if (array_key_exists('tanggal_lahir', $requestData)) {
            $updateData['tanggal_lahir'] = $tanggalLahir;
        }

        $updatedAlumni = $this->alumniRepo->update($id, $updateData);
        sendJson($updatedAlumni, 200, 'Alumni berhasil diperbarui');
    }

    /**
     * DELETE /admin/alumni/:id
     */
    public function delete($requestData, $id) {
        $id = (int)$id;
        $deleted = $this->alumniRepo->delete($id);
        if (!$deleted) {
            throw new HttpException('Alumni tidak ditemukan', 404);
        }
        sendJson(null, 200, 'Alumni berhasil dihapus');
    }

    /**
     * POST /admin/alumni/import
     */
    public function import($requestData) {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            throw new HttpException('File upload wajib disertakan', 400);
        }

        $file = $_FILES['file'];
        $tempPath = $file['tmp_name'];
        $origName = $file['name'];
        $ext = pathinfo($origName, PATHINFO_EXTENSION);

        if (!in_array(strtolower($ext), ['csv', 'xlsx'])) {
            throw new HttpException('Format file harus berupa CSV atau XLSX', 400);
        }

        // Parse file
        $parsedRows = ExcelReader::read($tempPath, $ext);
        
        $validRows = [];
        $invalidRows = [];
        $rowNumber = 1; // Row 1 is header

        foreach ($parsedRows as $row) {
            $rowNumber++;
            $errors = [];

            // Column Mapping
            $namaRaw = $row['Nama Lengkap'] ?? $row['Nama'] ?? '';
            $namaLengkap = trim($namaRaw);

            $nimRaw = $row['NIM'] ?? '';
            $nim = strtoupper(trim($nimRaw));

            $tahunLulusRaw = trim($row['Tahun Lulus'] ?? '');
            $tahunLulus = null;

            if (empty($namaLengkap)) {
                $errors[] = 'Nama (Lengkap) wajib diisi';
            }
            if (empty($nim)) {
                $errors[] = 'NIM wajib diisi';
            }

            if ($tahunLulusRaw !== '') {
                if (!preg_match('/^\d{4}$/', $tahunLulusRaw)) {
                    $errors[] = 'Tahun Lulus opsional, tetapi jika diisi harus berupa 4 digit angka (contoh: 2024)';
                } else {
                    $tahunLulus = (int)$tahunLulusRaw;
                    if ($tahunLulus < 1950 || $tahunLulus > 2100) {
                        $errors[] = 'Tahun Lulus out of range (1950-2100)';
                    }
                }
            }

            // Parse tanggal lahir
            $tglLahirRaw = $row['Tanggal Lahir'] ?? '';
            $tanggalLahir = null;
            if ($tglLahirRaw !== '') {
                if (is_numeric($tglLahirRaw)) {
                    // Excel Date Serial
                    try {
                        $excelEpoch = new DateTime('1899-12-30');
                        $days = (int)$tglLahirRaw;
                        $excelEpoch->modify("+$days days");
                        $tanggalLahir = $excelEpoch->format('Y-m-d');
                    } catch (Exception $e) {
                        // Unparseable date serial
                    }
                } else {
                    // Check DD-MM-YYYY or DD/MM/YYYY
                    if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $tglLahirRaw, $matches)) {
                        $tanggalLahir = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
                    } else if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglLahirRaw)) {
                        $tanggalLahir = $tglLahirRaw;
                    }
                }
            }

            $rowData = [
                'row_number' => $rowNumber,
                'nama_lengkap' => $namaLengkap,
                'nim' => $nim,
                'tahun_lulus' => $tahunLulus,
                'tanggal_lahir' => $tanggalLahir,
                'is_valid' => empty($errors),
                'errors' => $errors
            ];

            if (empty($errors)) {
                $validRows[] = $rowData;
            } else {
                $invalidRows[] = $rowData;
            }
        }

        if (empty($validRows) && empty($invalidRows)) {
            throw new HttpException('File Excel kosong atau tidak memiliki data yang dapat diproses', 400);
        }

        // Save to session cache
        $importId = uniqid('import_', true);
        if (!isset($_SESSION['import_drafts'])) {
            $_SESSION['import_drafts'] = [];
        }
        
        $_SESSION['import_drafts'][$importId] = [
            'valid_rows' => $validRows,
            'created_at' => time()
        ];

        sendJson([
            'import_id' => $importId,
            'total_valid' => count($validRows),
            'total_invalid' => count($invalidRows),
            'invalid_rows' => $invalidRows
        ], 201, 'Preview import berhasil dibuat');
    }

    /**
     * POST /admin/alumni/import/confirm
     */
    public function confirmImport($requestData) {
        $importId = $requestData['import_id'] ?? '';
        if (empty($importId)) {
            throw new HttpException('Import ID wajib diisi', 400);
        }

        if (!isset($_SESSION['import_drafts'][$importId])) {
            throw new HttpException('Import draft tidak ditemukan atau sudah kedaluwarsa. Silakan upload ulang.', 404);
        }

        $draft = $_SESSION['import_drafts'][$importId];
        $validRows = $draft['valid_rows'];

        if (empty($validRows)) {
            unset($_SESSION['import_drafts'][$importId]);
            throw new HttpException('Tidak ada data valid untuk diimport', 400);
        }

        // Upsert database transaction
        $inserted = 0;
        $updated = 0;
        $failedDetails = [];

        try {
            DB::transaction(function($pdo) use ($validRows, &$inserted, &$updated) {
                $sql = "INSERT INTO alumni (nama_lengkap, nim, tahun_lulus, tanggal_lahir) 
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            nama_lengkap = IF(VALUES(nama_lengkap) IS NOT NULL AND TRIM(VALUES(nama_lengkap)) != '', VALUES(nama_lengkap), nama_lengkap),
                            tahun_lulus = COALESCE(VALUES(tahun_lulus), tahun_lulus),
                            tanggal_lahir = COALESCE(VALUES(tanggal_lahir), tanggal_lahir)";
                
                $stmt = $pdo->prepare($sql);
                foreach ($validRows as $row) {
                    $stmt->execute([
                        $row['nama_lengkap'],
                        $row['nim'],
                        $row['tahun_lulus'],
                        $row['tanggal_lahir']
                    ]);
                    $count = $stmt->rowCount();
                    if ($count === 1) {
                        $inserted++;
                    } else {
                        // rowCount is 2 for update, or 0 for no change
                        $updated++;
                    }
                }
            });
        } catch (Exception $e) {
            throw new HttpException('Database error during batch insert: ' . $e->getMessage(), 500);
        }

        // Clean up session
        unset($_SESSION['import_drafts'][$importId]);

        sendJson([
            'import_id' => $importId,
            'total_processed' => $inserted + $updated,
            'inserted' => $inserted,
            'updated' => $updated,
            'failed_details' => $failedDetails
        ], 200, 'Import berhasil dikonfirmasi');
    }

    /**
     * GET /admin/alumni/export
     */
    public function export($requestData) {
        $tahunLulus = isset($_GET['tahun_lulus']) && $_GET['tahun_lulus'] !== '' ? (int)$_GET['tahun_lulus'] : null;

        $db = DB::getConnection();
        $sql = "SELECT nim, nama_lengkap, tahun_lulus, tanggal_lahir, email, created_at FROM alumni";
        $where = [];
        $params = [];

        if ($tahunLulus !== null) {
            $where[] = "tahun_lulus = ?";
            $params[] = $tahunLulus;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY nim ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Stream CSV download
        $filename = "Alumni_Tracer_Study_" . ($tahunLulus ? $tahunLulus . "_" : "") . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        
        // Write UTF-8 BOM so Excel opens it correctly
        fwrite($out, "\xEF\xBB\xBF");

        // Headers
        fputcsv($out, ['NIM', 'Nama Lengkap', 'Tahun Lulus', 'Tanggal Lahir', 'Email', 'Tanggal Ditambahkan']);

        foreach ($items as $item) {
            fputcsv($out, [
                $item['nim'],
                $item['nama_lengkap'],
                $item['tahun_lulus'] !== null ? $item['tahun_lulus'] : '',
                $item['tanggal_lahir'] !== null ? $item['tanggal_lahir'] : '',
                $item['email'] !== null ? $item['email'] : '',
                $item['created_at']
            ]);
        }

        fclose($out);
        exit();
    }

    /**
     * POST /admin/alumni/batch-delete
     */
    public function batchDelete($requestData) {
        $ids = $requestData['ids'] ?? null;
        if (!is_array($ids) || empty($ids)) {
            throw new HttpException('Pilih setidaknya satu alumni untuk dihapus', 400);
        }

        // Sanitize to integers
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function($id) {
            return $id > 0;
        });

        if (empty($ids)) {
            throw new HttpException('ID alumni tidak valid', 400);
        }

        try {
            DB::transaction(function($pdo) use ($ids) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $sql = "DELETE FROM alumni WHERE id IN ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($ids);
            });
        } catch (Exception $e) {
            throw new HttpException('Gagal menghapus data alumni terpilih: ' . $e->getMessage(), 500);
        }

        sendJson(null, 200, 'Alumni terpilih berhasil dihapus');
    }
}

