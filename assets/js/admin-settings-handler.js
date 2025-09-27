const adminSettingsHandler = (() => {
    let dom = {};

    const init = (domElements = {}) => {
        dom = domElements;
        addEventListeners();
    };

    const addEventListeners = () => {
        const settingsForm = document.getElementById('settings-form');
        if (settingsForm) {
            settingsForm.addEventListener('submit', handleSettingsSubmit);
        }

        // API Key management
        const addApiKeyBtn = document.getElementById('add-api-key-btn');
        const saveApiKeyBtn = document.getElementById('save-api-key-btn');
        const cancelApiKeyBtn = document.getElementById('cancel-api-key-btn');

        if (addApiKeyBtn) {
            addApiKeyBtn.addEventListener('click', showAddApiKeyForm);
        }
        if (saveApiKeyBtn) {
            saveApiKeyBtn.addEventListener('click', handleSaveApiKey);
        }
        if (cancelApiKeyBtn) {
            cancelApiKeyBtn.addEventListener('click', hideAddApiKeyForm);
        }
    };

    const loadSettings = async () => {
        try {
            const result = await api.call('get_settings', {}, 'POST', false);
            if (result && result.success) {
                populateForm(result.data);
            } else {
                ui.showToast(result?.message || 'Ayarlar yüklenirken hata oluştu', 'error');
            }
        } catch (error) {
            ui.showToast('Ayarlar yüklenirken hata oluştu', 'error');
        }

        // Load API keys
        loadApiKeys();
    };

    const loadApiKeys = async () => {
        try {
            const result = await api.call('get_api_keys', {}, 'POST', false);
            if (result && result.success) {
                renderApiKeys(result.data);
            } else {
            }
        } catch (error) {
        }
    };

    const populateForm = (settings) => {
        // Gemini API Key
        const geminiApiKeyInput = document.getElementById('gemini-api-key');
        if (geminiApiKeyInput && settings.gemini_api_key) {
            geminiApiKeyInput.value = settings.gemini_api_key.value || '';
        }

        // Gemini Model
        const geminiModelInput = document.getElementById('gemini-model');
        if (geminiModelInput && settings.gemini_model) {
            geminiModelInput.value = settings.gemini_model.value || 'gemini-1.5-flash';
        }

        // Site Name
        const siteNameInput = document.getElementById('site-name');
        if (siteNameInput && settings.site_name) {
            siteNameInput.value = settings.site_name.value || '';
        }

        // Registration Enabled
        const registrationCheckbox = document.getElementById('registration-enabled');
        if (registrationCheckbox && settings.registration_enabled) {
            registrationCheckbox.checked = settings.registration_enabled.value === '1';
        }

        // Timezone Setting
        const timezoneInput = document.getElementById('timezone-setting');
        if (timezoneInput && settings.timezone_setting) {
            timezoneInput.value = settings.timezone_setting.value || 'Europe/Istanbul';
        }
    };

    const handleSettingsSubmit = async (event) => {
        event.preventDefault();

        const formData = new FormData(event.target);
        const settings = {};

        // Process form data
        for (let [key, value] of formData.entries()) {
            settings[key] = value;
        }

        // Handle checkbox for registration_enabled
        if (!formData.has('registration_enabled')) {
            settings.registration_enabled = '0';
        }

        try {
            ui.showLoading('Ayarlar kaydediliyor...');
            const result = await api.call('update_settings', { settings });

            ui.showLoading(false);
            ui.showToast(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                // Optionally reload settings to confirm they were saved
                setTimeout(() => {
                    loadSettings();
                }, 1000);
            }
        } catch (error) {
            ui.showLoading(false);
            ui.showToast('Ayarlar kaydedilirken hata oluştu', 'error');
        }
    };

    const renderApiKeys = (apiKeys) => {
        const container = document.getElementById('api-keys-list');
        if (!container) return;

        if (apiKeys.length === 0) {
            container.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-sm">Henüz API anahtarı eklenmemiş.</p>';
            return;
        }

        container.innerHTML = apiKeys.map(key => `
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex-1">
                    <div class="flex items-center space-x-2">
                        <span class="font-medium text-gray-900 dark:text-white">${key.name}</span>
                        <span class="px-2 py-1 text-xs rounded-full ${key.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'}">
                            ${key.is_active ? 'Aktif' : 'Pasif'}
                        </span>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        <code class="bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded text-xs">${key.masked_key}</code>
                        <span class="ml-2">Kullanım: ${key.usage_count}</span>
                        ${key.last_used_at ? `<span class="ml-2">Son: ${new Date(key.last_used_at).toLocaleDateString('tr-TR')}</span>` : ''}
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        onclick="adminSettingsHandler.toggleApiKeyStatus(${key.id}, ${!key.is_active})"
                        class="px-3 py-1 text-xs rounded-lg transition-colors ${key.is_active ? 'bg-yellow-500 hover:bg-yellow-600 text-white' : 'bg-green-500 hover:bg-green-600 text-white'}"
                    >
                        ${key.is_active ? 'Pasifleştir' : 'Aktifleştir'}
                    </button>
                    <button
                        onclick="adminSettingsHandler.deleteApiKey(${key.id}, '${key.name}')"
                        class="px-3 py-1 text-xs bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors"
                    >
                        Sil
                    </button>
                </div>
            </div>
        `).join('');
    };

    const showAddApiKeyForm = () => {
        const form = document.getElementById('add-api-key-form');
        if (form) {
            form.classList.remove('hidden');
            document.getElementById('new-api-key-name').focus();
        }
    };

    const hideAddApiKeyForm = () => {
        const form = document.getElementById('add-api-key-form');
        if (form) {
            form.classList.add('hidden');
            document.getElementById('new-api-key-name').value = '';
            document.getElementById('new-api-key-value').value = '';
        }
    };

    const handleSaveApiKey = async () => {
        const name = document.getElementById('new-api-key-name').value.trim();
        const api_key = document.getElementById('new-api-key-value').value.trim();

        if (!name || !api_key) {
            ui.showToast('Lütfen tüm alanları doldurun', 'error');
            return;
        }

        try {
            const result = await api.call('add_api_key', { name, api_key });
            ui.showToast(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                hideAddApiKeyForm();
                loadApiKeys();
            }
        } catch (error) {
            ui.showToast('API anahtarı kaydedilirken hata oluştu', 'error');
        }
    };

    const toggleApiKeyStatus = async (id, is_active) => {
        try {
            const result = await api.call('update_api_key_status', { id, is_active });
            ui.showToast(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                loadApiKeys();
            }
        } catch (error) {
            ui.showToast('API anahtarı durumu güncellenirken hata oluştu', 'error');
        }
    };

    const deleteApiKey = async (id, name) => {
        if (!confirm(`'${name}' adlı API anahtarını silmek istediğinizden emin misiniz?`)) {
            return;
        }

        try {
            const result = await api.call('delete_api_key', { id });
            ui.showToast(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                loadApiKeys();
            }
        } catch (error) {
            ui.showToast('API anahtarı silinirken hata oluştu', 'error');
        }
    };

    return {
        init,
        loadSettings,
        toggleApiKeyStatus,
        deleteApiKey
    };
})();