const adminHandler = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        addEventListeners();
    };

    const updateDashboard = async () => {
        const result = await api.call('admin_get_dashboard_data', {}, 'POST', false);
        if (result && result.success) {
            ui.renderAdminDashboard(result.data);
        }
    };

    const updateUserList = async () => {
        const result = await api.call('admin_get_all_users', {}, 'POST', false);
        if (result && result.success) {
            const currentUser = appState.get('currentUser');
            ui.renderAdminUserList(result.data, currentUser.id);
        }
    };

    const handleUserRoleChange = async (userId, newRole) => {
        const result = await api.call('admin_update_user_role', { user_id: userId, new_role: newRole });
        ui.showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) {
            updateUserList();
        }
    };

    const handleUserDelete = async (userId, username) => {
        if (confirm(`'${username}' adlı kullanıcıyı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.`)) {
            const result = await api.call('admin_delete_user', { user_id: userId });
            ui.showToast(result.message, result.success ? 'success' : 'error');
            if (result.success) {
                updateUserList();
            }
        }
    };

    const updateAdvancedStats = async () => {
        const result = await api.call('admin_get_advanced_stats', {}, 'POST', false);
        if (result.success) {
            ui.renderAdvancedStats(result.data);
        }
    };

    const updateAll = () => {
        updateDashboard();
        updateUserList();
    };

    const addEventListeners = () => {
        if (dom.adminUserListBody) {
            dom.adminUserListBody.addEventListener('click', (e) => {
                const target = e.target;
                const userRow = target.closest('tr');
                if (!userRow) return;

                const userId = userRow.dataset.userId;

                // Rol değiştirme
                if (target.classList.contains('role-select')) {
                    // event listener'ı doğrudan select'e ekleyince daha iyi olur
                }

                // Kullanıcı silme
                if (target.closest('.delete-user-btn')) {
                    e.preventDefault();
                    const username = userRow.querySelector('td').textContent.split(' ')[0];
                    handleUserDelete(userId, username);
                }
            });

            dom.adminUserListBody.addEventListener('change', (e) => {
                if (e.target.classList.contains('role-select')) {
                    const userId = e.target.closest('tr').dataset.userId;
                    handleUserRoleChange(userId, e.target.value);
                }
            });
        }

        // Admin sekme geçişi
        dom.adminTabs?.addEventListener('click', (e) => {
            const tabButton = e.target.closest('.admin-tab-button');
            if (!tabButton) return;

            const tab = tabButton.dataset.tab;
            ui.showAdminTab(tab);
            if (tab === 'announcements') {
                // Bu anons handler'a taşınmalı veya oradan çağırılmalı
                // Şimdilik burada bırakıyorum ama en iyi pratik değil
                announcementHandler.updateAnnouncementsList();
            } else if (tab === 'stats') {
                updateAdvancedStats();
            }
        });
    };

    return {
        init,
        updateAll,
        updateAdvancedStats
    };
})();