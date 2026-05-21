"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Image from "next/image";
import { GoogleOAuthProvider, GoogleLogin, type CredentialResponse } from "@react-oauth/google";
import { EyeIcon, EyeSlashIcon } from "@heroicons/react/24/outline";
import { api } from "@/lib/api";
import { adminApi, ApiError } from "@/lib/admin-api";
import type { ApiSuccessResponse } from "@/lib/types";

const GOOGLE_CLIENT_ID = process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID || "";

interface GoogleLoginResponse {
  verified: boolean;
  alumni: {
    id: number;
    nama_lengkap: string;
    nim: string;
    tahun_lulus: number;
  } | null;
  survey_exists?: boolean;
  google_email?: string;
  message?: string;
}

function LoginContent() {
  const router = useRouter();
  
  // State for manual login (Admin)
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [manualLoading, setManualLoading] = useState(false);
  const [manualError, setManualError] = useState<string | null>(null);

  // State for Google login (Alumni)
  const [googleLoading, setGoogleLoading] = useState(false);
  const [googleError, setGoogleError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  
  // State for Linking/Registering
  const [needsRegistration, setNeedsRegistration] = useState(false);
  const [googleCredential, setGoogleCredential] = useState<string>("");
  const [linkNim, setLinkNim] = useState("");

  const [linkLoading, setLinkLoading] = useState(false);

  // --- MANUAL LOGIN HANDLER (ADMIN) ---
  const handleManualSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setManualError(null);
    setGoogleError(null);
    setManualLoading(true);
    
    // Trim suffixes like @ums.ac.id or @student.ums.ac.id if present
    const cleanedUsername = username.trim().replace(/@(ums\.ac\.id|student\.ums\.ac\.id)$/i, "");
    
    try {
      await adminApi.login(cleanedUsername, password);
      router.push("/admin/dashboard");
    } catch (err) {
      if (err instanceof ApiError) {
        setManualError(err.message || "Username atau password salah");
      } else {
        setManualError("Terjadi kesalahan jaringan. Silakan coba lagi.");
      }
    } finally {
      setManualLoading(false);
    }
  };

  // --- GOOGLE LOGIN HANDLER (ALUMNI) ---
  const handleGoogleSuccess = async (credentialResponse: CredentialResponse) => {
    if (!credentialResponse.credential) {
      setGoogleError("Gagal mendapatkan credential dari Google");
      return;
    }

    setGoogleLoading(true);
    setGoogleError(null);
    setManualError(null);
    setSuccess(null);

    try {
      const res = await api.post<ApiSuccessResponse<GoogleLoginResponse>>(
        "/auth/google",
        { credential: credentialResponse.credential }
      );

      // We added needs_registration flag to the response for unknown emails
      const data = res.data as GoogleLoginResponse & { needs_registration?: boolean, google_email?: string };

      if (data.needs_registration) {
        setGoogleCredential(credentialResponse.credential);
        setNeedsRegistration(true);
        setGoogleError(data.message || "Email belum terdaftar. Silakan verifikasi data Anda.");
        return;
      }

      if (data.verified && data.alumni) {
        setSuccess(`Selamat datang, ${data.alumni.nama_lengkap}!`);
        setTimeout(() => {
          router.push(`/survey?id=${data.alumni!.id}`);
        }, 1000);
      } else {
        setGoogleError(data.message || "Akun Google tidak valid.");
      }
    } catch {
      setGoogleError("Gagal memverifikasi akun Google. Silakan coba lagi.");
    } finally {
      setGoogleLoading(false);
    }
  };

  const handleGoogleError = () => {
    setGoogleError("Login Google gagal. Silakan coba lagi.");
  };

  // --- GOOGLE LINKING HANDLER (ALUMNI) ---
  const handleLinkSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setGoogleError(null);
    setLinkLoading(true);

    try {
      const res = await api.post<ApiSuccessResponse<GoogleLoginResponse>>(
        "/auth/google/link",
        { 
          credential: googleCredential,
          nim: linkNim,
        }
      );

      if (res.data.verified && res.data.alumni) {
        setNeedsRegistration(false);
        setSuccess(`Berhasil! Selamat datang, ${res.data.alumni.nama_lengkap}!`);
        setTimeout(() => {
          router.push(`/survey?id=${res.data.alumni!.id}`);
        }, 1000);
      } else {
        setGoogleError(res.data.message || "Gagal memverifikasi data.");
      }
    } catch (err: any) {
      setGoogleError(err?.response?.data?.message || "NIM tidak tepat. Silakan coba lagi.");
    } finally {
      setLinkLoading(false);
    }
  };

  return (
    <div 
      className="min-h-screen flex items-center justify-center p-4 relative bg-[#0a192f] overflow-hidden"
      style={{
        backgroundImage: 'radial-gradient(circle at center, #112240 0%, #0a192f 100%)'
      }}
    >
      {/* Decorative background elements to simulate the blurred building look */}
      <div className="absolute inset-0 z-0 opacity-20">
        <div className="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-500 rounded-full mix-blend-screen filter blur-[100px] animate-pulse"></div>
        <div className="absolute bottom-1/4 right-1/4 w-96 h-96 bg-cyan-500 rounded-full mix-blend-screen filter blur-[100px] animate-pulse" style={{ animationDelay: '2s' }}></div>
      </div>

      {/* Login Card */}
      <div className="w-full max-w-[440px] bg-white/[0.07] backdrop-blur-xl border border-white/[0.12] rounded-2xl shadow-2xl relative z-10 px-8 py-10">
        
        {/* UMS Logo */}
        <div className="flex justify-center mb-6">
          <img 
            src="/ums-logo.png" 
            alt="Universitas Muhammadiyah Surakarta" 
            className="h-20 w-auto object-contain drop-shadow-[0_2px_10px_rgba(255,255,255,0.15)]"
          />
        </div>

        {needsRegistration ? (
          <>
            <h2 className="text-[20px] text-center font-bold text-white mb-2">
              Verifikasi Data Alumni
            </h2>
            <p className="text-sm text-blue-200/60 text-center mb-6">
              Hubungkan akun Google ini dengan memasukkan data kemahasiswaan Anda.
            </p>

            {/* Error Alerts */}
            {googleError && (
              <div className="mb-6 p-3.5 bg-red-500/15 border border-red-400/30 rounded-xl text-red-300 text-sm flex items-start gap-2.5">
                <svg className="w-5 h-5 shrink-0 mt-0.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <span>{googleError}</span>
              </div>
            )}

            <form onSubmit={handleLinkSubmit} className="space-y-4 mb-6">
              <div>
                <label className="block text-sm font-medium text-blue-100/80 mb-1.5">NIM</label>
                <input
                  type="text"
                  value={linkNim}
                  onChange={(e) => setLinkNim(e.target.value)}
                  placeholder="Contoh: A710180052"
                  className="w-full px-4 py-3 bg-white/[0.06] border border-white/[0.12] rounded-xl text-white placeholder:text-blue-200/30 focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400/40 transition text-sm uppercase"
                  required
                  suppressHydrationWarning
                />
              </div>

              <button
                type="submit"
                disabled={linkLoading || !linkNim}
                className="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white py-3 rounded-xl text-sm font-semibold transition-all duration-200 mt-6 disabled:opacity-50 disabled:cursor-not-allowed flex justify-center items-center gap-2 shadow-lg shadow-blue-500/25"
                suppressHydrationWarning
              >
                {linkLoading ? (
                  <svg className="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                ) : (
                  "Hubungkan & Lanjutkan"
                )}
              </button>

              <button
                type="button"
                onClick={() => {
                  setNeedsRegistration(false);
                  setGoogleError(null);
                }}
                className="w-full text-center text-sm text-blue-200/50 hover:text-white transition mt-4"
              >
                Kembali ke Login
              </button>
            </form>
          </>
        ) : (
          <>
            {/* Title */}
            <h2 className="text-[22px] text-center font-bold text-white tracking-tight mb-1">
              Sign in to your account
            </h2>
            <p className="text-sm text-blue-200/60 text-center mb-8">
              Tracer Study PPG dan Studi Lanjut — PTI FKIP UMS
            </p>

            {/* Success Alert */}
            {success && (
              <div className="mb-6 p-3.5 bg-green-500/15 border border-green-400/30 rounded-xl text-green-300 text-sm flex items-start gap-2.5">
                <svg className="w-5 h-5 shrink-0 mt-0.5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{success}</span>
              </div>
            )}

            {/* Error Alerts */}
            {(manualError || googleError) && (
              <div className="mb-6 p-3.5 bg-red-500/15 border border-red-400/30 rounded-xl text-red-300 text-sm flex items-start gap-2.5">
                <svg className="w-5 h-5 shrink-0 mt-0.5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <span>{manualError || googleError}</span>
              </div>
            )}

            {/* Manual Login Form */}
            <form onSubmit={handleManualSubmit} className="space-y-4 mb-6">
              <div>
                <input
                  type="text"
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  placeholder="UniID/NIM (tanpa suffix email)"
                  className="w-full px-4 py-3 bg-white/[0.06] border border-white/[0.12] rounded-xl text-white placeholder:text-blue-200/30 focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400/40 transition text-sm"
                  required
                  suppressHydrationWarning
                />
              </div>
              
              <div className="relative">
                <input
                  type={showPassword ? "text" : "password"}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="Password"
                  className="w-full pl-4 pr-10 py-3 bg-white/[0.06] border border-white/[0.12] rounded-xl text-white placeholder:text-blue-200/30 focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400/40 transition text-sm"
                  required
                  suppressHydrationWarning
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3.5 top-1/2 -translate-y-1/2 text-blue-300/50 hover:text-white transition"
                  tabIndex={-1}
                >
                  {showPassword ? (
                    <EyeSlashIcon className="w-5 h-5" />
                  ) : (
                    <EyeIcon className="w-5 h-5" />
                  )}
                </button>
              </div>

              <button
                type="submit"
                disabled={manualLoading || !username || !password}
                className="w-full py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white text-sm font-semibold rounded-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 flex items-center justify-center gap-2"
                suppressHydrationWarning
              >
                {manualLoading ? (
                  <svg className="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                ) : (
                  "Sign In"
                )}
              </button>
            </form>

            {/* Divider */}
            <div className="flex items-center my-8 text-xs uppercase tracking-wider text-blue-200/40">
              <div className="flex-1 border-t border-white/[0.08]"></div>
              <span className="px-3 font-medium">Or sign in with</span>
              <div className="flex-1 border-t border-white/[0.08]"></div>
            </div>

            {/* Google Sign-in */}
            <div className="flex justify-center">
              {googleLoading ? (
                <div className="flex items-center justify-center py-2">
                  <svg className="w-6 h-6 animate-spin text-blue-400" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  <span className="ml-3 text-sm text-blue-200">Memverifikasi akun Google...</span>
                </div>
              ) : GOOGLE_CLIENT_ID ? (
                <GoogleLogin
                  onSuccess={handleGoogleSuccess}
                  onError={handleGoogleError}
                  text="signin_with"
                  shape="rectangular"
                  size="large"
                  width="376"
                  theme="filled_blue"
                />
              ) : (
                <div className="bg-amber-500/10 border border-amber-500/20 rounded-xl px-4 py-3 text-sm text-amber-300 w-full text-center">
                  Google Client ID belum dikonfigurasi.
                </div>
              )}
            </div>
          </>
        )}
      </div>
    </div>
  );
}

export default function LoginPage() {
  if (!GOOGLE_CLIENT_ID) {
    return <LoginContent />;
  }

  return (
    <GoogleOAuthProvider clientId={GOOGLE_CLIENT_ID}>
      <LoginContent />
    </GoogleOAuthProvider>
  );
}
