import React from 'react';
import { Page, UserRole } from '../types';
import DashboardIcon from './icons/DashboardIcon';
import RequisitionsIcon from './icons/RequisitionsIcon';
import POIcon from './icons/POIcon';
import ReceivingIcon from './icons/ReceivingIcon';
import InventoryIcon from './icons/InventoryIcon';
import IssuanceIcon from './icons/IssuanceIcon';
import AdjustmentsIcon from './icons/AdjustmentsIcon';
import ReportsIcon from './icons/ReportsIcon';
import SettingsIcon from './icons/SettingsIcon';
import WarehouseIcon from './icons/WarehouseIcon';
import SignOutIcon from './icons/SignOutIcon';

interface SidebarProps {
  currentPage: Page;
  setCurrentPage: (page: Page) => void;
  userRole: UserRole;
  isOpen: boolean;
  setIsOpen: (isOpen: boolean) => void;
  onSignOut: () => void;
}

const adminAccessPages: Page[] = ['Dashboard', 'Requisitions', 'Purchase Orders', 'Receiving', 'Inventory', 'Warehouse', 'Issuance', 'Adjustments', 'Reports', 'Settings'];

const rolePermissions: Record<UserRole, Page[]> = {
    'Health Center Staff': ['Dashboard', 'Requisitions', 'Settings'],
    'Administrator': adminAccessPages,
    'Head Pharmacist': adminAccessPages,
    'Accounting Office User': ['Dashboard', 'Reports', 'Settings'],
    'Warehouse Staff': ['Dashboard', 'Receiving', 'Issuance', 'Inventory', 'Purchase Orders', 'Settings'],
    'CMO/GSO/COA User': ['Dashboard', 'Reports', 'Settings'],
};

const NavLink: React.FC<{
  icon: React.ReactNode;
  label: Page;
  isActive: boolean;
  onClick: () => void;
}> = ({ icon, label, isActive, onClick }) => (
  <li className="px-3">
    <a
      href="#"
      onClick={(e) => {
        e.preventDefault();
        onClick();
      }}
      className={`relative flex items-center py-3 px-4 text-sm font-medium rounded-lg transition-all duration-200 group ${
        isActive
          ? 'bg-[var(--color-primary-light)] text-[var(--color-primary)]'
          : 'text-[var(--color-text-muted)] hover:bg-[var(--color-bg-muted)] hover:text-[var(--color-text-base)]'
      }`}
    >
      {isActive && <div className="absolute left-0 top-0 h-full w-1 bg-[var(--color-primary)] rounded-r-full"></div>}
      <div className={`transition-transform duration-200 group-hover:scale-110 ${isActive ? 'text-[var(--color-primary)]' : 'text-[var(--color-text-subtle)] group-hover:text-[var(--color-text-muted)]'}`}>
        {icon}
      </div>
      <span className="ml-3 flex-1 whitespace-nowrap">{label}</span>
    </a>
  </li>
);

const Sidebar: React.FC<SidebarProps> = ({ currentPage, setCurrentPage, userRole, isOpen, setIsOpen, onSignOut }) => {
  const allNavItems: { label: Page; icon: React.ReactNode }[] = [
    { label: 'Dashboard', icon: <DashboardIcon className="w-5 h-5" /> },
    { label: 'Requisitions', icon: <RequisitionsIcon className="w-5 h-5" /> },
    { label: 'Purchase Orders', icon: <POIcon className="w-5 h-5" /> },
    { label: 'Receiving', icon: <ReceivingIcon className="w-5 h-5" /> },
    { label: 'Inventory', icon: <InventoryIcon className="w-5 h-5" /> },
    { label: 'Warehouse', icon: <WarehouseIcon className="w-5 h-5" /> },
    { label: 'Issuance', icon: <IssuanceIcon className="w-5 h-5" /> },
    { label: 'Adjustments', icon: <AdjustmentsIcon className="w-5 h-5" /> },
    { label: 'Reports', icon: <ReportsIcon className="w-5 h-5" /> },
    { label: 'Settings', icon: <SettingsIcon className="w-5 h-5" /> },
  ];

  const accessiblePages = rolePermissions[userRole] || [];
  const navItems = allNavItems.filter(item => accessiblePages.includes(item.label));

  const handleLinkClick = (page: Page) => {
    setCurrentPage(page);
    setIsOpen(false);
  }

  return (
    <aside 
      className={`fixed inset-y-0 left-0 z-40 w-64 flex-shrink-0 bg-[var(--color-bg-surface)] border-r border-[var(--color-border)] transition-transform duration-300 ease-in-out md:relative md:translate-x-0 ${isOpen ? 'translate-x-0' : '-translate-x-full'}`}
      aria-label="Sidebar"
    >
      <div className="flex flex-col h-full overflow-y-auto py-5">
        <div className="px-4 mb-8 flex justify-between items-center">
            <a
            href="#"
            onClick={(e) => {
                e.preventDefault();
                handleLinkClick('Dashboard');
            }}
            className="flex items-center group"
            aria-label="Go to dashboard"
            >
                <div className="p-2 rounded-lg bg-gradient-to-br from-teal-500 to-teal-600 dark:from-teal-400 dark:to-teal-500 shadow-md shadow-teal-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-6 h-6 text-white transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110">
                        <path d="M12.378 1.602a.75.75 0 0 0-.756 0L3 6.632l9 5.25 9-5.25-8.622-5.03Z" />
                        <path fillRule="evenodd" d="M12 21a.75.75 0 0 1-.378-.102L3 15.632v-5.25l9 5.25 9-5.25v5.25l-8.622 5.268A.75.75 0 0 1 12 21Z" clipRule="evenodd" />
                    </svg>
                </div>
                <span className="ml-3 self-center text-base font-semibold text-[var(--color-text-base)]">Uswag Iloilo Pharmacy</span>
            </a>
            <button onClick={() => setIsOpen(false)} className="md:hidden text-[var(--color-text-muted)] hover:text-[var(--color-text-base)]">
                 <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <nav className="flex-1">
            <ul className="space-y-2">
            {navItems.map((item) => (
                <NavLink
                key={item.label}
                icon={item.icon}
                label={item.label}
                isActive={currentPage === item.label}
                onClick={() => handleLinkClick(item.label)}
                />
            ))}
            </ul>
        </nav>
        <div className="mt-auto px-4 pt-4">
          <button
            onClick={onSignOut}
            className="w-full flex items-center py-3 px-4 text-sm font-medium rounded-lg transition-all duration-200 group text-[var(--color-text-danger)] hover:bg-[var(--color-danger-light)]"
          >
            <SignOutIcon className="w-5 h-5" />
            <span className="ml-3 flex-1 whitespace-nowrap">Sign Out</span>
          </button>
        </div>
      </div>
    </aside>
  );
};

export default Sidebar;