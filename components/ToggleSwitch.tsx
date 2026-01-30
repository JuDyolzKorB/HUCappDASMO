import React from 'react';

interface ToggleSwitchProps {
  enabled: boolean;
  onChange: (enabled: boolean) => void;
  label?: string;
}

const ToggleSwitch: React.FC<ToggleSwitchProps> = ({ enabled, onChange, label }) => {
  const switchId = React.useId();
  return (
    <div className="flex items-center">
      {label && <label htmlFor={switchId} className="mr-3 text-sm text-slate-600 cursor-pointer">{label}</label>}
      <button
        id={switchId}
        role="switch"
        aria-checked={enabled}
        onClick={() => onChange(!enabled)}
        className={`relative inline-flex items-center h-6 w-12 rounded-full transition-colors duration-300 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[var(--color-primary)] ${enabled ? 'bg-[var(--color-primary)]' : 'bg-[var(--color-border)]'}`}
      >
        <span
          className={`inline-block w-4 h-4 transform bg-white rounded-full transition-transform duration-300 ease-in-out ${enabled ? 'translate-x-7' : 'translate-x-1'}`}
        />
      </button>
    </div>
  );
};

export default ToggleSwitch;