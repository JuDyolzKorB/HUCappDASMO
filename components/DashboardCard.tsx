
import React from 'react';

interface DashboardCardProps {
  title: string;
  value: string;
  icon: React.ReactNode;
  color: string;
}

const DashboardCard: React.FC<DashboardCardProps> = ({ title, value, icon, color }) => {
  return (
    <div className="bg-[var(--color-bg-surface)] p-6 rounded-xl shadow-md flex items-center space-x-6 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
      <div className={`flex-shrink-0 w-16 h-16 rounded-full flex items-center justify-center ${color}`}>
        <div className="text-white">
          {icon}
        </div>
      </div>
      <div>
        <p className="text-sm font-medium text-[var(--color-text-muted)]">{title}</p>
        <p className="text-2xl font-bold text-[var(--color-text-base)] mt-1">{value}</p>
      </div>
    </div>
  );
};

export default DashboardCard;