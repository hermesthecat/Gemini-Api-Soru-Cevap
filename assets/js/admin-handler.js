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
            const currentUserId = currentUser ? currentUser.id : null;
            ui.renderAdminUserList(result.data, currentUserId);
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

                // Jeton düzenleme
                if (target.closest('.coin-edit')) {
                    e.preventDefault();
                    const coinElement = target.closest('.coin-edit');
                    const userId = coinElement.dataset.userId;
                    const username = coinElement.dataset.username;
                    const currentCoins = coinElement.dataset.coins;
                    showCoinUpdateModal(userId, username, currentCoins);
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

        // Modal event listeners
        setupCoinModalEventListeners();

        // Quest refresh button
        setupQuestRefreshEventListener();
    };

    const showCoinUpdateModal = (userId, username, currentCoins) => {
        const modal = document.getElementById('coin-update-modal');
        const usernameSpan = document.getElementById('coin-modal-username');
        const currentCoinsSpan = document.getElementById('coin-modal-current');
        const input = document.getElementById('coin-modal-input');

        if (modal && usernameSpan && currentCoinsSpan && input) {
            usernameSpan.textContent = username;
            currentCoinsSpan.textContent = currentCoins;
            input.value = currentCoins;

            // Store userId for later use
            modal.dataset.userId = userId;

            modal.classList.remove('hidden');
            input.focus();
            input.select();
        }
    };

    const setupCoinModalEventListeners = () => {
        const modal = document.getElementById('coin-update-modal');
        const closeBtn = document.getElementById('coin-modal-close');
        const cancelBtn = document.getElementById('coin-modal-cancel');
        const saveBtn = document.getElementById('coin-modal-save');
        const input = document.getElementById('coin-modal-input');

        // Close modal
        [closeBtn, cancelBtn].forEach(btn => {
            btn?.addEventListener('click', () => {
                modal?.classList.add('hidden');
            });
        });

        // Close on backdrop click
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });

        // Save button
        saveBtn?.addEventListener('click', async () => {
            const userId = modal?.dataset.userId;
            const newCoins = parseInt(input?.value) || 0;

            if (userId && newCoins >= 0) {
                await handleCoinUpdate(userId, newCoins);
                modal?.classList.add('hidden');
            }
        });

        // Enter key to save
        input?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                saveBtn?.click();
            }
        });
    };

    const handleCoinUpdate = async (userId, newCoins) => {
        try {
            const result = await api.call('admin_update_user_coins', {
                user_id: userId,
                new_coins: newCoins
            });

            ui.showToast(result.message, result.success ? 'success' : 'error');

            if (result.success) {
                updateUserList();
            }
        } catch (error) {
            ui.showToast('Bir hata oluştu', 'error');
        }
    };

    const setupQuestRefreshEventListener = () => {
        const refreshBtn = document.getElementById('refresh-all-quests-btn');
        const statusDiv = document.getElementById('quest-refresh-status');

        refreshBtn?.addEventListener('click', async () => {
            try {
                // Butonu deaktif et ve yükleme durumunu göster
                refreshBtn.disabled = true;
                refreshBtn.textContent = 'Quest\'ler yenileniyor...';
                statusDiv?.classList.remove('hidden');
                statusDiv.textContent = 'İşlem başlatıldı...';

                const result = await api.call('refresh_quests', {}, 'POST', false);

                if (result.success) {
                    const message = `Başarılı! ${result.assigned_count || 0} yeni quest atandı`;
                    ui.showToast(message, 'success');
                    statusDiv.textContent = message;
                } else {
                    const message = result.message || 'Quest yenileme başarısız';
                    ui.showToast(message, 'warning');
                    statusDiv.textContent = message;
                }
            } catch (error) {
                ui.showToast('Quest yenilenirken hata oluştu', 'error');
                statusDiv.textContent = 'Hata oluştu';
            } finally {
                // Butonu tekrar aktif et
                refreshBtn.disabled = false;
                refreshBtn.textContent = 'Tüm Kullanıcıların Questlerini Yenile';

                // Status mesajını 5 saniye sonra gizle
                setTimeout(() => {
                    statusDiv?.classList.add('hidden');
                }, 5000);
            }
        });
    };

    return {
        init,
        updateAll,
        updateAdvancedStats,
        loadAdvancedStats: updateAdvancedStats
    };
})();