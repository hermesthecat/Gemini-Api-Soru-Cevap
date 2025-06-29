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
        if(viewToShow) {
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

    const renderAchievements = (achievements, achievementData) => {
        if (!dom.achievementsList || !dom.noAchievementsMessage) return;
        
        dom.achievementsList.innerHTML = '';
        if (achievements && achievements.length > 0) {
            dom.noAchievementsMessage.classList.add('hidden');
            achievements.forEach(ach => {
                const achInfo = achievementData[ach.achievement_key];
                if (achInfo) {
                    const achElement = document.createElement('div');
                    achElement.className = `text-center p-2 bg-${achInfo.color}-100 dark:bg-${achInfo.color}-900/50 rounded-lg w-20 h-20 flex flex-col justify-center items-center`;
                    achElement.title = `${achInfo.name}: ${achInfo.description}`;
                    achElement.innerHTML = `
                        <i class="fas ${achInfo.icon} fa-2x text-${achInfo.color}-500"></i>
                        <span class="text-xs mt-1 font-semibold">${achInfo.name}</span>
                    `;
                    dom.achievementsList.appendChild(achElement);
                }
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
            li.innerHTML = `
                <div class="flex items-center">
                    <span class="font-bold w-6">${index + 1}.</span>
                    <span>${player.username}</span>
                </div>
                <span class="font-semibold text-blue-500">${player.score}</span>`;
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

            tr.innerHTML = `
                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">${user.username} ${isCurrentUser ? '<span class="text-xs text-blue-500">(Siz)</span>' : ''}</td>
                <td class="px-6 py-4">${user.score || 0}</td>
                <td class="px-6 py-4">
                    <select class="role-select bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 p-2" ${isCurrentUser ? 'disabled' : ''}>
                        <option value="user" ${user.role === 'user' ? 'selected' : ''}>User</option>
                        <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                    </select>
                </td>
                <td class="px-6 py-4">${new Date(user.created_at).toLocaleDateString()}</td>
                <td class="px-6 py-4">
                    <button class="delete-user-btn ml-2 text-red-500 hover:text-red-700 disabled:opacity-50 disabled:cursor-not-allowed" ${isCurrentUser ? 'disabled' : ''}><i class="fas fa-trash"></i></button>
                </td>
            `;
            dom.adminUserListBody.appendChild(tr);
        });
    };

    return {
        init,
        showView,
        showLoading,
        showToast,
        renderAchievements,
        renderLeaderboard,
        renderUserData,
        renderAdminDashboard,
        renderAdminUserList
    };
})(); 