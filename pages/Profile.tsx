import React from 'react';
import { User } from '../types';
import UserCircleIcon from '../components/icons/UserCircleIcon';

interface ProfileInfoRowProps {
    label: string;
    value: string;
}
const ProfileInfoRow: React.FC<ProfileInfoRowProps> = ({ label, value }) => (
    <div className="sm:col-span-1 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
        <dt className="text-sm font-medium leading-6 text-[var(--color-text-muted)]">{label}</dt>
        <dd className="mt-1 text-sm leading-6 text-[var(--color-text-base)] sm:col-span-2 sm:mt-0">{value}</dd>
    </div>
);


interface ProfileProps {
  currentUser: User;
}

const Profile: React.FC<ProfileProps> = ({ currentUser }) => {
  return (
    <div className="max-w-4xl mx-auto space-y-8">
      {/* Profile Header Card */}
      <div className="bg-[var(--color-bg-surface)] p-6 rounded-xl shadow-lg flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-6">
        <img
          className="h-24 w-24 rounded-full object-cover ring-4 ring-offset-4 ring-offset-[var(--color-bg-surface)] ring-[var(--color-primary)]"
          src={`https://i.pravatar.cc/150?u=${currentUser.UserID}`}
          alt="User avatar"
        />
        <div>
          <h1 className="text-2xl text-center sm:text-left font-bold text-[var(--color-text-base)]">{`${currentUser.FirstName} ${currentUser.LastName}`}</h1>
          <p className="text-md text-center sm:text-left text-[var(--color-text-muted)]">{currentUser.Role}</p>
        </div>
      </div>

      {/* Account Information */}
      <div className="bg-[var(--color-bg-surface)] rounded-xl shadow-lg">
        <div className="px-6 py-4 border-b border-[var(--color-border)] flex items-center gap-3">
          <UserCircleIcon className="w-6 h-6 text-[var(--color-primary)]" />
          <h2 className="text-lg font-semibold text-[var(--color-text-base)]">Account Information</h2>
        </div>
        <div className="px-6">
           <dl className="divide-y divide-[var(--color-border)]">
                <ProfileInfoRow label="First Name" value={currentUser.FirstName} />
                <ProfileInfoRow label="Last Name" value={currentUser.LastName} />
                {currentUser.MiddleName && <ProfileInfoRow label="Middle Name" value={currentUser.MiddleName} />}
                <ProfileInfoRow label="Username" value={currentUser.Username} />
                <ProfileInfoRow label="User ID" value={currentUser.UserID} />
                <ProfileInfoRow label="Role" value={currentUser.Role} />
           </dl>
        </div>
      </div>
    </div>
  );
};

export default Profile;