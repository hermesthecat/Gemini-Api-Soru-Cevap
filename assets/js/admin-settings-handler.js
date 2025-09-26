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
            console.error('Settings load error:', error);
            ui.showToast('Ayarlar yüklenirken hata oluştu', 'error');
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

            ui.hideLoading();
            ui.showToast(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                // Optionally reload settings to confirm they were saved
                setTimeout(() => {
                    loadSettings();
                }, 1000);
            }
        } catch (error) {
            ui.hideLoading();
            console.error('Settings update error:', error);
            ui.showToast('Ayarlar kaydedilirken hata oluştu', 'error');
        }
    };

    return {
        init,
        loadSettings
    };
})();