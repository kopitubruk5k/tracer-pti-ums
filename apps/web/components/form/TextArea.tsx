interface TextAreaProps {
  id: string;
  label: string;
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  error?: string;
  required?: boolean;
  rows?: number;
  hint?: string;
}

export default function TextArea({
  id,
  label,
  value,
  onChange,
  placeholder,
  error,
  required = false,
  rows = 4,
  hint,
}: TextAreaProps) {
  return (
    <div className="flex flex-col">
      <label htmlFor={id} className="block text-xs sm:text-sm font-semibold text-neutral-700 mb-1.5 select-none">
        {label}
        {required && <span className="text-danger-500 ml-1 font-bold">*</span>}
      </label>
      
      <div className="relative">
        <textarea
          id={id}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          placeholder={placeholder}
          rows={rows}
          className={`w-full px-4 py-3 text-sm rounded-xl border transition-all duration-200 outline-none resize-y
            ${error
              ? "border-danger-500 bg-danger-50/20 text-neutral-800 placeholder:text-danger-300/80 focus:border-danger-500 focus:ring-4 focus:ring-danger-500/10"
              : "border-neutral-200 bg-white text-neutral-800 placeholder:text-neutral-400/80 hover:border-neutral-300 focus:border-ums-blue focus:ring-4 focus:ring-ums-blue/10"
            }
            shadow-xs focus:outline-none`}
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

