const apiCall = async (action, data = {}, method = 'POST') => {
    const options = {
        method: method,
        headers: { 'Content-Type': 'application/json' },
    };
    if (method === 'POST') {
        options.body = JSON.stringify({ action, ...data });
    }
    try {
        const url = `api.php${method === 'GET' ? '?action=' + action : ''}`;
        const response = await fetch(url, options);
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.message || `Sunucu hatası: ${response.status}`);
        }
        // Sunucudan {success: false, message: '...'} gibi bir yanıt gelebilir.
        // Bu bir "hata" durumu değil, "başarısız işlem" durumudur.
        // Bu yüzden bunu çağıran fonksiyona bırakıyoruz.
        return result;
    } catch (error) {
        console.error('API Call Error:', error);
        // Hatanın kullanıcıya gösterilebilmesi için yeniden fırlatıyoruz.
        throw error;
    }
}; 