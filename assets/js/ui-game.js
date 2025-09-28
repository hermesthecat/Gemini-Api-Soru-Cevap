/**
 * UI Game Module - Quest, Shop, and Announcements
 *
 * This module handles game-related UI functionality including
 * daily quests, shop system, achievement modals, announcements,
 * and coin balance management.
 *
 * Phase 3 of ui-handler.js modularization - Independent Feature Modules
 * Created: 2025-09-28
 */

const UIGame = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        // Add game-specific DOM elements
        dom.dailyQuestsList = document.getElementById('daily-quests-list');
        dom.dailyQuestsLoading = document.getElementById('daily-quests-loading');
        dom.shopItemsContainer = document.getElementById('shop-items-container');
        dom.userCoinBalance = document.getElementById('user-coin-balance');
        dom.announcementsListBody = document.getElementById('announcements-list-body');

        console.log('UIGame initialized');
    };

    // Quest type information helper
    const getQuestTypeInfo = (questKey) => {
        const questTypes = {
            'daily_questions': { icon: 'fas fa-question-circle', color: 'blue' },
            'correct_answers': { icon: 'fas fa-check-circle', color: 'green' },
            'category_master': { icon: 'fas fa-graduation-cap', color: 'purple' },
            'streak_master': { icon: 'fas fa-fire', color: 'red' },
            'duel_winner': { icon: 'fas fa-trophy', color: 'yellow' },
            'friend_challenge': { icon: 'fas fa-user-friends', color: 'indigo' }
        };
        return questTypes[questKey] || { icon: 'fas fa-tasks', color: 'gray' };
    };

    // Render daily quests (extracted from ui-handler.js)
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

            // Quest type'a göre icon ve renk belirleme
            const questTypeInfo = getQuestTypeInfo(quest.quest_key);

            const questEl = document.createElement('div');
            questEl.className = `p-3 rounded-lg ${isCompleted ? 'bg-green-50 dark:bg-green-900/40' : 'bg-gray-100 dark:bg-gray-800/60'}`;

            questEl.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-${questTypeInfo.color}-100 dark:bg-${questTypeInfo.color}-900 flex items-center justify-center">
                            <i class="${questTypeInfo.icon} text-${questTypeInfo.color}-600 dark:text-${questTypeInfo.color}-400"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800 dark:text-gray-200">${quest.title}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">${quest.description}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        ${isCompleted ?
                            '<span class="text-green-600 dark:text-green-400 font-semibold"><i class="fas fa-check"></i> Tamamlandı</span>' :
                            `<div class="text-sm">
                                <div class="text-gray-600 dark:text-gray-400">${quest.progress}/${quest.goal}</div>
                                <div class="w-16 bg-gray-200 rounded-full h-2 mt-1">
                                    <div class="bg-${questTypeInfo.color}-500 h-2 rounded-full" style="width: ${Math.min(progressPercent, 100)}%"></div>
                                </div>
                            </div>`
                        }
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <i class="fas fa-coins text-yellow-500"></i> ${quest.reward_amount} jeton
                        </div>
                    </div>
                </div>
            `;

            dom.dailyQuestsList.appendChild(questEl);
        });
    };

    // Render shop items (extracted from ui-handler.js)
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
                    ${item.price} Jeton ile Satın Al
                </button>
            `;
            dom.shopItemsContainer.appendChild(itemEl);
        });
    };

    // Update coin balance display
    const updateCoinBalance = (coins) => {
        if (dom.userCoinBalance) {
            dom.userCoinBalance.textContent = coins;
        }

        // Update all coin balance displays
        const coinElements = document.querySelectorAll('[data-coin-balance]');
        coinElements.forEach(element => {
            element.textContent = coins;
        });
    };

    // Show achievement modal with animation
    const showAchievementModal = (achievement) => {
        return new Promise((resolve) => {
            const modal = document.getElementById('achievement-modal');
            const modalContent = document.getElementById('achievement-modal-content');
            const iconContainer = document.getElementById('achievement-modal-icon-container');
            const nameElement = document.getElementById('achievement-modal-name');
            const descriptionElement = document.getElementById('achievement-modal-description');
            const closeBtn = document.getElementById('achievement-modal-close-btn');

            if (!modal || !iconContainer || !nameElement || !descriptionElement || !closeBtn) {
                resolve();
                return;
            }

            // Clear previous icon
            iconContainer.innerHTML = '';

            // Create achievement icon
            const iconDiv = document.createElement('div');
            iconDiv.className = `w-20 h-20 rounded-full flex items-center justify-center mx-auto bg-${achievement.color}-100 dark:bg-${achievement.color}-900`;

            const icon = document.createElement('i');
            icon.className = `${achievement.icon} fa-3x text-${achievement.color}-600 dark:text-${achievement.color}-400`;
            iconDiv.appendChild(icon);
            iconContainer.appendChild(iconDiv);

            // Set content
            nameElement.textContent = achievement.name;
            descriptionElement.textContent = achievement.description;

            // Show modal with animation
            modal.classList.remove('hidden');
            modal.classList.remove('opacity-0');
            modal.classList.add('opacity-100');

            if (modalContent) {
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            }

            // Handle close
            const handleClose = () => {
                modal.classList.add('opacity-0');
                modal.classList.remove('opacity-100');
                if (modalContent) {
                    modalContent.classList.add('scale-95');
                    modalContent.classList.remove('scale-100');
                }
                setTimeout(() => {
                    modal.classList.add('hidden');
                    resolve();
                }, 300);
            };

            closeBtn.onclick = handleClose;
            modal.onclick = (e) => {
                if (e.target === modal) handleClose();
            };

            // Auto-close after 5 seconds
            setTimeout(handleClose, 5000);
        });
    };

    // Render admin announcements list
    const renderAdminAnnouncementsList = (announcements) => {
        if (!dom.announcementsListBody) return;
        dom.announcementsListBody.innerHTML = '';

        if (announcements.length === 0) {
            dom.announcementsListBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-gray-500">Mevcut duyuru bulunmuyor.</td></tr>';
            return;
        }

        announcements.forEach(ann => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="py-2 px-4">${ann.title}</td>
                <td class="py-2 px-4">${ann.content.substring(0, 50)}${ann.content.length > 50 ? '...' : ''}</td>
                <td class="py-2 px-4">${ann.created_at}</td>
                <td class="py-2 px-4">
                    <button data-id="${ann.id}" class="delete-announcement-btn text-red-500 hover:text-red-700" title="Duyuruyu Sil">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            dom.announcementsListBody.appendChild(tr);
        });
    };

    // Show/hide announcements modal
    const showAnnouncementsModal = (show) => {
        const modal = dom.announcementModal;
        if (!modal) return;

        if (show) {
            modal.classList.remove('hidden');
        } else {
            modal.classList.add('hidden');
        }
    };

    // Render announcements in modal
    const renderAnnouncementsModal = (announcements) => {
        const modalBody = document.getElementById('announcement-modal-body');
        if (!modalBody) return;

        modalBody.innerHTML = '';

        if (!announcements || announcements.length === 0) {
            modalBody.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center">Yeni duyuru bulunmuyor.</p>';
            return;
        }

        announcements.forEach(announcement => {
            const announcementEl = document.createElement('div');
            announcementEl.className = 'border-b border-gray-200 dark:border-gray-700 pb-4 last:border-b-0';
            announcementEl.innerHTML = `
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">${announcement.title}</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">${announcement.content}</p>
                <p class="text-xs text-gray-500 dark:text-gray-500">${announcement.created_at}</p>
            `;
            modalBody.appendChild(announcementEl);
        });
    };

    // Update announcements badge
    const updateAnnouncementsBadge = (count) => {
        const badge = document.getElementById('announcements-badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    };

    // Quest progress animation
    const animateQuestProgress = (questElement, oldProgress, newProgress, goal) => {
        const progressBar = questElement.querySelector('.quest-progress-bar');
        if (!progressBar) return;

        const oldPercent = (oldProgress / goal) * 100;
        const newPercent = (newProgress / goal) * 100;

        // Animate progress bar
        progressBar.style.transition = 'width 0.5s ease-out';
        progressBar.style.width = `${Math.min(newPercent, 100)}%`;

        // Update progress text
        const progressText = questElement.querySelector('.quest-progress-text');
        if (progressText) {
            setTimeout(() => {
                progressText.textContent = `${newProgress}/${goal}`;
            }, 250);
        }
    };

    // Shop purchase feedback
    const showPurchaseFeedback = (success, message, itemName) => {
        const UICore = ModuleLoader.getModule('UICore');
        if (UICore) {
            if (success) {
                UICore.showToast(`${itemName} başarıyla satın alındı! 🎉`, 'success');
            } else {
                UICore.showToast(message || 'Satın alma işlemi başarısız', 'error');
            }
        }
    };

    // Public API
    return {
        init,

        // Quest functions
        renderQuests,
        animateQuestProgress,

        // Shop functions
        renderShop,
        updateCoinBalance,
        showPurchaseFeedback,

        // Achievement functions
        showAchievementModal,

        // Announcement functions
        renderAdminAnnouncementsList,
        showAnnouncementsModal,
        renderAnnouncementsModal,
        updateAnnouncementsBadge,

        // Utilities
        getQuestTypeInfo
    };
})();

// Auto-register with ModuleLoader when available
if (typeof ModuleLoader !== 'undefined') {
    if (ModuleLoader.isInitialized) {
        ModuleLoader.register('UIGame', UIGame);
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (typeof ModuleLoader !== 'undefined') {
                    ModuleLoader.register('UIGame', UIGame);
                }
            }, 100);
        });
    }
}