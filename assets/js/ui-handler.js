const ui = (() => {
    let dom = {};
    let charts = {}; // To store chart instances

    const init = (domElements) => {
        dom = domElements;
    };

    const showView = (viewId) => {
        dom.authView?.classList.add('hidden');
        dom.mainView?.classList.add('hidden');
        dom.adminView?.classList.add('hidden');
        dom.duelGameView?.classList.add('hidden');

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
        dom.arkadaslarTab?.classList.add('hidden');

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

        // Sekme değişimi olayını tetikle
        document.dispatchEvent(new CustomEvent('tabChanged', { detail: { tabId } }));
    };

    const showAdminTab = (tabId) => {
        dom.adminUsersTab?.classList.add('hidden');
        dom.adminAnnouncementsTab?.classList.add('hidden');
        dom.adminStatsTab?.classList.add('hidden');

        const tabToShow = document.getElementById(`admin-${tabId}-tab`);
        if (tabToShow) {
            tabToShow.classList.remove('hidden');
        }

        dom.adminTabs?.querySelectorAll('.admin-tab-button').forEach(btn => {
            btn.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-500');
            btn.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-600');
        });

        const activeButton = dom.adminTabs?.querySelector(`[data-tab="${tabId}"]`);
        if (activeButton) {
            activeButton.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-500');
            activeButton.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-600');
        }
    };

    const destroyChart = (chartName) => {
        if (charts[chartName]) {
            charts[chartName].destroy();
            delete charts[chartName];
        }
    };

    const renderAdvancedStats = (statsData) => {
        const { most_played_categories, new_users_last_7_days, answer_distribution } = statsData;

        // Destroy existing charts to prevent duplicates
        destroyChart('categories');
        destroyChart('answers');
        destroyChart('users');

        // Chart 1: Most Played Categories (Bar Chart)
        if (dom.categoryChart && most_played_categories) {
            const ctx = dom.categoryChart.getContext('2d');
            charts.categories = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: most_played_categories.map(c => c.category),
                    datasets: [{
                        label: 'Oynanma Sayısı',
                        data: most_played_categories.map(c => c.play_count),
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: { y: { beginAtZero: true } },
                    responsive: true
                }
            });
        }

        // Chart 2: Answer Distribution (Doughnut Chart)
        if (dom.answersChart && answer_distribution) {
            const ctx = dom.answersChart.getContext('2d');
            charts.answers = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: answer_distribution.map(d => `${d.difficulty} (Doğru/Yanlış)`),
                    datasets: [{
                        label: 'Cevaplar',
                        data: answer_distribution.flatMap(d => [d.correct, d.incorrect]),
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.7)', // green-500
                            'rgba(239, 68, 68, 0.7)',  // red-500
                            'rgba(245, 158, 11, 0.7)', // amber-500
                            'rgba(239, 68, 68, 0.5)',  // red-500/50
                            'rgba(99, 102, 241, 0.7)', // indigo-500
                            'rgba(239, 68, 68, 0.3)'   // red-500/30
                        ],
                    }]
                },
                options: { responsive: true }
            });
        }

        // Chart 3: New Users (Line Chart)
        if (dom.usersChart && new_users_last_7_days) {
            const ctx = dom.usersChart.getContext('2d');
            charts.users = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: new_users_last_7_days.map(u => u.registration_date),
                    datasets: [{
                        label: 'Yeni Kullanıcı Sayısı',
                        data: new_users_last_7_days.map(u => u.user_count),
                        backgroundColor: 'rgba(139, 92, 246, 0.2)',
                        borderColor: 'rgba(139, 92, 246, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.1
                    }]
                },
                options: {
                    scales: { y: { beginAtZero: true } },
                    responsive: true
                }
            });
        }
    };

    const renderWelcomeMessage = (username, avatar) => {
        if (!dom.welcomeMessage) return;
        dom.welcomeMessage.textContent = `Hoş Geldin, ${username}!`;
        if (dom.userAvatarDisplay) {
            dom.userAvatarDisplay.src = `assets/images/avatars/${avatar}`;
        }
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
                const isAchieved = ach.achieved_at !== null;
                const achElement = document.createElement('div');

                achElement.className = `w-full flex items-start p-4 rounded-lg transition-colors duration-200 ${isAchieved ? 'bg-yellow-50 dark:bg-yellow-900/50' : 'bg-gray-100 dark:bg-gray-800/60'}`;

                const iconContainer = document.createElement('div');
                iconContainer.className = `flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-full mr-4 ${isAchieved ? `bg-${ach.color}-100 dark:bg-${ach.color}-900` : 'bg-gray-200 dark:bg-gray-700'}`;

                const icon = document.createElement('i');
                icon.className = `fas ${isAchieved ? ach.icon : 'fa-lock'} fa-lg ${isAchieved ? `text-${ach.color}-500` : 'text-gray-400'}`;

                iconContainer.appendChild(icon);

                const textContainer = document.createElement('div');
                textContainer.className = 'flex-grow';

                const name = document.createElement('h4');
                name.className = `font-bold ${isAchieved ? 'text-gray-800 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'}`;
                name.textContent = ach.name;

                const description = document.createElement('p');
                description.className = `text-sm ${isAchieved ? 'text-gray-600 dark:text-gray-400' : 'text-gray-400 dark:text-gray-500'}`;
                description.textContent = ach.description;

                const date = document.createElement('p');
                if (isAchieved) {
                    date.className = 'text-xs text-gray-500 dark:text-gray-500 mt-1';
                    date.textContent = `Kazanıldı: ${new Date(ach.achieved_at).toLocaleDateString()}`;
                }

                textContainer.appendChild(name);
                textContainer.appendChild(description);
                if (isAchieved) textContainer.appendChild(date);

                achElement.appendChild(iconContainer);
                achElement.appendChild(textContainer);

                dom.achievementsList.appendChild(achElement);
            });
        } else {
            dom.noAchievementsMessage.classList.remove('hidden');
            dom.noAchievementsMessage.textContent = 'Gösterilecek başarım bulunamadı.';
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
            playerDiv.className = 'flex items-center space-x-2';

            const rankSpan = document.createElement('span');
            rankSpan.className = 'font-bold w-6 text-center';
            rankSpan.textContent = `${index + 1}.`;

            const avatarImg = document.createElement('img');
            avatarImg.src = `assets/images/avatars/${player.avatar}`;
            avatarImg.alt = player.username;
            avatarImg.className = 'w-8 h-8 rounded-full';

            const nameSpan = document.createElement('span');
            nameSpan.textContent = player.username;

            playerDiv.appendChild(rankSpan);
            playerDiv.appendChild(avatarImg);
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

        const { score, stats, coins } = userData;
        dom.userTotalScore.textContent = score;
        if (dom.userCoinBalance) dom.userCoinBalance.textContent = coins;

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
            userCell.className = 'px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white flex items-center space-x-3';

            const avatarImg = document.createElement('img');
            avatarImg.src = `assets/images/avatars/${user.avatar}`;
            avatarImg.alt = user.username;
            avatarImg.className = 'w-10 h-10 rounded-full';
            userCell.appendChild(avatarImg);

            const nameDiv = document.createElement('div');
            const nameSpan = document.createElement('span');
            nameSpan.textContent = user.username;
            nameDiv.appendChild(nameSpan);

            if (isCurrentUser) {
                const selfSpan = document.createElement('span');
                selfSpan.className = 'block text-xs text-blue-500';
                selfSpan.textContent = '(Siz)';
                nameDiv.appendChild(selfSpan);
            }
            userCell.appendChild(nameDiv);

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

    const renderAdminAnnouncementsList = (announcements) => {
        if (!dom.announcementsListBody) return;
        dom.announcementsListBody.innerHTML = '';

        if (announcements.length === 0) {
            dom.announcementsListBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-gray-500">Mevcut duyuru bulunmuyor.</td></tr>';
            return;
        }

        announcements.forEach(ann => {
            const tr = document.createElement('tr');
            tr.className = 'border-b dark:border-gray-700';
            tr.innerHTML = `
                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">${ann.title}</td>
                <td class="px-6 py-4">${ann.target_group}</td>
                <td class="px-6 py-4">${new Date(ann.end_date).toLocaleString()}</td>
                <td class="px-6 py-4">
                    <button data-id="${ann.id}" class="delete-announcement-btn text-red-500 hover:text-red-700" title="Duyuruyu Sil">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            dom.announcementsListBody.appendChild(tr);
        });
    };

    const renderFriendSearchResults = (users) => {
        if (!dom.friendSearchResults) return;
        dom.friendSearchResults.innerHTML = '';
        if (users.length === 0) {
            dom.friendSearchResults.innerHTML = '<p class="text-sm text-gray-500">Kullanıcı bulunamadı.</p>';
            return;
        }
        users.forEach(user => {
            const userEl = document.createElement('div');
            userEl.className = 'flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg';
            userEl.innerHTML = `
                <div class="flex items-center space-x-3">
                    <img src="assets/images/avatars/${user.avatar}" alt="${user.username}" class="w-8 h-8 rounded-full">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">${user.username}</span>
                </div>
                <button data-user-id="${user.id}" class="add-friend-btn text-sm bg-blue-500 hover:bg-blue-600 text-white py-1 px-3 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-1"></i> Ekle
                </button>
            `;
            dom.friendSearchResults.appendChild(userEl);
        });
    };

    const renderPendingRequests = (requests) => {
        if (!dom.pendingRequestsList || !dom.noPendingRequests) return;

        dom.pendingRequestsList.innerHTML = '';
        dom.noPendingRequests.classList.toggle('hidden', requests.length > 0);

        requests.forEach(req => {
            const reqEl = document.createElement('div');
            reqEl.className = 'flex items-center justify-between p-2';
            reqEl.innerHTML = `
                <span class="font-semibold text-gray-700 dark:text-gray-300">${req.username}</span>
                <div class="space-x-2">
                    <button data-request-id="${req.request_id}" data-action="accept" class="request-action-btn text-sm bg-green-500 hover:bg-green-600 text-white py-1 px-2 rounded-lg transition-colors" title="Kabul Et">
                        <i class="fas fa-check"></i>
                    </button>
                    <button data-request-id="${req.request_id}" data-action="decline" class="request-action-btn text-sm bg-red-500 hover:bg-red-600 text-white py-1 px-2 rounded-lg transition-colors" title="Reddet">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            dom.pendingRequestsList.appendChild(reqEl);
        });
    };

    const renderFriendsList = (friends) => {
        if (!dom.friendsList || !dom.noFriends) return;

        dom.friendsList.innerHTML = '';
        dom.noFriends.classList.toggle('hidden', friends.length > 0);

        friends.forEach(friend => {
            const friendEl = document.createElement('div');
            friendEl.className = 'flex items-center justify-between p-2 even:bg-gray-50 dark:even:bg-gray-700/50 rounded-lg';
            friendEl.innerHTML = `
                <div class="flex items-center space-x-3">
                     <img src="assets/images/avatars/${friend.avatar}" alt="${friend.username}" class="w-10 h-10 rounded-full">
                    <div class="flex flex-col">
                        <span class="font-semibold text-gray-800 dark:text-gray-200">${friend.username}</span>
                        <span class="text-xs text-blue-500">Puan: ${friend.score}</span>
                    </div>
                </div>
                <div class="space-x-2">
                     <button data-opponent-id="${friend.id}" data-opponent-name="${friend.username}" class="challenge-friend-btn text-sm bg-purple-500 hover:bg-purple-600 text-white py-1 px-3 rounded-lg transition-colors" title="Meydan Oku">
                        <i class="fas fa-fist-raised"></i>
                    </button>
                    <button data-friendship-id="${friend.friendship_id}" data-username="${friend.username}" class="remove-friend-btn text-sm bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-lg transition-colors" title="Arkadaşlıktan Çıkar">
                        <i class="fas fa-user-minus"></i>
                    </button>
                </div>
            `;
            dom.friendsList.appendChild(friendEl);
        });
    };

    const populateDuelCategories = () => {
        if (!dom.duelCategorySelect) return;
        dom.duelCategorySelect.innerHTML = '';
        for (const key of Object.keys(appData.categories)) {
            const option = document.createElement('option');
            option.value = key;
            option.textContent = key.charAt(0).toUpperCase() + key.slice(1);
            dom.duelCategorySelect.appendChild(option);
        }
    };

    const showDuelModal = (show, opponent = {}) => {
        if (!dom.duelModal) return;

        if (show) {
            dom.duelOpponentName.textContent = opponent.name || '';
            // Butona ileride data-* attribute eklemek için saklayalım
            dom.duelSendChallengeBtn.dataset.opponentId = opponent.id || '';

            populateDuelCategories();

            dom.duelModal.classList.remove('hidden');
            setTimeout(() => {
                dom.duelModal.classList.remove('opacity-0');
                dom.duelModal.querySelector('#duel-modal-content').classList.remove('scale-95');
            }, 10);
        } else {
            dom.duelModal.classList.add('opacity-0');
            dom.duelModal.querySelector('#duel-modal-content').classList.add('scale-95');
            setTimeout(() => {
                dom.duelModal.classList.add('hidden');
            }, 300);
        }
    };

    const renderDuelsList = (duels, currentUserId) => {
        if (!dom.duelsList || !dom.noDuels) return;

        dom.duelsList.innerHTML = '';
        dom.noDuels.classList.toggle('hidden', duels.length > 0);

        duels.forEach(duel => {
            const isChallenger = duel.challenger_id === currentUserId;
            const opponentName = isChallenger ? duel.opponent_name : duel.challenger_name;
            const opponentAvatar = isChallenger ? duel.opponent_avatar : duel.challenger_avatar;
            let statusText = '';
            let buttons = '';

            switch (duel.status) {
                case 'pending':
                    if (isChallenger) {
                        statusText = `<span class="text-yellow-500">Rakibin onayı bekleniyor.</span>`;
                    } else {
                        statusText = `<strong class="text-green-500">${opponentName} sana meydan okudu!</strong>`;
                        buttons = `
                            <button data-duel-id="${duel.id}" data-action="accept" class="duel-action-btn text-sm bg-green-500 hover:bg-green-600 text-white py-1 px-2 rounded-lg transition-colors" title="Kabul Et">
                                <i class="fas fa-check"></i> Kabul Et
                            </button>
                            <button data-duel-id="${duel.id}" data-action="decline" class="duel-action-btn text-sm bg-red-500 hover:bg-red-600 text-white py-1 px-2 rounded-lg transition-colors" title="Reddet">
                                <i class="fas fa-times"></i> Reddet
                            </button>
                        `;
                    }
                    break;
                case 'active':
                    statusText = `<span class="text-blue-500">Düello aktif!</span>`;
                    buttons = `<button data-duel-id="${duel.id}" data-action="play" class="duel-action-btn text-sm bg-blue-500 hover:bg-blue-600 text-white py-1 px-2 rounded-lg transition-colors">Oyna!</button>`;
                    break;
                case 'challenger_completed':
                case 'opponent_completed':
                    statusText = `<span class="text-purple-500">Rakibin bitirmesi bekleniyor...</span>`;
                    // Eğer sırası gelen bizsek Oyna butonu göster
                    const userHasPlayed = (isChallenger && duel.status === 'challenger_completed') || (!isChallenger && duel.status === 'opponent_completed');
                    if (!userHasPlayed) {
                        buttons = `<button data-duel-id="${duel.id}" data-action="play" class="duel-action-btn text-sm bg-blue-500 hover:bg-blue-600 text-white py-1 px-2 rounded-lg transition-colors">Sıra Sende!</button>`;
                    }
                    break;
                case 'completed':
                    if (duel.winner_id === null) {
                        statusText = `<strong class="text-gray-500">Berabere!</strong> (${duel.challenger_score} - ${duel.opponent_score})`;
                    } else if (duel.winner_id === currentUserId) {
                        statusText = `<strong class="text-green-500">Kazandın!</strong> (${duel.challenger_score} - ${duel.opponent_score})`;
                    } else {
                        statusText = `<strong class="text-red-500">Kaybettin.</strong> (${duel.challenger_score} - ${duel.opponent_score})`;
                    }
                    buttons = `<button data-duel-id="${duel.id}" data-action="details" class="duel-action-btn text-sm bg-gray-400 hover:bg-gray-500 text-white py-1 px-2 rounded-lg transition-colors">Detaylar</button>`;
                    break;
                case 'declined':
                    statusText = `<span class="text-gray-400">Meydan okuma reddedildi.</span>`;
                    break;
                case 'expired':
                    statusText = `<span class="text-gray-400">Zaman aşımına uğradı.</span>`;
                    break;
            }

            const duelEl = document.createElement('div');
            duelEl.className = 'flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg';
            duelEl.innerHTML = `
                <div class="flex items-center space-x-3 mb-2 sm:mb-0">
                    <img src="assets/images/avatars/${opponentAvatar}" alt="${opponentName}" class="w-10 h-10 rounded-full">
                    <div>
                        <p class="font-semibold text-gray-800 dark:text-gray-200">
                            Rakip: ${opponentName} 
                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400">(${duel.category} - ${duel.difficulty})</span>
                        </p>
                        <p class="text-sm">${statusText}</p>
                    </div>
                </div>
                <div class="space-x-2 flex-shrink-0 self-end sm:self-center">
                    ${buttons}
                </div>
            `;
            dom.duelsList.appendChild(duelEl);
        });
    };

    const renderDuelGame = (duelState) => {
        const currentUser = appState.get('currentUser');
        dom.duelGameOpponentName.textContent = duelState.opponent.username;
        dom.duelMyUsername.textContent = currentUser.username;
        dom.duelMyScore.textContent = '0';

        // Önceki oyunlardan kalanları temizle
        dom.duelQuestionContainer.classList.remove('hidden');
        dom.duelSummaryContainer.classList.add('hidden');
        toggleDuelNextButton(false);
    };

    const renderDuelQuestion = (question, index, total) => {
        dom.duelGameProgress.textContent = `Soru ${index + 1} / ${total}`;
        dom.duelQuestionText.textContent = question.soru;
        dom.duelOptionsContainer.innerHTML = '';
        dom.duelExplanationContainer.classList.add('hidden');

        const createButton = (text, answer) => {
            const btn = document.createElement('button');
            btn.className = 'duel-option-button p-4 text-left rounded-lg border dark:border-gray-600 hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors';
            btn.dataset.answer = answer;
            btn.innerHTML = text;
            return btn;
        };

        if (question.tip === 'dogru_yanlis') {
            dom.duelOptionsContainer.className = 'grid grid-cols-1 gap-4 items-center';
            dom.duelOptionsContainer.appendChild(createButton('Doğru', 'Doğru'));
            dom.duelOptionsContainer.appendChild(createButton('Yanlış', 'Yanlış'));
        } else {
            dom.duelOptionsContainer.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 items-center';
            Object.entries(question.siklar).forEach(([key, value]) => {
                dom.duelOptionsContainer.appendChild(createButton(`<span class="font-semibold">${key}</span>) ${value}`, key));
            });
        }
    };

    const disableDuelOptions = () => {
        dom.duelOptionsContainer.querySelectorAll('.duel-option-button').forEach(btn => {
            btn.disabled = true;
        });
    };

    const showDuelAnswerResult = (userAnswer, correctAnswer, explanation, myScore) => {
        dom.duelMyScore.textContent = myScore;

        dom.duelOptionsContainer.querySelectorAll('.duel-option-button').forEach(btn => {
            if (btn.dataset.answer === correctAnswer) {
                btn.classList.add('bg-green-200', 'dark:bg-green-500', 'font-semibold');
            } else if (btn.dataset.answer === userAnswer) {
                btn.classList.add('bg-red-200', 'dark:bg-red-500', 'font-semibold');
            }
        });

        dom.duelExplanationText.textContent = explanation;
        dom.duelExplanationContainer.classList.remove('hidden');
    };

    const toggleDuelNextButton = (show) => {
        dom.duelNextQuestionBtn.classList.toggle('hidden', !show);
    };

    const renderDuelSummary = async (duelState, finalState) => {
        // En güncel düello listesini almak için API'den veriyi çekelim.
        const result = await api.call('duel_get_duels', {}, 'POST', false);
        let finalDuelData = null;
        if (result.success) {
            finalDuelData = result.data.find(d => d.id === duelState.id);
        }

        if (!finalDuelData) {
            showToast("Düello sonucu alınamadı.", "error");
            return;
        }

        const currentUser = appState.get('currentUser');
        const isChallenger = finalDuelData.challenger_id === currentUser.id;
        const myFinalScore = isChallenger ? finalDuelData.challenger_score : finalDuelData.opponent_score;
        const opponentFinalScore = isChallenger ? finalDuelData.opponent_score : finalDuelData.challenger_score;

        dom.duelQuestionContainer.classList.add('hidden');
        dom.duelSummaryContainer.classList.remove('hidden');

        dom.duelSummaryMyName.textContent = currentUser.username;
        dom.duelSummaryMyScore.textContent = myFinalScore;
        dom.duelSummaryOpponentName.textContent = duelState.opponent.username;
        dom.duelSummaryOpponentScore.textContent = opponentFinalScore;

        if (finalDuelData.status !== 'completed') {
            dom.duelSummaryTitle.textContent = "Sıra Rakibinde!";
            dom.duelSummaryIcon.innerHTML = `<i class="fas fa-hourglass-half text-blue-500"></i>`;
            dom.duelSummaryText.textContent = `Sıranı tamamladın. Rakibinin düelloyu bitirmesi bekleniyor.`;
            dom.duelSummaryMyScore.className = "text-4xl text-blue-500";
            dom.duelSummaryOpponentScore.className = "text-4xl text-gray-500";
        } else {
            if (finalDuelData.winner_id === currentUser.id) {
                dom.duelSummaryTitle.textContent = "Kazandın!";
                dom.duelSummaryIcon.innerHTML = `<i class="fas fa-trophy text-yellow-500"></i>`;
                dom.duelSummaryText.textContent = `Tebrikler, bu düellonun galibi sensin!`;
                dom.duelSummaryMyScore.className = "text-4xl text-green-500";
                dom.duelSummaryOpponentScore.className = "text-4xl text-red-500";
            } else if (finalDuelData.winner_id === 0) { // Berabere durumu
                dom.duelSummaryTitle.textContent = "Berabere!";
                dom.duelSummaryIcon.innerHTML = `<i class="fas fa-handshake text-gray-500"></i>`;
                dom.duelSummaryText.textContent = `İkiniz de harikaydınız! Sonuç berabere.`;
                dom.duelSummaryMyScore.className = "text-4xl text-gray-500";
                dom.duelSummaryOpponentScore.className = "text-4xl text-gray-500";
            } else {
                dom.duelSummaryTitle.textContent = "Kaybettin";
                dom.duelSummaryIcon.innerHTML = `<i class="far fa-sad-tear text-red-500"></i>`;
                dom.duelSummaryText.textContent = `Bu sefer olmadı. Bir dahaki sefere daha iyi olacağına eminiz!`;
                dom.duelSummaryMyScore.className = "text-4xl text-red-500";
                dom.duelSummaryOpponentScore.className = "text-4xl text-green-500";
            }
        }
    };

    const renderQuests = (quests) => {
        if (!dom.dailyQuestsList || !dom.dailyQuestsLoading) return;

        dom.dailyQuestsLoading.classList.add('hidden');
        dom.dailyQuestsList.innerHTML = '';

        if (!quests || quests.length === 0) {
            dom.dailyQuestsList.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center">Bugün için görev bulunmuyor.</p>';
            return;
        }

        quests.forEach(quest => {
            const progressPercent = quest.goal > 0 ? (quest.progress / quest.goal) * 100 : 0;
            const isCompleted = quest.is_completed;

            const questEl = document.createElement('div');
            questEl.className = `p-3 rounded-lg ${isCompleted ? 'bg-green-50 dark:bg-green-900/40' : 'bg-gray-100 dark:bg-gray-800/60'}`;

            questEl.innerHTML = `
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-sm text-gray-700 dark:text-gray-200">${quest.name}</span>
                    ${isCompleted
                    ? `<span class="text-green-500 font-bold flex items-center text-sm"><i class="fas fa-check-circle mr-1"></i> Tamamlandı!</span>`
                    : `<span class="text-xs font-medium text-gray-500 dark:text-gray-400">${quest.progress} / ${quest.goal}</span>`
                }
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-2">${quest.description}</p>
                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" style="width: ${progressPercent}%"></div>
                </div>
            `;
            dom.dailyQuestsList.appendChild(questEl);
        });
    };

    const renderShop = (items) => {
        if (!dom.shopItemsContainer) return;

        dom.shopItemsContainer.innerHTML = '';
        items.forEach(item => {
            const itemEl = document.createElement('div');
            itemEl.className = 'bg-white dark:bg-gray-800/80 rounded-xl shadow-lg p-6 flex flex-col items-center text-center';
            itemEl.innerHTML = `
                <div class="w-20 h-20 mb-4 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                    <i class="${item.icon} fa-2x text-blue-500"></i>
                </div>
                <h4 class="text-lg font-bold text-gray-800 dark:text-gray-100">${item.name}</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 h-12">${item.description}</p>
                <div class="mt-4 text-sm">
                    Stok: <span class="font-bold text-gray-700 dark:text-gray-200">${item.current_stock}</span>
                </div>
                <button 
                    class="purchase-lifeline-btn mt-4 w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center"
                    data-item-key="${item.key}"
                    data-price="${item.price}"
                >
                    <i class="fas fa-coins mr-2"></i>
                    <span>${item.price}</span>
                </button>
            `;
            dom.shopItemsContainer.appendChild(itemEl);
        });
    };

    const populateAvatarGrid = (currentAvatar) => {
        if (!dom.avatarGrid) return;
        dom.avatarGrid.innerHTML = '';
        for (let i = 1; i <= 10; i++) {
            const avatarFile = `avatar${i}.svg`;
            const avatarWrapper = document.createElement('div');
            avatarWrapper.className = 'relative cursor-pointer avatar-wrapper';

            const avatarImg = document.createElement('img');
            avatarImg.src = `assets/images/avatars/${avatarFile}`;
            avatarImg.dataset.avatar = avatarFile;
            avatarImg.className = `w-full h-auto rounded-full transition-all duration-200`;

            const checkmark = document.createElement('div');
            checkmark.className = 'absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded-full opacity-0 transition-opacity';
            checkmark.innerHTML = '<i class="fas fa-check text-white text-2xl"></i>';

            if (avatarFile === currentAvatar) {
                avatarImg.classList.add('ring-4', 'ring-blue-500', 'p-1');
                avatarWrapper.classList.add('selected');
            }

            avatarWrapper.appendChild(avatarImg);
            avatarWrapper.appendChild(checkmark);
            dom.avatarGrid.appendChild(avatarWrapper);
        }
    };

    const updateAvatarDisplay = (newAvatar) => {
        if (dom.userAvatarDisplay) {
            dom.userAvatarDisplay.src = `assets/images/avatars/${newAvatar}`;
        }
        if (dom.avatarGrid) {
            dom.avatarGrid.querySelectorAll('.avatar-wrapper').forEach(wrapper => {
                wrapper.classList.remove('selected');
                wrapper.querySelector('img').classList.remove('ring-4', 'ring-blue-500', 'p-1');
            });
            const newSelection = dom.avatarGrid.querySelector(`img[data-avatar="${newAvatar}"]`);
            if (newSelection) {
                newSelection.classList.add('ring-4', 'ring-blue-500', 'p-1');
                newSelection.parentElement.classList.add('selected');
            }
        }
    };

    const showAnnouncementsModal = (show) => {
        if (!dom.announcementModal) return;
        if (show) {
            dom.announcementModal.classList.remove('hidden');
            setTimeout(() => {
                dom.announcementModal.classList.remove('opacity-0');
                dom.announcementModal.querySelector('#announcement-modal-content').classList.remove('scale-95');
            }, 10);
        } else {
            dom.announcementModal.classList.add('opacity-0');
            dom.announcementModal.querySelector('#announcement-modal-content').classList.add('scale-95');
            setTimeout(() => {
                dom.announcementModal.classList.add('hidden');
            }, 300);
        }
    };

    const renderAnnouncementsModal = (announcements) => {
        if (!dom.announcementModalBody) return;
        dom.announcementModalBody.innerHTML = '';
        announcements.forEach(ann => {
            const annEl = document.createElement('div');
            annEl.className = 'border-b border-gray-200 dark:border-gray-700 pb-4';
            annEl.innerHTML = `
                <h3 class="font-bold text-lg text-gray-800 dark:text-gray-100">${ann.title}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">${new Date(ann.created_at).toLocaleString()} </p>
                <p class="mt-2 text-gray-700 dark:text-gray-300">${ann.content}</p>
            `;
            dom.announcementModalBody.appendChild(annEl);
        });
    };

    const updateAnnouncementsBadge = (count) => {
        if (!dom.announcementsBadge) return;
        if (count > 0) {
            dom.announcementsBadge.textContent = count;
            dom.announcementsBadge.classList.remove('hidden');
        } else {
            dom.announcementsBadge.classList.add('hidden');
        }
    };

    const updateCoinBalance = (coins) => {
        if (dom.userCoinBalance) {
            dom.userCoinBalance.textContent = coins;
        }
    };

    return {
        init,
        showView,
        showLoading,
        showToast,
        showTab,
        showAdminTab,
        renderAdvancedStats,
        renderWelcomeMessage,
        toggleAdminButton,
        renderAchievements,
        renderLeaderboard,
        renderUserData,
        renderAdminDashboard,
        renderAdminUserList,
        renderAdminAnnouncementsList,
        renderFriendSearchResults,
        renderPendingRequests,
        renderFriendsList,
        showDuelModal,
        renderDuelsList,
        populateAvatarGrid,
        updateAvatarDisplay,
        showAnnouncementsModal,
        renderAnnouncementsModal,
        updateAnnouncementsBadge,
        // Duel Game UI
        renderDuelGame,
        renderDuelQuestion,
        disableDuelOptions,
        showDuelAnswerResult,
        toggleDuelNextButton,
        renderDuelSummary,
        // Quests
        renderQuests,
        renderShop,
        updateCoinBalance
    };
})(); 