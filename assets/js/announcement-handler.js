const announcementHandler = (() => {
    let dom = {};
    let unreadAnnouncements = [];

    const init = (domElements) => {
        dom = domElements;
        addEventListeners();
    };

    const checkForAnnouncements = async () => {
        const result = await api.call('get_active_announcements', {}, 'POST', false);
        if (result.success && result.data.length > 0) {
            unreadAnnouncements = result.data;
            ui.updateAnnouncementsBadge(unreadAnnouncements.length);
            // İsteğe bağlı: Yeni duyuru varsa modalı otomatik aç
            // showAnnouncementsModal(); 
        } else {
            ui.updateAnnouncementsBadge(0);
        }
    };

    const showAnnouncementsModal = () => {
        if (unreadAnnouncements.length === 0) {
            ui.showToast('Okunmamış yeni bir duyuru yok.', 'info');
            return;
        }
        ui.renderAnnouncementsModal(unreadAnnouncements);
        ui.showAnnouncementsModal(true);
    };

    const markAsRead = async () => {
        const idsToMark = unreadAnnouncements.map(ann => ann.id);
        if (idsToMark.length === 0) return;

        await api.call('mark_announcements_as_read', { ids: idsToMark });

        unreadAnnouncements = [];
        ui.updateAnnouncementsBadge(0);
        ui.showAnnouncementsModal(false);
    };

    // --- Admin Functions ---
    const updateAnnouncementsList = async () => {
        console.log('updateAnnouncementsList çağrıldı');

        const currentUser = appState.get('currentUser');
        console.log('currentUser:', currentUser);

        if (!currentUser || currentUser.role !== 'admin') {
            console.log('Admin olmayan kullanıcı, çıkılıyor');
            return;
        }

        console.log('Admin kullanıcı tespit edildi, API çağrısı yapılıyor...');

        try {
            const result = await api.call('admin_get_announcements', {}, 'POST', false);
            console.log('API sonucu:', result);

            if (result.success) {
                console.log('API başarılı, veri sayısı:', result.data.length);
                ui.renderAdminAnnouncementsList(result.data);
                console.log('UI render tamamlandı');
            } else {
                console.error('API hatası:', result.message);
            }
        } catch (error) {
            console.error('updateAnnouncementsList hatası:', error);
        }
    };

    const handleCreateAnnouncement = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        const result = await api.call('admin_create_announcement', data);
        ui.showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) {
            e.target.reset();
            updateAnnouncementsList();
        }
    };

    const handleDeleteAnnouncement = async (announcementId) => {
        if (confirm('Bu duyuruyu silmek istediğinizden emin misiniz?')) {
            const result = await api.call('admin_delete_announcement', { announcement_id: announcementId });
            ui.showToast(result.message, result.success ? 'success' : 'error');
            if (result.success) {
                updateAnnouncementsList();
            }
        }
    };

    const addEventListeners = () => {
        // User events
        dom.announcementsBtn?.addEventListener('click', showAnnouncementsModal);
        dom.announcementModalCloseBtn?.addEventListener('click', () => ui.showAnnouncementsModal(false));
        dom.announcementModalOkBtn?.addEventListener('click', markAsRead);

        // Admin events
        dom.createAnnouncementForm?.addEventListener('submit', handleCreateAnnouncement);
        dom.announcementsListBody?.addEventListener('click', (e) => {
            const deleteBtn = e.target.closest('.delete-announcement-btn');
            if (deleteBtn) {
                handleDeleteAnnouncement(deleteBtn.dataset.id);
            }
        });
    };

    return {
        init,
        checkForAnnouncements,
        updateAnnouncementsList
    };
})(); 