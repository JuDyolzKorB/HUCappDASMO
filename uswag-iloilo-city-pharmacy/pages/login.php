<?php
// pages/login.php
$all_users = get_data('users');
?>
<div class="min-h-screen animated-gradient flex items-center justify-center p-4" x-data="loginFlow()">
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
                <h2 class="text-3xl font-bold text-slate-800 font-display">Welcome Back</h2>
                <div class="flex items-center justify-center gap-2 mt-2">
                    <p class="text-sm text-slate-500">
                        Don't have an account? 
                        <button onclick="location.href='index.php?page=signup'" class="text-teal-600 hover:underline font-medium">
                            Sign Up
                        </button>
                    </p>
                </div>
            </div>

            <form class="space-y-4" @submit.prevent="handleLogin">
                <div>
                    <label for="role" class="block text-sm font-medium text-slate-600 mb-1.5">Role (for simulation)</label>
                    <select id="role" name="role" x-model="selectedRole" @change="updateUserlist" required class="form-select bg-slate-50 border-slate-200 text-sm py-2.5">
                        <option value="">Select a role...</option>
                        <template x-for="role in roles" :key="role">
                            <option :value="role" x-text="role"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label for="username" class="block text-sm font-medium text-slate-600 mb-1.5">Username (Predefined)</label>
                    <select id="username" name="username" x-model="selectedUser" :disabled="!selectedRole || filteredUsers.length === 0" :readonly="filteredUsers.length === 1" required class="form-select bg-slate-50 border-slate-200 text-sm py-2.5">
                        <option value="">Select user...</option>
                        <template x-for="user in filteredUsers" :key="user.Username">
                            <option :value="user.Username" x-text="`${user.FirstName} ${user.LastName} (${user.Username})`"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-600 mb-1.5">Password</label>
                    <div class="relative">
                        <input id="password" name="password" :type="passwordVisible ? 'text' : 'password'" x-model="password" required class="form-input bg-slate-50 border-slate-200 text-sm py-2.5 pr-10" placeholder="••••••••">
                        <button type="button" @click="passwordVisible = !passwordVisible" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 focus:outline-none">
                             <svg x-show="!passwordVisible" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639l4.42-7.108a1.012 1.012 0 0 1 1.638 0l4.42 7.108a1.012 1.012 0 0 1 0 .639l-4.42 7.108a1.012 1.012 0 0 1-1.638 0l-4.42-7.108Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg x-show="passwordVisible" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.828 9.828" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-1.5 text-[10px] text-slate-400">Hardcoded to <span class="text-slate-800 font-bold">'password'</span> for simulation</p>
                </div>

                <div x-show="message" x-transition class="p-2 text-xs text-center border font-semibold rounded-lg" :class="messageType === 'error' ? 'text-red-600 bg-red-50 border-red-200' : 'text-green-600 bg-green-50 border-green-200'" x-text="message"></div>

                <div class="pt-1.5">
                    <button type="submit" :disabled="loading" class="btn btn-primary w-full py-2.5 shadow-sm active:scale-[0.98] text-sm font-semibold flex justify-center items-center gap-2">
                        <span x-show="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        <span x-text="loading ? 'Signing in...' : 'Sign In'"></span>
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
function loginFlow() {
    return {
        allUsers: <?= json_encode($all_users) ?>,
        roles: [],
        filteredUsers: [],
        selectedRole: '',
        selectedUser: '',
        password: 'password',
        passwordVisible: false,
        loading: false,
        message: '',
        messageType: '',
        
        init() {
            const uniqueRoles = [...new Set(this.allUsers.map(u => u.Role))];
            this.roles = uniqueRoles.sort();
        },
        
        updateUserlist() {
            if (!this.selectedRole) {
                this.filteredUsers = [];
                this.selectedUser = '';
                return;
            }
            this.filteredUsers = this.allUsers.filter(u => u.Role === this.selectedRole);
            this.selectedUser = this.filteredUsers.length > 0 ? this.filteredUsers[0].Username : '';
        },
        
        async handleLogin() {
            this.loading = true;
            this.message = '';
            
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('role', this.selectedRole);
            formData.append('username', this.selectedUser);
            formData.append('password', this.password);
            
            try {
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.success) {
                    this.message = 'Login successful! Redirecting...';
                    this.messageType = 'success';
                    setTimeout(() => window.location.href = result.redirect || 'index.php?page=dashboard', 1000);
                } else {
                    this.message = result.message || 'Login failed';
                    this.messageType = 'error';
                }
            } catch (e) {
                this.message = 'Server error. Please try again.';
                this.messageType = 'error';
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
