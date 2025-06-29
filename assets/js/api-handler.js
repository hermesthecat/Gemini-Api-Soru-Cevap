const api = (() => {

    const call = async (action, data = {}, method = 'POST', showLoading = true) => {
        if (showLoading) {
            ui.showLoading(true);
        }

        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
        };

        // POST isteklerine CSRF token ekle
        const csrfToken = appState.get('csrfToken');
        if (method === 'POST' && csrfToken) {
            options.headers['X-CSRF-Token'] = csrfToken;
        }

        if (method === 'POST') {
            options.body = JSON.stringify({ action, ...data });
        }

        try {
            const url = `api.php${method === 'GET' ? '?action=' + action : ''}`;
            const response = await fetch(url, options);
            
            // fetch API'si 4xx/5xx gibi HTTP hatalarında exception fırlatmaz.
            // Bu yüzden response.ok durumunu kontrol etmeliyiz.
            if (!response.ok) {
                // Hatalı yanıtı JSON olarak işlemeye çalış, eğer değilse metin olarak al.
                let errorData;
                try {
                    errorData = await response.json();
                } catch (e) {
                    errorData = { message: await response.text() || 'Bilinmeyen sunucu hatası.' };
                }
                throw new Error(errorData.message || `Sunucuya ulaşılamadı: ${response.status}`);
            }

            // Başarılı yanıtı işle
            const result = await response.json();
            return result;

        } catch (error) {
            // Bu blok, ağ hatalarını (fetch başarısız oldu) veya yukarıda fırlattığımız hataları yakalar.
            console.error(`API Çağrı Hatası (${action}):`, error);
            ui.showToast(error.message, 'error');
            return { success: false, message: error.message }; // Çağıran fonksiyona standart bir hata nesnesi döndür
        } finally {
            if (showLoading) {
                ui.showLoading(false);
            }
        }
    };

    return {
        call
    };
})(); 