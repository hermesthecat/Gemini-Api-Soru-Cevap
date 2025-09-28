/**
 * Module Loader - Dependency Injection System
 *
 * This module provides a centralized way to load and manage UI modules
 * with proper dependency injection for DOM elements and utilities.
 *
 * Phase 1 of ui-handler.js modularization
 * Created: 2025-09-28
 */

const ModuleLoader = (() => {
    let domElements = {};
    let loadedModules = {};
    let isInitialized = false;

    // Centralized DOM element references
    const initializeDOMElements = () => {
        domElements = {
            // Views
            authView: document.getElementById('auth-view'),
            mainView: document.getElementById('main-view'),
            adminView: document.getElementById('admin-view'),
            duelGameView: document.getElementById('duel-game-view'),

            // Loading and notifications
            loadingOverlay: document.getElementById('loading-overlay'),
            notificationToast: document.getElementById('notification-toast'),
            notificationText: document.getElementById('notification-text'),

            // Main tabs
            mainTabs: document.getElementById('main-tabs'),
            yarışmaTab: document.getElementById('yarışma-tab'),
            profilTab: document.getElementById('profil-tab'),
            arkadaslarTab: document.getElementById('arkadaşlar-tab'),

            // Admin tabs
            adminTabs: document.getElementById('admin-tabs'),
            adminUsersTab: document.getElementById('admin-users-tab'),
            adminAnnouncementsTab: document.getElementById('admin-announcements-tab'),
            adminStatsTab: document.getElementById('admin-stats-tab'),

            // Common UI elements
            settingModal: document.getElementById('setting-modal'),
            announcementModal: document.getElementById('announcement-modal'),
            duelModal: document.getElementById('duel-modal')
        };

        console.log('ModuleLoader: DOM elements initialized');
        return domElements;
    };

    // Initialize all loaded modules
    const initializeModules = () => {
        if (isInitialized) {
            console.warn('ModuleLoader: Already initialized');
            return;
        }

        // Initialize DOM elements first
        initializeDOMElements();

        // Initialize core module
        if (window.UICore) {
            window.UICore.init(domElements);
            loadedModules.UICore = window.UICore;
            console.log('ModuleLoader: UICore initialized');
        }

        // Initialize other modules as they are loaded
        // TODO: Add other modules as they are created

        isInitialized = true;
        console.log('ModuleLoader: All modules initialized');
    };

    // Register a new module
    const registerModule = (name, moduleInstance) => {
        if (loadedModules[name]) {
            console.warn(`ModuleLoader: Module ${name} already registered`);
            return;
        }

        // Initialize the module if it has an init method
        if (moduleInstance && typeof moduleInstance.init === 'function') {
            moduleInstance.init(domElements);
        }

        loadedModules[name] = moduleInstance;
        console.log(`ModuleLoader: Module ${name} registered`);
    };

    // Get a loaded module
    const getModule = (name) => {
        if (!loadedModules[name]) {
            console.warn(`ModuleLoader: Module ${name} not found`);
            return null;
        }
        return loadedModules[name];
    };

    // Get DOM element reference
    const getDOM = (elementName) => {
        if (!domElements[elementName]) {
            console.warn(`ModuleLoader: DOM element ${elementName} not found`);
            return null;
        }
        return domElements[elementName];
    };

    // Refresh DOM elements (useful for dynamic content)
    const refreshDOM = () => {
        initializeDOMElements();
        console.log('ModuleLoader: DOM elements refreshed');
    };

    // Public API
    return {
        init: initializeModules,
        register: registerModule,
        getModule,
        getDOM,
        refreshDOM,
        get isInitialized() { return isInitialized; },
        get modules() { return { ...loadedModules }; },
        get dom() { return { ...domElements }; }
    };
})();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ModuleLoader.init);
} else {
    ModuleLoader.init();
}