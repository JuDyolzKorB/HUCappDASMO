
import React, { useState, useEffect } from 'react';
import { User, UserRole } from '../types';
import EyeIcon from '../components/icons/EyeIcon';
import EyeSlashIcon from '../components/icons/EyeSlashIcon';

interface SignInProps {
  onSignIn: (user: User) => void;
  onSwitchToSignUp: () => void;
  users: User[];
  onSignInFail: (username: string) => void;
}

const allRoles: UserRole[] = [
    'Administrator',
    'Head Pharmacist',
    'Health Center Staff',
    'Accounting Office User',
    'Warehouse Staff',
    'CMO/GSO/COA User',
];

const SignIn: React.FC<SignInProps> = ({ onSignIn, onSwitchToSignUp, users, onSignInFail }) => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('password');
  const [error, setError] = useState('');
  const [selectedRole, setSelectedRole] = useState<UserRole>('Administrator');
  const [isPasswordVisible, setIsPasswordVisible] = useState(false);

  useEffect(() => {
    const userForRole = users.find(u => u.Role === selectedRole);
    setUsername(userForRole?.Username || '');
  }, [selectedRole, users]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const user = users.find(u => u.Username === username && u.Password === password && u.Role === selectedRole);
    if (user) {
      onSignIn(user);
    } else {
      setError('Invalid username, password, or role.');
      onSignInFail(username);
    }
  };

  const handleUsernameChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
      setUsername(e.target.value);
  }

  const usersForSelectedRole = users.filter(u => u.Role === selectedRole);

  return (
    <div className="min-h-screen flex items-center justify-center animated-gradient p-4">
        <div className="w-full max-w-6xl md:grid md:grid-cols-2 rounded-2xl shadow-xl overflow-hidden bg-[var(--color-bg-surface)]">
             {/* Left Panel - Hero */}
            <div className="hidden md:flex flex-col justify-center items-center p-8 lg:p-12 bg-gradient-to-br from-[var(--color-primary)] to-[var(--color-primary-hover)] text-white text-center">
                <div className="bg-white/20 p-4 rounded-xl mb-6">
                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-10 h-10 text-white">
                        <path d="M12.378 1.602a.75.75 0 0 0-.756 0L3 6.632l9 5.25 9-5.25-8.622-5.03Z" />
                        <path fillRule="evenodd" d="M12 21a.75.75 0 0 1-.378-.102L3 15.632v-5.25l9 5.25 9-5.25v5.25l-8.622 5.268A.75.75 0 0 1 12 21Z" clipRule="evenodd" />
                    </svg>
                </div>
                <p className="text-lg text-white/70 mb-2">Iloilo City Government</p>
                <h1 className="text-4xl font-bold mb-2">Uswag Iloilo City Pharmacy</h1>
                <p className="text-lg text-white/70 mb-8">A comprehensive, end-to-end inventory management system designed for modern healthcare.</p>
                <div className="bg-white/10 p-6 rounded-lg backdrop-blur-sm border border-white/20 w-full max-w-md">
                    <p className="text-base italic">"This system has revolutionized our inventory control, enhancing accountability and ensuring the efficient delivery of medical supplies for the people of Iloilo City."</p>
                    <p className="mt-4 font-semibold text-white/90">- Head Pharmacist, Uswag Iloilo City Pharmacy</p>
                </div>
            </div>

            {/* Right Panel - Form */}
            <div className="w-full p-8 sm:p-12 flex flex-col justify-center">
                <div className="text-center mb-10">
                    <h2 className="text-3xl font-bold text-[var(--color-text-base)]">Welcome Back</h2>
                    <p className="mt-2 text-sm text-[var(--color-text-muted)]">
                        Don't have an account?{' '}
                        <button onClick={onSwitchToSignUp} className="font-medium text-[var(--color-primary)] hover:underline">
                            Sign Up
                        </button>
                    </p>
                </div>
                
                <form className="space-y-6" onSubmit={handleSubmit}>
                    <div>
                        <label htmlFor="role-signin" className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Role (for simulation)</label>
                        <select id="role-signin" value={selectedRole} onChange={e => setSelectedRole(e.target.value as UserRole)} className="form-select">
                            {allRoles.map(r => <option key={r} value={r}>{r}</option>)}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="username" className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Username</label>
                        {usersForSelectedRole.length > 1 ? (
                            <select id="username" value={username} onChange={handleUsernameChange} className="form-select">
                                {usersForSelectedRole.map(u => (
                                    <option key={u.UserID} value={u.Username}>{`${u.FirstName} ${u.LastName} (${u.Username})`}</option>
                                ))}
                            </select>
                        ) : (
                            <input
                                id="username" name="username" type="text" autoComplete="username" required
                                value={username} readOnly className="form-input bg-[var(--color-bg-muted)] cursor-not-allowed"
                            />
                        )}
                    </div>
                    <div>
                        <label htmlFor="password-signin" className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Password</label>
                        <div className="relative">
                            <input
                                id="password-signin" name="password" type={isPasswordVisible ? 'text' : 'password'} autoComplete="current-password"
                                required value={password} onChange={(e) => setPassword(e.target.value)}
                                className="form-input pr-10" placeholder="••••••••"
                            />
                             <button
                                type="button"
                                onClick={() => setIsPasswordVisible(!isPasswordVisible)}
                                className="absolute inset-y-0 right-0 px-3 flex items-center text-[var(--color-text-subtle)] hover:text-[var(--color-text-muted)]"
                                aria-label={isPasswordVisible ? "Hide password" : "Show password"}
                            >
                                {isPasswordVisible ? <EyeSlashIcon className="w-5 h-5" /> : <EyeIcon className="w-5 h-5" />}
                            </button>
                        </div>
                    </div>
                    {error && <p className="text-sm text-red-600 text-center">{error}</p>}
                    <div className="pt-2">
                        <button type="submit" className="btn btn-primary w-full">
                            Sign In
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
  );
};

export default SignIn;