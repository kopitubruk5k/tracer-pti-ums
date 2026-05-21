interface Option {
  value: string;
  label: string;
}

interface SelectFieldProps {
  id: string;
  label: string;
  value: string;
  onChange: (value: string) => void;
  options: Option[];
  placeholder?: string;
  error?: string;
  required?: boolean;
  disabled?: boolean;
}

export default function SelectField({
  id,
  label,
  value,
  onChange,
  options,
  placeholder = "Pilih...",
  error,
  required = false,
  disabled = false,
}: SelectFieldProps) {
  return (
    <div className="flex flex-col">
      <label htmlFor={id} className="block text-xs sm:text-sm font-semibold text-neutral-700 mb-1.5 select-none">
        {label}
        {required && <span className="text-danger-500 ml-1 font-bold">*</span>}
      </label>
      
      <div className="relative">
        <select
          id={id}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          disabled={disabled}
          className={`w-full px-4 py-3 text-sm rounded-xl border transition-all duration-200 outline-none appearance-none
            bg-[url('data:image/svg+xml;charset=UTF-8,%3csvg%20xmlns%3d%22http%3a%2f%2fwww.w3.org%2f2000%2fsvg%22%20viewBox%3d%220%200%2020%2020%22%20fill%3d%22%236b7280%22%3e%3cpath%20fill-rule%3d%22evenodd%22%20d%3d%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%20clip-rule%3d%22evenodd%22%2f%3e%3c%2fsvg%3e')]
            bg-[length:1.25rem] bg-[right_1rem_center] bg-no-repeat pr-10
            ${error
              ? "border-danger-500 bg-danger-50/20 text-neutral-800 focus:border-danger-500 focus:ring-4 focus:ring-danger-500/10"
              : "border-neutral-200 bg-white text-neutral-800 hover:border-neutral-300 focus:border-ums-blue focus:ring-4 focus:ring-ums-blue/10"
            }
            ${disabled ? "bg-neutral-50 text-neutral-400 border-neutral-200/60 cursor-not-allowed shadow-none" : "shadow-xs"}
            focus:outline-none`}
        >
          <option value="" disabled>
            {placeholder}
          </option>
          {options.map((opt) => (
            <option key={opt.value} value={opt.value}>
              {opt.label}
            </option>
          ))}
        </select>
      </div>

      {error && (
        <p className="mt-1.5 text-xs text-danger-500 font-medium pl-1 animate-fadeIn">{error}</p>
      )}
    </div>
  );
}

