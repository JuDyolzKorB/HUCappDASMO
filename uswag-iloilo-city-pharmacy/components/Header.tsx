import React, { useState, useEffect, useRef } from 'react';
import { Page, User, Notification, NotificationType } from '../types';
import TimeAgo from './TimeAgo';
import EnterFullscreenIcon from './icons/EnterFullscreenIcon';
import ExitFullscreenIcon from './icons/ExitFullscreenIcon';
import DashboardIcon from './icons/DashboardIcon';
import RequisitionsIcon from './icons/RequisitionsIcon';
import POIcon from './icons/POIcon';
import ReceivingIcon from './icons/ReceivingIcon';
import InventoryIcon from './icons/InventoryIcon';
import WarehouseIcon from './icons/WarehouseIcon';
import IssuanceIcon from './icons/IssuanceIcon';
import AdjustmentsIcon from './icons/AdjustmentsIcon';
import ReportsIcon from './icons/ReportsIcon';
import SettingsIcon from './icons/SettingsIcon';
import UserCircleIcon from './icons/UserCircleIcon';
import AlertIcon from './icons/AlertIcon';
import SignOutIcon from './icons/SignOutIcon';
import BellIcon from './icons/BellIcon';

interface HeaderProps {
  currentPage: Page;
  onSignOut: () => void;
  currentUser: User;
  notifications: Notification[];
  onMarkNotificationsAsRead: () => void;
  setCurrentPage: (page: Page) => void;
  toggleSidebar: () => void;
}

const getNotificationIcon = (type: NotificationType) => {
    const iconClass = "w-5 h-5";
    switch (type) {
        case 'requisition': return <div className="p-1.5 bg-blue-100 dark:bg-blue-900/50 rounded-full"><RequisitionsIcon className={`${iconClass} text-blue-500 dark:text-blue-400`} /></div>;
        case 'po': return <div className="p-1.5 bg-emerald-100 dark:bg-emerald-900/50 rounded-full"><POIcon className={`${iconClass} text-emerald-500 dark:text-emerald-400`} /></div>;
        case 'inventory': return <div className="p-1.5 bg-purple-100 dark:bg-purple-900/50 rounded-full"><InventoryIcon className={`${iconClass} text-purple-500 dark:text-purple-400`} /></div>;
        case 'alert': return <div className="p-1.5 bg-amber-100 dark:bg-amber-900/50 rounded-full"><AlertIcon className={`${iconClass} text-amber-500 dark:text-amber-400`} /></div>;
        case 'system':
        default: return <div className="p-1.5 bg-slate-100 dark:bg-slate-700/50 rounded-full"><SettingsIcon className={`${iconClass} text-slate-500 dark:text-slate-400`} /></div>;
    }
};

const getPageIcon = (page: Page) => {
    const iconClass = "w-6 h-6 text-[var(--color-primary)]";
    switch (page) {
        case 'Dashboard': return <DashboardIcon className={iconClass} />;
        case 'Requisitions': return <RequisitionsIcon className={iconClass} />;
        case 'Purchase Orders': return <POIcon className={iconClass} />;
        case 'Receiving': return <ReceivingIcon className={iconClass} />;
        case 'Inventory': return <InventoryIcon className={iconClass} />;
        case 'Warehouse': return <WarehouseIcon className={iconClass} />;
        case 'Issuance': return <IssuanceIcon className={iconClass} />;
        case 'Adjustments': return <AdjustmentsIcon className={iconClass} />;
        case 'Reports': return <ReportsIcon className={iconClass} />;
        case 'Settings': return <SettingsIcon className={iconClass} />;
        case 'Profile': return <UserCircleIcon className={iconClass} />;
        default: return null;
    }
};

const groupNotificationsByDate = (notifications: Notification[]) => {
    const groups: { [key: string]: Notification[] } = {
        Today: [],
        Yesterday: [],
        "Older": [],
    };
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    notifications.forEach(n => {
        const notificationDate = new Date(n.timestamp);
        if (notificationDate.toDateString() === today.toDateString()) {
            groups.Today.push(n);
        } else if (notificationDate.toDateString() === yesterday.toDateString()) {
            groups.Yesterday.push(n);
        } else {
            groups.Older.push(n);
        }
    });
    return groups;
};


