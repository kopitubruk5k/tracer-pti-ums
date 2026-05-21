"use client";

import { useState, useEffect, useCallback, Suspense } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import Link from "next/link";
import Navbar from "@/components/ui/Navbar";
import Footer from "@/components/ui/Footer";
import LoadingSpinner from "@/components/ui/LoadingSpinner";
import FormSection from "@/components/form/FormSection";
import InputField from "@/components/form/InputField";
import SelectField from "@/components/form/SelectField";
import RadioGroup from "@/components/form/RadioGroup";
import TextArea from "@/components/form/TextArea";
import { api, ApiError } from "@/lib/api";
import type {
  Alumni,
  Survey,
  SurveyInput,
  ApiSuccessResponse,
} from "@/lib/types";
import { STATUS_PEKERJAAN_LABELS, StatusPekerjaan } from "@/lib/types";
import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
} from "@heroicons/react/24/outline";

// --- Status pekerjaan options ---
const STATUS_OPTIONS = Object.entries(STATUS_PEKERJAAN_LABELS).map(
  ([value, label]) => ({ value, label })
);

// --- Tahun options ---
const currentYear = new Date().getFullYear();
const TAHUN_OPTIONS = Array.from({ length: currentYear - 1950 + 1 }, (_, i) => {
  const year = currentYear - i;
  return { value: String(year), label: String(year) };
});

// --- Form state type ---
interface FormData {
  tahun_lulus_konfirmasi: string;
  status_pekerjaan: string;
  status_pekerjaan_detail: string;
  nama_instansi: string;
  nomor_hp: string;
  lanjut_s2s3: boolean | null;
  jurusan_s2s3: string;
  universitas_s2s3: string;
  lanjut_ppg: boolean | null;
  tahun_ppg: string;
  universitas_ppg: string;
  pesan_saran: string;
}

type FormErrors = Partial<Record<keyof FormData, string>>;

const INITIAL_FORM: FormData = {
  tahun_lulus_konfirmasi: "",
  status_pekerjaan: "",
  status_pekerjaan_detail: "",
  nama_instansi: "",
  nomor_hp: "",
  lanjut_s2s3: null,
  jurusan_s2s3: "",
  universitas_s2s3: "",
  lanjut_ppg: null,
  tahun_ppg: "",
  universitas_ppg: "",
  pesan_saran: "",
};

