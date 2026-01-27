
import React, { useState, useEffect } from 'react';
import { SecurityLog, TransactionAuditLog, User, SettingsPage, Theme, Notification } from '../types';
import ToggleSwitch from '../components/ToggleSwitch';
import UserCircleIcon from '../components/icons/UserCircleIcon';
import KeyIcon from '../components/icons/KeyIcon';
import BellIcon from '../components/icons/BellIcon';
import PaintBrushIcon from '../components/icons/PaintBrushIcon';
import ClockIcon from '../components/icons/ClockIcon';
import SunIcon from '../components/icons/SunIcon';
import MoonIcon from '../components/icons/MoonIcon';
import ComputerDesktopIcon from '../components/icons/ComputerDesktopIcon';
import ChecklistIcon from '../components/icons/ChecklistIcon';
import EyeIcon from '../components/icons/EyeIcon';
import EyeSlashIcon from '../components/icons/EyeSlashIcon';


interface SettingsProps {
    securityLogs: SecurityLog[];
    transactionLogs: TransactionAuditLog[];
    currentUser: User;
    theme: Theme;
    onThemeChange: (theme: Theme) => void;
    onUpdateProfile: (userId: string, updates: { FirstName: string; LastName: string }) => void;
    onChangePassword: (userId: string, newPassword: string) => boolean;
    addNotification: (notification: Omit<Notification, 'id' | 'timestamp' | 'isRead'>) => void;
}

const SettingsCard: React.FC<{ children: React.ReactNode, title: string, description: string, footerContent?: React.ReactNode }> = ({ children, title, description, footerContent }) => (
    <div className="bg-[var(--color-bg-surface)] rounded-xl shadow-md">
        <div className="p-6 md:p-8 border-b border-[var(--color-border)]">
            <h3 className="text-lg font-semibold text-[var(--color-text-base)]">{title}</h3>
            <p className="text-sm text-[var(--color-text-muted)] mt-1">{description}</p>
        </div>
        <div className="p-6 md:p-8">
            {children}
        </div>
        {footerContent && (
            <div className="bg-[var(--color-bg-muted)] p-4 md:p-6 rounded-b-xl border-t border-[var(--color-border)] text-right">
                {footerContent}
            </div>
        )}
    </div>
);

const themeOptions = [
    { id: 'Light', label: 'Light', description: 'Light theme for all pages.', icon: <SunIcon className="w-6 h-6" /> },
    { id: 'Dark', label: 'Dark', description: 'Dark theme for all pages.', icon: <MoonIcon className="w-6 h-6" /> },
    { id: 'System', label: 'System', description: "Follows your system's appearance.", icon: <ComputerDesktopIcon className="w-6 h-6" /> },
];

