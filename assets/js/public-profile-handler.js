/**
 * Public Profile Handler
 * Manages public profile page functionality and user interactions
 */

const publicProfileHandler = (function() {
    let currentUsername = null;
    let profileData = null;

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

    const showNotification = (message, type) => {
        const UICore = ModuleLoader?.getModule('UICore');
        if (UICore) {
            UICore.showToast(message, type);
        } else if (window.ui && window.ui.showToast) {
            window.ui.showToast(message, type);
        } else {
            console.log(`[${type}] ${message}`);
        }
    };

    const showModal = (content) => {
        const UICore = ModuleLoader?.getModule('UICore');
        if (UICore) {
            UICore.showModal(content);
        } else if (window.ui && window.ui.showModal) {
            window.showModal(content);
        }
    };

    const hideModal = () => {
        const UICore = ModuleLoader?.getModule('UICore');
        if (UICore) {
            UICore.hideModal();
        } else if (window.ui && window.hideModal) {
            window.hideModal();
        }
    };

    // Initialize the public profile handler
    function init(username) {
        currentUsername = username;
        loadProfileData();
        initializeEventListeners();
        loadSocialFeatures();
    }

    // Set up event listeners
    function initializeEventListeners() {
        // Privacy settings change
        const privacySelect = document.getElementById('profile-visibility-select');
        if (privacySelect) {
            privacySelect.addEventListener('change', updateProfileVisibility);
        }

        // Retry button
        const retryBtn = document.getElementById('retry-load-profile');
        if (retryBtn) {
            retryBtn.addEventListener('click', () => loadProfileData());
        }

        // View all achievements button
        const viewAchievementsBtn = document.getElementById('view-all-achievements');
        if (viewAchievementsBtn) {
            viewAchievementsBtn.addEventListener('click', showAllAchievementsModal);
        }

        // Social action buttons
        const shareBtn = document.getElementById('share-profile-btn');
        if (shareBtn) {
            shareBtn.addEventListener('click', showShareModal);
        }

        const compareBtn = document.getElementById('compare-achievements-btn');
        if (compareBtn) {
            compareBtn.addEventListener('click', showAchievementComparison);
        }

        const bookmarkBtn = document.getElementById('bookmark-profile-btn');
        if (bookmarkBtn) {
            bookmarkBtn.addEventListener('click', toggleProfileBookmark);
        }

        // Share modal event listeners
        const shareModalClose = document.getElementById('share-profile-modal-close');
        if (shareModalClose) {
            shareModalClose.addEventListener('click', hideShareModal);
        }

        const copyUrlBtn = document.getElementById('copy-url-btn');
        if (copyUrlBtn) {
            copyUrlBtn.addEventListener('click', copyProfileUrl);
        }

        const shareWhatsAppBtn = document.getElementById('share-whatsapp-btn');
        if (shareWhatsAppBtn) {
            shareWhatsAppBtn.addEventListener('click', () => shareToSocial('whatsapp'));
        }

        const shareTwitterBtn = document.getElementById('share-twitter-btn');
        if (shareTwitterBtn) {
            shareTwitterBtn.addEventListener('click', () => shareToSocial('twitter'));
        }

        // Achievement comparison modal
        const comparisonModalClose = document.getElementById('achievement-comparison-modal-close');
        if (comparisonModalClose) {
            comparisonModalClose.addEventListener('click', hideAchievementComparisonModal);
        }

        // All achievements modal background click
        const allAchievementsModal = document.getElementById('all-achievements-modal');
        if (allAchievementsModal) {
            allAchievementsModal.addEventListener('click', (e) => {
                if (e.target === allAchievementsModal) {
                    hideAllAchievementsModal();
                }
            });
        }

        // Visit history load more
        const loadMoreVisitsBtn = document.getElementById('load-more-visits-btn');
        if (loadMoreVisitsBtn) {
            loadMoreVisitsBtn.addEventListener('click', loadMoreVisitHistory);
        }
    }

    // Load profile data from API
    async function loadProfileData() {
        try {
            showLoadingState();

            const response = await api.call('get_public_profile', {
                username: currentUsername
            }, 'POST', false);

            if (response.success) {
                profileData = response.data;
                populateProfileData();
                showProfileContent();
            } else {
                showErrorState(response.message || 'Profil yüklenirken bir hata oluştu.');
            }
        } catch (error) {
            console.error('Profile load error:', error);
            showErrorState('Bağlantı hatası. Lütfen internet bağlantınızı kontrol edin.');
        }
    }

    // Populate profile data in the UI
    function populateProfileData() {
        if (!profileData) return;

        const profile = profileData.user;
        const stats = profileData.statistics;

        // Update profile header
        updateProfileHeader(profile);

        // Update quick stats
        updateQuickStats(stats);

        // Update detailed statistics
        updateCategoryPerformance(stats.category_performance || []);
        updateDuelStats(stats.duel_stats);

        // Update achievements
        updateAchievements(profileData.achievements || []);

        // Update activity summary
        updateActivitySummary(stats);

        // Show/hide privacy settings for own profile
        updatePrivacySettings(profile);

        // Record profile visit if viewing another user's profile
        if (!profile.is_own_profile) {
            recordProfileVisit();
        }
    }

    // Update profile header section
    function updateProfileHeader(profile) {
        // Avatar with initials
        const avatar = document.getElementById('profile-avatar');
        if (avatar && profile.username) {
            const initials = profile.username.substring(0, 2).toUpperCase();
            avatar.textContent = initials;
        }

        // Username
        const usernameEl = document.getElementById('profile-username');
        if (usernameEl) {
            usernameEl.textContent = profile.username || 'Bilinmeyen Kullanıcı';
        }

        // Member since
        const memberSinceEl = document.getElementById('profile-member-since');
        if (memberSinceEl && profile.created_at) {
            const date = new Date(profile.created_at);
            memberSinceEl.innerHTML = `<i class="fas fa-calendar-alt mr-2"></i>Üye: ${date.getFullYear()}`;
        }

        // Global rank
        const globalRankEl = document.getElementById('profile-global-rank');
        if (globalRankEl) {
            const rank = profile.global_rank || 'N/A';
            globalRankEl.innerHTML = `<i class="fas fa-trophy mr-2"></i>Sıralama: #${rank}`;
        }

        // Login streak
        const streakEl = document.getElementById('profile-login-streak');
        if (streakEl) {
            const streak = profile.login_streak || 0;
            streakEl.innerHTML = `<i class="fas fa-fire mr-2"></i>Seri: ${streak} gün`;
        }
    }

    // Update quick stats section
    function updateQuickStats(stats) {
        // Total score
        const totalScoreEl = document.getElementById('profile-total-score');
        if (totalScoreEl) {
            totalScoreEl.textContent = formatNumber(stats.total_score || 0);
        }

        // Accuracy
        const accuracyEl = document.getElementById('profile-accuracy');
        if (accuracyEl) {
            accuracyEl.textContent = (stats.accuracy || 0) + '%';
        }

        // Achievement count
        const achievementsEl = document.getElementById('profile-achievements');
        if (achievementsEl) {
            achievementsEl.textContent = stats.achievement_count || 0;
        }

        // Quest count
        const questsEl = document.getElementById('profile-quests');
        if (questsEl) {
            questsEl.textContent = stats.quest_count || 0;
        }
    }

    // Update category performance
    function updateCategoryPerformance(categoryData) {
        const container = document.getElementById('category-performance-container');
        if (!container) return;

        if (categoryData.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center">Henüz kategori verisi yok</p>';
            return;
        }

        container.innerHTML = categoryData.map(cat => `
            <div class="stat-card p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-3">
                        <i class="fas ${cat.icon || 'fa-question'} text-${cat.color || 'blue'}-500 text-lg"></i>
                        <span class="font-medium text-gray-800 dark:text-gray-200">${cat.category_name}</span>
                    </div>
                    <div class="text-right">
                        <div class="animated-number text-lg font-bold text-${cat.color || 'blue'}-600">${cat.accuracy}%</div>
                        <div class="text-xs text-gray-500">${cat.questions_answered} soru</div>
                    </div>
                </div>
                <div class="category-progress" style="--progress-color: var(--tw-color-${cat.color || 'blue'}-500); --progress-color-light: var(--tw-color-${cat.color || 'blue'}-400)">
                    <div class="category-progress-bar" style="width: ${cat.accuracy}%"></div>
                </div>
            </div>
        `).join('');
    }

    // Update duel statistics
    function updateDuelStats(duelStats) {
        if (!duelStats) return;

        // Total duels
        const totalEl = document.getElementById('duel-total');
        if (totalEl) {
            totalEl.textContent = duelStats.total_duels || 0;
        }

        // Wins
        const winsEl = document.getElementById('duel-wins');
        if (winsEl) {
            winsEl.textContent = duelStats.wins || 0;
        }

        // Win rate
        const winRateEl = document.getElementById('duel-win-rate');
        if (winRateEl) {
            const winRate = duelStats.total_duels > 0
                ? Math.round((duelStats.wins / duelStats.total_duels) * 100)
                : 0;
            winRateEl.textContent = winRate + '%';
        }
    }

    // Update achievements section
    function updateAchievements(achievements) {
        // Update achievement count badge - only count earned achievements
        const earnedAchievements = achievements.filter(ach => ach.is_earned == 1 || ach.is_earned === true);
        const countBadge = document.getElementById('achievement-count-badge');
        if (countBadge) {
            countBadge.textContent = earnedAchievements.length;
        }

        // Update recent achievements
        const container = document.getElementById('recent-achievements-container');
        if (!container) return;

        const recentAchievements = earnedAchievements
            .filter(ach => ach.earned_at)
            .sort((a, b) => new Date(b.earned_at) - new Date(a.earned_at))
            .slice(0, 6); // Show 6 most recent

        if (recentAchievements.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center col-span-full">Henüz başarım kazanılmamış</p>';
            return;
        }

        container.innerHTML = recentAchievements.map(ach => `
            <div class="achievement-card earned bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <div class="text-3xl animate-bounce" style="animation-duration: 2s;">
                        ${ach.icon ? `<i class="fas ${ach.icon}"></i>` : '🏆'}
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-200">${ach.achievement_name}</h4>
                        ${ach.earned_at ? `<p class="text-xs text-green-600 dark:text-green-400 mt-1 flex items-center">
                            <i class="fas fa-calendar-check mr-1"></i>
                            ${formatDate(ach.earned_at)}
                        </p>` : ''}
                        ${ach.rarity ? `<span class="inline-block px-2 py-1 text-xs rounded-full mt-2 bg-purple-100 text-purple-800">
                            ${ach.rarity}
                        </span>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Update activity summary
    function updateActivitySummary(stats) {
        // Total questions answered - API returns 'total_questions'
        const totalQuestionsEl = document.getElementById('total-questions-answered');
        if (totalQuestionsEl) {
            totalQuestionsEl.textContent = formatNumber(stats.total_questions || stats.total_questions_answered || 0);
        }

        // Correct answers
        const correctAnswersEl = document.getElementById('correct-answers');
        if (correctAnswersEl) {
            correctAnswersEl.textContent = formatNumber(stats.correct_answers || 0);
        }

        // Longest streak
        const longestStreakEl = document.getElementById('longest-streak');
        if (longestStreakEl) {
            longestStreakEl.textContent = stats.longest_streak || 0;
        }
    }

    // Update privacy settings visibility and current value
    function updatePrivacySettings(profile) {
        const privacySection = document.getElementById('profile-privacy-settings');
        const privacySelect = document.getElementById('profile-visibility-select');

        if (!privacySection || !privacySelect) return;

        // Show privacy settings only for own profile
        if (profile.is_own_profile) {
            privacySection.classList.remove('hidden');
            privacySelect.value = profile.profile_visibility || 'public';
        } else {
            privacySection.classList.add('hidden');
        }

        // Show/hide social actions
        const socialActions = document.getElementById('social-actions');
        if (socialActions) {
            if (profile.is_own_profile) {
                socialActions.classList.add('hidden');
            } else {
                socialActions.classList.remove('hidden');
            }
        }
    }

    // Update profile visibility setting
    async function updateProfileVisibility() {
        const select = document.getElementById('profile-visibility-select');
        if (!select) return;

        try {
            const response = await api.call('update_profile_visibility', {
                visibility: select.value
            }, 'POST', true);

            if (response.success) {
                showNotification('Profil gizlilik ayarı güncellendi', 'success');
            } else {
                showNotification(response.message || 'Güncelleme başarısız', 'error');
                // Revert selection on error
                if (profileData && profileData.profile) {
                    select.value = profileData.profile.profile_visibility;
                }
            }
        } catch (error) {
            console.error('Privacy update error:', error);
            showNotification('Bağlantı hatası', 'error');
        }
    }

    // Show all achievements modal
    function showAllAchievementsModal() {
        if (!profileData) return;

        const achievements = profileData.achievements || [];
        const earned = achievements.filter(ach => ach.earned_at);
        const notEarned = achievements.filter(ach => !ach.earned_at);

        const modal = document.getElementById('all-achievements-modal');
        const modalContent = document.getElementById('all-achievements-modal-content');
        const modalBody = document.getElementById('all-achievements-modal-body');

        if (!modal || !modalContent || !modalBody) return;

        if (achievements.length === 0) {
            // No achievements available
            modalBody.innerHTML = `
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">
                        Tüm Başarımlar (0/0)
                    </h2>
                    <button id="close-achievements-modal" class="text-gray-500 hover:text-gray-800 dark:hover:text-white text-2xl">
                        &times;
                    </button>
                </div>
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">🏆</div>
                    <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-400 mb-2">Henüz Başarım Yok</h3>
                    <p class="text-gray-500 dark:text-gray-500 mb-4">
                        Bu kullanıcı henüz hiç başarım kazanmamış. Oyuna katılarak başarımlar kazanmaya başlayabilirsin!
                    </p>
                    <div class="inline-flex items-center px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg">
                        <i class="fas fa-info-circle mr-2"></i>
                        Başarımlar oyun oynayarak kazanılır
                    </div>
                </div>
            `;
        } else {
            // Has achievements
            modalBody.innerHTML = `
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">
                        Tüm Başarımlar (${earned.length}/${achievements.length})
                    </h2>
                    <button id="close-achievements-modal" class="text-gray-500 hover:text-gray-800 dark:hover:text-white text-2xl">
                        &times;
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold mb-4 text-green-600">Kazanılanlar (${earned.length})</h3>
                        <div class="space-y-3">
                            ${earned.length > 0 ? earned.map(ach => `
                                <div class="achievement-card earned bg-gradient-to-br from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="text-3xl">${ach.icon ? `<i class="fas ${ach.icon}"></i>` : '🏆'}</div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-800 dark:text-gray-200">${ach.achievement_name}</h4>
                                            <p class="text-xs text-green-600 dark:text-green-400 mt-1 flex items-center">
                                                <i class="fas fa-calendar-check mr-1"></i>
                                                ${formatDate(ach.earned_at)}
                                            </p>
                                            ${ach.rarity ? `<span class="inline-block px-2 py-1 text-xs rounded-full mt-2 bg-green-100 text-green-800">
                                                ${ach.rarity}
                                            </span>` : ''}
                                        </div>
                                    </div>
                                </div>
                            `).join('') : '<p class="text-gray-500 dark:text-gray-400 text-center py-8">Henüz başarım kazanılmamış</p>'}
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold mb-4 text-gray-600">Henüz Kazanılmayanlar (${notEarned.length})</h3>
                        <div class="space-y-3">
                            ${notEarned.length > 0 ? notEarned.map(ach => `
                                <div class="achievement-card not-earned bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="text-3xl">${ach.icon ? `<i class="fas ${ach.icon}"></i>` : '🏆'}</div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-600 dark:text-gray-400">${ach.achievement_name}</h4>
                                            <p class="text-sm text-gray-500 dark:text-gray-500">${ach.description}</p>
                                            ${ach.progress ? `<div class="mt-2">
                                                <div class="text-xs text-gray-500 mb-1">${ach.progress}/${ach.target || 100}</div>
                                                <div class="category-progress">
                                                    <div class="category-progress-bar bg-gray-400" style="width: ${(ach.progress / (ach.target || 100)) * 100}%"></div>
                                                </div>
                                            </div>` : ''}
                                            ${ach.rarity ? `<span class="inline-block px-2 py-1 text-xs rounded-full mt-2 bg-gray-200 text-gray-600">
                                                ${ach.rarity}
                                            </span>` : ''}
                                        </div>
                                    </div>
                                </div>
                            `).join('') : '<p class="text-gray-500 dark:text-gray-400 text-center py-8">Tüm başarımlar kazanılmış!</p>'}
                        </div>
                    </div>
                </div>
            `;
        }

        // Show modal with animation
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
        }, 10);

        // Close modal handler
        document.getElementById('close-achievements-modal').addEventListener('click', hideAllAchievementsModal);
    }

    // Hide all achievements modal
    function hideAllAchievementsModal() {
        const modal = document.getElementById('all-achievements-modal');
        const modalContent = document.getElementById('all-achievements-modal-content');

        if (!modal || !modalContent) return;

        // Hide modal with animation
        modal.classList.add('opacity-0');
        modalContent.classList.add('scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // UI state management functions
    function showLoadingState() {
        document.getElementById('profile-loading').classList.remove('hidden');
        document.getElementById('profile-content').classList.add('hidden');
        document.getElementById('profile-error').classList.add('hidden');
    }

    function showProfileContent() {
        document.getElementById('profile-loading').classList.add('hidden');
        document.getElementById('profile-content').classList.remove('hidden');
        document.getElementById('profile-error').classList.add('hidden');
    }

    function showErrorState(message) {
        document.getElementById('profile-loading').classList.add('hidden');
        document.getElementById('profile-content').classList.add('hidden');
        document.getElementById('profile-error').classList.remove('hidden');

        const errorMessageEl = document.getElementById('profile-error-message');
        if (errorMessageEl) {
            errorMessageEl.textContent = message;
        }
    }

    // === SOCIAL FEATURES ===

    // Load social features (friend shortcuts, visit history for own profile)
    async function loadSocialFeatures() {
        try {
            // Load friend shortcuts for all users
            await loadFriendShortcuts();

            // Load visit history only for own profile
            if (profileData && profileData.profile && profileData.profile.is_own_profile) {
                await loadVisitHistory();
            }
        } catch (error) {
            console.error('Error loading social features:', error);
        }
    }

    // Record profile visit
    async function recordProfileVisit() {
        try {
            await api.call('record_profile_visit', {
                username: currentUsername
            }, 'POST', false);
        } catch (error) {
            console.error('Error recording profile visit:', error);
        }
    }

    // Load friend shortcuts
    async function loadFriendShortcuts() {
        try {
            const response = await api.call('get_friend_shortcuts', {}, 'POST', false);

            if (response.success) {
                displayBookmarkedFriends(response.data.bookmarks);
                displayRecentFriends(response.data.recent_friends);
            }
        } catch (error) {
            console.error('Error loading friend shortcuts:', error);
        }
    }

    // Display bookmarked friends
    function displayBookmarkedFriends(bookmarks) {
        const container = document.getElementById('bookmarked-friends-container');
        if (!container) return;

        if (bookmarks.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center col-span-full">Henüz işaretlenmiş profil yok</p>';
            return;
        }

        container.innerHTML = bookmarks.map(bookmark => `
            <div class="stat-card bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-3 cursor-pointer"
                 onclick="window.location.href='/profile/${bookmark.username}'">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                        ${bookmark.username.substring(0, 2).toUpperCase()}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-200 truncate">${bookmark.bookmark_name || bookmark.username}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">${formatNumber(bookmark.total_score || 0)} puan</p>
                        <p class="text-xs text-blue-600 dark:text-blue-400">${bookmark.achievement_count || 0} başarım</p>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Display recent friends
    function displayRecentFriends(friends) {
        const container = document.getElementById('recent-friends-container');
        if (!container) return;

        if (friends.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center col-span-full">Henüz arkadaş yok</p>';
            return;
        }

        container.innerHTML = friends.map(friend => `
            <div class="stat-card bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-3 cursor-pointer"
                 onclick="window.location.href='/profile/${friend.username}'">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                        ${friend.username.substring(0, 2).toUpperCase()}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-200 truncate">${friend.username}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">${formatNumber(friend.total_score || 0)} puan</p>
                        <p class="text-xs text-green-600 dark:text-green-400">${friend.achievement_count || 0} başarım</p>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Load visit history (own profile only)
    let visitHistoryOffset = 0;
    async function loadVisitHistory() {
        try {
            const response = await api.call('get_profile_visit_history', {
                username: currentUsername,
                limit: 10,
                offset: visitHistoryOffset
            }, 'POST', false);

            if (response.success) {
                displayVisitHistory(response.data.visits, visitHistoryOffset === 0);

                // Show/hide load more button
                const loadMoreBtn = document.getElementById('load-more-visits-btn');
                if (loadMoreBtn) {
                    loadMoreBtn.style.display = response.data.has_more ? 'block' : 'none';
                }

                // Show visit history section
                const visitSection = document.getElementById('visit-history-section');
                if (visitSection && response.data.visits.length > 0) {
                    visitSection.classList.remove('hidden');
                }
            }
        } catch (error) {
            console.error('Error loading visit history:', error);
        }
    }

    // Display visit history
    function displayVisitHistory(visits, replace = false) {
        const container = document.getElementById('visit-history-container');
        if (!container) return;

        if (visits.length === 0 && replace) {
            container.innerHTML = '<p class="text-gray-500 text-center">Henüz ziyaretçi yok</p>';
            return;
        }

        const visitHtml = visits.map(visit => `
            <div class="stat-card bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-3 cursor-pointer"
                 onclick="window.location.href='/profile/${visit.visitor_username}'">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center text-white font-bold">
                            ${visit.visitor_username.substring(0, 2).toUpperCase()}
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800 dark:text-gray-200">${visit.visitor_username}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">${visit.visit_count} ziyaret</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">${formatDate(visit.last_visit)}</p>
                    </div>
                </div>
            </div>
        `).join('');

        if (replace) {
            container.innerHTML = visitHtml;
        } else {
            container.innerHTML += visitHtml;
        }
    }

    // Load more visit history
    async function loadMoreVisitHistory() {
        visitHistoryOffset += 10;
        await loadVisitHistory();
    }

    // Show share modal
    function showShareModal() {
        const modal = document.getElementById('share-profile-modal');
        const modalContent = document.getElementById('share-profile-modal-content');
        const urlInput = document.getElementById('share-url-input');

        if (!modal || !modalContent || !urlInput) return;

        // Set share URL
        const profileUrl = window.location.origin + '/profile/' + currentUsername;
        urlInput.value = profileUrl;

        // Show modal with animation
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
        }, 10);
    }

    // Hide share modal
    function hideShareModal() {
        const modal = document.getElementById('share-profile-modal');
        const modalContent = document.getElementById('share-profile-modal-content');

        if (!modal || !modalContent) return;

        modal.classList.add('opacity-0');
        modalContent.classList.add('scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Copy profile URL
    async function copyProfileUrl() {
        try {
            const urlInput = document.getElementById('share-url-input');
            if (!urlInput) return;

            await navigator.clipboard.writeText(urlInput.value);

            // Record share action
            await api.call('share_profile', {
                username: currentUsername,
                method: 'copy'
            }, 'POST', false);

            showNotification('Profil linki kopyalandı!', 'success');
        } catch (error) {
            console.error('Error copying URL:', error);
            showNotification('Link kopyalanamadı', 'error');
        }
    }

    // Share to social platforms
    async function shareToSocial(platform) {
        try {
            const profileUrl = window.location.origin + '/profile/' + currentUsername;
            const text = `${currentUsername} kullanıcısının AI Quiz profilini inceleyin!`;

            let shareUrl = '';

            if (platform === 'whatsapp') {
                shareUrl = `https://wa.me/?text=${encodeURIComponent(text + ' ' + profileUrl)}`;
            } else if (platform === 'twitter') {
                shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(profileUrl)}`;
            }

            if (shareUrl) {
                window.open(shareUrl, '_blank');

                // Record share action
                await api.call('share_profile', {
                    username: currentUsername,
                    method: 'social'
                }, 'POST', false);
            }
        } catch (error) {
            console.error('Error sharing to social:', error);
        }
    }

    // Show achievement comparison
    async function showAchievementComparison() {
        try {
            const modal = document.getElementById('achievement-comparison-modal');
            const modalContent = document.getElementById('achievement-comparison-modal-content');
            const contentDiv = document.getElementById('achievement-comparison-content');

            if (!modal || !modalContent || !contentDiv) return;

            // Show loading
            contentDiv.innerHTML = `
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-500"></div>
                    <span class="ml-3 text-gray-600 dark:text-gray-400">Başarımlar karşılaştırılıyor...</span>
                </div>
            `;

            // Show modal
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
            }, 10);

            // Get comparison data
            const response = await api.call('compare_achievements', {
                username: currentUsername
            }, 'POST', true);

            if (response.success) {
                displayAchievementComparison(response.data);
            } else {
                contentDiv.innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-4"></i>
                        <p class="text-red-600 dark:text-red-400">${response.message}</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error showing achievement comparison:', error);
            showNotification('Başarım karşılaştırması yapılamadı', 'error');
        }
    }

    // Display achievement comparison
    function displayAchievementComparison(data) {
        const contentDiv = document.getElementById('achievement-comparison-content');
        if (!contentDiv) return;

        const html = `
            <!-- Comparison Summary -->
            <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-6 mb-6">
                <div class="grid grid-cols-2 gap-6">
                    <div class="text-center">
                        <h3 class="text-lg font-semibold text-blue-600 mb-2">${data.user1.username}</h3>
                        <div class="text-3xl font-bold text-blue-800 dark:text-blue-400">${data.user1.total_achievements}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Toplam Başarım</div>
                        <div class="text-lg font-semibold text-green-600 mt-2">${data.user1.unique_achievements}</div>
                        <div class="text-xs text-gray-500">Benzersiz Başarım</div>
                    </div>
                    <div class="text-center">
                        <h3 class="text-lg font-semibold text-purple-600 mb-2">${data.user2.username}</h3>
                        <div class="text-3xl font-bold text-purple-800 dark:text-purple-400">${data.user2.total_achievements}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Toplam Başarım</div>
                        <div class="text-lg font-semibold text-green-600 mt-2">${data.user2.unique_achievements}</div>
                        <div class="text-xs text-gray-500">Benzersiz Başarım</div>
                    </div>
                </div>
                <div class="text-center mt-4 pt-4 border-t border-blue-200 dark:border-blue-700">
                    <div class="text-2xl font-bold text-yellow-600">${data.comparison_stats.common_count}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Ortak Başarım</div>
                </div>
            </div>

            <!-- Achievement Categories -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Common Achievements -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-yellow-600 flex items-center">
                        <i class="fas fa-handshake mr-2"></i>
                        Ortak Başarımlar (${data.common_achievements.length})
                    </h3>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        ${data.common_achievements.map(ach => `
                            <div class="achievement-card earned bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-3">
                                <div class="flex items-center space-x-3">
                                    <div class="text-2xl">${ach.icon || '🏆'}</div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-gray-800 dark:text-gray-200 truncate">${ach.achievement_name}</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">${ach.description}</p>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>

                <!-- User1 Only -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-blue-600 flex items-center">
                        <i class="fas fa-user-check mr-2"></i>
                        ${data.user1.username} Özel (${data.user1_only_achievements.length})
                    </h3>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        ${data.user1_only_achievements.map(ach => `
                            <div class="achievement-card earned bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-3">
                                <div class="flex items-center space-x-3">
                                    <div class="text-2xl">${ach.icon || '🏆'}</div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-gray-800 dark:text-gray-200 truncate">${ach.achievement_name}</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">${ach.description}</p>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>

                <!-- User2 Only -->
                <div>
                    <h3 class="text-lg font-semibold mb-4 text-purple-600 flex items-center">
                        <i class="fas fa-user-plus mr-2"></i>
                        ${data.user2.username} Özel (${data.user2_only_achievements.length})
                    </h3>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        ${data.user2_only_achievements.map(ach => `
                            <div class="achievement-card earned bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-3">
                                <div class="flex items-center space-x-3">
                                    <div class="text-2xl">${ach.icon || '🏆'}</div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-gray-800 dark:text-gray-200 truncate">${ach.achievement_name}</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">${ach.description}</p>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;

        contentDiv.innerHTML = html;
    }

    // Hide achievement comparison modal
    function hideAchievementComparisonModal() {
        const modal = document.getElementById('achievement-comparison-modal');
        const modalContent = document.getElementById('achievement-comparison-modal-content');

        if (!modal || !modalContent) return;

        modal.classList.add('opacity-0');
        modalContent.classList.add('scale-95');

        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Toggle profile bookmark
    let isBookmarked = false;
    async function toggleProfileBookmark() {
        try {
            const btn = document.getElementById('bookmark-profile-btn');
            if (!btn) return;

            if (isBookmarked) {
                const response = await api.call('remove_profile_bookmark', {
                    username: currentUsername
                }, 'POST', true);

                if (response.success) {
                    isBookmarked = false;
                    btn.innerHTML = '<i class="fas fa-bookmark mr-2"></i>İşaretle';
                    btn.className = btn.className.replace('bg-red-500 hover:bg-red-600', 'bg-yellow-500 hover:bg-yellow-600');
                    showNotification('İşaret kaldırıldı', 'success');
                    loadFriendShortcuts(); // Refresh shortcuts
                }
            } else {
                const response = await api.call('add_profile_bookmark', {
                    username: currentUsername,
                    name: currentUsername
                }, 'POST', true);

                if (response.success) {
                    isBookmarked = true;
                    btn.innerHTML = '<i class="fas fa-bookmark-remove mr-2"></i>İşaret Kaldır';
                    btn.className = btn.className.replace('bg-yellow-500 hover:bg-yellow-600', 'bg-red-500 hover:bg-red-600');
                    showNotification('Profil işaretlendi', 'success');
                    loadFriendShortcuts(); // Refresh shortcuts
                }
            }
        } catch (error) {
            console.error('Error toggling bookmark:', error);
            showNotification('İşlem başarısız', 'error');
        }
    }

    // Utility functions
    function formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('tr-TR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    // Public API
    return {
        init,
        showAllAchievementsModal
    };
})();

// Make it globally available
window.publicProfileHandler = publicProfileHandler;