/**
 * UI Social Module - Friends and Duel System
 *
 * This module handles all social features including friend management,
 * duel challenges, friend search, and social interactions.
 *
 * Phase 4 of ui-handler.js modularization - Social Feature Modules
 * Created: 2025-09-28
 */

const UISocial = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        // Add social-specific DOM elements
        dom.friendSearchResults = document.getElementById('friend-search-results');
        dom.pendingRequestsList = document.getElementById('pending-requests-list');
        dom.noPendingRequests = document.getElementById('no-pending-requests');
        dom.friendsList = document.getElementById('friends-list');
        dom.noFriends = document.getElementById('no-friends');
        dom.duelModal = document.getElementById('duel-modal');
        dom.duelOpponentName = document.getElementById('duel-opponent-name');
        dom.duelSendChallengeBtn = document.getElementById('duel-send-challenge-btn');
        dom.duelsList = document.getElementById('duels-list');
        dom.noDuels = document.getElementById('no-duels');

        console.log('UISocial initialized');
    };

    // Render friend search results (extracted from ui-handler.js)
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

            // Use UIComponents for consistent avatar generation
            const UIComponents = ModuleLoader.getModule('UIComponents');
            const avatarHTML = UIComponents ? UIComponents.getAvatarHTML(user.username) :
                `<div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">${user.username.charAt(0).toUpperCase()}</div>`;

            userEl.innerHTML = `
                <div class="flex items-center space-x-3">
                    ${avatarHTML}
                    <span class="font-semibold text-gray-700 dark:text-gray-300">${user.username}</span>
                </div>
                <button data-id="${user.id}" class="add-friend-btn bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition-colors">
                    Arkadaş Ekle
                </button>
            `;
            dom.friendSearchResults.appendChild(userEl);
        });
    };

    // Render pending friend requests (extracted from ui-handler.js)
    const renderPendingRequests = (requests) => {
        if (!dom.pendingRequestsList || !dom.noPendingRequests) return;

        dom.pendingRequestsList.innerHTML = '';
        dom.noPendingRequests.classList.toggle('hidden', requests.length > 0);

        requests.forEach(req => {
            const reqEl = document.createElement('div');
            reqEl.className = 'flex items-center justify-between p-2';

            // Use UIComponents for consistent avatar generation
            const UIComponents = ModuleLoader.getModule('UIComponents');
            const avatarHTML = UIComponents ? UIComponents.getAvatarHTML(req.username) :
                `<div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">${req.username.charAt(0).toUpperCase()}</div>`;

            reqEl.innerHTML = `
                <div class="flex items-center space-x-3">
                    ${avatarHTML}
                    <div>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">${req.username}</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Arkadaşlık isteği gönderdi</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <button data-request-id="${req.id}" class="accept-friend-btn bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">Kabul Et</button>
                    <button data-request-id="${req.id}" class="reject-friend-btn bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">Reddet</button>
                </div>
            `;
            dom.pendingRequestsList.appendChild(reqEl);
        });
    };

    // Render friends list (extracted from ui-handler.js)
    const renderFriendsList = (friends) => {
        if (!dom.friendsList || !dom.noFriends) return;

        dom.friendsList.innerHTML = '';
        dom.noFriends.classList.toggle('hidden', friends.length > 0);

        friends.forEach((friend, index) => {
            const friendEl = document.createElement('div');
            friendEl.className = 'flex items-center justify-between p-2 even:bg-gray-50 dark:even:bg-gray-700/50 rounded-lg';

            // Use UIComponents for consistent avatar generation
            const UIComponents = ModuleLoader.getModule('UIComponents');
            const avatarHTML = UIComponents ? UIComponents.getAvatarHTML(friend.username) :
                `<div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">${friend.username.charAt(0).toUpperCase()}</div>`;

            const rank = index + 1;

            friendEl.innerHTML = `
                <div class="flex items-center space-x-3">
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 w-6">#${rank}</span>
                    ${avatarHTML}
                    <div>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">${friend.username}</span>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-blue-500">Puan: ${friend.score || friend.total_score || 0}</span>
                            ${friend.global_rank ? `<span class="text-xs text-purple-500">Genel: #${friend.global_rank}</span>` : ''}
                        </div>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <button data-user-id="${friend.user_id}" data-username="${friend.username}" class="challenge-friend-btn bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                        <i class="fas fa-sword"></i> Düello
                    </button>
                    <button data-user-id="${friend.user_id}" class="remove-friend-btn text-red-500 hover:text-red-700 px-2 py-1 rounded text-sm">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            dom.friendsList.appendChild(friendEl);
        });
    };

    // Show/hide duel modal (extracted from ui-handler.js)
    const showDuelModal = (show, opponent = {}) => {
        if (!dom.duelModal) return;

        if (show) {
            dom.duelOpponentName.textContent = opponent.name || '';
            // Store opponent ID for future use
            dom.duelSendChallengeBtn.dataset.opponentId = opponent.id || '';

            populateDuelCategories();

            dom.duelModal.classList.remove('hidden');
            setTimeout(() => {
                dom.duelModal.classList.remove('opacity-0');
                const modalContent = dom.duelModal.querySelector('#duel-modal-content');
                if (modalContent) {
                    modalContent.classList.remove('scale-95');
                    modalContent.classList.add('scale-100');
                }
            }, 10);
        } else {
            dom.duelModal.classList.add('opacity-0');
            const modalContent = dom.duelModal.querySelector('#duel-modal-content');
            if (modalContent) {
                modalContent.classList.add('scale-95');
                modalContent.classList.remove('scale-100');
            }
            setTimeout(() => {
                dom.duelModal.classList.add('hidden');
            }, 300);
        }
    };

    // Populate duel categories dropdown
    const populateDuelCategories = () => {
        const categorySelect = document.getElementById('duel-category-select');
        if (!categorySelect) return;

        categorySelect.innerHTML = '';

        // Use categories from app state if available
        if (window.categories && window.categories.length > 0) {
            window.categories.forEach(cat => {
                if (cat.is_active) {
                    const option = document.createElement('option');
                    option.value = cat.category_key;
                    option.textContent = cat.category_name;
                    categorySelect.appendChild(option);
                }
            });
        } else {
            // Fallback options
            const defaultCategories = [
                { key: 'genel_kultur', name: 'Genel Kültür' },
                { key: 'tarih', name: 'Tarih' },
                { key: 'fen_bilgisi', name: 'Fen Bilgisi' },
                { key: 'spor', name: 'Spor' },
                { key: 'sanat', name: 'Sanat' }
            ];

            defaultCategories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.key;
                option.textContent = cat.name;
                categorySelect.appendChild(option);
            });
        }
    };

    // Render duels list (extracted from ui-handler.js)
    const renderDuelsList = (duels, currentUserId) => {
        if (!dom.duelsList || !dom.noDuels) return;

        dom.duelsList.innerHTML = '';
        dom.noDuels.classList.toggle('hidden', duels.length > 0);

        duels.forEach(duel => {
            const isChallenger = duel.challenger_id === currentUserId;
            const opponentName = isChallenger ? duel.opponent_name : duel.challenger_name;

            // Use UIComponents for consistent avatar generation
            const UIComponents = ModuleLoader.getModule('UIComponents');
            const avatarHTML = UIComponents ? UIComponents.getAvatarHTML(opponentName) :
                `<div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">${opponentName.charAt(0).toUpperCase()}</div>`;

            const duelEl = document.createElement('div');
            duelEl.className = 'bg-white dark:bg-gray-800 rounded-lg p-4 shadow-md border-l-4 border-red-500';

            let statusHTML = '';
            let actionHTML = '';

            if (duel.status === 'pending') {
                if (isChallenger) {
                    statusHTML = '<span class="text-yellow-600 bg-yellow-100 dark:bg-yellow-900 dark:text-yellow-300 px-2 py-1 rounded-full text-sm">Beklemede</span>';
                    actionHTML = `
                        <button data-duel-id="${duel.id}" class="cancel-duel-btn bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">
                            <i class="fas fa-times mr-1"></i> İptal Et
                        </button>
                    `;
                } else {
                    statusHTML = '<span class="text-blue-600 bg-blue-100 dark:bg-blue-900 dark:text-blue-300 px-2 py-1 rounded-full text-sm">Davet Geldi</span>';
                    actionHTML = `
                        <div class="flex space-x-2">
                            <button data-duel-id="${duel.id}" class="accept-duel-btn bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-check mr-1"></i> Kabul Et
                            </button>
                            <button data-duel-id="${duel.id}" class="reject-duel-btn bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-times mr-1"></i> Reddet
                            </button>
                        </div>
                    `;
                }
            } else if (duel.status === 'active') {
                statusHTML = '<span class="text-green-600 bg-green-100 dark:bg-green-900 dark:text-green-300 px-2 py-1 rounded-full text-sm">Aktif</span>';
                actionHTML = `
                    <button data-duel-id="${duel.id}" class="continue-duel-btn bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                        <i class="fas fa-play mr-1"></i> Devam Et
                    </button>
                `;
            } else if (duel.status === 'completed') {
                const winnerId = parseInt(duel.winner_id);
                const isWinner = winnerId === currentUserId;
                statusHTML = isWinner ?
                    '<span class="text-green-600 bg-green-100 dark:bg-green-900 dark:text-green-300 px-2 py-1 rounded-full text-sm">Kazandın!</span>' :
                    '<span class="text-red-600 bg-red-100 dark:bg-red-900 dark:text-red-300 px-2 py-1 rounded-full text-sm">Kaybettin</span>';
                actionHTML = `
                    <button data-duel-id="${duel.id}" class="view-duel-result-btn bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">
                        <i class="fas fa-eye mr-1"></i> Sonuçları Gör
                    </button>
                `;
            } else if (duel.status === 'cancelled') {
                statusHTML = '<span class="text-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-gray-300 px-2 py-1 rounded-full text-sm">İptal Edildi</span>';
                actionHTML = '';
            }

            duelEl.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        ${avatarHTML}
                        <div>
                            <h4 class="font-semibold text-gray-800 dark:text-gray-200">${opponentName}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                ${duel.category_name || 'Genel Kültür'} • ${duel.difficulty || duel.difficulty_level || 'Orta'} • ${duel.question_count || 5} soru
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500">${duel.created_at ? new Date(duel.created_at).toLocaleDateString('tr-TR') : 'Bilinmiyor'}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        ${statusHTML}
                        <div class="mt-2">
                            ${actionHTML}
                        </div>
                    </div>
                </div>
            `;

            dom.duelsList.appendChild(duelEl);
        });
    };

    // Update social UI elements based on user data
    const updateSocialStats = (stats) => {
        // Update friends count
        const friendsCountElement = document.getElementById('friends-count');
        if (friendsCountElement && stats.friends_count !== undefined) {
            friendsCountElement.textContent = stats.friends_count;
        }

        // Update pending requests count
        const pendingCountElement = document.getElementById('pending-requests-count');
        if (pendingCountElement && stats.pending_requests !== undefined) {
            pendingCountElement.textContent = stats.pending_requests;
        }

        // Update active duels count
        const activeDuelsElement = document.getElementById('active-duels-count');
        if (activeDuelsElement && stats.active_duels !== undefined) {
            activeDuelsElement.textContent = stats.active_duels;
        }
    };

    // Show friend request feedback
    const showFriendRequestFeedback = (success, message) => {
        const UICore = ModuleLoader.getModule('UICore');
        if (UICore) {
            if (success) {
                UICore.showToast('Arkadaşlık isteği gönderildi! 👥', 'success');
            } else {
                UICore.showToast(message || 'Arkadaşlık isteği gönderilemedi', 'error');
            }
        }
    };

    // Show duel challenge feedback
    const showDuelChallengeFeedback = (success, message) => {
        const UICore = ModuleLoader.getModule('UICore');
        if (UICore) {
            if (success) {
                UICore.showToast('Düello daveti gönderildi! ⚔️', 'success');
            } else {
                UICore.showToast(message || 'Düello daveti gönderilemedi', 'error');
            }
        }
    };

    // Clear friend search results
    const clearFriendSearchResults = () => {
        if (dom.friendSearchResults) {
            dom.friendSearchResults.innerHTML = '';
        }
    };

    // Public API
    return {
        init,

        // Friend management functions
        renderFriendSearchResults,
        renderPendingRequests,
        renderFriendsList,
        clearFriendSearchResults,

        // Duel functions
        showDuelModal,
        populateDuelCategories,
        renderDuelsList,

        // Social stats and feedback
        updateSocialStats,
        showFriendRequestFeedback,
        showDuelChallengeFeedback
    };
})();

// Auto-register with ModuleLoader when available
if (typeof ModuleLoader !== 'undefined') {
    if (ModuleLoader.isInitialized) {
        ModuleLoader.register('UISocial', UISocial);
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (typeof ModuleLoader !== 'undefined') {
                    ModuleLoader.register('UISocial', UISocial);
                }
            }, 100);
        });
    }
}