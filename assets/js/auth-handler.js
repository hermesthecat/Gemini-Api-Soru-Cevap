const auth = {
    init(dom, ui) {
        this.dom = dom;
        this.ui = ui;
        this.addEventListeners();
    },

    addEventListeners() {
        this.dom.loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            this.ui.showLoading(true, 'Giriş yapılıyor...');
            try {
                const result = await apiCall('login', {
                    username: e.target.elements['login-username'].value,
                    password: e.target.elements['login-password'].value
                });
                if (result && result.success) {
                    this.ui.showToast('Giriş başarılı, hoş geldiniz!', 'success');
                    // Başarılı girişi ana uygulamaya bildir
                    document.dispatchEvent(new CustomEvent('authSuccess', { detail: { user: result.data } }));
                } else {
                    this.ui.showToast(result.message || 'Giriş başarısız.', 'error');
                }
            } catch (error) {
                this.ui.showToast(error.message, 'error');
            } finally {
                this.ui.showLoading(false);
            }
        });

        this.dom.registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            this.ui.showLoading(true, 'Kayıt oluşturuluyor...');
            try {
                const result = await apiCall('register', {
                    username: e.target.elements['register-username'].value,
                    password: e.target.elements['register-password'].value
                });
                if (result && result.success) {
                    this.ui.showToast(result.message, 'success');
                    this.dom.showLoginBtn.click(); // Kayıt sonrası giriş sekmesini göster
                } else {
                    this.ui.showToast(result.message || 'Kayıt başarısız.', 'error');
                }
            } catch (error) {
                this.ui.showToast(error.message, 'error');
            } finally {
                this.ui.showLoading(false);
            }
        });

        this.dom.logoutBtn.addEventListener('click', async () => {
            try {
                await apiCall('logout');
                // Başarılı çıkışı ana uygulamaya bildir
                document.dispatchEvent(new Event('logoutSuccess'));
                this.ui.showToast('Başarıyla çıkış yapıldı.', 'success');
            } catch (error) {
                this.ui.showToast(`Çıkış yapılamadı: ${error.message}`, 'error');
            }
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