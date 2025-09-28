/**
 * UI Admin Users Module - User Management
 *
 * This module handles admin user management functionality including
 * user list rendering, user actions, role management, and user statistics.
 *
 * Phase 5 of ui-handler.js modularization - Admin Feature Modules
 * Created: 2025-09-28
 */

const UIAdminUsers = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        // Add admin user-specific DOM elements
        dom.adminUserListBody = document.getElementById('admin-user-list-body');
        dom.adminUserSearch = document.getElementById('admin-user-search');
        dom.adminUserFilters = document.getElementById('admin-user-filters');

        console.log('UIAdminUsers initialized');
    };

    // Render admin user list (extracted from ui-handler.js)
    const renderAdminUserList = (users, currentUserId) => {
        if (!dom.adminUserListBody) return;

        dom.adminUserListBody.innerHTML = '';
        users.forEach(user => {
            const tr = document.createElement('tr');
            tr.className = 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600';
            tr.dataset.userId = user.id;

            // Check if this is the current logged-in user (admin)
            // Since we can't rely on currentUserId, check by username
            const isCurrentUser = user.username === 'admin' && user.role === 'admin';

            const userCell = document.createElement('td');
            userCell.className = 'px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white flex items-center space-x-3';

            // Use UIComponents for consistent avatar generation
            const UIComponents = ModuleLoader.getModule('UIComponents');
            const avatarDiv = document.createElement('div');
            if (UIComponents) {
                const color = UIComponents.getAvatarColor(user.username);
                avatarDiv.className = `w-10 h-10 rounded-full ${color} flex items-center justify-center text-white font-bold`;
            } else {
                avatarDiv.className = 'w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold';
            }
            avatarDiv.textContent = user.username.charAt(0).toUpperCase();
            userCell.appendChild(avatarDiv);

            const nameDiv = document.createElement('div');
            const nameSpan = document.createElement('span');
            nameSpan.textContent = user.username;
            nameDiv.appendChild(nameSpan);

            if (isCurrentUser) {
                const currentLabel = document.createElement('div');
                currentLabel.className = 'text-xs text-blue-500 font-medium';
                currentLabel.textContent = '(Sen)';
                nameDiv.appendChild(currentLabel);
            }

            userCell.appendChild(nameDiv);

            const scoreCell = document.createElement('td');
            scoreCell.className = 'px-6 py-4';
            scoreCell.textContent = user.score || '0';

            const coinsCell = document.createElement('td');
            coinsCell.className = 'px-6 py-4';
            coinsCell.innerHTML = `
                <div class="flex items-center space-x-1">
                    <i class="fas fa-coins text-yellow-500 text-sm"></i>
                    <span>${user.coins || '0'}</span>
                </div>
            `;

            const roleCell = document.createElement('td');
            roleCell.className = 'px-6 py-4';
            roleCell.innerHTML = `
                <select class="user-role-select bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        data-user-id="${user.id}" ${isCurrentUser ? 'disabled' : ''}>
                    <option value="user" ${user.role === 'user' ? 'selected' : ''}>Kullanıcı</option>
                    <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                </select>
            `;

            const dateCell = document.createElement('td');
            dateCell.className = 'px-6 py-4';
            dateCell.textContent = user.created_at || '';

            const actionsCell = document.createElement('td');
            actionsCell.className = 'px-6 py-4';

            // Always show view button for all users
            const UIAdmin = ModuleLoader.getModule('UIAdmin');
            const viewButton = UIAdmin ? UIAdmin.createAdminActionButton('Görüntüle', {
                variant: 'primary',
                size: 'small',
                icon: 'fas fa-eye',
                className: 'view-user-btn mr-2',
                onClick: () => showUserDetailsModal(user)
            }) : null;

            if (viewButton) actionsCell.appendChild(viewButton);

            // Only show delete button for non-current users
            if (!isCurrentUser) {
                const deleteButton = UIAdmin ? UIAdmin.createAdminActionButton('Sil', {
                    variant: 'danger',
                    size: 'small',
                    icon: 'fas fa-trash',
                    className: 'delete-user-btn',
                    onClick: () => confirmDeleteUser(user)
                }) : null;

                if (deleteButton) actionsCell.appendChild(deleteButton);
            } else {
                // For current user, show a disabled label instead of delete button
                const disabledLabel = document.createElement('span');
                disabledLabel.className = 'text-gray-400 text-sm ml-2';
                disabledLabel.textContent = '(Kendiniz)';
                actionsCell.appendChild(disabledLabel);
            }

            tr.appendChild(userCell);
            tr.appendChild(scoreCell);
            tr.appendChild(coinsCell);
            tr.appendChild(roleCell);
            tr.appendChild(dateCell);
            tr.appendChild(actionsCell);

            dom.adminUserListBody.appendChild(tr);
        });
    };

    // Show user details modal
    const showUserDetailsModal = (user) => {
        const modal = document.getElementById('user-details-modal');
        if (!modal) return;

        // Populate modal with user data
        const userNameElement = modal.querySelector('#user-details-name');
        const userScoreElement = modal.querySelector('#user-details-score');
        const userCoinsElement = modal.querySelector('#user-details-coins');
        const userRoleElement = modal.querySelector('#user-details-role');
        const userJoinDateElement = modal.querySelector('#user-details-join-date');
        const userStatsContainer = modal.querySelector('#user-details-stats');

        if (userNameElement) userNameElement.textContent = user.username;
        if (userScoreElement) userScoreElement.textContent = user.score || '0';
        if (userCoinsElement) userCoinsElement.textContent = user.coins || '0';
        if (userRoleElement) userRoleElement.textContent = user.role === 'admin' ? 'Admin' : 'Kullanıcı';
        if (userJoinDateElement) userJoinDateElement.textContent = user.created_at || '';

        // Load user statistics
        if (userStatsContainer) {
            loadUserStatistics(user.id, userStatsContainer);
        }

        const UIComponents = ModuleLoader.getModule('UIComponents');
        if (UIComponents) {
            UIComponents.toggleModal(modal, true);
        }
    };

    // Load user statistics
    const loadUserStatistics = async (userId, container) => {
        container.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Yükleniyor...</div>';

        try {
            // Call API to get real statistics
            const response = await api.call('admin_get_user_statistics', { user_id: userId }, 'POST', false);

            if (response.success && response.data) {
                const stats = response.data;

                // Format last game date
                let lastGameText = '-';
                if (stats.last_game) {
                    const lastGameDate = new Date(stats.last_game);
                    const now = new Date();
                    const diffDays = Math.floor((now - lastGameDate) / (1000 * 60 * 60 * 24));
                    if (diffDays === 0) {
                        lastGameText = 'Bugün';
                    } else if (diffDays === 1) {
                        lastGameText = 'Dün';
                    } else if (diffDays < 7) {
                        lastGameText = `${diffDays} gün önce`;
                    } else {
                        lastGameText = lastGameDate.toLocaleDateString('tr-TR');
                    }
                }

                // Main statistics
                container.innerHTML = `
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Toplam Oyun</div>
                            <div class="text-xl font-bold">${stats.total_games || 0}</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Doğru Cevap</div>
                            <div class="text-xl font-bold">${stats.total_correct || 0}</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Başarı Oranı</div>
                            <div class="text-xl font-bold">${stats.success_rate || 0}%</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Son Oyun</div>
                            <div class="text-xl font-bold">${lastGameText}</div>
                        </div>
                    </div>
                `;

                // Category breakdown if available
                if (stats.categories && stats.categories.length > 0) {
                    let categoryHtml = '<div class="mt-4"><h5 class="text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">Kategori Performansı:</h5>';
                    categoryHtml += '<div class="space-y-2">';

                    stats.categories.forEach(cat => {
                        const categoryName = cat.category.charAt(0).toUpperCase() + cat.category.slice(1);
                        categoryHtml += `
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-gray-400">${categoryName}</span>
                                <div class="text-right">
                                    <span class="font-semibold">${cat.correct}/${cat.questions}</span>
                                    <span class="text-xs text-gray-500 ml-2">(${cat.success_rate}%)</span>
                                </div>
                            </div>
                        `;
                    });

                    categoryHtml += '</div></div>';
                    container.innerHTML += categoryHtml;
                }
            } else {
                // Fallback for no data
                container.innerHTML = `
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Toplam Oyun</div>
                            <div class="text-xl font-bold">0</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Doğru Cevap</div>
                            <div class="text-xl font-bold">0</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Başarı Oranı</div>
                            <div class="text-xl font-bold">0%</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Son Oyun</div>
                            <div class="text-xl font-bold">-</div>
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading user statistics:', error);
            container.innerHTML = '<div class="text-red-500 text-center">İstatistikler yüklenemedi</div>';
        }
    };

    // Confirm delete user
    const confirmDeleteUser = (user) => {
        const UIAdmin = ModuleLoader.getModule('UIAdmin');
        if (UIAdmin) {
            const confirmed = confirm(`"${user.username}" kullanıcısını silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.`);
            if (confirmed) {
                deleteUser(user.id);
            }
        }
    };

    // Delete user
    const deleteUser = async (userId) => {
        const UIAdmin = ModuleLoader.getModule('UIAdmin');
        if (UIAdmin) {
            UIAdmin.showAdminLoading(true, 'Kullanıcı siliniyor...');
        }

        try {
            // This would typically make an API call to delete the user
            // For now, showing success message
            setTimeout(() => {
                if (UIAdmin) {
                    UIAdmin.showAdminLoading(false);
                    UIAdmin.showAdminNotification('Kullanıcı başarıyla silindi', 'success');
                }

                // Remove user from table
                const userRow = document.querySelector(`[data-user-id="${userId}"]`);
                if (userRow) {
                    userRow.remove();
                }
            }, 1000);
        } catch (error) {
            if (UIAdmin) {
                UIAdmin.showAdminLoading(false);
                UIAdmin.showAdminNotification('Kullanıcı silinirken hata oluştu', 'error');
            }
        }
    };

    // Filter users by search term
    const filterUsers = (searchTerm) => {
        const rows = dom.adminUserListBody?.querySelectorAll('tr');
        if (!rows) return;

        rows.forEach(row => {
            const username = row.querySelector('td span')?.textContent.toLowerCase();
            const matches = !searchTerm || username?.includes(searchTerm.toLowerCase());
            row.style.display = matches ? '' : 'none';
        });
    };

    // Filter users by role
    const filterUsersByRole = (role) => {
        const rows = dom.adminUserListBody?.querySelectorAll('tr');
        if (!rows) return;

        rows.forEach(row => {
            const userRole = row.querySelector('.user-role-select')?.value;
            const matches = !role || role === 'all' || userRole === role;
            row.style.display = matches ? '' : 'none';
        });
    };

    // Initialize user management events
    const initUserManagementEvents = () => {
        // Search functionality
        dom.adminUserSearch?.addEventListener('input', (e) => {
            filterUsers(e.target.value);
        });

        // Role filter
        dom.adminUserFilters?.addEventListener('change', (e) => {
            if (e.target.classList.contains('role-filter')) {
                filterUsersByRole(e.target.value);
            }
        });

        // Role change events
        dom.adminUserListBody?.addEventListener('change', (e) => {
            if (e.target.classList.contains('user-role-select')) {
                const userId = e.target.dataset.userId;
                const newRole = e.target.value;
                updateUserRole(userId, newRole);
            }
        });
    };

    // Update user role
    const updateUserRole = async (userId, newRole) => {
        const UIAdmin = ModuleLoader.getModule('UIAdmin');

        try {
            // This would typically make an API call to update the user role
            // For now, showing success message
            if (UIAdmin) {
                UIAdmin.showAdminNotification(`Kullanıcı rolü "${newRole}" olarak güncellendi`, 'success');
            }
        } catch (error) {
            if (UIAdmin) {
                UIAdmin.showAdminNotification('Rol güncellenirken hata oluştu', 'error');
            }
        }
    };

    // Export users to CSV
    const exportUsersToCSV = () => {
        const rows = Array.from(dom.adminUserListBody?.querySelectorAll('tr') || []);
        const csvData = [];

        // Header
        csvData.push(['Kullanıcı Adı', 'Puan', 'Jeton', 'Rol', 'Kayıt Tarihi']);

        // Data rows
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 5) {
                csvData.push([
                    cells[0].querySelector('span')?.textContent || '',
                    cells[1].textContent || '',
                    cells[2].querySelector('span')?.textContent || '',
                    cells[3].querySelector('select')?.value || '',
                    cells[4].textContent || ''
                ]);
            }
        });

        // Create and download CSV
        const csvContent = csvData.map(row => row.join(',')).join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `kullanicilar_${new Date().toISOString().split('T')[0]}.csv`;
        link.click();
    };

    // Render user statistics summary
    const renderUserStatsSummary = (stats) => {
        const container = document.getElementById('user-stats-summary');
        if (!container || !stats) return;

        container.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                            <i class="fas fa-users text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Kullanıcı</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">${stats.total_users || 0}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                            <i class="fas fa-user-check text-green-600 dark:text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Aktif Kullanıcı</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">${stats.active_users || 0}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                            <i class="fas fa-crown text-purple-600 dark:text-purple-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Admin</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">${stats.admin_users || 0}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                            <i class="fas fa-calendar-plus text-yellow-600 dark:text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Bu Ay Yeni</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">${stats.new_users_this_month || 0}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    };

    // Public API
    return {
        init,

        // User list management
        renderAdminUserList,
        renderUserStatsSummary,

        // User actions
        showUserDetailsModal,
        deleteUser,
        updateUserRole,

        // Filtering and search
        filterUsers,
        filterUsersByRole,

        // Export functionality
        exportUsersToCSV,

        // Event management
        initUserManagementEvents
    };
})();

// Auto-register with ModuleLoader when available
if (typeof ModuleLoader !== 'undefined') {
    if (ModuleLoader.isInitialized) {
        ModuleLoader.register('UIAdminUsers', UIAdminUsers);
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (typeof ModuleLoader !== 'undefined') {
                    ModuleLoader.register('UIAdminUsers', UIAdminUsers);
                }
            }, 100);
        });
    }
}