<?php
// Dashboard Controller

class DashboardController {
    private $surveyRepo;

    public function __construct() {
        $this->surveyRepo = new SurveyRepository();
    }

    /**
     * GET /admin/dashboard/charts
     */
    public function charts($requestData) {
        $db = DB::getConnection();

        // 1. Total Alumni
        $totalAlumni = (int)$db->query("SELECT COUNT(*) FROM alumni")->fetchColumn();

        // 2. Total Surveys (filled)
        $totalSurveys = $this->surveyRepo->countTotal();

        // 3. Unfilled surveys
        $belumMengisi = $totalAlumni - $totalSurveys;

        // 4. Response rate
        $responseRate = $totalAlumni > 0 ? round(($totalSurveys / $totalAlumni) * 100, 2) : 0;

        // Fetch distributions from repo
        $statusPekerjaan = $this->surveyRepo->countByStatusPekerjaan();
        $universitasPpg = $this->surveyRepo->countByUniversitasPpg();
        $universitasS2s3 = $this->surveyRepo->countByUniversitasS2s3();
        $jurusanS2s3 = $this->surveyRepo->countByJurusanS2s3();
        $ppgDistribution = $this->surveyRepo->countPpgDistribution();
        $s2s3Distribution = $this->surveyRepo->countS2s3Distribution();
        $tahunLulus = $this->surveyRepo->countByTahunLulus();

        sendJson([
            'summary' => [
                'total_alumni' => $totalAlumni,
                'total_surveys' => $totalSurveys,
                'belum_mengisi' => $belumMengisi,
                'response_rate' => $responseRate
            ],
            'status_pekerjaan' => $this->formatChartData($statusPekerjaan),
            'universitas_ppg' => $this->formatChartData($universitasPpg),
            'universitas_s2s3' => $this->formatChartData($universitasS2s3),
            'jurusan_s2s3' => $this->formatChartData($jurusanS2s3),
            'ppg_distribution' => $this->formatChartData($ppgDistribution),
            's2s3_distribution' => $this->formatChartData($s2s3Distribution),
            'tahun_lulus' => $this->formatChartData($tahunLulus)
        ], 200, 'Data dashboard');
    }

    private function formatChartData($rows) {
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'label' => $row['label'] !== null ? (string)$row['label'] : '',
                'value' => (int)$row['value']
            ];
        }
        return $result;
    }
}
