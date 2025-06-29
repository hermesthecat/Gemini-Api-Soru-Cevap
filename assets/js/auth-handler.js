const auth = {
    init(dom, ui) {
        this.dom = dom;
        this.ui = ui;
        this.addEventListeners();
    },

    addEventListeners() {
        this.dom.loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const result = await api.call('login', {
                username: e.target.elements['login-username'].value,
                password: e.target.elements['login-password'].value
            });

            if (result && result.success) {
                this.ui.showToast('Giriş başarılı, hoş geldiniz!', 'success');
                // Başarılı girişi ana uygulamaya bildir
                document.dispatchEvent(new CustomEvent('loginSuccess', { detail: result }));
            } else if (result && result.message) {
                // Sunucudan gelen özel hata mesajlarını göster (örn. "Şifre hatalı")
                this.ui.showToast(result.message, 'error');
            }
        });

        this.dom.registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const result = await api.call('register', {
                username: e.target.elements['register-username'].value,
                password: e.target.elements['register-password'].value
            });

            if (result && result.success) {
                this.ui.showToast(result.message, 'success');
                this.dom.showLoginBtn.click(); // Kayıt sonrası giriş sekmesini göster
            } else if (result && result.message) {
                this.ui.showToast(result.message, 'error');
            }
        });

        this.dom.logoutBtn.addEventListener('click', async () => {
            const result = await api.call('logout');
            if (result && result.success) {
                // Başarılı çıkışı ana uygulamaya bildir
                document.dispatchEvent(new Event('logoutSuccess'));
                this.ui.showToast('Başarıyla çıkış yapıldı.', 'success');
            }
            // Hata durumu zaten api.call tarafından yönetilir.
        });

        // Form geçiş butonları
        this.dom.showLoginBtn.addEventListener('click', () => {
            this.dom.loginForm.classList.remove('hidden');
            this.dom.registerForm.classList.add('hidden');
            this.dom.showLoginBtn.classList.add('border-blue-500', 'text-blue-500');
            this.dom.showRegisterBtn.classList.remove('border-blue-500', 'text-blue-500');
        });
        this.dom.showRegisterBtn.addEventListener('click', () => {
            this.dom.loginForm.classList.add('hidden');
            this.dom.registerForm.classList.remove('hidden');
            this.dom.showLoginBtn.classList.remove('border-blue-500', 'text-blue-500');
            this.dom.showRegisterBtn.classList.add('border-blue-500', 'text-blue-500');
        });
    }
}; 