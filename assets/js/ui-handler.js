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

    const showAchievementModal = (achievement) => {
        if (!dom.achievementModal) return;

        // Modalı doldur
        const iconContainer = dom.achievementModal.querySelector('#achievement-modal-icon-container');
        const nameEl = dom.achievementModal.querySelector('#achievement-modal-name');
        const descriptionEl = dom.achievementModal.querySelector('#achievement-modal-description');
        const closeBtn = dom.achievementModal.querySelector('#achievement-modal-close-btn');

        iconContainer.innerHTML = `<i class="fas ${achievement.icon} fa-5x text-${achievement.color}-500"></i>`;
        nameEl.textContent = achievement.name;
        descriptionEl.textContent = achievement.description;
        
        // Modalı göster
        dom.achievementModal.classList.remove('hidden');
        setTimeout(() => { // Tarayıcının 'hidden' sınıfının kaldırılmasını işlemesi için kısa bir gecikme
             dom.achievementModal.classList.remove('opacity-0');
             dom.achievementModal.querySelector('#achievement-modal-content').classList.remove('scale-95');
        }, 10);
       
        // Kapatma butonu
        const closeHandler = () => {
            dom.achievementModal.classList.add('opacity-0');
            dom.achievementModal.querySelector('#achievement-modal-content').classList.add('scale-95');
            setTimeout(() => {
                 dom.achievementModal.classList.add('hidden');
                 closeBtn.removeEventListener('click', closeHandler);
            }, 300); // animasyon süresiyle eşleşmeli
        };
        closeBtn.addEventListener('click', closeHandler);
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
                if(isAchieved){
                    date.className = 'text-xs text-gray-500 dark:text-gray-500 mt-1';
                    date.textContent = `Kazanıldı: ${new Date(ach.achieved_at).toLocaleDateString()}`;
                }

                textContainer.appendChild(name);
                textContainer.appendChild(description);
                if(isAchieved) textContainer.appendChild(date);
                
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
                <span class="font-semibold text-gray-700 dark:text-gray-300">${user.username}</span>
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
                <div class="flex flex-col">
                    <span class="font-semibold text-gray-800 dark:text-gray-200">${friend.username}</span>
                    <span class="text-xs text-blue-500">Puan: ${friend.score}</span>
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
        renderAdminUserList,
        renderFriendSearchResults,
        renderPendingRequests,
        renderFriendsList,
        showDuelModal
    };
})(); 