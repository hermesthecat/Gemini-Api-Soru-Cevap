const ui = {
    dom: {}, // Will be populated by init
    init(domElements) {
        this.dom = domElements;
    },
    showView(viewName) {
        this.dom.authView.classList.add('hidden');
        this.dom.mainView.classList.add('hidden');
        this.dom.adminView.classList.add('hidden');
        if (viewName === 'auth') this.dom.authView.classList.remove('hidden');
        else if (viewName === 'main') this.dom.mainView.classList.remove('hidden');
        else if (viewName === 'admin') this.dom.adminView.classList.remove('hidden');
    },
    showLoading(isLoading, text = 'Yükleniyor...') {
        this.dom.loadingOverlay.classList.toggle('hidden', !isLoading);
        if (isLoading) this.dom.loadingOverlay.querySelector('#loading-text').textContent = text;
    },
    showToast(message, type = 'success', duration = 3000) {
        this.dom.notificationText.textContent = message;
        this.dom.notificationToast.className = `fixed bottom-5 right-5 text-white py-2 px-4 rounded-lg shadow-lg text-sm ${type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-yellow-500'}`;
        this.dom.notificationToast.classList.remove('hidden');
        setTimeout(() => this.dom.notificationToast.classList.add('hidden'), duration);
    }
}; 