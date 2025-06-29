const friendsHandler = (() => {
    let dom = {};

    // Arama yaparken gecikme sağlamak için (debounce)
    let searchTimeout;

    const init = (domElements) => {
        dom = domElements;
        addEventListeners();
    };

    const updateAll = () => {
        updatePendingRequests();
        updateFriendsList();
        updateDuelsList();
    };

    const searchUsers = async (username) => {
        if (username.length < 2) {
            dom.friendSearchResults.innerHTML = '';
            return;
        }
        const result = await api.call('friends_search_users', { username }, 'POST', false);
        if (result.success) {
            ui.renderFriendSearchResults(result.data);
            updateAll();
        }
    };

    const sendRequest = async (userId) => {
        const result = await api.call('friends_send_request', { user_id: userId });
        ui.showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) {
            // Arama sonuçlarını temizle veya butonu deaktif et
            dom.friendSearchInput.value = '';
            dom.friendSearchResults.innerHTML = '';
        }
    };

    const updatePendingRequests = async () => {
        const result = await api.call('friends_get_pending_requests', {}, 'POST', false);
        if (result.success) {
            ui.renderPendingRequests(result.data);
        }
    };
    
    const respondToRequest = async (requestId, response) => {
        const result = await api.call('friends_respond_to_request', { request_id: requestId, response });
        ui.showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) {
            updateAll();
        }
    };

    const updateFriendsList = async () => {
        const result = await api.call('friends_get_list', {}, 'POST', false);
        if (result.success) {
            ui.renderFriendsList(result.data);
        }
    };

    const removeFriend = async (friendshipId, username) => {
        if (confirm(`'${username}' adlı kullanıcıyı arkadaşlıktan çıkarmak istediğinizden emin misiniz?`)) {
            const result = await api.call('friends_remove', { friendship_id: friendshipId });
            ui.showToast(result.message, result.success ? 'success' : 'error');
            if (result.success) {
                updateAll();
            }
        }
    };

    const handleChallengeClick = (button) => {
        const opponentId = button.dataset.opponentId;
        const opponentName = button.dataset.opponentName;
        ui.showDuelModal(true, { id: opponentId, name: opponentName });
    };

    const sendChallenge = async () => {
        const opponentId = dom.duelSendChallengeBtn.dataset.opponentId;
        const category = dom.duelCategorySelect.value;
        const difficulty = dom.duelDifficultySelect.value;

        ui.showLoading(true);
        const result = await api.call('duel_create', {
            opponent_id: opponentId,
            category: category,
            difficulty: difficulty
        }, 'POST', false); // showLoading'i manuel yöneteceğiz
        ui.showLoading(false);

        ui.showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) {
            ui.showDuelModal(false);
            // İleride düello listesini güncelleme fonksiyonu buraya gelebilir.
        }
    };

    const updateDuelsList = async () => {
        const result = await api.call('duel_get_duels', {}, 'POST', false);
        if (result.success) {
            const currentUser = appState.get('currentUser');
            ui.renderDuelsList(result.data, currentUser.id);
        }
    };

    const respondToDuel = async (duelId, response) => {
        const result = await api.call('duel_respond', { duel_id: duelId, response: response });
        ui.showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) {
            updateDuelsList();
        }
    };

    const addEventListeners = () => {
        // Kullanıcı Arama
        dom.friendSearchInput?.addEventListener('keyup', (e) => {
            clearTimeout(searchTimeout);
            const searchTerm = e.target.value.trim();
            if (searchTerm) {
                searchTimeout = setTimeout(() => {
                    searchUsers(searchTerm);
                }, 300); // 300ms sonra ara
            } else {
                dom.friendSearchResults.innerHTML = '';
            }
        });

        // Arama sonucundan istek gönderme
        dom.friendSearchResults?.addEventListener('click', (e) => {
            const button = e.target.closest('.add-friend-btn');
            if (button) {
                const userId = button.dataset.userId;
                sendRequest(userId);
            }
        });

        // İstek yanıtlama
        dom.pendingRequestsList?.addEventListener('click', (e) => {
            const button = e.target.closest('.request-action-btn');
            if (button) {
                const requestId = button.dataset.requestId;
                const action = button.dataset.action;
                respondToRequest(requestId, action);
            }
        });

        // Arkadaş silme veya Meydan Okuma
        dom.friendsList?.addEventListener('click', (e) => {
            const removeButton = e.target.closest('.remove-friend-btn');
            if (removeButton) {
                const friendshipId = removeButton.dataset.friendshipId;
                const username = removeButton.dataset.username;
                removeFriend(friendshipId, username);
                return; // Başka bir butona basılmadığından emin ol
            }

            const challengeButton = e.target.closest('.challenge-friend-btn');
            if (challengeButton) {
                handleChallengeClick(challengeButton);
            }
        });

        // Düello Modalı Kapatma
        dom.duelModalCloseBtn?.addEventListener('click', () => {
            ui.showDuelModal(false);
        });

        // Düello Gönderme
        dom.duelSendChallengeBtn?.addEventListener('click', sendChallenge);

        // Düello Yanıtlama
        dom.duelsList?.addEventListener('click', (e) => {
            const button = e.target.closest('.duel-action-btn');
            if(button) {
                const duelId = button.dataset.duelId;
                const action = button.dataset.action;
                if (action === 'accept' || action === 'decline') {
                    respondToDuel(duelId, action);
                } else if (action === 'play') {
                    // Oynama eylemini duelHandler'a devret
                    duelHandler.startDuel(duelId);
                }
            }
        });
    };

    return {
        init,
        updateAll
    };
})(); 