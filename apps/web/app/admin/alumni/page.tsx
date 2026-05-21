"use client";

import { useState, useEffect, useCallback } from "react";
import Link from "next/link";
import { adminApi, ApiError } from "@/lib/admin-api";
import type { Alumni, PaginationMeta } from "@/lib/types";

/* ─── Edit Modal ─── */
interface EditFormData {
  nama_lengkap: string;
  nim: string;
  tahun_lulus: string;
}

function EditModal({
  alumni,
  onClose,
  onSaved,
}: {
  alumni: Alumni;
  onClose: () => void;
  onSaved: () => void;
}) {
  const [form, setForm] = useState<EditFormData>({
    nama_lengkap: alumni.nama_lengkap,
    nim: alumni.nim,
    tahun_lulus: alumni.tahun_lulus ? String(alumni.tahun_lulus) : "",
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleChange = (field: keyof EditFormData, value: string) => {
    setForm((prev) => ({ ...prev, [field]: value }));
    setError(null);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.nama_lengkap.trim()) {
      setError("Nama lengkap wajib diisi");
      return;
    }
    if (!form.nim.trim()) {
      setError("NIM wajib diisi");
      return;
    }

    setSaving(true);
    setError(null);
    try {
      await adminApi.updateAlumni(alumni.id, {
        nama_lengkap: form.nama_lengkap.trim(),
        nim: form.nim.trim(),
        tahun_lulus: form.tahun_lulus ? parseInt(form.tahun_lulus) : null,
      });
      onSaved();
    } catch (err) {
      setError(
        err instanceof ApiError ? err.message : "Gagal menyimpan perubahan"
      );
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
      <div className="bg-white rounded-2xl shadow-xl w-full max-w-md animate-in fade-in zoom-in-95 duration-200">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-neutral-100">
          <h2 className="text-lg font-bold text-neutral-800">Edit Alumni</h2>
          <button
            onClick={onClose}
            className="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-neutral-100 transition text-neutral-400 hover:text-neutral-600"
          >
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="px-6 py-5 space-y-4">
          {/* Nama Lengkap */}
          <div>
            <label className="block text-sm font-medium text-neutral-700 mb-1.5">
              Nama Lengkap
            </label>
            <input
              type="text"
              value={form.nama_lengkap}
              onChange={(e) => handleChange("nama_lengkap", e.target.value)}
              className="w-full px-3.5 py-2.5 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400"
              placeholder="Nama lengkap alumni"
            />
          </div>

          {/* NIM */}
          <div>
            <label className="block text-sm font-medium text-neutral-700 mb-1.5">
              NIM
            </label>
            <input
              type="text"
              value={form.nim}
              onChange={(e) => handleChange("nim", e.target.value)}
              className="w-full px-3.5 py-2.5 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400"
              placeholder="Nomor Induk Mahasiswa"
            />
          </div>

          {/* Tahun Lulus */}
          <div>
            <label className="block text-sm font-medium text-neutral-700 mb-1.5">
              Tahun Lulus
            </label>
            <input
              type="number"
              value={form.tahun_lulus}
              onChange={(e) => handleChange("tahun_lulus", e.target.value)}
              className="w-full px-3.5 py-2.5 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400"
              placeholder="Contoh: 2020"
              min={2000}
              max={new Date().getFullYear()}
            />
          </div>


          {/* Error */}
          {error && (
            <div className="px-3.5 py-2.5 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600">
              {error}
            </div>
          )}

          {/* Actions */}
          <div className="flex justify-end gap-2 pt-2">
            <button
              type="button"
              onClick={onClose}
              disabled={saving}
              className="px-4 py-2.5 text-sm font-medium text-neutral-600 bg-neutral-100 rounded-xl hover:bg-neutral-200 transition disabled:opacity-50"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={saving}
              className="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition disabled:opacity-50 flex items-center gap-2"
            >
              {saving && (
                <svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
              )}
              {saving ? "Menyimpan..." : "Simpan"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

/* ─── Main Page ─── */
export default function AdminAlumniPage() {
  const [alumni, setAlumni] = useState<Alumni[]>([]);
  const [meta, setMeta] = useState<PaginationMeta | null>(null);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [searchInput, setSearchInput] = useState("");
  const [tahunFilter, setTahunFilter] = useState("");
  const [duplicateType, setDuplicateType] = useState("");
  const [sortBy, setSortBy] = useState("created_at");
  const [sortOrder, setSortOrder] = useState("desc");
  const [page, setPage] = useState(1);
  const [limit, setLimit] = useState(25);
  const [deleting, setDeleting] = useState<number | null>(null);
  const [exporting, setExporting] = useState(false);
  const [editingAlumni, setEditingAlumni] = useState<Alumni | null>(null);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [batchDeleting, setBatchDeleting] = useState(false);

  const fetchAlumni = useCallback(async () => {
    setLoading(true);
    try {
      const res = await adminApi.listAlumni({
        page,
        limit,
        search: search || undefined,
        tahun_lulus: tahunFilter ? parseInt(tahunFilter) : undefined,
        sort_by: sortBy,
        sort_order: sortOrder,
        duplicate_type: duplicateType || undefined,
      });
      setAlumni(res.data.items);
      setMeta(res.data.meta);
    } catch {
      // ignore
    } finally {
      setLoading(false);
    }
  }, [page, limit, search, tahunFilter, sortBy, sortOrder, duplicateType]);

  useEffect(() => {
    fetchAlumni();
  }, [fetchAlumni]);

  useEffect(() => {
    setSelectedIds([]);
  }, [page, limit, search, tahunFilter, sortBy, sortOrder, duplicateType]);

  const handleSort = (column: string) => {
    if (sortBy === column) {
      setSortOrder(sortOrder === "asc" ? "desc" : "asc");
    } else {
      setSortBy(column);
      setSortOrder("asc");
    }
    setPage(1);
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setPage(1);
    setSearch(searchInput);
  };

  const handleBatchDelete = async () => {
    if (selectedIds.length === 0) return;
    if (
      !confirm(
        `Hapus ${selectedIds.length} alumni terpilih? Semua data survey terkait juga akan dihapus.`
      )
    )
      return;

    setBatchDeleting(true);
    try {
      await adminApi.batchDeleteAlumni(selectedIds);
      setSelectedIds([]);
      fetchAlumni();
    } catch (err) {
      alert(err instanceof ApiError ? err.message : "Gagal menghapus alumni terpilih");
    } finally {
      setBatchDeleting(false);
    }
  };

  const handleDelete = async (id: number, nama: string) => {
    if (!confirm(`Hapus alumni "${nama}"? Data survey terkait juga akan dihapus.`)) return;
    setDeleting(id);
    try {
      await adminApi.deleteAlumni(id);
      fetchAlumni();
    } catch (err) {
      alert(err instanceof ApiError ? err.message : "Gagal menghapus alumni");
    } finally {
      setDeleting(null);
    }
  };

  const handleExport = async () => {
    setExporting(true);
    try {
      const blob = await adminApi.exportAlumni({
        tahun_lulus: tahunFilter ? parseInt(tahunFilter) : undefined,
      });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `alumni-export-${new Date().toISOString().slice(0, 10)}.xlsx`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      a.remove();
    } catch {
      alert("Gagal mengunduh file export");
    } finally {
      setExporting(false);
    }
  };

  const handleEditSaved = () => {
    setEditingAlumni(null);
    fetchAlumni();
  };

  const currentYear = new Date().getFullYear();
  const yearOptions = Array.from({ length: currentYear - 2000 + 1 }, (_, i) => currentYear - i);

  return (
    <div className="space-y-6">
      {/* Edit Modal */}
      {editingAlumni && (
        <EditModal
          alumni={editingAlumni}
          onClose={() => setEditingAlumni(null)}
          onSaved={handleEditSaved}
        />
      )}

      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-xl sm:text-2xl font-bold text-neutral-800">Data Alumni</h1>
          <p className="text-sm text-neutral-500 mt-1">
            Kelola database alumni PTI UMS
            {meta && <span className="text-neutral-400"> — {meta.total_items} alumni</span>}
          </p>
        </div>
        <div className="flex gap-2">
          <button
            onClick={handleExport}
            disabled={exporting}
            className="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 rounded-xl text-sm font-medium text-white hover:bg-green-700 transition shadow-sm disabled:opacity-50"
          >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            {exporting ? "Mengunduh..." : "Export Excel"}
          </button>
          <Link
            href="/admin/alumni/import"
            className="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-neutral-200 rounded-xl text-sm font-medium text-neutral-700 hover:bg-neutral-50 transition shadow-sm"
          >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
            </svg>
            Import Excel
          </Link>
          <Link
            href="/admin/alumni/tambah"
            className="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 rounded-xl text-sm font-medium text-white hover:bg-blue-700 transition shadow-sm"
          >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Manual
          </Link>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-2xl border border-neutral-100 p-4 shadow-sm">
        <form onSubmit={handleSearch} className="flex flex-col sm:flex-row gap-3">
          <div className="flex-1 relative">
            <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <input
              type="text"
              value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
              placeholder="Cari nama atau NIM..."
              className="w-full pl-10 pr-4 py-2.5 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400"
            />
          </div>
          <select
            value={tahunFilter}
            onChange={(e) => { setTahunFilter(e.target.value); setPage(1); }}
            className="px-4 py-2.5 border border-neutral-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30"
          >
            <option value="">Semua Tahun</option>
            {yearOptions.map((y) => (
              <option key={y} value={y}>{y}</option>
            ))}
          </select>
          <select
            value={duplicateType}
            onChange={(e) => { setDuplicateType(e.target.value); setPage(1); }}
            className="px-4 py-2.5 border border-neutral-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30"
          >
            <option value="">Semua Data</option>
            <option value="nama_lengkap">Duplikat Nama</option>
            <option value="nim">Duplikat NIM</option>
          </select>
          <select
            value={limit === 999999 ? "all" : String(limit)}
            onChange={(e) => {
              const val = e.target.value;
              setLimit(val === "all" ? 999999 : parseInt(val, 10));
              setPage(1);
            }}
            className="px-4 py-2.5 border border-neutral-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/30"
          >
            <option value="25">25 data</option>
            <option value="100">100 data</option>
            <option value="250">250 data</option>
            <option value="all">Semua data</option>
          </select>
          <button
            type="submit"
            className="px-5 py-2.5 bg-neutral-800 text-white text-sm font-medium rounded-xl hover:bg-neutral-900 transition"
          >
            Cari
          </button>
        </form>
      </div>

      {duplicateType !== "" && meta && (
        <div className="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-xl text-sm flex items-start gap-3">
          <svg className="w-5 h-5 shrink-0 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <p>
            <strong>Mode Deteksi Duplikat Aktif:</strong> Menampilkan {meta.total_items} data alumni yang memiliki {duplicateType === "nim" ? "NIM" : "Nama"} yang sama. Silakan periksa dan hapus salah satu entri ganda menggunakan fitur <em>Hapus Terpilih</em>.
          </p>
        </div>
      )}

      {/* Table */}
      <div className="bg-white rounded-2xl border border-neutral-100 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-neutral-50 border-b border-neutral-100">
                <th className="px-5 py-3 w-12 text-center">
                  <input
                    type="checkbox"
                    checked={alumni.length > 0 && selectedIds.length === alumni.length}
                    onChange={(e) => {
                      if (e.target.checked) {
                        setSelectedIds(alumni.map((a) => a.id));
                      } else {
                        setSelectedIds([]);
                      }
                    }}
                    className="w-4 h-4 rounded border-neutral-300 text-blue-600 focus:ring-blue-500/30 cursor-pointer"
                  />
                </th>
                <th className="text-left px-5 py-3 font-semibold text-neutral-600">No</th>
                <th 
                  className="text-left px-5 py-3 font-semibold text-neutral-600 cursor-pointer hover:bg-neutral-100 transition group"
                  onClick={() => handleSort("nama_lengkap")}
                >
                  <div className="flex items-center gap-1">
                    Nama Lengkap
                    <span className={`text-neutral-400 ${sortBy === "nama_lengkap" ? "opacity-100 text-blue-600" : "opacity-0 group-hover:opacity-50"}`}>
                      {sortBy === "nama_lengkap" && sortOrder === "desc" ? "↓" : "↑"}
                    </span>
                  </div>
                </th>
                <th 
                  className="text-left px-5 py-3 font-semibold text-neutral-600 cursor-pointer hover:bg-neutral-100 transition group"
                  onClick={() => handleSort("nim")}
                >
                  <div className="flex items-center gap-1">
                    NIM
                    <span className={`text-neutral-400 ${sortBy === "nim" ? "opacity-100 text-blue-600" : "opacity-0 group-hover:opacity-50"}`}>
                      {sortBy === "nim" && sortOrder === "desc" ? "↓" : "↑"}
                    </span>
                  </div>
                </th>
                <th 
                  className="text-left px-5 py-3 font-semibold text-neutral-600 cursor-pointer hover:bg-neutral-100 transition group"
                  onClick={() => handleSort("tahun_lulus")}
                >
                  <div className="flex items-center gap-1">
                    Tahun Lulus
                    <span className={`text-neutral-400 ${sortBy === "tahun_lulus" ? "opacity-100 text-blue-600" : "opacity-0 group-hover:opacity-50"}`}>
                      {sortBy === "tahun_lulus" && sortOrder === "desc" ? "↓" : "↑"}
                    </span>
                  </div>
                </th>
                <th className="text-right px-5 py-3 font-semibold text-neutral-600">Aksi</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={6} className="px-5 py-12 text-center">
                    <svg className="w-6 h-6 animate-spin text-blue-500 mx-auto" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                  </td>
                </tr>
              ) : alumni.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-5 py-12 text-center text-neutral-400">
                    Tidak ada data alumni ditemukan
                  </td>
                </tr>
              ) : (
                alumni.map((a, idx) => (
                  <tr key={a.id} className={`border-b border-neutral-50 hover:bg-blue-50/30 transition-colors ${selectedIds.includes(a.id) ? "bg-blue-50/20" : ""}`}>
                    <td className="px-5 py-3 text-center">
                      <input
                        type="checkbox"
                        checked={selectedIds.includes(a.id)}
                        onChange={(e) => {
                          if (e.target.checked) {
                            setSelectedIds((prev) => [...prev, a.id]);
                          } else {
                            setSelectedIds((prev) => prev.filter((id) => id !== a.id));
                          }
                        }}
                        className="w-4 h-4 rounded border-neutral-300 text-blue-600 focus:ring-blue-500/30 cursor-pointer"
                      />
                    </td>
                    <td className="px-5 py-3 text-neutral-400">{(page - 1) * limit + idx + 1}</td>
                    <td className="px-5 py-3 font-medium text-neutral-800">{a.nama_lengkap}</td>
                    <td className="px-5 py-3 text-neutral-600 font-mono text-xs">{a.nim}</td>
                    <td className="px-5 py-3 text-neutral-600">{a.tahun_lulus || "-"}</td>
                    <td className="px-5 py-3 text-right">
                      <div className="flex items-center justify-end gap-2">
                        <button
                          onClick={() => setEditingAlumni(a)}
                          className="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition"
                        >
                          <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                          </svg>
                          Edit
                        </button>
                        <button
                          onClick={() => handleDelete(a.id, a.nama_lengkap)}
                          disabled={deleting === a.id}
                          className="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition disabled:opacity-50"
                        >
                          <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                          </svg>
                          {deleting === a.id ? "..." : "Hapus"}
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        {meta && meta.total_pages > 1 && (
          <div className="flex items-center justify-between px-5 py-3 border-t border-neutral-100 bg-neutral-50/50">
            <p className="text-xs text-neutral-400">
              Halaman {meta.current_page} dari {meta.total_pages}
            </p>
            <div className="flex gap-1.5">
              <button
                onClick={() => setPage(page - 1)}
                disabled={!meta.has_prev_page}
                className="px-3 py-1.5 text-xs font-medium rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 disabled:opacity-40 disabled:cursor-not-allowed transition"
              >
                ← Sebelumnya
              </button>
              <button
                onClick={() => setPage(page + 1)}
                disabled={!meta.has_next_page}
                className="px-3 py-1.5 text-xs font-medium rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 disabled:opacity-40 disabled:cursor-not-allowed transition"
              >
                Selanjutnya →
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Floating Action Bar */}
      {selectedIds.length > 0 && (
        <div className="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-[#0e1a2d]/95 backdrop-blur border border-white/10 px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-6 animate-in slide-in-from-bottom-4 duration-300 text-white w-[90%] max-w-xl justify-between">
          <div className="flex items-center gap-3">
            <span className="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse" />
            <p className="text-sm font-medium">
              Terpilih <span className="font-bold text-red-400">{selectedIds.length}</span> data alumni
            </p>
          </div>
          <div className="flex gap-2 shrink-0">
            <button
              onClick={() => setSelectedIds([])}
              className="px-4 py-2 text-xs font-semibold text-neutral-300 hover:text-white bg-white/10 hover:bg-white/20 rounded-xl transition"
            >
              Batal
            </button>
            <button
              onClick={handleBatchDelete}
              disabled={batchDeleting}
              className="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 rounded-xl transition flex items-center gap-1.5 shadow-sm"
            >
              {batchDeleting ? (
                <svg className="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
              ) : (
                <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              )}
              Hapus Terpilih
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
