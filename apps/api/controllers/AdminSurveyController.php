<?php
// Admin Survey Controller

class AdminSurveyController {
    private $surveyRepo;
    private $alumniRepo;

    public function __construct() {
        $this->surveyRepo = new SurveyRepository();
        $this->alumniRepo = new AlumniRepository();
    }

    /**
     * GET /admin/surveys
     */
    public function index($requestData) {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $sortBy = $_GET['sort_by'] ?? 'created_at';
        $sortOrder = $_GET['sort_order'] ?? 'desc';
        $search = $_GET['search'] ?? null;
        $tahunLulus = isset($_GET['tahun_lulus']) && $_GET['tahun_lulus'] !== '' ? (int)$_GET['tahun_lulus'] : null;
        $statusPekerjaan = $_GET['status_pekerjaan'] ?? null;
        $lanjutPpg = isset($_GET['lanjut_ppg']) && $_GET['lanjut_ppg'] !== '' ? (bool)($_GET['lanjut_ppg'] === 'true' || $_GET['lanjut_ppg'] == '1') : null;
        $statusPengisian = $_GET['status_pengisian'] ?? null; // 'MENGISI' | 'BELUM_MENGISI'

        $params = [
            'page' => max($page, 1),
            'limit' => min(max($limit, 1), 100),
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'search' => $search,
            'tahunLulus' => $tahunLulus,
            'statusPekerjaan' => $statusPekerjaan,
            'lanjutPpg' => $lanjutPpg,
            'statusPengisian' => $statusPengisian
        ];

        $result = $this->surveyRepo->findWithFilters($params);

        // Paginated metadata structure matching express app
        $meta = [
            'total_items' => $result['total'],
            'items_per_page' => $params['limit'],
            'current_page' => $params['page'],
            'total_pages' => ceil($result['total'] / $params['limit'])
        ];

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Daftar survey',
            'data' => [
                'items' => $result['items'],
                'meta' => $meta
            ]
        ]);
        exit();
    }

    /**
     * GET /admin/surveys/:id
     */
    public function show($requestData, $id) {
        $id = (int)$id;
        $survey = $this->surveyRepo->findById($id);
        if (!$survey) {
            throw new HttpException('Survey tidak ditemukan', 404);
        }

        sendJson($survey, 200, 'Detail survey');
    }

    /**
     * PUT /admin/surveys/:id
     */
    public function update($requestData, $id) {
        $id = (int)$id;
        $existing = $this->surveyRepo->findById($id);
        if (!$existing) {
            throw new HttpException('Survey tidak ditemukan', 404);
        }

        // Validate survey fields
        $validatedData = $this->validateSurveyInput($requestData);

        $survey = DB::transaction(function() use ($id, $validatedData, $existing) {
            // Update alumni's official grad year if different
            if ($validatedData['tahun_lulus_konfirmasi'] !== (int)$existing['tahun_lulus']) {
                $this->alumniRepo->update($existing['alumni_id'], ['tahun_lulus' => $validatedData['tahun_lulus_konfirmasi']]);
            }
            return $this->surveyRepo->updateById($id, $validatedData);
        });

        sendJson($survey, 200, 'Survey berhasil diperbarui');
    }

    /**
     * DELETE /admin/surveys/:id
     */
    public function delete($requestData, $id) {
        $id = (int)$id;
        $deleted = $this->surveyRepo->deleteById($id);
        if (!$deleted) {
            throw new HttpException('Survey tidak ditemukan', 404);
        }
        sendJson(null, 200, 'Survey berhasil dihapus');
    }

    /**
     * GET /admin/surveys/export
     */
    public function export($requestData) {
        $tahunLulus = isset($_GET['tahun_lulus']) && $_GET['tahun_lulus'] !== '' ? (int)$_GET['tahun_lulus'] : null;
        $statusPekerjaan = $_GET['status_pekerjaan'] ?? null;
        $lanjutPpg = isset($_GET['lanjut_ppg']) && $_GET['lanjut_ppg'] !== '' ? (bool)($_GET['lanjut_ppg'] === 'true' || $_GET['lanjut_ppg'] == '1') : null;

        $filters = [
            'tahunLulus' => $tahunLulus,
            'statusPekerjaan' => $statusPekerjaan,
            'lanjutPpg' => $lanjutPpg
        ];

        $rows = $this->surveyRepo->listAll($filters);

        // Stream CSV download
        $filename = "Survey_Tracer_Study_" . ($tahunLulus ? $tahunLulus . "_" : "") . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        
        // Write UTF-8 BOM
        fwrite($out, "\xEF\xBB\xBF");

        // Headers
        fputcsv($out, [
            'Nama Lengkap', 
            'NIM', 
            'Tahun Lulus Konfirmasi', 
            'Status Pekerjaan', 
            'Detail Status Pekerjaan',
            'Nama Instansi', 
            'Nomor HP', 
            'Lanjut S2/S3', 
            'Jurusan S2/S3', 
            'Universitas S2/S3', 
            'Lanjut PPG', 
            'Tahun PPG', 
            'Universitas PPG', 
            'Pesan & Saran'
        ]);

        foreach ($rows as $row) {
            fputcsv($out, [
                $row['nama_lengkap'],
                $row['nim'],
                $row['tahun_lulus_konfirmasi'],
                $row['status_pekerjaan'],
                $row['status_pekerjaan_detail'] ? $row['status_pekerjaan_detail'] : '-',
                $row['nama_instansi'],
                $row['nomor_hp'],
                $row['lanjut_s2s3'] ? 'Ya' : 'Tidak',
                $row['jurusan_s2s3'] ? $row['jurusan_s2s3'] : '-',
                $row['universitas_s2s3'] ? $row['universitas_s2s3'] : '-',
                $row['lanjut_ppg'] ? 'Ya' : 'Tidak',
                $row['tahun_ppg'] ? $row['tahun_ppg'] : '-',
                $row['universitas_ppg'] ? $row['universitas_ppg'] : '-',
                $row['pesan_saran'] ? $row['pesan_saran'] : '-'
            ]);
        }

        fclose($out);
        exit();
    }

    /**
     * POST /admin/surveys/import
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

            // Mapping
            $namaLengkap = trim($row['Nama Lengkap'] ?? '');
            $nim = strtoupper(trim($row['NIM'] ?? ''));
            $tLulusKonf = isset($row['Tahun Lulus Konfirmasi']) && $row['Tahun Lulus Konfirmasi'] !== '' ? (int)$row['Tahun Lulus Konfirmasi'] : 0;
            $statusPekerjaan = trim($row['Status Pekerjaan'] ?? '');
            $statusPekerjaanDetail = trim($row['Detail Status Pekerjaan'] ?? '');
            $namaInstansi = trim($row['Nama Instansi'] ?? '');
            $nomorHp = trim($row['Nomor HP'] ?? '');
            
            $lanjutS2S3Raw = strtolower(trim($row['Lanjut S2/S3'] ?? ''));
            $lanjutS2S3 = ($lanjutS2S3Raw === 'ya' || $lanjutS2S3Raw === 'true' || $lanjutS2S3Raw === '1');
            $jurusanS2S3 = trim($row['Jurusan S2/S3'] ?? '');
            $universitasS2S3 = trim($row['Universitas S2/S3'] ?? '');
            
            $lanjutPpgRaw = strtolower(trim($row['Lanjut PPG'] ?? ''));
            $lanjutPpg = ($lanjutPpgRaw === 'ya' || $lanjutPpgRaw === 'true' || $lanjutPpgRaw === '1');
            $tPpgRaw = isset($row['Tahun PPG']) && $row['Tahun PPG'] !== '' ? (int)$row['Tahun PPG'] : 0;
            $universitasPpg = trim($row['Universitas PPG'] ?? '');
            
            $pesanSaran = trim($row['Pesan & Saran'] ?? '');

            if (empty($namaLengkap)) $errors[] = 'Nama Lengkap wajib diisi';
            if (empty($nim)) $errors[] = 'NIM wajib diisi';
            if ($tLulusKonf < 1950 || $tLulusKonf > 2100) $errors[] = 'Tahun Lulus Konfirmasi harus antara 1950 dan 2100';
            
            $validStatuses = ['BELUM_BEKERJA', 'GURU', 'NON_PENDIDIKAN', 'MAHASISWA_S2_S3', 'LAINNYA'];
            if (!in_array($statusPekerjaan, $validStatuses)) {
                $errors[] = 'Status Pekerjaan tidak valid';
            }
            if ($statusPekerjaan === 'LAINNYA') {
                if (empty($statusPekerjaanDetail) || $statusPekerjaanDetail === '-') {
                    $errors[] = 'Detail Status Pekerjaan wajib diisi jika memilih LAINNYA';
                }
            } else {
                $statusPekerjaanDetail = null;
            }
            if (empty($namaInstansi)) $errors[] = 'Nama Instansi wajib diisi';
            if (!preg_match('/^[0-9]{10,15}$/', $nomorHp)) {
                $errors[] = 'Nomor HP harus 10-15 digit angka';
            }

            if ($lanjutS2S3) {
                if (empty($jurusanS2S3) || $jurusanS2S3 === '-') $errors[] = 'Jurusan S2/S3 wajib diisi';
                if (empty($universitasS2S3) || $universitasS2S3 === '-') $errors[] = 'Universitas S2/S3 wajib diisi';
            } else {
                $jurusanS2S3 = null;
                $universitasS2S3 = null;
            }

            if ($lanjutPpg) {
                if ($tPpgRaw < 1950 || $tPpgRaw > 2100) {
                    $errors[] = 'Tahun PPG harus antara 1950 dan 2100';
                }
                if (empty($universitasPpg) || $universitasPpg === '-') $errors[] = 'Universitas PPG wajib diisi';
            } else {
                $tPpgRaw = null;
                $universitasPpg = null;
            }

            $surveyData = [
                'nim' => $nim,
                'nama_lengkap' => $namaLengkap,
                'tahun_lulus' => $tLulusKonf,
                'tahun_lulus_konfirmasi' => $tLulusKonf,
                'status_pekerjaan' => $statusPekerjaan,
                'status_pekerjaan_detail' => $statusPekerjaanDetail,
                'nama_instansi' => $namaInstansi,
                'nomor_hp' => $nomorHp,
                'lanjut_s2s3' => $lanjutS2S3,
                'jurusan_s2s3' => $jurusanS2S3,
                'universitas_s2s3' => $universitasS2S3,
                'lanjut_ppg' => $lanjutPpg,
                'tahun_ppg' => $tPpgRaw,
                'universitas_ppg' => $universitasPpg,
                'pesan_saran' => empty($pesanSaran) || $pesanSaran === '-' ? null : $pesanSaran
            ];

            $previewRow = [
                'row_number' => $rowNumber,
                'nama_lengkap' => $namaLengkap,
                'nim' => $nim,
                'tahun_lulus' => $tLulusKonf,
                'is_valid' => empty($errors),
                'errors' => $errors,
                'data' => $surveyData
            ];

            if (empty($errors)) {
                $validRows[] = $previewRow;
            } else {
                $invalidRows[] = $previewRow;
            }
        }

        if (empty($validRows) && empty($invalidRows)) {
            throw new HttpException('File Excel kosong atau tidak memiliki data yang dapat diproses', 400);
        }

        // Save to session
        $importId = uniqid('survey_import_', true);
        if (!isset($_SESSION['survey_import_drafts'])) {
            $_SESSION['survey_import_drafts'] = [];
        }
        
        $_SESSION['survey_import_drafts'][$importId] = [
            'valid_rows' => $validRows,
            'created_at' => time()
        ];

        sendJson([
            'import_id' => $importId,
            'total_valid' => count($validRows),
            'total_invalid' => count($invalidRows),
            'invalid_rows' => $invalidRows
        ], 201, 'Preview import survey berhasil dibuat');
    }

    /**
     * POST /admin/surveys/import/confirm
     */
    public function confirmImport($requestData) {
        $importId = $requestData['import_id'] ?? '';
        if (empty($importId)) {
            throw new HttpException('import_id dibutuhkan', 400);
        }

        if (!isset($_SESSION['survey_import_drafts'][$importId])) {
            throw new HttpException('Import draft tidak ditemukan atau expired.', 404);
        }

        $draft = $_SESSION['survey_import_drafts'][$importId];
        $validRows = $draft['valid_rows'];

        if (empty($validRows)) {
            unset($_SESSION['survey_import_drafts'][$importId]);
            throw new HttpException('Tidak ada data valid untuk diimport', 400);
        }

        $inserted = 0;
        $skipped = 0;

        try {
            DB::transaction(function($pdo) use ($validRows, &$inserted, &$skipped) {
                foreach ($validRows as $row) {
                    $surveyData = $row['data'];
                    
                    // Find alumni by NIM
                    $alumni = $this->alumniRepo->findByNim($surveyData['nim']);
                    if (!$alumni) {
                        $skipped++;
                        continue;
                    }

                    $alumniId = (int)$alumni['id'];
                    $exists = $this->surveyRepo->existsByAlumniId($alumniId);

                    if ($exists) {
                        $this->surveyRepo->updateByAlumniId($alumniId, $surveyData);
                    } else {
                        $this->surveyRepo->create($alumniId, $surveyData);
                    }

                    // Sync grad year to alumni profile
                    if ($surveyData['tahun_lulus_konfirmasi'] !== (int)$alumni['tahun_lulus']) {
                        $this->alumniRepo->update($alumniId, ['tahun_lulus' => $surveyData['tahun_lulus_konfirmasi']]);
                    }

                    $inserted++;
                }
            });
        } catch (Exception $e) {
            throw new HttpException('Database error during import confirmation: ' . $e->getMessage(), 500);
        }

        // Clean up session
        unset($_SESSION['survey_import_drafts'][$importId]);

        sendJson([
            'import_id' => $importId,
            'total_processed' => $inserted + $skipped,
            'inserted' => $inserted,
            'skipped' => $skipped
        ], 200, 'Import survey berhasil dikonfirmasi');
    }

    /**
     * Validate and sanitize survey input data.
     * Throws 422 exception if validation fails.
     */
    private function validateSurveyInput($data) {
        $errors = [];

        $tahunLulus = isset($data['tahun_lulus_konfirmasi']) ? (int)$data['tahun_lulus_konfirmasi'] : 0;
        if ($tahunLulus < 1950 || $tahunLulus > 2100) {
            $errors[] = ['field' => 'tahun_lulus_konfirmasi', 'message' => 'Tahun lulus harus antara 1950 dan 2100'];
        }

        $validStatuses = ['BELUM_BEKERJA', 'GURU', 'NON_PENDIDIKAN', 'MAHASISWA_S2_S3', 'LAINNYA'];
        $statusPekerjaan = $data['status_pekerjaan'] ?? '';
        if (!in_array($statusPekerjaan, $validStatuses)) {
            $errors[] = ['field' => 'status_pekerjaan', 'message' => 'Status pekerjaan tidak valid'];
        }

        $statusPekerjaanDetail = null;
        if ($statusPekerjaan === 'LAINNYA') {
            $statusPekerjaanDetail = isset($data['status_pekerjaan_detail']) ? trim($data['status_pekerjaan_detail']) : '';
            if (empty($statusPekerjaanDetail)) {
                $errors[] = ['field' => 'status_pekerjaan_detail', 'message' => 'Detail status pekerjaan wajib diisi jika memilih Lainnya'];
            }
        }

        $namaInstansi = isset($data['nama_instansi']) ? trim($data['nama_instansi']) : '';
        if (empty($namaInstansi)) {
            $errors[] = ['field' => 'nama_instansi', 'message' => 'Nama instansi wajib diisi'];
        }

        $nomorHp = isset($data['nomor_hp']) ? trim($data['nomor_hp']) : '';
        if (!preg_match('/^[0-9]{10,15}$/', $nomorHp)) {
            $errors[] = ['field' => 'nomor_hp', 'message' => 'Nomor HP harus 10-15 digit angka'];
        }

        $lanjutS2S3 = isset($data['lanjut_s2s3']) ? (bool)$data['lanjut_s2s3'] : null;
        if ($lanjutS2S3 === null) {
            $errors[] = ['field' => 'lanjut_s2s3', 'message' => 'Pilihan studi lanjut S2/S3 wajib diisi'];
        }

        $lanjutPpg = isset($data['lanjut_ppg']) ? (bool)$data['lanjut_ppg'] : null;
        if ($lanjutPpg === null) {
            $errors[] = ['field' => 'lanjut_ppg', 'message' => 'Pilihan PPG wajib diisi'];
        }

        $jurusanS2S3 = null;
        $universitasS2S3 = null;
        if ($lanjutS2S3) {
            $jurusanS2S3 = isset($data['jurusan_s2s3']) ? trim($data['jurusan_s2s3']) : '';
            if (empty($jurusanS2S3)) {
                $errors[] = ['field' => 'jurusan_s2s3', 'message' => 'Jurusan S2/S3 wajib diisi jika lanjut S2/S3'];
            }
            $universitasS2S3 = isset($data['universitas_s2s3']) ? trim($data['universitas_s2s3']) : '';
            if (empty($universitasS2S3)) {
                $errors[] = ['field' => 'universitas_s2s3', 'message' => 'Universitas S2/S3 wajib diisi jika lanjut S2/S3'];
            }
        }

        $tahunPpg = null;
        $universitasPpg = null;
        if ($lanjutPpg) {
            $tahunPpg = isset($data['tahun_ppg']) && $data['tahun_ppg'] !== '' ? (int)$data['tahun_ppg'] : null;
            if ($tahunPpg === null) {
                $errors[] = ['field' => 'tahun_ppg', 'message' => 'Tahun PPG wajib diisi jika mengikuti PPG'];
            } elseif ($tahunPpg < 1950 || $tahunPpg > 2100) {
                $errors[] = ['field' => 'tahun_ppg', 'message' => 'Tahun PPG harus antara 1950 dan 2100'];
            }
            $universitasPpg = isset($data['universitas_ppg']) ? trim($data['universitas_ppg']) : '';
            if (empty($universitasPpg)) {
                $errors[] = ['field' => 'universitas_ppg', 'message' => 'Universitas PPG wajib diisi jika mengikuti PPG'];
            }
        }

        $pesanSaran = isset($data['pesan_saran']) ? trim($data['pesan_saran']) : null;
        if (empty($pesanSaran)) $pesanSaran = null;

        if (!empty($errors)) {
            throw new HttpException('Terdapat kesalahan pada data yang diisi.', 422, $errors);
        }

        return [
            'tahun_lulus_konfirmasi' => $tahunLulus,
            'status_pekerjaan' => $statusPekerjaan,
            'status_pekerjaan_detail' => $statusPekerjaanDetail,
            'nama_instansi' => $namaInstansi,
            'nomor_hp' => $nomorHp,
            'lanjut_s2s3' => $lanjutS2S3,
            'jurusan_s2s3' => $jurusanS2S3,
            'universitas_s2s3' => $universitasS2S3,
            'lanjut_ppg' => $lanjutPpg,
            'tahun_ppg' => $tahunPpg,
            'universitas_ppg' => $universitasPpg,
            'pesan_saran' => $pesanSaran
        ];
    }
}
