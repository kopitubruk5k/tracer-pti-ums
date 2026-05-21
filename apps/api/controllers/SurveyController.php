<?php
// Survey Controller

class SurveyController {
    private $surveyRepo;
    private $alumniRepo;

    public function __construct() {
        $this->surveyRepo = new SurveyRepository();
        $this->alumniRepo = new AlumniRepository();
    }

    /**
     * GET /alumni/:alumni_id/survey
     */
    public function getSurvey($requestData, $alumniId) {
        $alumniId = (int)$alumniId;
        $alumni = $this->alumniRepo->findById($alumniId);
        if (!$alumni) {
            throw new HttpException('Alumni tidak ditemukan', 404);
        }

        $survey = $this->surveyRepo->findByAlumniId($alumniId);
        if (!$survey) {
            throw new HttpException('Survey belum diisi untuk alumni ini', 404);
        }

        sendJson($survey, 200, 'Data survey alumni');
    }

    /**
     * POST /alumni/:alumni_id/survey
     */
    public function createSurvey($requestData, $alumniId) {
        $alumniId = (int)$alumniId;
        $alumni = $this->alumniRepo->findById($alumniId);
        if (!$alumni) {
            throw new HttpException('Alumni tidak ditemukan', 404);
        }

        $exists = $this->surveyRepo->existsByAlumniId($alumniId);
        if ($exists) {
            throw new HttpException('Survey sudah pernah diisi untuk alumni ini. Gunakan PUT untuk memperbarui.', 409);
        }

        $validatedData = $this->validateSurveyInput($requestData);

        // Transactional create
        $survey = DB::transaction(function() use ($alumniId, $validatedData, $alumni) {
            // Update alumni's official grad year if different
            if ($validatedData['tahun_lulus_konfirmasi'] !== (int)$alumni['tahun_lulus']) {
                $this->alumniRepo->update($alumniId, ['tahun_lulus' => $validatedData['tahun_lulus_konfirmasi']]);
            }
            return $this->surveyRepo->create($alumniId, $validatedData);
        });

        sendJson($survey, 201, 'Survey berhasil disimpan');
    }

    /**
     * PUT /alumni/:alumni_id/survey
     */
    public function updateSurvey($requestData, $alumniId) {
        $alumniId = (int)$alumniId;
        $alumni = $this->alumniRepo->findById($alumniId);
        if (!$alumni) {
            throw new HttpException('Alumni tidak ditemukan', 404);
        }

        $exists = $this->surveyRepo->existsByAlumniId($alumniId);
        if (!$exists) {
            throw new HttpException('Survey belum ada. Gunakan POST untuk membuat survey baru.', 404);
        }

        $validatedData = $this->validateSurveyInput($requestData);

        // Transactional update
        $survey = DB::transaction(function() use ($alumniId, $validatedData, $alumni) {
            // Update alumni's official grad year if different
            if ($validatedData['tahun_lulus_konfirmasi'] !== (int)$alumni['tahun_lulus']) {
                $this->alumniRepo->update($alumniId, ['tahun_lulus' => $validatedData['tahun_lulus_konfirmasi']]);
            }
            return $this->surveyRepo->updateByAlumniId($alumniId, $validatedData);
        });

        sendJson($survey, 200, 'Survey berhasil diperbarui');
    }

    /**
     * Validate and sanitize survey input data.
     * Throws 422 exception if validation fails.
     */
    private function validateSurveyInput($data) {
        $errors = [];

        // 1. tahun_lulus_konfirmasi
        $tahunLulus = isset($data['tahun_lulus_konfirmasi']) ? (int)$data['tahun_lulus_konfirmasi'] : 0;
        if ($tahunLulus < 1950 || $tahunLulus > 2100) {
            $errors[] = ['field' => 'tahun_lulus_konfirmasi', 'message' => 'Tahun lulus harus antara 1950 dan 2100'];
        }

        // 2. status_pekerjaan
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

        // 3. nama_instansi
        $namaInstansi = isset($data['nama_instansi']) ? trim($data['nama_instansi']) : '';
        if (empty($namaInstansi)) {
            $errors[] = ['field' => 'nama_instansi', 'message' => 'Nama instansi wajib diisi'];
        }

        // 4. nomor_hp
        $nomorHp = isset($data['nomor_hp']) ? trim($data['nomor_hp']) : '';
        if (!preg_match('/^[0-9]{10,15}$/', $nomorHp)) {
            $errors[] = ['field' => 'nomor_hp', 'message' => 'Nomor HP harus 10-15 digit angka'];
        }

        // 5. lanjut_s2s3
        $lanjutS2S3 = isset($data['lanjut_s2s3']) ? (bool)$data['lanjut_s2s3'] : null;
        if ($lanjutS2S3 === null) {
            $errors[] = ['field' => 'lanjut_s2s3', 'message' => 'Pilihan studi lanjut S2/S3 wajib diisi'];
        }

        // 6. lanjut_ppg
        $lanjutPpg = isset($data['lanjut_ppg']) ? (bool)$data['lanjut_ppg'] : null;
        if ($lanjutPpg === null) {
            $errors[] = ['field' => 'lanjut_ppg', 'message' => 'Pilihan PPG wajib diisi'];
        }

        // Conditional S2/S3
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

        // Conditional PPG
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
