/**
 * UI Core Module - Essential UI Utilities
 *
 * This module provides the foundational UI functions that other modules depend on.
 * It handles view switching, loading states, notifications, and tab management.
 *
 * Phase 1 of ui-handler.js modularization
 * Created: 2025-09-28
 */

const UICore = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        console.log('UICore initialized with DOM elements:', Object.keys(dom));
    };

    const showView = (viewId) => {
        // Hide all main views
        dom.authView?.classList.add('hidden');
        dom.mainView?.classList.add('hidden');
        dom.adminView?.classList.add('hidden');
        dom.duelGameView?.classList.add('hidden');

        // Show the requested view
        const viewToShow = document.getElementById(viewId);
        if (viewToShow) {
            viewToShow.classList.remove('hidden');
            console.log('View switched to:', viewId);
        } else {
            console.warn('View not found:', viewId);
        }
    };

    const showLoading = (show) => {
        if (!dom.loadingOverlay) {
            console.warn('Loading overlay element not found');
            return;
        }
        dom.loadingOverlay.classList.toggle('hidden', !show);
    };

    const showToast = (message, type = 'info') => {
        if (!dom.notificationToast || !dom.notificationText) {
            console.warn('Toast notification elements not found');
            return;
        }

        dom.notificationText.textContent = message;

        const colorClasses = {
            info: 'bg-blue-500',
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500'
        };

        // Remove previous color classes
        Object.values(colorClasses).forEach(cls => dom.notificationToast.classList.remove(cls));
        // Add new color class
        dom.notificationToast.classList.add(colorClasses[type] || colorClasses.info);

        dom.notificationToast.classList.remove('hidden', 'translate-x-full');
        dom.notificationToast.classList.add('animate-toast-in', 'translate-x-0');

        setTimeout(() => {
            dom.notificationToast.classList.remove('animate-toast-in', 'translate-x-0');
            dom.notificationToast.classList.add('hidden', 'translate-x-full');
        }, 3000);
    };

    const showTab = (tabId) => {
        // Hide all main tab contents
        dom.yarışmaTab?.classList.add('hidden');
        dom.profilTab?.classList.add('hidden');
        dom.arkadaslarTab?.classList.add('hidden');

        // Show the requested tab content
        const tabToShow = document.getElementById(`${tabId}-tab`);
        if (tabToShow) {
            tabToShow.classList.remove('hidden');
        }

        // Reset all tab button styles
        dom.mainTabs?.querySelectorAll('.main-tab-button').forEach(btn => {
            btn.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-500', 'dark:border-blue-500');
            btn.classList.add('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300', 'dark:hover:text-gray-300');
        });

        // Style the active tab button
        const activeButton = dom.mainTabs?.querySelector(`[data-tab="${tabId}"]`);
        if (activeButton) {
            activeButton.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-500', 'dark:border-blue-500');
            activeButton.classList.remove('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300', 'dark:hover:text-gray-300');
        }

        // Dispatch tab change event for other modules to listen to
        document.dispatchEvent(new CustomEvent('tabChanged', { detail: { tabId } }));
    };

    const showAdminTab = (tabId) => {
        // Hide all admin tab contents
        dom.adminUsersTab?.classList.add('hidden');
        dom.adminAnnouncementsTab?.classList.add('hidden');
        dom.adminStatsTab?.classList.add('hidden');

        // Show the requested admin tab content
        const tabToShow = document.getElementById(`admin-${tabId}-tab`);
        if (tabToShow) {
            tabToShow.classList.remove('hidden');
        }

        // Reset all admin tab button styles
        dom.adminTabs?.querySelectorAll('.admin-tab-button').forEach(btn => {
            btn.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-500');
            btn.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-600');
        });

        // Style the active admin tab button
        const activeButton = dom.adminTabs?.querySelector(`[data-tab="${tabId}"]`);
        if (activeButton) {
            activeButton.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-500');
            activeButton.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-600');
        }
    };

    // Public API
    return {
        init,
        showView,
        showLoading,
        showToast,
        showTab,
        showAdminTab
    };
})();

// Auto-register with ModuleLoader when available
if (typeof ModuleLoader !== 'undefined') {
    if (ModuleLoader.isInitialized) {
        ModuleLoader.register('UICore', UICore);
    } else {
        // Wait for ModuleLoader to initialize
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (typeof ModuleLoader !== 'undefined') {
                    ModuleLoader.register('UICore', UICore);
                }
            }, 100);
        });
    }
}