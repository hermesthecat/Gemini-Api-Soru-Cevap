const auth = {
    init(dom) {
        this.dom = dom;
        this.addEventListeners();
    },

    // Helper method to get UICore module with fallback
    showToast(message, type) {
        const UICore = ModuleLoader?.getModule('UICore');
        if (UICore) {
            UICore.showToast(message, type);
        } else if (this.ui && this.ui.showToast) {
            // Fallback to legacy ui-handler
            this.ui.showToast(message, type);
        } else {
            // Final fallback to console
            console.log(`[${type}] ${message}`);
        }
    },

    addEventListeners() {
        // Login form sadece varsa event listener ekle
        if (this.dom.loginForm) {
            this.dom.loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const result = await api.call('login', {
                username: e.target.elements['login-username'].value,
                password: e.target.elements['login-password'].value
            });

            if (result && result.success) {
                // CSRF token'ı hemen set et
                if (result.data && result.data.csrf_token && window.appState) {
                    window.appState.set('csrfToken', result.data.csrf_token);
                }

                this.showToast('Giriş başarılı, hoş geldiniz!', 'success');
                // Başarılı girişi ana uygulamaya bildir
                document.dispatchEvent(new CustomEvent('loginSuccess', { detail: result }));
                // MPA'da ana sayfaya redirect
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1000);
            } else if (result && result.message) {
                // Sunucudan gelen özel hata mesajlarını göster (örn. "Şifre hatalı")
                this.showToast(result.message, 'error');
            }
            });
        }

        // Register form sadece varsa event listener ekle
        if (this.dom.registerForm) {
            this.dom.registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const result = await api.call('register', {
                username: e.target.elements['register-username'].value,
                password: e.target.elements['register-password'].value
            });

            if (result && result.success) {
                this.showToast(result.message, 'success');
                this.dom.showLoginBtn.click(); // Kayıt sonrası giriş sekmesini göster
            } else if (result && result.message) {
                this.showToast(result.message, 'error');
            }
            });
        }

        // Logout button sadece varsa event listener ekle
        if (this.dom.logoutBtn) {
            this.dom.logoutBtn.addEventListener('click', async () => {
            const result = await api.call('logout');
            if (result && result.success) {
                // Başarılı çıkışı ana uygulamaya bildir
                document.dispatchEvent(new Event('logoutSuccess'));
                this.showToast('Başarıyla çıkış yapıldı.', 'success');
            }
            // Hata durumu zaten api.call tarafından yönetilir.
            });
        }

        // Form geçiş butonları sadece varsa event listener ekle
        if (this.dom.showLoginBtn) {
            this.dom.showLoginBtn.addEventListener('click', () => {
            this.dom.loginForm.classList.remove('hidden');
            this.dom.registerForm.classList.add('hidden');
            this.dom.showLoginBtn.classList.add('border-blue-500', 'text-blue-500');
            this.dom.showRegisterBtn.classList.remove('border-blue-500', 'text-blue-500');
            });
        }

        if (this.dom.showRegisterBtn) {
            this.dom.showRegisterBtn.addEventListener('click', () => {
            this.dom.loginForm.classList.add('hidden');
            this.dom.registerForm.classList.remove('hidden');
            this.dom.showLoginBtn.classList.remove('border-blue-500', 'text-blue-500');
            this.dom.showRegisterBtn.classList.add('border-blue-500', 'text-blue-500');
            });
        }
    },

    async checkUserSession() {
        try {
            // Token yoksa direkt başarısız dön (login sayfasında token olmaz)
            const csrfToken = (window.appState && window.appState.get ? window.appState.get('csrfToken') : null) || window.CSRF_TOKEN;
            if (!csrfToken) {
                return { success: false };
            }

            const result = await api.call('check_session');
            return result;
        } catch (error) {
            return { success: false };
        }
    }
}; 