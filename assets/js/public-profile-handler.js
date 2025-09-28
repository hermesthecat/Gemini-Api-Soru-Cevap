/**
 * Public Profile Handler
 * Manages public profile page functionality and user interactions
 */

const publicProfileHandler = (function() {
    let currentUsername = null;
    let profileData = null;

    // Initialize the public profile handler
    function init(username) {
        currentUsername = username;
        loadProfileData();
        initializeEventListeners();
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

        const profile = profileData.profile;
        const stats = profileData.stats;

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
        // Update achievement count badge
        const countBadge = document.getElementById('achievement-count-badge');
        if (countBadge) {
            countBadge.textContent = achievements.length;
        }

        // Update recent achievements
        const container = document.getElementById('recent-achievements-container');
        if (!container) return;

        const recentAchievements = achievements
            .filter(ach => ach.earned_at)
            .sort((a, b) => new Date(b.earned_at) - new Date(a.earned_at))
            .slice(0, 6); // Show 6 most recent

        if (recentAchievements.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center col-span-full">Henüz başarım kazanılmamış</p>';
            return;
        }

        container.innerHTML = recentAchievements.map(ach => `
            <div class="achievement-card earned bg-gradient-to-br from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <div class="text-3xl animate-bounce" style="animation-duration: 2s;">${ach.icon || '🏆'}</div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-200">${ach.achievement_name}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">${ach.description}</p>
                        ${ach.earned_at ? `<p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1 flex items-center">
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
        // Total questions answered
        const totalQuestionsEl = document.getElementById('total-questions-answered');
        if (totalQuestionsEl) {
            totalQuestionsEl.textContent = formatNumber(stats.total_questions_answered || 0);
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
                ui.showNotification('Profil gizlilik ayarı güncellendi', 'success');
            } else {
                ui.showNotification(response.message || 'Güncelleme başarısız', 'error');
                // Revert selection on error
                if (profileData && profileData.profile) {
                    select.value = profileData.profile.profile_visibility;
                }
            }
        } catch (error) {
            console.error('Privacy update error:', error);
            ui.showNotification('Bağlantı hatası', 'error');
        }
    }

    // Show all achievements modal
    function showAllAchievementsModal() {
        if (!profileData || !profileData.achievements) return;

        const achievements = profileData.achievements;
        const earned = achievements.filter(ach => ach.earned_at);
        const notEarned = achievements.filter(ach => !ach.earned_at);

        const modalContent = `
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-6 w-full max-w-4xl max-h-[80vh] overflow-y-auto">
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
                            ${earned.map(ach => `
                                <div class="achievement-card earned bg-gradient-to-br from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="text-3xl">${ach.icon || '🏆'}</div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-800 dark:text-gray-200">${ach.achievement_name}</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">${ach.description}</p>
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
                            `).join('')}
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold mb-4 text-gray-600">Henüz Kazanılmayanlar (${notEarned.length})</h3>
                        <div class="space-y-3">
                            ${notEarned.map(ach => `
                                <div class="achievement-card not-earned bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="text-3xl">${ach.icon || '🏆'}</div>
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
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `;

        ui.showModal(modalContent);

        // Close modal handler
        document.getElementById('close-achievements-modal').addEventListener('click', ui.hideModal);
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
        init
    };
})();

// Make it globally available
window.publicProfileHandler = publicProfileHandler;