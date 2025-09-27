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

    const renderWelcomeMessage = (username) => {
        if (!dom.welcomeMessage) return;
        dom.welcomeMessage.textContent = `Hoş Geldin, ${username}!`;
        updateAvatarDisplay(username);
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

    const renderLeaderboard = (leaderboardData, userRank = null) => {
        if (!dom.leaderboardList || !dom.leaderboardLoading) return;

        dom.leaderboardLoading.classList.add('hidden');
        dom.leaderboardList.innerHTML = '';

        // Top 3 Podium
        if (leaderboardData.length > 0) {
            const podiumContainer = document.createElement('div');
            podiumContainer.className = 'mb-8';

            const podiumTitle = document.createElement('h3');
            podiumTitle.className = 'text-xl font-bold text-center mb-6 dark:text-white';
            podiumTitle.textContent = '🏆 İlk 3';
            podiumContainer.appendChild(podiumTitle);

            const podium = document.createElement('div');
            podium.className = 'flex justify-center items-end space-x-4';

            // 2nd place (left)
            if (leaderboardData[1]) {
                const secondPlace = createPodiumPlace(leaderboardData[1], 2, 'h-24 bg-gray-300 dark:bg-gray-600', '🥈');
                podium.appendChild(secondPlace);
            }

            // 1st place (center, tallest)
            if (leaderboardData[0]) {
                const firstPlace = createPodiumPlace(leaderboardData[0], 1, 'h-32 bg-yellow-400 dark:bg-yellow-500', '👑');
                podium.appendChild(firstPlace);
            }

            // 3rd place (right)
            if (leaderboardData[2]) {
                const thirdPlace = createPodiumPlace(leaderboardData[2], 3, 'h-20 bg-orange-300 dark:bg-orange-500', '🥉');
                podium.appendChild(thirdPlace);
            }

            podiumContainer.appendChild(podium);
            dom.leaderboardList.appendChild(podiumContainer);
        }

        // Next 7 players in table format
        if (leaderboardData.length > 3) {
            const tableContainer = document.createElement('div');
            tableContainer.className = 'mb-8';

            const tableTitle = document.createElement('h3');
            tableTitle.className = 'text-lg font-bold mb-4 dark:text-white';
            tableTitle.textContent = '📊 Sıralama';
            tableContainer.appendChild(tableTitle);

            const table = document.createElement('table');
            table.className = 'w-full text-sm';

            const thead = document.createElement('thead');
            thead.className = 'bg-gray-50 dark:bg-gray-700';
            thead.innerHTML = `
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sıra</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Oyuncu</th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Puan</th>
                </tr>
            `;
            table.appendChild(thead);

            const tbody = document.createElement('tbody');
            tbody.className = 'bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700';

            const playersToShow = leaderboardData.slice(3, 10);
            playersToShow.forEach((player, index) => {
                const row = createTableRow(player, index + 4);
                tbody.appendChild(row);
            });

            table.appendChild(tbody);
            tableContainer.appendChild(table);
            dom.leaderboardList.appendChild(tableContainer);
        }

        // User's own position (if provided and not in top 10)
        if (userRank && userRank.position > 10) {
            const userPositionContainer = document.createElement('div');
            userPositionContainer.className = 'mt-8 pt-6 border-t dark:border-gray-700';

            const userTitle = document.createElement('h3');
            userTitle.className = 'text-lg font-bold mb-4 dark:text-white';
            userTitle.textContent = '👤 Senin Sıran';
            userPositionContainer.appendChild(userTitle);

            const userCard = document.createElement('div');
            userCard.className = 'bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 flex justify-between items-center';

            const userInfo = document.createElement('div');
            userInfo.className = 'flex items-center space-x-3';

            const userRankSpan = document.createElement('span');
            userRankSpan.className = 'bg-blue-500 text-white font-bold px-3 py-1 rounded-full text-sm';
            userRankSpan.textContent = `#${userRank.position}`;

            const userAvatar = document.createElement('div');
            userAvatar.className = 'w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold';
            userAvatar.textContent = userRank.username.charAt(0).toUpperCase();

            const userName = document.createElement('span');
            userName.className = 'font-semibold dark:text-white';
            userName.textContent = userRank.username;

            userInfo.appendChild(userRankSpan);
            userInfo.appendChild(userAvatar);
            userInfo.appendChild(userName);

            const userScore = document.createElement('span');
            userScore.className = 'font-bold text-blue-600 dark:text-blue-400 text-lg';
            userScore.textContent = userRank.score;

            userCard.appendChild(userInfo);
            userCard.appendChild(userScore);
            userPositionContainer.appendChild(userCard);
            dom.leaderboardList.appendChild(userPositionContainer);
        }
    };

    const createPodiumPlace = (player, rank, heightClass, icon) => {
        const place = document.createElement('div');
        place.className = 'flex flex-col items-center';

        const playerCard = document.createElement('div');
        playerCard.className = 'bg-white dark:bg-gray-800 rounded-lg p-3 mb-2 shadow-lg text-center min-w-[100px]';

        const avatar = document.createElement('div');
        avatar.className = 'w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-lg mx-auto mb-2';
        avatar.textContent = player.username.charAt(0).toUpperCase();

        const name = document.createElement('div');
        name.className = 'font-semibold text-sm dark:text-white truncate';
        name.textContent = player.username;

        const score = document.createElement('div');
        score.className = 'text-blue-600 dark:text-blue-400 font-bold text-lg';
        score.textContent = player.score;

        playerCard.appendChild(avatar);
        playerCard.appendChild(name);
        playerCard.appendChild(score);

        const pedestal = document.createElement('div');
        pedestal.className = `${heightClass} w-20 rounded-t-lg flex items-end justify-center pb-2`;

        const iconSpan = document.createElement('span');
        iconSpan.className = 'text-2xl';
        iconSpan.textContent = icon;
        pedestal.appendChild(iconSpan);

        place.appendChild(playerCard);
        place.appendChild(pedestal);

        return place;
    };

    const createTableRow = (player, rank) => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 dark:hover:bg-gray-700/50';

        const rankCell = document.createElement('td');
        rankCell.className = 'px-3 py-3 font-semibold';
        rankCell.textContent = `#${rank}`;

        const playerCell = document.createElement('td');
        playerCell.className = 'px-3 py-3';

        const playerDiv = document.createElement('div');
        playerDiv.className = 'flex items-center space-x-3';

        const avatar = document.createElement('div');
        avatar.className = 'w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-sm';
        avatar.textContent = player.username.charAt(0).toUpperCase();

        const name = document.createElement('span');
        name.className = 'font-medium dark:text-white';
        name.textContent = player.username;

        playerDiv.appendChild(avatar);
        playerDiv.appendChild(name);
        playerCell.appendChild(playerDiv);

        const scoreCell = document.createElement('td');
        scoreCell.className = 'px-3 py-3 text-right font-semibold text-blue-600 dark:text-blue-400';
        scoreCell.textContent = player.score;

        row.appendChild(rankCell);
        row.appendChild(playerCell);
        row.appendChild(scoreCell);

        return row;
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

            const avatarDiv = document.createElement('div');
            avatarDiv.className = 'w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold';
            avatarDiv.textContent = user.username.charAt(0).toUpperCase();
            userCell.appendChild(avatarDiv);

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

            const coinsCell = document.createElement('td');
            coinsCell.className = 'px-6 py-4';
            coinsCell.innerHTML = `
                <span class="inline-flex items-center space-x-1 cursor-pointer hover:bg-yellow-50 dark:hover:bg-yellow-900 px-2 py-1 rounded transition-colors coin-edit"
                      data-user-id="${user.id}" data-username="${user.username}" data-coins="${user.coins || 0}"
                      title="Jeton miktarını düzenlemek için tıklayın">
                    <i class="fas fa-coins text-yellow-500"></i>
                    <span>${user.coins || 0}</span>
                    <i class="fas fa-edit text-xs text-gray-400 ml-1"></i>
                </span>
            `;

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
            tr.appendChild(coinsCell);
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
                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-sm">${user.username.charAt(0).toUpperCase()}</div>
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
            const isCompleted = parseInt(quest.is_completed) === 1;

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
                    Sahip Olduğunuz: <span class="font-bold text-gray-700 dark:text-gray-200">${item.current_stock}</span>
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


    const updateAvatarDisplay = (username) => {
        if (dom.userAvatarDisplay) {
            const initial = username ? username.charAt(0).toUpperCase() : '?';
            dom.userAvatarDisplay.textContent = initial;
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

    const showAchievementModal = (achievement) => {
        return new Promise((resolve) => {
            const modal = document.getElementById('achievement-modal');
            const modalContent = document.getElementById('achievement-modal-content');
            const iconContainer = document.getElementById('achievement-modal-icon-container');
            const nameElement = document.getElementById('achievement-modal-name');
            const descriptionElement = document.getElementById('achievement-modal-description');
            const closeBtn = document.getElementById('achievement-modal-close-btn');

            if (!modal || !iconContainer || !nameElement || !descriptionElement || !closeBtn) {
                console.error('Achievement modal elements not found');
                resolve();
                return;
            }

            // Clear previous icon
            iconContainer.innerHTML = '';

            // Create achievement icon
            const iconDiv = document.createElement('div');
            iconDiv.className = `w-20 h-20 rounded-full flex items-center justify-center mx-auto bg-${achievement.color}-100 dark:bg-${achievement.color}-900`;

            const icon = document.createElement('i');
            icon.className = `fas ${achievement.icon} fa-2x text-${achievement.color}-500`;

            iconDiv.appendChild(icon);
            iconContainer.appendChild(iconDiv);

            // Set achievement details
            nameElement.textContent = achievement.name;
            descriptionElement.textContent = achievement.description;

            // Show modal with animation
            modal.classList.remove('hidden');

            // Trigger animation
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
                modal.classList.add('opacity-100');
                modalContent.classList.add('scale-100');
            }, 10);

            // Close handler
            const closeHandler = () => {
                // Hide animation
                modal.classList.remove('opacity-100');
                modal.classList.add('opacity-0');
                modalContent.classList.remove('scale-100');
                modalContent.classList.add('scale-95');

                setTimeout(() => {
                    modal.classList.add('hidden');
                    closeBtn.removeEventListener('click', closeHandler);
                    resolve();
                }, 300);
            };

            closeBtn.addEventListener('click', closeHandler);

            // Close on backdrop click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeHandler();
                }
            });

            // Close on Escape key
            const escapeHandler = (e) => {
                if (e.key === 'Escape') {
                    closeHandler();
                    document.removeEventListener('keydown', escapeHandler);
                }
            };
            document.addEventListener('keydown', escapeHandler);
        });
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
        updateCoinBalance,
        showAchievementModal
    };
})(); 