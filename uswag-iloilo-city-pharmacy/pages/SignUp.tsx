
import React, { useState } from 'react';
import { User, UserRole } from '../types';
import EyeIcon from '../components/icons/EyeIcon';
import EyeSlashIcon from '../components/icons/EyeSlashIcon';

interface SignUpProps {
  onSignUp: (user: Omit<User, 'UserID'>) => void;
  onSwitchToSignIn: () => void;
}

const SignUp: React.FC<SignUpProps> = ({ onSignUp, onSwitchToSignIn }) => {
  const [isPasswordVisible, setIsPasswordVisible] = useState(false);

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const formData = new FormData(e.currentTarget);
    const user: Omit<User, 'UserID'> = {
        Username: formData.get('username') as string,
        FirstName: formData.get('firstName') as string,
        MiddleName: formData.get('middleName') as string,
        LastName: formData.get('lastName') as string,
        Role: formData.get('role') as UserRole,
        Password: formData.get('password') as string,
    };
    onSignUp(user);
  };

  return (
     <div className="min-h-screen flex items-center justify-center animated-gradient p-4">
        <div className="w-full max-w-6xl md:grid md:grid-cols-2 rounded-2xl shadow-2xl overflow-hidden bg-[var(--color-bg-surface)]">
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
                <div className="text-center mb-8">
                    <h2 className="text-3xl font-bold text-[var(--color-text-base)]">Create Account</h2>
                    <p className="mt-2 text-sm text-[var(--color-text-muted)]">
                        Already have an account?{' '}
                        <button onClick={onSwitchToSignIn} className="font-medium text-[var(--color-primary)] hover:underline">
                            Sign In
                        </button>
                    </p>
                </div>
                
                <form className="space-y-4" onSubmit={handleSubmit}>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label htmlFor="firstName" className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">First Name</label>
                            <input id="firstName" name="firstName" type="text" required className="form-input" placeholder="John"/>
                        </div>
                        <div>
                            <label htmlFor="middleName" className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Middle Name</label>
                            <input id="middleName" name="middleName" type="text" className="form-input" placeholder="M."/>
                        </div>
                        <div>
                            <label htmlFor="lastName" className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Last Name</label>
                            <input id="lastName" name="lastName" type="text" required className="form-input" placeholder="Doe"/>
                        </div>
                    </div>
                    <div>
                        <label htmlFor="role" className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Role</label>
                        <select id="role" name="role" required className="form-select">
                            <option value="Administrator">Administrator</option>
                            <option value="Head Pharmacist">Head Pharmacist</option>
                            <option value="Health Center Staff">Health Center Staff</option>
                            <option value="Accounting Office User">Accounting Office User</option>
                            <option value="Warehouse Staff">Warehouse Staff</option>
                            <option value="CMO/GSO/COA User">CMO/GSO/COA User</option>
                        </select>
                    </div>
                    <div>
                        <label htmlFor="username-signup" className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Username</label>
                        <input
                            id="username-signup" name="username" type="text" autoComplete="username"
                            required className="form-input" placeholder="johndoe"
                        />
                    </div>
                    <div>
                        <label htmlFor="password-signup" className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Password</label>
                         <div className="relative">
                            <input
                                id="password-signup" name="password" type={isPasswordVisible ? 'text' : 'password'}
                                required className="form-input pr-10" placeholder="••••••••"
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
                    <div className="pt-2">
                        <button type="submit" className="btn btn-primary w-full">
                            Create Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
  );
};

export default SignUp;