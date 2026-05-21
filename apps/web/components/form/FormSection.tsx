interface FormSectionProps {
  title: string;
  description?: string;
  children: React.ReactNode;
  icon?: React.ReactNode;
}

export default function FormSection({
  title,
  description,
  children,
  icon,
}: FormSectionProps) {
  return (
    <div className="bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg shadow-neutral-100/80 border border-neutral-200/60 overflow-hidden transition-all duration-300 hover:shadow-xl hover:shadow-neutral-200/50">
      {/* Top Gradient bar for accent */}
      <div className="h-1 bg-gradient-to-r from-[#1b3a5c] via-[#155d9b] to-[#2e7d32]" />
      
      <div className="p-5 sm:p-6">
        <div className="flex items-center gap-3 mb-4">
          {icon && (
            <div className="p-2 bg-[#f4f7fb] text-[#155d9b] rounded-xl shrink-0">
              {icon}
            </div>
          )}
          <div>
            <h3 className="text-sm sm:text-base font-bold text-neutral-800 tracking-tight">
              {title}
            </h3>
            {description && (
              <p className="text-[11px] sm:text-xs text-neutral-500 mt-0.5">{description}</p>
            )}
          </div>
        </div>
        
        <div className="space-y-4">{children}</div>
      </div>
    </div>
  );
}