const NotificationPanel: React.FC<{ 
    notifications: Notification[], 
    onMarkAllRead: () => void, 
    onViewAll: () => void,
    unreadCount: number 
}> = ({ notifications, onMarkAllRead, onViewAll, unreadCount }) => {
    const groupedNotifications = groupNotificationsByDate(notifications);

    return (
    <div className="absolute right-0 mt-2 w-80 sm:w-96 bg-[var(--color-bg-surface)] rounded-xl shadow-lg border border-[var(--color-border)] z-50 flex flex-col">
        <div className="p-4 border-b border-[var(--color-border)]">
            <h3 className="text-base font-semibold text-[var(--color-text-base)]">Notifications</h3>
            { unreadCount > 0 && <p className="text-xs text-[var(--color-primary)]">{unreadCount} new notifications</p> }
        </div>
        <div className="flex-grow max-h-96 overflow-y-auto">
            {notifications.length > 0 ? (
                Object.entries(groupedNotifications).map(([groupName, groupNotifications]) => 
                    groupNotifications.length > 0 && (
                        <div key={groupName}>
                            <h4 className="text-xs font-bold uppercase text-[var(--color-text-muted)] p-2 px-4 bg-[var(--color-bg-muted)] sticky top-0">{groupName}</h4>
                            {groupNotifications.map(n => (
                                <div key={n.id} className="flex items-start p-4 hover:bg-[var(--color-bg-muted)] transition-colors duration-150 relative">
                                    {!n.isRead && <div className="absolute left-2 top-1/2 -translate-y-1/2 h-1.5 w-1.5 bg-[var(--color-primary)] rounded-full"></div>}
                                    <div className="flex-shrink-0 mr-3">{getNotificationIcon(n.type)}</div>
                                    <div>
                                        <p className="text-sm font-medium text-[var(--color-text-base)]">{n.title}</p>
                                        <p className="text-xs text-[var(--color-text-muted)] leading-relaxed">{n.message}</p>
                                        <TimeAgo timestamp={n.timestamp} />
                                    </div>
                                </div>
                            ))}
                        </div>
                    )
                )
            ) : (
                <div className="text-center py-12 px-4">
                    <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-[var(--color-bg-muted)]">
                        <BellIcon className="h-6 w-6 text-[var(--color-text-muted)]" />
                    </div>
                    <h4 className="mt-4 text-sm font-semibold text-[var(--color-text-base)]">No new notifications</h4>
                    <p className="mt-1 text-xs text-[var(--color-text-muted)]">You're all caught up!</p>
                </div>
            )}
        </div>
         <div className="p-2 border-t border-[var(--color-border)] bg-[var(--color-bg-muted)] rounded-b-xl flex justify-between text-sm">
            <button onClick={onMarkAllRead} className="px-3 py-1.5 text-[var(--color-text-muted)] font-medium hover:text-[var(--color-primary)] rounded-md hover:bg-[var(--color-primary-light)]">Mark all as read</button>
            <button onClick={onViewAll} className="px-3 py-1.5 text-[var(--color-text-muted)] font-medium hover:text-[var(--color-primary)] rounded-md hover:bg-[var(--color-primary-light)]">View all</button>
        </div>
    </div>
);
}