function SurveyFormContent() {
  const searchParams = useSearchParams();
  const router = useRouter();
  const alumniId = searchParams.get("id") || "";

  // Page state
  const [pageLoading, setPageLoading] = useState(true);
  const [pageError, setPageError] = useState<string | null>(null);
  const [mode, setMode] = useState<"create" | "edit" | null>(null);
  const [alumni, setAlumni] = useState<Alumni | null>(null);

  // Form state
  const [form, setForm] = useState<FormData>(INITIAL_FORM);
  const [errors, setErrors] = useState<FormErrors>({});
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [updateSuccess, setUpdateSuccess] = useState(false);

  // Update a single form field
  const setField = useCallback(
    <K extends keyof FormData>(key: K, value: FormData[K]) => {
      setForm((prev) => ({ ...prev, [key]: value }));
      // Clear field error on change
      setErrors((prev) => {
        if (prev[key]) {
          const next = { ...prev };
          delete next[key];
          return next;
        }
        return prev;
      });
      setUpdateSuccess(false);
    },
    []
  );

  // --- Progress calculation ---
  const calculateProgress = useCallback(() => {
    let filled = 0;
    const fieldsToTrack = [
      form.tahun_lulus_konfirmasi,
      form.status_pekerjaan,
      form.nama_instansi,
      form.nomor_hp,
      form.lanjut_s2s3,
      form.lanjut_ppg,
    ];
    
    if (form.status_pekerjaan?.toUpperCase() === "LAINNYA") {
      fieldsToTrack.push(form.status_pekerjaan_detail);
    }
    
    fieldsToTrack.forEach((val) => {
      if (val !== null && val !== "") {
        if (typeof val === "string" && val.trim() === "") return;
        filled++;
      }
    });
    
    return Math.round((filled / fieldsToTrack.length) * 100);
  }, [form]);

  const progress = calculateProgress();

  // --- Load alumni status & existing survey ---
  useEffect(() => {
    if (!alumniId) {
      setPageError("ID Alumni tidak ditemukan. Silakan lakukan pencarian alumni terlebih dahulu.");
      setPageLoading(false);
      return;
    }

    async function loadSurveyStatus() {
      setPageLoading(true);
      setPageError(null);

      try {
        // Step 1: Check survey status
        const statusRes = await api.get<
          ApiSuccessResponse<{ survey_exists: boolean; alumni: Alumni }>
        >(`/alumni/${alumniId}/status`);

        setAlumni(statusRes.data.alumni);

        if (statusRes.data.survey_exists) {
          // EDIT mode — fetch existing survey data
          setMode("edit");
          try {
            const surveyRes = await api.get<ApiSuccessResponse<Survey>>(
              `/alumni/${alumniId}/survey`
            );
            const s = surveyRes.data;
            setForm({
              tahun_lulus_konfirmasi: String(s.tahun_lulus_konfirmasi),
              status_pekerjaan: s.status_pekerjaan,
              status_pekerjaan_detail: s.status_pekerjaan_detail || "",
              nama_instansi: s.nama_instansi,
              nomor_hp: s.nomor_hp,
              lanjut_s2s3: s.lanjut_s2s3,
              jurusan_s2s3: s.jurusan_s2s3 || "",
              universitas_s2s3: s.universitas_s2s3 || "",
              lanjut_ppg: s.lanjut_ppg,
              tahun_ppg: s.tahun_ppg ? String(s.tahun_ppg) : "",
              universitas_ppg: s.universitas_ppg || "",
              pesan_saran: s.pesan_saran || "",
            });
          } catch {
            setPageError("Gagal memuat data survey. Silakan coba lagi.");
          }
        } else {
          // CREATE mode
          setMode("create");
          // Pre-fill tahun_lulus from alumni data
          if (statusRes.data.alumni) {
            setForm((prev) => ({
              ...prev,
              tahun_lulus_konfirmasi: String(statusRes.data.alumni.tahun_lulus),
            }));
          }
        }
      } catch (err) {
        if (err instanceof ApiError && err.status === 404) {
          setPageError("Data alumni tidak ditemukan.");
        } else {
          setPageError("Gagal memuat status survey. Silakan coba lagi.");
        }
      } finally {
        setPageLoading(false);
      }
    }

    loadSurveyStatus();
  }, [alumniId]);

  // --- Frontend validation (mirrors backend schema) ---
  const validate = (): FormErrors => {
    const e: FormErrors = {};

    if (!form.tahun_lulus_konfirmasi) {
      e.tahun_lulus_konfirmasi = "Tahun lulus wajib diisi";
    }

    if (!form.status_pekerjaan) {
      e.status_pekerjaan = "Status pekerjaan wajib dipilih";
    } else if (form.status_pekerjaan?.toUpperCase() === "LAINNYA") {
      if (!form.status_pekerjaan_detail.trim()) {
        e.status_pekerjaan_detail = "Detail status pekerjaan wajib diisi";
      }
    }

    if (!form.nama_instansi.trim()) {
      e.nama_instansi = "Nama instansi wajib diisi";
    }

    if (!form.nomor_hp.trim()) {
      e.nomor_hp = "Nomor HP wajib diisi";
    } else if (!/^[0-9]{10,15}$/.test(form.nomor_hp.trim())) {
      e.nomor_hp = "Nomor HP harus 10-15 digit angka";
    }

    // S2/S3 conditional
    if (form.lanjut_s2s3 === null) {
      e.lanjut_s2s3 = "Pilihan studi lanjut S2/S3 wajib diisi";
    } else if (form.lanjut_s2s3) {
      if (!form.jurusan_s2s3.trim()) {
        e.jurusan_s2s3 = "Jurusan S2/S3 wajib diisi jika lanjut S2/S3";
      }
      if (!form.universitas_s2s3.trim()) {
        e.universitas_s2s3 = "Universitas S2/S3 wajib diisi jika lanjut S2/S3";
      }
    }

    // PPG conditional
    if (form.lanjut_ppg === null) {
      e.lanjut_ppg = "Pilihan PPG wajib diisi";
    } else if (form.lanjut_ppg) {
      if (!form.tahun_ppg) {
        e.tahun_ppg = "Tahun PPG wajib diisi jika mengikuti PPG";
      }
      if (!form.universitas_ppg.trim()) {
        e.universitas_ppg =
          "Universitas PPG wajib diisi jika mengikuti PPG";
      }
    }

    return e;
  };

  // --- Build API body ---
  const buildBody = (): SurveyInput => {
    return {
      tahun_lulus_konfirmasi: parseInt(form.tahun_lulus_konfirmasi, 10),
      status_pekerjaan: form.status_pekerjaan as StatusPekerjaan,
      status_pekerjaan_detail: form.status_pekerjaan?.toUpperCase() === "LAINNYA" ? form.status_pekerjaan_detail.trim() : null,
      nama_instansi: form.nama_instansi.trim(),
      nomor_hp: form.nomor_hp.trim(),
      lanjut_s2s3: form.lanjut_s2s3!,
      jurusan_s2s3: form.lanjut_s2s3 ? form.jurusan_s2s3.trim() : null,
      universitas_s2s3: form.lanjut_s2s3
        ? form.universitas_s2s3.trim()
        : null,
      lanjut_ppg: form.lanjut_ppg!,
      tahun_ppg: form.lanjut_ppg ? parseInt(form.tahun_ppg, 10) : null,
      universitas_ppg: form.lanjut_ppg
        ? form.universitas_ppg.trim()
        : null,
      pesan_saran: form.pesan_saran.trim() || null,
    };
  };

  // --- Submit handler ---
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitError(null);
    setUpdateSuccess(false);

    // Validate
    const validationErrors = validate();
    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      // Scroll to first error
      const firstErrorKey = Object.keys(validationErrors)[0];
      document.getElementById(firstErrorKey)?.scrollIntoView({
        behavior: "smooth",
        block: "center",
      });
      return;
    }

    setErrors({});
    setSubmitting(true);

    try {
      const body = buildBody();

      if (mode === "create") {
        // POST — create new survey
        await api.post(`/alumni/${alumniId}/survey`, body);
        router.push("/survey/selesai");
      } else {
        // PUT — update existing survey
        await api.put(`/alumni/${alumniId}/survey`, body);
        setUpdateSuccess(true);
        // Scroll to top to show success
        window.scrollTo({ top: 0, behavior: "smooth" });
      }
    } catch (err) {
      if (err instanceof ApiError) {
        if (err.status === 409) {
          setSubmitError("Survey sudah pernah diisi sebelumnya.");
        } else if (err.status === 404) {
          setSubmitError("Data alumni tidak ditemukan.");
        } else if (err.status === 422) {
          // Validation errors from backend
          const apiData = err.data as {
            errors?: Array<{ field?: string; message: string }>;
          };
          if (apiData?.errors) {
            const backendErrors: FormErrors = {};
            apiData.errors.forEach((e) => {
              if (e.field) {
                backendErrors[e.field as keyof FormData] = e.message;
              }
            });
            setErrors(backendErrors);
          }
          setSubmitError("Terdapat kesalahan pada data yang diisi.");
        } else {
          setSubmitError(err.message || "Terjadi kesalahan. Silakan coba lagi.");
        }
      } else {
        setSubmitError("Terjadi kesalahan jaringan. Silakan coba lagi.");
      }
    } finally {
      setSubmitting(false);
    }
  };

  // Loading state
  if (pageLoading) {
    return (
      <main className="flex-1 bg-gradient-to-br from-slate-50 via-blue-50/30 to-emerald-50/20 min-h-[60vh] flex items-center justify-center py-20">
        <div className="text-center p-8 bg-white/80 backdrop-blur-md rounded-2xl shadow-xl shadow-neutral-100 border border-neutral-100 max-w-sm">
          <LoadingSpinner size="lg" />
          <p className="mt-4 text-sm font-semibold text-neutral-600">
            Memuat data survey...
          </p>
        </div>
      </main>
    );
  }

  // Error state
  if (pageError) {
    return (
      <main className="flex-1 bg-gradient-to-br from-slate-50 via-blue-50/30 to-emerald-50/20 min-h-[60vh] flex items-center justify-center py-20">
        <div className="text-center p-8 bg-white/90 backdrop-blur-md rounded-2xl shadow-xl shadow-neutral-100 border border-neutral-100 max-w-md mx-4">
          <div className="w-12 h-12 bg-red-50 text-danger-500 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-red-100">
            <ExclamationTriangleIcon className="w-6 h-6" />
          </div>
          <p className="text-base font-bold text-neutral-800 mb-2">
            Terjadi Kesalahan
          </p>
          <p className="text-sm text-neutral-500 mb-6 leading-relaxed">
            {pageError}
          </p>
          <Link
            href="/"
            className="inline-flex items-center justify-center px-5 py-2.5 bg-ums-blue hover:bg-ums-blue-dark text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-md shadow-ums-blue/10 hover:shadow-lg hover:shadow-ums-blue/20"
          >
            Kembali ke Beranda
          </Link>
        </div>
      </main>
    );
  }

  return (
    <main className="flex-1 bg-gradient-to-br from-slate-50 via-blue-50/30 to-emerald-50/20 py-8 sm:py-12 min-h-screen">
      <div className="max-w-7xl mx-auto px-4 sm:px-6">
        {/* Page header */}
        <div className="mb-6">
          <Link
            href="/"
            className="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-ums-blue hover:text-ums-blue-dark transition-colors mb-3 group"
          >
            <span className="transition-transform group-hover:-translate-x-0.5">←</span> Kembali ke Pencarian
          </Link>

          <h1 className="text-2xl sm:text-3xl font-extrabold text-neutral-800 tracking-tight">
            {mode === "create"
              ? "Pengisian Survey Tracer Study"
              : "Edit Jawaban Survey"}
          </h1>
          <p className="text-xs sm:text-sm text-neutral-500 mt-1">
            Tracer Study Program Studi Pendidikan Teknik Informatika (PTI) UMS
          </p>
        </div>

        {/* Two-column layout on Desktop, stacked on Mobile */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8 items-start">
          
          {/* LEFT COLUMN: Profile and Progress (Sticky on Desktop) */}
          <div className="lg:col-span-1 lg:sticky lg:top-6 space-y-6">
            {alumni && (
              <div className="bg-white/95 backdrop-blur-md border border-neutral-200/60 rounded-2xl p-4 sm:p-5 shadow-lg shadow-neutral-100/70 flex flex-col sm:flex-row lg:flex-col items-center sm:items-start lg:items-center gap-4 transition-all duration-300 hover:shadow-xl">
                <div className="w-16 h-16 rounded-2xl bg-gradient-to-tr from-[#1b3a5c] via-[#155d9b] to-[#2e7d32] flex items-center justify-center shrink-0 shadow-md shadow-neutral-200/50">
                  <span className="text-2xl font-bold text-white tracking-wide">
                    {alumni.nama_lengkap.charAt(0).toUpperCase()}
                  </span>
                </div>
                <div className="flex-1 min-w-0 text-center sm:text-left lg:text-center w-full">
                  <div className="flex flex-col items-center sm:items-start lg:items-center gap-1">
                    <h2 className="text-base sm:text-lg font-bold text-neutral-800 break-words w-full">
                      {alumni.nama_lengkap}
                    </h2>
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] sm:text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100/80 shadow-xs mt-1">
                      <svg className="w-3.5 h-3.5 fill-current text-emerald-600 shrink-0" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clipRule="evenodd"/>
                      </svg>
                      Alumni Terverifikasi
                    </span>
                  </div>
                  <div className="text-xs sm:text-sm text-neutral-500 mt-3 border-t border-neutral-100 pt-3 flex flex-col gap-1.5 items-center sm:items-start lg:items-center font-medium">
                    <span className="text-neutral-700">NIM: {alumni.nim}</span>
                    <span>Tahun Lulus: {alumni.tahun_lulus}</span>
                  </div>
                </div>
              </div>
            )}

            {/* Progress indicator card */}
            <div className="bg-white/95 backdrop-blur-md border border-neutral-200/60 rounded-2xl p-4 sm:p-5 shadow-lg shadow-neutral-100/60 flex flex-col gap-3">
              <div className="flex justify-between items-center">
                <span className="text-xs sm:text-sm font-semibold text-neutral-700">Progress Pengisian</span>
                <span className="text-sm sm:text-base font-bold text-ums-blue">{progress}%</span>
              </div>
              <div className="w-full bg-neutral-100 h-2.5 rounded-full overflow-hidden">
                <div 
                  className="bg-gradient-to-r from-ums-blue via-ums-blue-light to-secondary-500 h-full rounded-full transition-all duration-500 ease-out" 
                  style={{ width: `${progress}%` }} 
                />
              </div>
              <p className="text-[11px] text-neutral-400 mt-1">
                Lengkapi semua bidang wajib bertanda bintang (*) untuk dapat mengirim survey.
              </p>
            </div>
          </div>

          {/* RIGHT COLUMN: Form Sections and Actions */}
          <div className="lg:col-span-2 space-y-6">
            
            {/* Update success message */}
            {updateSuccess && (
              <div className="bg-emerald-50/90 backdrop-blur-sm border border-emerald-200/60 rounded-2xl p-4 flex items-start gap-3.5 shadow-sm shadow-emerald-100/50 animate-fadeIn">
                <div className="p-1.5 bg-emerald-500 text-white rounded-lg shrink-0">
                  <CheckCircleIcon className="w-5 h-5" />
                </div>
                <div>
                  <p className="text-sm font-bold text-emerald-800">
                    Data Berhasil Diperbarui
                  </p>
                  <p className="text-xs text-emerald-600/90 mt-0.5 font-medium">
                    Jawaban survey Anda telah disimpan dengan sukses.
                  </p>
                </div>
              </div>
            )}

            {/* Submit error */}
            {submitError && (
              <div className="bg-red-50 border border-red-200 rounded-2xl p-4 flex items-start gap-3 shadow-xs animate-fadeIn">
                <ExclamationTriangleIcon className="w-5 h-5 text-danger-500 mt-0.5 shrink-0" />
                <p className="text-sm text-danger-500 font-medium">{submitError}</p>
              </div>
            )}

            {/* === FORM === */}
            <form onSubmit={handleSubmit} className="space-y-6" noValidate>
              {/* Group 1: Data Dasar */}
              <FormSection
                title="A. Identitas Dasar"
                description="Konfirmasi data dan isi informasi pekerjaan Anda saat ini."
              >
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <SelectField
                    id="tahun_lulus_konfirmasi"
                    label="Tahun Lulus dari UMS"
                    value={form.tahun_lulus_konfirmasi}
                    onChange={(v) => setField("tahun_lulus_konfirmasi", v)}
                    options={TAHUN_OPTIONS}
                    placeholder="Pilih tahun lulus"
                    error={errors.tahun_lulus_konfirmasi}
                    required
                  />

                  <SelectField
                    id="status_pekerjaan"
                    label="Status Pekerjaan Saat Ini"
                    value={form.status_pekerjaan}
                    onChange={(v) => {
                      setField("status_pekerjaan", v);
                      if (v.toUpperCase() !== "LAINNYA") {
                        setField("status_pekerjaan_detail", "");
                      }
                    }}
                    options={STATUS_OPTIONS}
                    placeholder="Pilih status pekerjaan"
                    error={errors.status_pekerjaan}
                    required
                  />

                  {form.status_pekerjaan?.toUpperCase() === "LAINNYA" && (
                    <InputField
                      id="status_pekerjaan_detail"
                      label="Sebutkan Pekerjaan / Status Lainnya"
                      value={form.status_pekerjaan_detail}
                      onChange={(v) => setField("status_pekerjaan_detail", v)}
                      placeholder="Contoh: Wirausaha, Swasta, dll"
                      error={errors.status_pekerjaan_detail}
                      required
                    />
                  )}

                  <InputField
                    id="nama_instansi"
                    label="Nama Instansi / Tempat Mengajar"
                    value={form.nama_instansi}
                    onChange={(v) => setField("nama_instansi", v)}
                    placeholder="Contoh: SDN 01 Surakarta"
                    error={errors.nama_instansi}
                    required
                  />

                  <InputField
                    id="nomor_hp"
                    label="Nomor HP Aktif"
                    type="tel"
                    value={form.nomor_hp}
                    onChange={(v) => setField("nomor_hp", v)}
                    placeholder="Contoh: 081234567890"
                    hint="10-15 digit angka tanpa spasi/tanda hubung"
                    error={errors.nomor_hp}
                    required
                  />
                </div>
              </FormSection>

              {/* Group 2: Studi Lanjut S2/S3 */}
              <FormSection
                title="B. Studi Lanjut S2/S3"
                description="Apakah Anda melanjutkan studi ke jenjang S2 atau S3?"
              >
                <RadioGroup
                  id="lanjut_s2s3"
                  label="Apakah melanjutkan studi ke S2/S3?"
                  value={form.lanjut_s2s3}
                  onChange={(v) => {
                    setField("lanjut_s2s3", v);
                    if (!v) {
                      setField("jurusan_s2s3", "");
                      setField("universitas_s2s3", "");
                    }
                  }}
                  error={errors.lanjut_s2s3}
                  required
                />

                {form.lanjut_s2s3 === true && (
                  <div className="space-y-4 pl-4 sm:pl-5 border-l-2 border-ums-blue/20 ml-1 sm:ml-2 mt-4 animate-fadeIn">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <InputField
                        id="jurusan_s2s3"
                        label="Jurusan / Program Studi S2/S3"
                        value={form.jurusan_s2s3}
                        onChange={(v) => setField("jurusan_s2s3", v)}
                        placeholder="Contoh: Pendidikan Teknik Informatika"
                        error={errors.jurusan_s2s3}
                        required
                      />
                      <InputField
                        id="universitas_s2s3"
                        label="Universitas Tempat S2/S3"
                        value={form.universitas_s2s3}
                        onChange={(v) => setField("universitas_s2s3", v)}
                        placeholder="Contoh: Universitas Negeri Yogyakarta"
                        error={errors.universitas_s2s3}
                        required
                      />
                    </div>
                  </div>
                )}
              </FormSection>

              {/* Group 3: Program PPG */}
              <FormSection
                title="C. Program PPG"
                description="Apakah Anda mengikuti program Pendidikan Profesi Guru?"
              >
                <RadioGroup
                  id="lanjut_ppg"
                  label="Apakah mengikuti program PPG?"
                  value={form.lanjut_ppg}
                  onChange={(v) => {
                    setField("lanjut_ppg", v);
                    if (!v) {
                      setField("tahun_ppg", "");
                      setField("universitas_ppg", "");
                    }
                  }}
                  error={errors.lanjut_ppg}
                  required
                />

                {form.lanjut_ppg === true && (
                  <div className="space-y-4 pl-4 sm:pl-5 border-l-2 border-ums-blue/20 ml-1 sm:ml-2 mt-4 animate-fadeIn">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <SelectField
                        id="tahun_ppg"
                        label="Tahun Mengikuti PPG"
                        value={form.tahun_ppg}
                        onChange={(v) => setField("tahun_ppg", v)}
                        options={TAHUN_OPTIONS}
                        placeholder="Pilih tahun PPG"
                        error={errors.tahun_ppg}
                        required
                      />
                      <InputField
                        id="universitas_ppg"
                        label="Universitas Penyelenggara PPG"
                        value={form.universitas_ppg}
                        onChange={(v) => setField("universitas_ppg", v)}
                        placeholder="Contoh: Universitas Muhammadiyah Surakarta"
                        error={errors.universitas_ppg}
                        required
                      />
                    </div>
                  </div>
                )}
              </FormSection>

              {/* Group 4: Pesan & Saran */}
              <FormSection
                title="D. Pesan & Saran"
                description="Berikan pesan atau saran untuk Program Studi PTI UMS."
              >
                <TextArea
                  id="pesan_saran"
                  label="Pesan/Saran untuk Prodi PTI UMS"
                  value={form.pesan_saran}
                  onChange={(v) => setField("pesan_saran", v)}
                  placeholder="Tuliskan pesan, masukan, atau saran Anda untuk pengembangan Prodi PTI UMS..."
                  hint="Opsional"
                  rows={4}
                />
              </FormSection>

              {/* Submit button */}
              <div className="flex flex-col sm:flex-row gap-3 pt-4">
                <button
                  type="submit"
                  disabled={submitting}
                  className="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-gradient-to-r from-[#2e7d32] to-[#4caf50] hover:from-[#1b5e20] hover:to-[#388e3c] text-white text-sm font-semibold rounded-xl
                           transition-all duration-300 hover:scale-[1.02] hover:shadow-lg hover:shadow-emerald-500/10 active:scale-[0.98] disabled:opacity-60 disabled:cursor-not-allowed disabled:transform-none disabled:shadow-none cursor-pointer"
                >
                  {submitting ? (
                    <>
                      <LoadingSpinner size="sm" />
                      <span>
                        {mode === "create"
                          ? "Mengirim..."
                          : "Menyimpan..."}
                      </span>
                    </>
                  ) : mode === "create" ? (
                    "Kirim Survey"
                  ) : (
                    "Simpan Perubahan"
                  )}
                </button>

                <Link
                  href="/"
                  className="inline-flex items-center justify-center px-6 py-3.5 border border-neutral-200 bg-white/80 text-neutral-600 text-sm font-semibold rounded-xl hover:bg-neutral-50 transition-all duration-300 hover:scale-[1.01] active:scale-[0.99]"
                >
                  Batal
                </Link>
              </div>
            </form>
          </div>

        </div>
      </div>
    </main>
  );
}

export default function SurveyPage() {
  return (
    <>
      <Navbar />
      <Suspense fallback={
        <main className="flex-1 flex items-center justify-center py-20">
          <div className="text-center">
            <LoadingSpinner size="lg" />
            <p className="mt-4 text-sm text-neutral-500">
              Memuat halaman...
            </p>
          </div>
        </main>
      }>
        <SurveyFormContent />
      </Suspense>
      <Footer />
    </>
  );
}
