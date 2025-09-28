const friendsHandler = (() => {
    let dom = {};

    // Arama yaparken gecikme sağlamak için (debounce)
    let searchTimeout;

    const init = (domElements) => {
        dom = domElements;
        addEventListeners();
    };

    // Helper methods to get modules with fallback
    const showToast = (message, type) => {
        const UICore = ModuleLoader?.getModule('UICore');
        if (UICore) {
            UICore.showToast(message, type);
        } else if (window.ui && window.ui.showToast) {
            window.ui.showToast(message, type);
        } else {
            console.log(`[${type}] ${message}`);
        }
    };

    const showLoading = (show) => {
        const UICore = ModuleLoader?.getModule('UICore');
        if (UICore) {
            UICore.showLoading(show);
        } else if (window.ui && window.ui.showLoading) {
            window.ui.showLoading(show);
        }
    };

    const getUISocial = () => {
        const UISocial = ModuleLoader?.getModule('UISocial');
        if (UISocial) {
            return UISocial;
        } else if (window.ui) {
            return window.ui;
        }
        return null;
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
            const uiSocial = getUISocial();
            if (uiSocial && uiSocial.renderFriendSearchResults) {
                uiSocial.renderFriendSearchResults(result.data);
            }
            updateAll();
        }
    };

    const sendRequest = async (userId) => {
        const result = await api.call('friends_send_request', { user_id: userId });
        showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) {
            // Arama sonuçlarını temizle veya butonu deaktif et
            dom.friendSearchInput.value = '';
            dom.friendSearchResults.innerHTML = '';
        }
    };

    const updatePendingRequests = async () => {
        const result = await api.call('friends_get_pending_requests', {}, 'POST', false);
        if (result.success) {
            const uiSocial = getUISocial();
            if (uiSocial && uiSocial.renderPendingRequests) {
                uiSocial.renderPendingRequests(result.data);
            }
        }
    };

    const respondToRequest = async (requestId, response) => {
        const result = await api.call('friends_respond_to_request', { request_id: requestId, response });
        showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) {
            updateAll();
        }
    };

    const updateFriendsList = async () => {
        const result = await api.call('friends_get_list', {}, 'POST', false);
        if (result.success) {
            const uiSocial = getUISocial();
            if (uiSocial && uiSocial.renderFriendsList) {
                uiSocial.renderFriendsList(result.data);
            }
        }
    };

    const removeFriend = async (friendshipId, username) => {
        if (confirm(`'${username}' adlı kullanıcıyı arkadaşlıktan çıkarmak istediğinizden emin misiniz?`)) {
            const result = await api.call('friends_remove', { friendship_id: friendshipId });
            showToast(result.message, result.success ? 'success' : 'error');
            if (result.success) {
                updateAll();
            }
        }
    };

    const handleChallengeClick = (button) => {
        const opponentId = button.dataset.opponentId;
        const opponentName = button.dataset.opponentName;
        const uiSocial = getUISocial();
        if (uiSocial && uiSocial.showDuelModal) {
            uiSocial.showDuelModal(true, { id: opponentId, name: opponentName });
        }
    };

    const sendChallenge = async () => {
        const opponentId = dom.duelSendChallengeBtn.dataset.opponentId;
        const category = dom.duelCategorySelect.value;
        const difficulty = dom.duelDifficultySelect.value;
        const questionCount = dom.duelQuestionCountSelect.value;

        showLoading(true);
        const result = await api.call('duel_create', {
            opponent_id: opponentId,
            category: category,
            difficulty: difficulty,
            question_count: questionCount
        }, 'POST', false); // showLoading'i manuel yöneteceğiz
        showLoading(false);

        showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) {
            const uiSocial = getUISocial();
            if (uiSocial && uiSocial.showDuelModal) {
                uiSocial.showDuelModal(false);
            }
            // İleride düello listesini güncelleme fonksiyonu buraya gelebilir.
        }
    };

    const updateDuelsList = async () => {
        const result = await api.call('duel_get_duels', {}, 'POST', false);
        if (result.success) {
            const currentUser = appState.get('currentUser');
            if (currentUser && currentUser.id) {
                const uiSocial = getUISocial();
                if (uiSocial && uiSocial.renderDuelsList) {
                    uiSocial.renderDuelsList(result.data, currentUser.id);
                }
            }
        }
    };

    const respondToDuel = async (duelId, response) => {
        const result = await api.call('duel_respond', { duel_id: duelId, response: response });
        showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) {
            updateDuelsList();
        }
    };

    const cancelDuel = async (duelId) => {
        if (confirm('Bu düello davetini iptal etmek istediğinizden emin misiniz?')) {
            const result = await api.call('duel_cancel', { duel_id: duelId });
            showToast(result.message, result.success ? 'success' : 'error');
            if (result.success) {
                updateDuelsList();
            }
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
            const uiSocial = getUISocial();
            if (uiSocial && uiSocial.showDuelModal) {
                uiSocial.showDuelModal(false);
            }
        });

        // Düello Gönderme
        dom.duelSendChallengeBtn?.addEventListener('click', sendChallenge);

        // Düello Yanıtlama
        dom.duelsList?.addEventListener('click', (e) => {
            const button = e.target.closest('.duel-action-btn');
            if (button) {
                const duelId = button.dataset.duelId;
                const action = button.dataset.action;
                if (action === 'accept' || action === 'decline') {
                    respondToDuel(duelId, action);
                } else if (action === 'cancel') {
                    cancelDuel(duelId);
                } else if (action === 'play') {
                    // Oynama eylemini duelHandler'a devret
                    duelHandler.startDuel(duelId);
                }
            }
        });
    };

    return {
        init,
        updateAll,
        searchUsers,
        sendRequest,
        updatePendingRequests,
        respondToRequest,
        updateFriendsList,
        removeFriend,
        updateDuelsList,
        respondToDuel,
        cancelDuel
    };
})(); 