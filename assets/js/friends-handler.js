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
    };

    const searchUsers = async (username) => {
        if (username.length < 2) {
            dom.friendSearchResults.innerHTML = '';
            return;
        }
        const result = await api.call('friends_search_users', { username }, 'POST', false);
        if (result.success) {
            ui.renderFriendSearchResults(result.data);
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

        // Arkadaş silme
        dom.friendsList?.addEventListener('click', (e) => {
            const button = e.target.closest('.remove-friend-btn');
             if (button) {
                const friendshipId = button.dataset.friendshipId;
                const username = button.dataset.username;
                removeFriend(friendshipId, username);
            }
        });
    };

    return {
        init,
        updateAll
    };
})(); 