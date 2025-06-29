const adminHandler = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        addEventListeners();
    };

    const updateDashboard = async () => {
        try {
            const result = await api.call('admin_get_dashboard_data');
            if (result && result.success) {
                ui.renderAdminDashboard(result.data);
            }
        } catch (error) {
            ui.showToast(`Admin verileri alınamadı: ${error.message}`, 'error');
        }
    };

    const updateUserList = async () => {
        try {
            const result = await api.call('admin_get_all_users');
            if (result && result.success) {
                const currentUser = appState.get('currentUser');
                ui.renderAdminUserList(result.data, currentUser.id);
            }
        } catch (error) {
            ui.showToast(`Kullanıcı listesi alınamadı: ${error.message}`, 'error');
        }
    };

    const handleUserRoleChange = async (userId, newRole) => {
        try {
            const result = await api.call('admin_update_user_role', { user_id: userId, new_role: newRole });
            ui.showToast(result.message, result.success ? 'success' : 'error');
            if (result.success) {
                updateUserList(); // Listeyi yenile
            }
        } catch (error) {
            ui.showToast(`Rol güncellenemedi: ${error.message}`, 'error');
        }
    };

    const handleUserDelete = async (userId, username) => {
        if (confirm(`'${username}' adlı kullanıcıyı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.`)) {
            try {
                const result = await api.call('admin_delete_user', { user_id: userId });
                ui.showToast(result.message, result.success ? 'success' : 'error');
                if (result.success) {
                    updateUserList(); // Listeyi yenile
                }
            } catch (error) {
                ui.showToast(`Kullanıcı silinemedi: ${error.message}`, 'error');
            }
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
    };

    return {
        init,
        updateAll
    };
})(); 