const ui = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
    };

    const showView = (viewId) => {
        dom.authView?.classList.add('hidden');
        dom.mainView?.classList.add('hidden');
        dom.adminView?.classList.add('hidden');

        const viewToShow = document.getElementById(viewId);
        if (viewToShow) {
            viewToShow.classList.remove('hidden');
        }
    };

    const showLoading = (show) => {
        dom.loadingOverlay?.classList.toggle('hidden', !show);
    };

    const showToast = (message, type = 'info') => {
        if (!dom.notificationToast || !dom.notificationText) return;

        dom.notificationText.textContent = message;

        const colorClasses = {
            info: 'bg-blue-500',
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500'
        };

        // Önceki renk sınıflarını kaldır
        Object.values(colorClasses).forEach(cls => dom.notificationToast.classList.remove(cls));
        // Yeni renk sınıfını ekle
        dom.notificationToast.classList.add(colorClasses[type] || colorClasses.info);

        dom.notificationToast.classList.remove('hidden');
        dom.notificationToast.classList.add('animate-toast-in');

        setTimeout(() => {
            dom.notificationToast.classList.remove('animate-toast-in');
            dom.notificationToast.classList.add('hidden');
        }, 3000);
    };

    const showTab = (tabId) => {
        // Tüm sekme içeriklerini gizle
        dom.yarışmaTab?.classList.add('hidden');
        dom.profilTab?.classList.add('hidden');

        // İlgili sekme içeriğini göster
        const tabToShow = document.getElementById(`${tabId}-tab`);
        if (tabToShow) {
            tabToShow.classList.remove('hidden');
        }

        // Tüm sekme buton stillerini sıfırla
        dom.mainTabs?.querySelectorAll('.main-tab-button').forEach(btn => {
            btn.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-500', 'dark:border-blue-500');
            btn.classList.add('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300', 'dark:hover:text-gray-300');
        });

        // Aktif sekme butonunu stillendir
        const activeButton = dom.mainTabs?.querySelector(`[data-tab="${tabId}"]`);
        if (activeButton) {
            activeButton.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-500', 'dark:border-blue-500');
            activeButton.classList.remove('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300', 'dark:hover:text-gray-300');
        }
    };

    const renderWelcomeMessage = (username) => {
        if (!dom.welcomeMessage) return;
        dom.welcomeMessage.textContent = `Hoş Geldin, ${username}!`;
    };

    const toggleAdminButton = (isAdmin) => {
        if (!dom.adminViewBtn) return;
        dom.adminViewBtn.classList.toggle('hidden', !isAdmin);
    };

    const renderAchievements = (achievements) => {
        if (!dom.achievementsList || !dom.noAchievementsMessage) return;
        
        dom.achievementsList.innerHTML = '';
        if (achievements && achievements.length > 0) {
            dom.noAchievementsMessage.classList.add('hidden');
            achievements.forEach(ach => {
                const achElement = document.createElement('div');
                achElement.className = `text-center p-2 bg-${ach.color}-100 dark:bg-${ach.color}-900/50 rounded-lg w-20 h-20 flex flex-col justify-center items-center`;
                achElement.title = `${ach.name}: ${ach.description}`;
                achElement.innerHTML = `
                    <i class="fas ${ach.icon} fa-2x text-${ach.color}-500"></i>
                    <span class="text-xs mt-1 font-semibold">${ach.name}</span>
                `;
                dom.achievementsList.appendChild(achElement);
            });
        } else {
            dom.noAchievementsMessage.classList.remove('hidden');
        }
    };

    const renderLeaderboard = (leaderboardData) => {
        if (!dom.leaderboardList || !dom.leaderboardLoading) return;

        dom.leaderboardLoading.classList.add('hidden');
        dom.leaderboardList.innerHTML = '';
        leaderboardData.forEach((player, index) => {
            const li = document.createElement('li');
            li.className = 'flex justify-between items-center text-sm p-2 rounded-md';
            
            const playerDiv = document.createElement('div');
            playerDiv.className = 'flex items-center';
            
            const rankSpan = document.createElement('span');
            rankSpan.className = 'font-bold w-6';
            rankSpan.textContent = `${index + 1}.`;
            
            const nameSpan = document.createElement('span');
            nameSpan.textContent = player.username;
            
            playerDiv.appendChild(rankSpan);
            playerDiv.appendChild(nameSpan);
            
            const scoreSpan = document.createElement('span');
            scoreSpan.className = 'font-semibold text-blue-500';
            scoreSpan.textContent = player.score;
            
            li.appendChild(playerDiv);
            li.appendChild(scoreSpan);

            if (index === 0) li.classList.add('bg-yellow-100', 'dark:bg-yellow-800/50');
            if (index === 1) li.classList.add('bg-gray-200', 'dark:bg-gray-700/50');
            if (index === 2) li.classList.add('bg-yellow-50', 'dark:bg-yellow-900/50');
            dom.leaderboardList.appendChild(li);
        });
    };

    const renderUserData = (userData) => {
        if (!dom.userTotalScore || !dom.categoryStatsBody || !dom.noStatsMessage) return;

        const { score, stats } = userData;
        dom.userTotalScore.textContent = score;

        dom.categoryStatsBody.innerHTML = '';
        if (stats && stats.length > 0) {
            dom.noStatsMessage.classList.add('hidden');
            stats.forEach(cat => {
                const rate = cat.total_questions > 0 ? Math.round((cat.correct_answers / cat.total_questions) * 100) : 0;
                const tr = document.createElement('tr');
                tr.className = 'border-b dark:border-gray-700';
                tr.innerHTML = `
                    <td class="py-2 px-2">${cat.category.charAt(0).toUpperCase() + cat.category.slice(1)}</td>
                    <td class="py-2 px-2 text-center">${cat.total_questions}</td>
                    <td class="py-2 px-2 text-center">${cat.correct_answers}</td>
                    <td class="py-2 px-2 text-center font-bold ${rate > 60 ? 'text-green-500' : 'text-yellow-500'}">${rate}%</td>`;
                dom.categoryStatsBody.appendChild(tr);
            });
        } else {
            dom.noStatsMessage.classList.remove('hidden');
        }
    };

    const renderAdminDashboard = (dashboardData) => {
        if (!dom.adminTotalUsers || !dom.adminTotalQuestions) return;
        dom.adminTotalUsers.textContent = dashboardData.total_users;
        dom.adminTotalQuestions.textContent = dashboardData.total_questions_answered;
    };

    const renderAdminUserList = (users, currentUserId) => {
        if (!dom.adminUserListBody) return;

        dom.adminUserListBody.innerHTML = '';
        users.forEach(user => {
            const tr = document.createElement('tr');
            tr.className = 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600';
            tr.dataset.userId = user.id;

            const isCurrentUser = user.id === currentUserId;

            const userCell = document.createElement('td');
            userCell.className = 'px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white';
            userCell.textContent = user.username;
            if (isCurrentUser) {
                const selfSpan = document.createElement('span');
                selfSpan.className = 'text-xs text-blue-500 ml-2';
                selfSpan.textContent = '(Siz)';
                userCell.appendChild(selfSpan);
            }

            const scoreCell = document.createElement('td');
            scoreCell.className = 'px-6 py-4';
            scoreCell.textContent = user.score || 0;

            const roleCell = document.createElement('td');
            roleCell.className = 'px-6 py-4';
            roleCell.innerHTML = `
                <select class="role-select bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 p-2" ${isCurrentUser ? 'disabled' : ''}>
                    <option value="user" ${user.role === 'user' ? 'selected' : ''}>User</option>
                    <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                </select>
            `;

            const dateCell = document.createElement('td');
            dateCell.className = 'px-6 py-4';
            dateCell.textContent = new Date(user.created_at).toLocaleDateString();

            const actionsCell = document.createElement('td');
            actionsCell.className = 'px-6 py-4';
            actionsCell.innerHTML = `
                <button class="delete-user-btn text-red-500 hover:text-red-700 disabled:opacity-50 disabled:cursor-not-allowed" ${isCurrentUser ? 'disabled' : ''} title="Kullanıcıyı Sil">
                    <i class="fas fa-trash"></i>
                </button>
            `;

            tr.appendChild(userCell);
            tr.appendChild(scoreCell);
            tr.appendChild(roleCell);
            tr.appendChild(dateCell);
            tr.appendChild(actionsCell);
            
            dom.adminUserListBody.appendChild(tr);
        });
    };

    return {
        init,
        showView,
        showLoading,
        showToast,
        showTab,
        renderWelcomeMessage,
        toggleAdminButton,
        renderAchievements,
        renderLeaderboard,
        renderUserData,
        renderAdminDashboard,
        renderAdminUserList
    };
})(); 