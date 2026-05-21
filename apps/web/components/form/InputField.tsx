interface InputFieldProps {
  id: string;
  label: string;
  type?: "text" | "number" | "tel";
  value: string | number;
  onChange: (value: string) => void;
  placeholder?: string;
  error?: string;
  required?: boolean;
  disabled?: boolean;
  hint?: string;
}

export default function InputField({
  id,
  label,
  type = "text",
  value,
  onChange,
  placeholder,
  error,
  required = false,
  disabled = false,
  hint,
}: InputFieldProps) {
  return (
    <div className="group flex flex-col">
      <label htmlFor={id} className="block text-xs sm:text-sm font-semibold text-neutral-700 mb-1.5 select-none">
        {label}
        {required && <span className="text-danger-500 ml-1 font-bold">*</span>}
      </label>
      
      <div className="relative">
        <input
          id={id}
          type={type}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          placeholder={placeholder}
          disabled={disabled}
          className={`w-full px-4 py-3 text-sm rounded-xl border transition-all duration-200 outline-none
            ${error
              ? "border-danger-500 bg-danger-50/20 text-neutral-800 placeholder:text-danger-300/80 focus:border-danger-500 focus:ring-4 focus:ring-danger-500/10"
              : "border-neutral-200 bg-white text-neutral-800 placeholder:text-neutral-400/80 hover:border-neutral-300 focus:border-ums-blue focus:ring-4 focus:ring-ums-blue/10"
            }
            ${disabled ? "bg-neutral-50 text-neutral-400 border-neutral-200/60 cursor-not-allowed shadow-none" : "shadow-xs"}
            focus:outline-none`}
        />
      </div>

      {hint && !error && (
        <p className="mt-1.5 text-xs text-neutral-400/90 leading-relaxed pl-1">{hint}</p>
      )}
      {error && (
        <p className="mt-1.5 text-xs text-danger-500 font-medium pl-1 animate-fadeIn">{error}</p>
      )}
    </div>
  );
}