const Header: React.FC<HeaderProps> = ({ currentPage, onSignOut, currentUser, notifications, onMarkNotificationsAsRead, setCurrentPage, toggleSidebar }) => {
  const [isNotifPanelOpen, setIsNotifPanelOpen] = useState(false);
  const [isUserMenuOpen, setIsUserMenuOpen] = useState(false);
  const [isFullscreen, setIsFullscreen] = useState(!!document.fullscreenElement);
  const notifRef = useRef<HTMLDivElement>(null);
  const userMenuRef = useRef<HTMLDivElement>(null);

  const unreadCount = notifications.filter(n => !n.isRead).length;

  const handleSignOutClick = () => {
    setIsUserMenuOpen(false);
    onSignOut();
  };

  const handleViewAllNotifications = () => {
    setIsNotifPanelOpen(false);
    setCurrentPage('Settings');
  };

  const toggleNotifPanel = () => setIsNotifPanelOpen(prev => !prev);
  
  const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch((err) => {
        console.error(`Error attempting to enable full-screen mode: ${err.message} (${err.name})`);
      });
    } else {
      if (document.exitFullscreen) {
        document.exitFullscreen();
      }
    }
  };

  useEffect(() => {
    const handleFullscreenChange = () => setIsFullscreen(!!document.fullscreenElement);
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    return () => document.removeEventListener('fullscreenchange', handleFullscreenChange);
  }, []);

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (notifRef.current && !notifRef.current.contains(event.target as Node)) {
        setIsNotifPanelOpen(false);
      }
      if (userMenuRef.current && !userMenuRef.current.contains(event.target as Node)) {
        setIsUserMenuOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);


  return (
    <header className="bg-[var(--color-bg-surface)]/80 backdrop-blur-sm sticky top-0 z-20">
        <div className="mx-auto py-3 px-4 sm:px-6 lg:px-8 flex justify-between items-center border-b border-black/5 dark:border-white/10">
          <div className="flex items-center gap-3">
              <button onClick={toggleSidebar} className="md:hidden text-[var(--color-text-muted)] hover:text-[var(--color-primary)]">
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
              </button>
              <div className="hidden sm:block">{getPageIcon(currentPage)}</div>
              <h1 className="text-lg sm:text-xl font-bold text-[var(--color-text-base)]">{currentPage}</h1>
          </div>
          <div className="flex items-center space-x-3 sm:space-x-5">
              <button onClick={toggleFullscreen} title={isFullscreen ? 'Exit Fullscreen' : 'Enter Fullscreen'} className="text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors hidden sm:block">
                  {isFullscreen ? <ExitFullscreenIcon className="w-6 h-6"/> : <EnterFullscreenIcon className="w-6 h-6"/>}
              </button>
              <div className="relative" ref={notifRef}>
                  <button onClick={toggleNotifPanel} className="relative text-[var(--color-text-muted)] hover:text-[var(--color-primary)] transition-colors">
                    <BellIcon className="w-6 h-6"/>
                    {unreadCount > 0 && (
                        <span className="absolute -top-1 -right-1 flex h-4 w-4">
                          <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-[var(--color-danger)] opacity-75"></span>
                          <span className="relative inline-flex rounded-full h-4 w-4 bg-[var(--color-danger)] items-center justify-center text-xs font-medium text-white">{unreadCount}</span>
                        </span>
                    )}
                  </button>
                  {isNotifPanelOpen && <NotificationPanel notifications={notifications} onMarkAllRead={onMarkNotificationsAsRead} onViewAll={handleViewAllNotifications} unreadCount={unreadCount} />}
              </div>
              <div className="h-8 w-px bg-[var(--color-border)]"></div>
              <div className="relative" ref={userMenuRef}>
                <button onClick={() => setIsUserMenuOpen(prev => !prev)} className="flex items-center space-x-2 group">
                    <img className="h-9 w-9 rounded-full object-cover ring-2 ring-offset-2 ring-transparent group-hover:ring-[var(--color-primary)] transition-all" src={`https://i.pravatar.cc/100?u=${currentUser.UserID}`} alt="User avatar" />
                    <div className="text-left hidden sm:block">
                        <p className="text-sm font-semibold text-[var(--color-text-base)]">{`${currentUser.FirstName} ${currentUser.LastName}`}</p>
                        <p className="text-xs text-[var(--color-text-muted)]">{currentUser.Role}</p>
                    </div>
                     <svg className={`w-4 h-4 text-[var(--color-text-subtle)] group-hover:text-[var(--color-text-base)] transition-transform ${isUserMenuOpen ? 'rotate-180' : ''}`} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fillRule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clipRule="evenodd" />
                    </svg>
                </button>
                 {isUserMenuOpen && (
                    <div className="absolute right-0 mt-2 w-56 bg-[var(--color-bg-surface)] rounded-xl shadow-lg border border-[var(--color-border)] z-50 overflow-hidden">
                        <div className="p-2 border-b border-[var(--color-border)]">
                            <p className="text-sm font-semibold text-[var(--color-text-base)] truncate">{`${currentUser.FirstName} ${currentUser.LastName}`}</p>
                            <p className="text-xs text-[var(--color-text-muted)] truncate">{currentUser.Role}</p>
                        </div>
                        <ul className="p-1">
                            <li>
                                <button onClick={() => { setCurrentPage('Profile'); setIsUserMenuOpen(false); }} className="w-full text-left flex items-center gap-2 px-3 py-2 text-sm text-[var(--color-text-base)] rounded-lg hover:bg-[var(--color-bg-muted)]">
                                  <UserCircleIcon className="w-4 h-4 text-[var(--color-text-muted)]" /> Profile
                                </button>
                            </li>
                             <li>
                                <button onClick={() => { setCurrentPage('Settings'); setIsUserMenuOpen(false); }} className="w-full text-left flex items-center gap-2 px-3 py-2 text-sm text-[var(--color-text-base)] rounded-lg hover:bg-[var(--color-bg-muted)]">
                                  <SettingsIcon className="w-4 h-4 text-[var(--color-text-muted)]" /> Settings
                                </button>
                            </li>
                             <li className="my-1 h-px bg-[var(--color-border)]"></li>
                            <li>
                                <button onClick={handleSignOutClick} className="w-full text-left flex items-center gap-2 px-3 py-2 text-sm text-[var(--color-text-danger)] rounded-lg hover:bg-[var(--color-danger-light)]">
                                    <SignOutIcon className="w-4 h-4" /> Sign Out
                                </button>
                            </li>
                        </ul>
                    </div>
                )}
              </div>
          </div>
        </div>
      </header>
  );
};

export default Header;