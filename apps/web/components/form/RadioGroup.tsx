interface RadioGroupProps {
  id: string;
  label: string;
  value: boolean | null;
  onChange: (value: boolean) => void;
  error?: string;
  required?: boolean;
}

export default function RadioGroup({
  id,
  label,
  value,
  onChange,
  error,
  required = false,
}: RadioGroupProps) {
  return (
    <div className="flex flex-col">
      <span className="block text-xs sm:text-sm font-semibold text-neutral-700 mb-2 select-none">
        {label}
        {required && <span className="text-danger-500 ml-1 font-bold">*</span>}
      </span>
      
      <div className="grid grid-cols-2 gap-3.5 w-full sm:max-w-md">
        {/* Option: YA */}
        <label
          htmlFor={`${id}-ya`}
          className={`relative flex items-center justify-between cursor-pointer px-4 py-3.5 rounded-xl border transition-all duration-300 select-none
            ${value === true
              ? "border-ums-blue bg-gradient-to-br from-white to-[#155d9b]/5 text-[#155d9b] font-semibold scale-[1.01] shadow-sm shadow-[#155d9b]/5 ring-2 ring-ums-blue/10"
              : "border-neutral-200 bg-white text-neutral-600 hover:border-neutral-300 hover:bg-neutral-50/50 hover:scale-[1.005]"
            }`}
        >
          <input
            type="radio"
            id={`${id}-ya`}
            name={id}
            checked={value === true}
            onChange={() => onChange(true)}
            className="sr-only"
          />
          <span className="text-sm">Ya</span>
          
          <span className={`w-5 h-5 rounded-full border transition-all duration-300 flex items-center justify-center shrink-0
            ${value === true 
              ? "border-ums-blue bg-ums-blue text-white" 
              : "border-neutral-300 bg-white"
            }`}
          >
            {value === true ? (
              <svg className="w-3 h-3 stroke-current stroke-[3]" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 6L4.5 8L9.5 3" strokeLinecap="round" strokeLinejoin="round"/>
              </svg>
            ) : (
              <span className="w-1.5 h-1.5 rounded-full bg-transparent" />
            )}
          </span>
        </label>

        {/* Option: TIDAK */}
        <label
          htmlFor={`${id}-tidak`}
          className={`relative flex items-center justify-between cursor-pointer px-4 py-3.5 rounded-xl border transition-all duration-300 select-none
            ${value === false
              ? "border-ums-blue bg-gradient-to-br from-white to-[#155d9b]/5 text-[#155d9b] font-semibold scale-[1.01] shadow-sm shadow-[#155d9b]/5 ring-2 ring-ums-blue/10"
              : "border-neutral-200 bg-white text-neutral-600 hover:border-neutral-300 hover:bg-neutral-50/50 hover:scale-[1.005]"
            }`}
        >
          <input
            type="radio"
            id={`${id}-tidak`}
            name={id}
            checked={value === false}
            onChange={() => onChange(false)}
            className="sr-only"
          />
          <span className="text-sm">Tidak</span>
          
          <span className={`w-5 h-5 rounded-full border transition-all duration-300 flex items-center justify-center shrink-0
            ${value === false 
              ? "border-ums-blue bg-ums-blue text-white" 
              : "border-neutral-300 bg-white"
            }`}
          >
            {value === false ? (
              <svg className="w-3 h-3 stroke-current stroke-[3]" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 6L4.5 8L9.5 3" strokeLinecap="round" strokeLinejoin="round"/>
              </svg>
            ) : (
              <span className="w-1.5 h-1.5 rounded-full bg-transparent" />
            )}
          </span>
        </label>
      </div>

      {error && (
        <p className="mt-2 text-xs text-danger-500 font-medium pl-1 animate-fadeIn">{error}</p>
      )}
    </div>
  );
}