const Settings: React.FC<SettingsProps> = ({ securityLogs, transactionLogs, currentUser, theme, onThemeChange, onUpdateProfile, onChangePassword, addNotification }) => {
    const [activeTab, setActiveTab] = useState<SettingsPage>('Profile');
    
    const [firstName, setFirstName] = useState(currentUser.FirstName);
    const [lastName, setLastName] = useState(currentUser.LastName);
    const [emailNotifications, setEmailNotifications] = useState(true);
    const [inAppNotifications, setInAppNotifications] = useState(true);

    const [currentPassword, setCurrentPassword] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [passwordError, setPasswordError] = useState('');

    const [isCurrentPasswordVisible, setIsCurrentPasswordVisible] = useState(false);
    const [isNewPasswordVisible, setIsNewPasswordVisible] = useState(false);
    const [isConfirmPasswordVisible, setIsConfirmPasswordVisible] = useState(false);

    const canViewActivity = ['Administrator', 'Head Pharmacist'].includes(currentUser.Role);

    useEffect(() => {
        if (activeTab === 'Activity' && !canViewActivity) {
            setActiveTab('Profile');
        }
    }, [activeTab, canViewActivity]);
    
    useEffect(() => {
        setFirstName(currentUser.FirstName);
        setLastName(currentUser.LastName);
    }, [currentUser]);

    const handleProfileUpdate = () => {
        onUpdateProfile(currentUser.UserID, { FirstName: firstName, LastName: lastName });
    };

    const handlePasswordChange = () => {
        setPasswordError('');
        if (currentPassword !== currentUser.Password) {
            setPasswordError('Your current password does not match.');
            return;
        }
        if (!newPassword || newPassword.length < 6) {
            setPasswordError('New password must be at least 6 characters long.');
            return;
        }
        if (newPassword !== confirmPassword) {
            setPasswordError('New passwords do not match.');
            return;
        }

        const success = onChangePassword(currentUser.UserID, newPassword);
        if (success) {
            setCurrentPassword('');
            setNewPassword('');
            setConfirmPassword('');
        }
    };

    const handleSavePreferences = () => {
        addNotification({
            title: 'Preferences Saved',
            message: 'Your notification preferences have been updated.',
            type: 'system',
            targetRoles: [currentUser.Role]
        });
    };

    const navItems: { id: SettingsPage; label: string; icon: React.ReactNode }[] = [
        { id: 'Profile', label: 'Profile', icon: <UserCircleIcon className="w-5 h-5" /> },
        { id: 'Security', label: 'Security', icon: <KeyIcon className="w-5 h-5" /> },
        { id: 'Notifications', label: 'Notifications', icon: <BellIcon className="w-5 h-5" /> },
        { id: 'Appearance', label: 'Appearance', icon: <PaintBrushIcon className="w-5 h-5" /> },
        { id: 'Activity', label: 'Activity', icon: <ClockIcon className="w-5 h-5" /> },
    ];

    const visibleNavItems = navItems.filter(item => item.id !== 'Activity' || canViewActivity);

    const renderContent = () => {
        switch (activeTab) {
            case 'Profile':
                return (
                    <SettingsCard
                        title="Public Profile"
                        description="This information will be displayed internally to other users."
                        footerContent={<button onClick={handleProfileUpdate} className="btn btn-primary">Update Profile</button>}
                    >
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                             <div>
                                <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">First Name</label>
                                <input type="text" value={firstName} onChange={e => setFirstName(e.target.value)} className="form-input"/>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Last Name</label>
                                <input type="text" value={lastName} onChange={e => setLastName(e.target.value)} className="form-input"/>
                            </div>
                             <div>
                                <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Username</label>
                                <input type="text" value={currentUser.Username} readOnly className="form-input bg-[var(--color-bg-muted)] cursor-not-allowed"/>
                            </div>
                             <div>
                                <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Role</label>
                                <input type="text" value={currentUser.Role} readOnly className="form-input bg-[var(--color-bg-muted)] cursor-not-allowed"/>
                            </div>
                        </div>
                    </SettingsCard>
                );
            case 'Security':
                 return (
                    <SettingsCard
                        title="Password"
                        description="Update your password. Ensure it's a strong one!"
                        footerContent={<button onClick={handlePasswordChange} className="btn btn-primary">Change Password</button>}
                    >
                        <div className="space-y-4">
                           <div>
                                <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Current Password</label>
                                <div className="relative">
                                    <input type={isCurrentPasswordVisible ? 'text' : 'password'} value={currentPassword} onChange={e => setCurrentPassword(e.target.value)} placeholder="••••••••" className="form-input pr-10"/>
                                    <button type="button" onClick={() => setIsCurrentPasswordVisible(v => !v)} className="absolute inset-y-0 right-0 px-3 flex items-center text-[var(--color-text-subtle)] hover:text-[var(--color-text-muted)]">
                                        {isCurrentPasswordVisible ? <EyeSlashIcon className="w-5 h-5"/> : <EyeIcon className="w-5 h-5"/>}
                                    </button>
                                </div>
                            </div>
                           <div>
                                <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">New Password</label>
                                <div className="relative">
                                    <input type={isNewPasswordVisible ? 'text' : 'password'} value={newPassword} onChange={e => setNewPassword(e.target.value)} placeholder="••••••••" className="form-input pr-10"/>
                                     <button type="button" onClick={() => setIsNewPasswordVisible(v => !v)} className="absolute inset-y-0 right-0 px-3 flex items-center text-[var(--color-text-subtle)] hover:text-[var(--color-text-muted)]">
                                        {isNewPasswordVisible ? <EyeSlashIcon className="w-5 h-5"/> : <EyeIcon className="w-5 h-5"/>}
                                    </button>
                                </div>
                            </div>
                             <div>
                                <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Confirm New Password</label>
                                 <div className="relative">
                                    <input type={isConfirmPasswordVisible ? 'text' : 'password'} value={confirmPassword} onChange={e => setConfirmPassword(e.target.value)} placeholder="••••••••" className="form-input pr-10"/>
                                     <button type="button" onClick={() => setIsConfirmPasswordVisible(v => !v)} className="absolute inset-y-0 right-0 px-3 flex items-center text-[var(--color-text-subtle)] hover:text-[var(--color-text-muted)]">
                                        {isConfirmPasswordVisible ? <EyeSlashIcon className="w-5 h-5"/> : <EyeIcon className="w-5 h-5"/>}
                                    </button>
                                </div>
                            </div>
                             {passwordError && <p className="text-sm text-[var(--color-text-danger)]">{passwordError}</p>}
                        </div>
                    </SettingsCard>
                );
             case 'Notifications':
                return (
                    <SettingsCard
                        title="Notification Settings"
                        description="Manage how you receive notifications from the system."
                        footerContent={<button onClick={handleSavePreferences} className="btn btn-primary">Save Preferences</button>}
                    >
                        <div className="space-y-4">
                            <div className="flex justify-between items-center p-3 rounded-lg hover:bg-[var(--color-bg-muted)]">
                                <div>
                                    <h4 className="font-medium text-[var(--color-text-base)]">Email Notifications</h4>
                                    <p className="text-sm text-[var(--color-text-muted)]">Receive alerts and updates in your inbox.</p>
                                </div>
                                <ToggleSwitch enabled={emailNotifications} onChange={setEmailNotifications} />
                            </div>
                             <div className="flex justify-between items-center p-3 rounded-lg hover:bg-[var(--color-bg-muted)]">
                                <div>
                                    <h4 className="font-medium text-[var(--color-text-base)]">In-App Notifications</h4>
                                    <p className="text-sm text-[var(--color-text-muted)]">Show notifications inside the application header.</p>
                                </div>
                                <ToggleSwitch enabled={inAppNotifications} onChange={setInAppNotifications} />
                            </div>
                        </div>
                    </SettingsCard>
                );
            case 'Appearance':
                return (
                     <SettingsCard
                        title="Appearance"
                        description="Customize the look and feel of the application."
                    >
                        <div>
                            <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-2">Theme</label>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                {themeOptions.map(t => (
                                    <button 
                                        key={t.id} 
                                        onClick={() => onThemeChange(t.id as Theme)} 
                                        className={`relative p-4 border-2 rounded-lg text-left transition-all duration-200 ${
                                            theme === t.id 
                                            ? 'border-[var(--color-primary)] bg-[var(--color-primary-light)]' 
                                            : 'border-[var(--color-border)] hover:border-slate-400 hover:bg-[var(--color-bg-muted)]'
                                        }`}
                                    >
                                        {theme === t.id && (
                                            <ChecklistIcon className="absolute top-2 right-2 w-5 h-5 text-[var(--color-primary)]" />
                                        )}
                                        <div className="mb-2 text-[var(--color-text-muted)]">{t.icon}</div>
                                        <h4 className="font-semibold text-[var(--color-text-base)]">{t.label}</h4>
                                        <p className="text-xs text-[var(--color-text-muted)] mt-1">{t.description}</p>
                                    </button>
                                ))}
                            </div>
                        </div>
                    </SettingsCard>
                );
            case 'Activity':
                if (!canViewActivity) return null;
                return (
                    <div className="space-y-10">
                        <SettingsCard title="Security Logs" description="Recent login and security-related activities across the system.">
                            <div className="table-wrapper max-h-96">
                                <table className="custom-table">
                                    <thead className="sticky top-0"><tr><th>User</th><th>Action</th><th>Description</th><th>IP Address</th><th>Date</th></tr></thead>
                                    <tbody>
                                        {securityLogs.map(log => (
                                            <tr key={log.SecurityLogID}>
                                                <td>{log.UserFullName}</td>
                                                <td>{log.ActionType}</td>
                                                <td>{log.ActionDescription}</td>
                                                <td>{log.IPAddress}</td>
                                                <td>{new Date(log.ActionDate).toLocaleString()}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </SettingsCard>
                        <SettingsCard title="Transaction Audit Logs" description="Recent transactions and actions performed across the system.">
                             <div className="table-wrapper max-h-96">
                                <table className="custom-table">
                                    <thead className="sticky top-0"><tr><th>User</th><th>Action</th><th>Type</th><th>Reference ID</th><th>Date</th></tr></thead>
                                    <tbody>
                                        {transactionLogs.map(log => (
                                            <tr key={log.AuditLogID}>
                                                <td>{log.UserFullName}</td>
                                                <td>{log.ActionType}</td>
                                                <td>{log.ReferenceType}</td>
                                                <td className="font-mono">{log.ReferenceID}</td>
                                                <td>{new Date(log.ActionDate).toLocaleString()}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </SettingsCard>
                    </div>
                );
            default:
                return null;
        }
    };

    return (
        <div className="space-y-6">
            <div className="md:hidden">
                <h1 className="text-2xl font-bold text-[var(--color-text-base)]">Settings</h1>
                <p className="text-[var(--color-text-muted)] mt-1">Manage your account settings and set preferences.</p>
            </div>
            <div className="flex flex-col lg:flex-row items-start gap-8">
                <aside className="w-full lg:w-1/4">
                    <nav className="flex flex-row overflow-x-auto pb-2 -mx-4 px-4 md:px-0 md:-mx-0 lg:flex-col lg:space-y-2 lg:pb-0">
                        {visibleNavItems.map(item => (
                             <button
                                key={item.id}
                                onClick={() => setActiveTab(item.id)}
                                className={`flex items-center py-2.5 px-4 text-sm font-medium rounded-lg text-left transition-colors group shrink-0 ${
                                    activeTab === item.id
                                    ? 'bg-[var(--color-primary-light)] text-[var(--color-primary)]'
                                    : 'text-[var(--color-text-muted)] hover:bg-[var(--color-bg-muted)]'
                                }`}
                            >
                                <span className={`mr-3 ${activeTab === item.id ? 'text-[var(--color-primary)]' : 'text-[var(--color-text-subtle)] group-hover:text-[var(--color-text-muted)]'}`}>{item.icon}</span>
                                {item.label}
                            </button>
                        ))}
                    </nav>
                </aside>
                <main className="flex-1 w-full">
                    {renderContent()}
                </main>
            </div>
        </div>
    );
};

export default Settings;