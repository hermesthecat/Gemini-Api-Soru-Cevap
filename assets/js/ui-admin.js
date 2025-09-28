/**
 * UI Admin Module - Core Admin Functionality
 *
 * This module handles core admin interface functionality including
 * tab management, dashboard rendering, and admin-specific UI components.
 *
 * Phase 5 of ui-handler.js modularization - Admin Feature Modules
 * Created: 2025-09-28
 */

const UIAdmin = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        // Add admin-specific DOM elements
        dom.adminUsersTab = document.getElementById('admin-users-tab');
        dom.adminAnnouncementsTab = document.getElementById('admin-announcements-tab');
        dom.adminStatsTab = document.getElementById('admin-stats-tab');
        dom.adminTabs = document.getElementById('admin-tabs');
        dom.adminViewBtn = document.getElementById('admin-view-btn');
        dom.adminTotalUsers = document.getElementById('admin-total-users');
        dom.adminTotalQuestions = document.getElementById('admin-total-questions');

        console.log('UIAdmin initialized');
    };

    // Show admin tab (extracted from ui-handler.js)
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

    // Toggle admin button visibility (extracted from ui-handler.js)
    const toggleAdminButton = (isAdmin) => {
        if (!dom.adminViewBtn) return;
        dom.adminViewBtn.classList.toggle('hidden', !isAdmin);
    };

    // Render admin dashboard (extracted from ui-handler.js)
    const renderAdminDashboard = (dashboardData) => {
        if (!dom.adminTotalUsers || !dom.adminTotalQuestions) return;
        dom.adminTotalUsers.textContent = dashboardData.total_users;
        dom.adminTotalQuestions.textContent = dashboardData.total_questions_answered;
    };

    // Create admin card component
    const createAdminCard = (options = {}) => {
        const {
            title = '',
            value = '',
            icon = 'fas fa-chart-bar',
            color = 'blue',
            description = '',
            className = ''
        } = options;

        const UIComponents = ModuleLoader.getModule('UIComponents');
        const card = UIComponents ? UIComponents.createCard({
            className: `${className}`,
            content: `
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-${color}-100 dark:bg-${color}-900">
                        <i class="${icon} text-${color}-600 dark:text-${color}-400 text-xl"></i>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">${title}</h3>
                        <p class="text-3xl font-bold text-${color}-600 dark:text-${color}-400">${value}</p>
                        ${description ? `<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">${description}</p>` : ''}
                    </div>
                </div>
            `
        }) : null;

        return card;
    };

    // Render admin overview cards
    const renderAdminOverview = (data) => {
        const container = document.getElementById('admin-overview-container');
        if (!container || !data) return;

        container.innerHTML = '';

        // Create overview cards
        const cards = [
            {
                title: 'Toplam Kullanıcı',
                value: data.total_users || '0',
                icon: 'fas fa-users',
                color: 'blue',
                description: 'Kayıtlı kullanıcı sayısı'
            },
            {
                title: 'Toplam Soru',
                value: data.total_questions || '0',
                icon: 'fas fa-question-circle',
                color: 'green',
                description: 'Veritabanındaki soru sayısı'
            },
            {
                title: 'Cevaplanan Soru',
                value: data.total_questions_answered || '0',
                icon: 'fas fa-check-circle',
                color: 'purple',
                description: 'Toplam cevaplanan soru'
            },
            {
                title: 'Aktif Düello',
                value: data.active_duels || '0',
                icon: 'fas fa-sword',
                color: 'red',
                description: 'Devam eden düellolar'
            }
        ];

        const cardContainer = document.createElement('div');
        cardContainer.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6';

        cards.forEach(cardData => {
            const card = createAdminCard(cardData);
            if (card) {
                cardContainer.appendChild(card);
            }
        });

        container.appendChild(cardContainer);
    };

    // Show admin notification
    const showAdminNotification = (message, type = 'info') => {
        const UICore = ModuleLoader.getModule('UICore');
        if (UICore) {
            const iconMap = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            UICore.showToast(`${iconMap[type]} ${message}`, type);
        }
    };

    // Create admin action button
    const createAdminActionButton = (text, options = {}) => {
        const {
            variant = 'primary',
            size = 'medium',
            icon = null,
            onClick = null,
            className = ''
        } = options;

        const UIComponents = ModuleLoader.getModule('UIComponents');
        return UIComponents ? UIComponents.createButton(text, {
            variant,
            size,
            icon,
            onClick,
            className: `admin-action-btn ${className}`
        }) : null;
    };

    // Admin panel utilities
    const initAdminPanelEvents = () => {
        // Tab switching events
        dom.adminTabs?.addEventListener('click', (e) => {
            const button = e.target.closest('.admin-tab-button');
            if (button && button.dataset.tab) {
                showAdminTab(button.dataset.tab);
            }
        });

        // Admin view button events
        dom.adminViewBtn?.addEventListener('click', () => {
            const UICore = ModuleLoader.getModule('UICore');
            if (UICore) {
                UICore.showView('admin-view');
                showAdminTab('stats'); // Default to stats tab
            }
        });
    };

    // Admin form validation
    const validateAdminForm = (formData, rules = {}) => {
        const errors = {};

        Object.entries(rules).forEach(([field, rule]) => {
            const value = formData[field];

            if (rule.required && (!value || value.trim() === '')) {
                errors[field] = `${rule.label || field} gereklidir`;
            }

            if (rule.minLength && value && value.length < rule.minLength) {
                errors[field] = `${rule.label || field} en az ${rule.minLength} karakter olmalıdır`;
            }

            if (rule.maxLength && value && value.length > rule.maxLength) {
                errors[field] = `${rule.label || field} en fazla ${rule.maxLength} karakter olmalıdır`;
            }

            if (rule.email && value && !isValidEmail(value)) {
                errors[field] = 'Geçerli bir email adresi giriniz';
            }
        });

        return {
            isValid: Object.keys(errors).length === 0,
            errors
        };
    };

    // Email validation helper
    const isValidEmail = (email) => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    };

    // Show admin loading state
    const showAdminLoading = (show = true, message = 'Yükleniyor...') => {
        const UICore = ModuleLoader.getModule('UICore');
        if (UICore) {
            UICore.showLoading(show, message);
        }
    };

    // Admin breadcrumb management
    const updateAdminBreadcrumb = (items = []) => {
        const breadcrumb = document.getElementById('admin-breadcrumb');
        if (!breadcrumb) return;

        breadcrumb.innerHTML = '';

        items.forEach((item, index) => {
            const isLast = index === items.length - 1;

            const breadcrumbItem = document.createElement('span');
            breadcrumbItem.className = 'flex items-center';

            if (item.href && !isLast) {
                breadcrumbItem.innerHTML = `
                    <a href="${item.href}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                        ${item.text}
                    </a>
                    <i class="fas fa-chevron-right mx-2 text-gray-400"></i>
                `;
            } else {
                breadcrumbItem.innerHTML = `
                    <span class="${isLast ? 'text-gray-700 dark:text-gray-300 font-medium' : 'text-gray-500 dark:text-gray-400'}">
                        ${item.text}
                    </span>
                    ${!isLast ? '<i class="fas fa-chevron-right mx-2 text-gray-400"></i>' : ''}
                `;
            }

            breadcrumb.appendChild(breadcrumbItem);
        });
    };

    // Public API
    return {
        init,

        // Tab management
        showAdminTab,

        // Admin UI functions
        toggleAdminButton,
        renderAdminDashboard,
        renderAdminOverview,

        // Admin components
        createAdminCard,
        createAdminActionButton,

        // Admin utilities
        initAdminPanelEvents,
        validateAdminForm,
        showAdminNotification,
        showAdminLoading,
        updateAdminBreadcrumb,

        // Helpers
        isValidEmail
    };
})();

// Auto-register with ModuleLoader when available
if (typeof ModuleLoader !== 'undefined') {
    if (ModuleLoader.isInitialized) {
        ModuleLoader.register('UIAdmin', UIAdmin);
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (typeof ModuleLoader !== 'undefined') {
                    ModuleLoader.register('UIAdmin', UIAdmin);
                }
            }, 100);
        });
    }
}