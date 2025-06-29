const ui = (() => {
    let dom = {};

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

    const renderDuelsList = (duels, currentUserId) => {
        if (!dom.duelsList || !dom.noDuels) return;

        dom.duelsList.innerHTML = '';
        dom.noDuels.classList.toggle('hidden', duels.length > 0);

        duels.forEach(duel => {
            const isChallenger = duel.challenger_id === currentUserId;
            const opponentName = isChallenger ? duel.opponent_name : duel.challenger_name;
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
                <div class="mb-2 sm:mb-0">
                    <p class="font-semibold text-gray-800 dark:text-gray-200">
                        Rakip: ${opponentName} 
                        <span class="text-xs font-normal text-gray-500 dark:text-gray-400">(${duel.category} - ${duel.difficulty})</span>
                    </p>
                    <p class="text-sm">${statusText}</p>
                </div>
                <div class="space-x-2 flex-shrink-0">
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
        showDuelModal,
        renderDuelsList,
        // Duel Game UI
        renderDuelGame,
        renderDuelQuestion,
        disableDuelOptions,
        showDuelAnswerResult,
        toggleDuelNextButton,
        renderDuelSummary,
        // Quests
        renderQuests
    };
})(); 