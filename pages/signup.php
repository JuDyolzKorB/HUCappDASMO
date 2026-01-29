<?php
// pages/signup.php
?>
<div class="min-h-screen animated-gradient flex items-center justify-center p-4" x-data="{ passVisible: false, confirmVisible: false }">
    <div class="auth-card animate-premium-in">
        <!-- Left Panel - Hero -->
        <div class="auth-branding hidden md:flex animated-gradient">
            <div class="logo-container">
                <img src="assets/img/logo.png" alt="Uswag Logo" class="w-16 h-16">
            </div>
            <p class="text-sm font-medium text-white/80 mb-1">Iloilo City Government</p>
            <h1 class="text-3xl font-bold mb-2 leading-tight font-display text-white">Uswag Iloilo City Pharmacy</h1>
            <p class="text-sm text-white/90 mb-6 max-w-xs px-2 leading-relaxed font-normal">A comprehensive, end-to-end inventory management system designed for modern healthcare.</p>
            <div class="testimonial-box bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 shadow-lg">
                <p class="text-xs italic leading-relaxed font-medium">"This system has revolutionized our inventory control, enhancing accountability and ensuring the efficient delivery of medical supplies for the people of Iloilo City."</p>
                <p class="mt-4 text-xs font-semibold text-white/90">- Head Pharmacist, Uswag Iloilo City Pharmacy</p>
            </div>
        </div>

        <!-- Right Panel - Form -->
        <div class="auth-form-container w-full md:w-1/2 overflow-y-auto max-h-[96vh]">
            <div class="text-center mb-6">
                <h2 class="text-3xl font-bold text-slate-800 font-display">Create Account</h2>
                <p class="mt-1.5 text-sm text-slate-500">
                    Existing user? 
                    <button onclick="location.href='index.php?page=login'" class="text-teal-600 hover:underline font-medium">
                        Sign In
                    </button>
                </p>
            </div>

            <form class="space-y-3" id="signupForm" onsubmit="handleSignup(event)">
                <input type="hidden" name="action" value="signup">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label for="firstName" class="block text-sm font-medium text-slate-600 mb-1">First Name</label>
                        <input id="firstName" name="firstName" type="text" required class="form-input bg-slate-50 border-slate-200 text-sm py-2.5" placeholder="John">
                    </div>
                    <div>
                        <label for="middleName" class="block text-sm font-medium text-slate-600 mb-1.5">Middle Name</label>
                        <input id="middleName" name="middleName" type="text" class="form-input bg-slate-50 border-slate-200 text-sm py-2.5" placeholder="M.">
                    </div>
                    <div>
                        <label for="lastName" class="block text-sm font-medium text-slate-600 mb-1.5">Last Name</label>
                        <input id="lastName" name="lastName" type="text" required class="form-input bg-slate-50 border-slate-200 text-sm py-2.5" placeholder="Doe">
                    </div>
                </div>

                <div>
                    <label for="role" class="block text-sm font-medium text-slate-600 mb-1.5">Role</label>
                    <select id="role" name="role" required class="form-select bg-slate-50 border-slate-200 text-sm py-2.5">
                        <option value="Administrator">Administrator</option>
                        <option value="Head Pharmacist">Head Pharmacist</option>
                        <option value="Health Center Staff">Health Center Staff</option>
                        <option value="Warehouse Staff">Warehouse Staff</option>
                        <option value="Accounting Office User">Accounting Office User</option>
                        <option value="CMO/GSO/COA User">CMO/GSO/COA User</option>
                    </select>
                </div>

                <div>
                    <label for="username" class="block text-sm font-medium text-slate-600 mb-1.5">Username</label>
                    <input id="username" name="username" type="text" required class="form-input bg-slate-50 border-slate-200 text-sm py-2.5" placeholder="johndoe">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-600 mb-1.5">Password</label>
                        <div class="relative">
                            <input id="password" name="password" :type="passVisible ? 'text' : 'password'" required class="form-input bg-slate-50 border-slate-200 text-sm py-2.5 pr-10" placeholder="••••••••">
                            <button type="button" @click="passVisible = !passVisible" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 focus:outline-none">
                                <svg x-show="!passVisible" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639l4.42-7.108a1.012 1.012 0 0 1 1.638 0l4.42 7.108a1.012 1.012 0 0 1 0 .639l-4.42 7.108a1.012 1.012 0 0 1-1.638 0l-4.42-7.108Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg x-show="passVisible" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.828 9.828" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="confirmPassword" class="block text-sm font-medium text-slate-600 mb-1.5">Confirm Password</label>
                        <div class="relative">
                            <input id="confirmPassword" name="confirmPassword" :type="confirmVisible ? 'text' : 'password'" required class="form-input bg-slate-50 border-slate-200 text-sm py-2.5 pr-10" placeholder="••••••••">
                            <button type="button" @click="confirmVisible = !confirmVisible" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 focus:outline-none">
                                <svg x-show="!confirmVisible" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639l4.42-7.108a1.012 1.012 0 0 1 1.638 0l4.42 7.108a1.012 1.012 0 0 1 0 .639l-4.42 7.108a1.012 1.012 0 0 1-1.638 0l-4.42-7.108Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg x-show="confirmVisible" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.828 9.828" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="message" class="text-xs text-center hidden p-2 rounded-lg border font-semibold"></div>

                <div class="pt-1.5">
                    <button type="submit" class="btn btn-primary w-full py-2.5 shadow-sm active:scale-[0.98] text-sm font-semibold">
                        Create Account
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center border-t border-slate-50 pt-4">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                    &copy; 2026 USWAG ILOILO CITY
                </p>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
async function handleSignup(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const messageDiv = document.getElementById('message');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    if(formData.get('password') !== formData.get('confirmPassword')) {
         messageDiv.textContent = "Passwords do not match!";
         messageDiv.className = "text-sm text-center text-red-600 p-2 border border-red-200 bg-red-50 font-medium";
         messageDiv.classList.remove('hidden');
         return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="animate-spin mr-2">⏳</span> Creating account...';

    try {
        const response = await fetch('api.php', { method: 'POST', body: formData });
        const result = await response.json();
        
        if (result.success) {
            messageDiv.textContent = "Account created successfully! Redirecting...";
            messageDiv.className = "text-sm text-center text-green-600 p-2 border border-green-200 bg-green-50 font-medium";
            messageDiv.classList.remove('hidden');
            setTimeout(() => window.location.href = 'index.php?page=dashboard', 1000);
        } else {
            messageDiv.textContent = result.message || "Signup failed";
            messageDiv.className = "text-sm text-center text-red-600 p-2 border border-red-200 bg-red-50 font-medium";
            messageDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Create Account';
        }
    } catch (error) {
        messageDiv.textContent = "An error occurred. Please try again.";
        messageDiv.className = "text-sm text-center text-red-600 p-2 border border-red-200 bg-red-50 font-medium";
        messageDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Create Account';
    }
}
</script>
