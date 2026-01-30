
import React from 'react';

const SettingsIcon: React.FC<{ className?: string }> = ({ className }) => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className={className}>
    <path strokeLinecap="round" strokeLinejoin="round" d="M9.594 3.94c.09-.542.56-1.007 1.11-.95.542.057.955.546.955 1.099v.716c.58.079 1.135.245 1.65.498a1.5 1.5 0 0 0 1.83.213l.44-.22c.5-.248 1.135.04 1.34.54l.82 1.638a1.5 1.5 0 0 1-.213 1.83l-.44.22a1.5 1.5 0 0 0-.213 1.83c.253.515.42.086.498 1.65v.716c0 .553-.413 1.042-.955 1.099-.55.057-1.02-.408-1.11-.95a12.001 12.001 0 0 1-1.65-.498 1.5 1.5 0 0 0-1.83-.213l-.44.22c-.5.248-1.135-.04-1.34-.54l-.82-1.638a1.5 1.5 0 0 1 .213-1.83l.44-.22a1.5 1.5 0 0 0 .213-1.83 12.001 12.001 0 0 1-.498-1.65v-.716c0-.553.413-1.042.955-1.099.55-.057 1.02.408 1.11.95Z" />
    <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
  </svg>
);

export default SettingsIcon;
