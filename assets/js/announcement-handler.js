const announcementHandler = (() => {
    let dom = {};
    let unreadAnnouncements = [];

    const init = (domElements) => {
        dom = domElements;
        addEventListeners();
    };

    // Helper methods to get modules with fallback
    const showToast = (message, type) => {
        const UICore = ModuleLoader?.getModule('UICore');
        if (UICore) {
            UICore.showToast(message, type);
        } else if (window.ui && window.ui.showToast) {
            window.ui.showToast(message, type);
        } else {
            console.log(`[${type}] ${message}`);
        }
    };

    const getUIAdminStats = () => {
        const UIAdminStats = ModuleLoader?.getModule('UIAdminStats');
        if (UIAdminStats) {
            return UIAdminStats;
        } else if (window.ui) {
            return window.ui;
        }
        return null;
    };

    const checkForAnnouncements = async () => {
        const result = await api.call('get_active_announcements', {}, 'POST', false);
        if (result.success && result.data.length > 0) {
            unreadAnnouncements = result.data;
            const uiAdminStats = getUIAdminStats();
            if (uiAdminStats && uiAdminStats.updateAnnouncementsBadge) {
                uiAdminStats.updateAnnouncementsBadge(unreadAnnouncements.length);
            }
            // İsteğe bağlı: Yeni duyuru varsa modalı otomatik aç
            // showAnnouncementsModal();
        } else {
            const uiAdminStats = getUIAdminStats();
            if (uiAdminStats && uiAdminStats.updateAnnouncementsBadge) {
                uiAdminStats.updateAnnouncementsBadge(0);
            }
        }
    };

    const showAnnouncementsModal = () => {
        if (unreadAnnouncements.length === 0) {
            showToast('Okunmamış yeni bir duyuru yok.', 'info');
            return;
        }
        const uiAdminStats = getUIAdminStats();
        if (uiAdminStats && uiAdminStats.renderAnnouncementsModal) {
            uiAdminStats.renderAnnouncementsModal(unreadAnnouncements);
        }
        if (uiAdminStats && uiAdminStats.showAnnouncementsModal) {
            uiAdminStats.showAnnouncementsModal(true);
        }
    };

    const markAsRead = async () => {
        const idsToMark = unreadAnnouncements.map(ann => ann.id);
        if (idsToMark.length === 0) return;

        await api.call('mark_announcements_as_read', { ids: idsToMark });

        unreadAnnouncements = [];
        const uiAdminStats = getUIAdminStats();
        if (uiAdminStats && uiAdminStats.updateAnnouncementsBadge) {
            uiAdminStats.updateAnnouncementsBadge(0);
        }
        if (uiAdminStats && uiAdminStats.showAnnouncementsModal) {
            uiAdminStats.showAnnouncementsModal(false);
        }
    };

    // --- Admin Functions ---
    const updateAnnouncementsList = async () => {
        const currentUser = appState.get('currentUser');

        if (!currentUser || currentUser.role !== 'admin') {
            return;
        }

        try {
            const result = await api.call('admin_get_announcements', {}, 'POST', false);

            if (result.success) {
                ui.renderAdminAnnouncementsList(result.data);
            } else {
                // API error occurred
            }
        } catch (error) {
            // Error handled silently
        }
    };

    const handleCreateAnnouncement = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        const result = await api.call('admin_create_announcement', data);
        showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) {
            e.target.reset();
            updateAnnouncementsList();
        }
    };

    const handleDeleteAnnouncement = async (announcementId) => {
        if (confirm('Bu duyuruyu silmek istediğinizden emin misiniz?')) {
            const result = await api.call('admin_delete_announcement', { announcement_id: announcementId });
            showToast(result.message, result.success ? 'success' : 'error');
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