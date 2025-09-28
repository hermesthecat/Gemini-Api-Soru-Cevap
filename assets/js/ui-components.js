/**
 * UI Components Module - Reusable UI Components
 *
 * This module provides shared, reusable UI components that can be used
 * across different parts of the application. It includes avatar generation,
 * user displays, and common UI patterns.
 *
 * Phase 2 of ui-handler.js modularization
 * Created: 2025-09-28
 */

const UIComponents = (() => {
    let dom = {};

    // Avatar color palette - consistent across the application
    const avatarColors = [
        'bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-red-500',
        'bg-yellow-500', 'bg-indigo-500', 'bg-pink-500', 'bg-teal-500'
    ];

    const init = (domElements) => {
        dom = domElements;
        console.log('UIComponents initialized');
    };

    // Generate consistent avatar color based on username
    const getAvatarColor = (username) => {
        if (!username) return avatarColors[0];
        const colorIndex = username.charCodeAt(0) % avatarColors.length;
        return avatarColors[colorIndex];
    };

    // Get user initial from username
    const getUserInitial = (username) => {
        return username ? username.charAt(0).toUpperCase() : '?';
    };

    // Create avatar element (DOM element)
    const createAvatarElement = (username, size = 'medium') => {
        const initial = getUserInitial(username);
        const color = getAvatarColor(username);

        // Size configurations
        const sizeClasses = {
            small: 'w-8 h-8 text-sm',
            medium: 'w-10 h-10 text-base',
            large: 'w-12 h-12 text-lg'
        };

        const avatar = document.createElement('div');
        avatar.className = `${sizeClasses[size] || sizeClasses.medium} rounded-full ${color} flex items-center justify-center text-white font-bold`;
        avatar.textContent = initial;
        avatar.setAttribute('data-username', username || '');

        return avatar;
    };

    // Generate avatar HTML string (for innerHTML usage)
    const getAvatarHTML = (username, size = 'medium') => {
        const initial = getUserInitial(username);
        const color = getAvatarColor(username);

        const sizeClasses = {
            small: 'w-8 h-8 text-sm',
            medium: 'w-10 h-10 text-base',
            large: 'w-12 h-12 text-lg'
        };

        return `<div class="${sizeClasses[size] || sizeClasses.medium} rounded-full ${color} flex items-center justify-center text-white font-bold" data-username="${username || ''}">${initial}</div>`;
    };

    // Create user display with avatar (DOM element)
    const createUserDisplay = (user, options = {}) => {
        const {
            size = 'medium',
            showAvatar = true,
            showUsername = true,
            className = '',
            onClick = null
        } = options;

        const container = document.createElement('div');
        container.className = `flex items-center space-x-3 ${className}`;

        if (showAvatar) {
            const avatar = createAvatarElement(user.username, size);
            container.appendChild(avatar);
        }

        if (showUsername) {
            const usernameSpan = document.createElement('span');
            usernameSpan.className = 'font-semibold text-gray-700 dark:text-gray-300';
            usernameSpan.textContent = user.username;
            container.appendChild(usernameSpan);
        }

        if (onClick && typeof onClick === 'function') {
            container.style.cursor = 'pointer';
            container.addEventListener('click', () => onClick(user));
        }

        return container;
    };

    // Generate user display HTML string
    const getUserDisplayHTML = (user, options = {}) => {
        const {
            size = 'medium',
            showAvatar = true,
            showUsername = true,
            className = ''
        } = options;

        let html = `<div class="flex items-center space-x-3 ${className}">`;

        if (showAvatar) {
            html += getAvatarHTML(user.username, size);
        }

        if (showUsername) {
            html += `<span class="font-semibold text-gray-700 dark:text-gray-300">${user.username}</span>`;
        }

        html += '</div>';
        return html;
    };

    // Update existing avatar display (for legacy DOM elements)
    const updateAvatarDisplay = (username, targetElement = null) => {
        const element = targetElement || dom.userAvatarDisplay;
        if (element) {
            const initial = getUserInitial(username);
            element.textContent = initial;
            element.setAttribute('data-username', username || '');
        }
    };

    // Create button with consistent styling
    const createButton = (text, options = {}) => {
        const {
            variant = 'primary',
            size = 'medium',
            icon = null,
            onClick = null,
            disabled = false,
            className = ''
        } = options;

        const variants = {
            primary: 'bg-blue-500 hover:bg-blue-600 text-white',
            secondary: 'bg-gray-500 hover:bg-gray-600 text-white',
            success: 'bg-green-500 hover:bg-green-600 text-white',
            danger: 'bg-red-500 hover:bg-red-600 text-white',
            warning: 'bg-yellow-500 hover:bg-yellow-600 text-white'
        };

        const sizes = {
            small: 'py-1 px-3 text-sm',
            medium: 'py-2 px-4 text-base',
            large: 'py-3 px-6 text-lg'
        };

        const button = document.createElement('button');
        button.className = `${variants[variant]} ${sizes[size]} rounded-lg transition-colors font-bold ${className}`;

        if (disabled) {
            button.disabled = true;
            button.className += ' opacity-50 cursor-not-allowed';
        }

        let buttonContent = '';
        if (icon) {
            buttonContent += `<i class="${icon} mr-2"></i>`;
        }
        buttonContent += text;

        button.innerHTML = buttonContent;

        if (onClick && typeof onClick === 'function') {
            button.addEventListener('click', onClick);
        }

        return button;
    };

    // Create loading spinner element
    const createLoadingSpinner = (size = 'medium') => {
        const sizes = {
            small: 'h-4 w-4',
            medium: 'h-8 w-8',
            large: 'h-12 w-12'
        };

        const spinner = document.createElement('div');
        spinner.className = `animate-spin rounded-full ${sizes[size]} border-b-2 border-blue-500`;
        return spinner;
    };

    // Create badge element
    const createBadge = (text, variant = 'info') => {
        const variants = {
            info: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            success: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            error: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
        };

        const badge = document.createElement('span');
        badge.className = `inline-block ${variants[variant]} px-3 py-1 rounded-full text-sm font-medium`;
        badge.textContent = text;
        return badge;
    };

    // Welcome message utility
    const renderWelcomeMessage = (username, targetElement = null) => {
        const element = targetElement || dom.welcomeMessage;
        if (element) {
            element.textContent = `Hoş Geldin, ${username}!`;
        }
    };

    // Admin button toggle utility
    const toggleAdminButton = (isAdmin, targetElement = null) => {
        const element = targetElement || dom.adminViewBtn;
        if (element) {
            element.classList.toggle('hidden', !isAdmin);
        }
    };

    // Modal show/hide utility
    const toggleModal = (modalElement, show, options = {}) => {
        if (!modalElement) return;

        if (show) {
            modalElement.classList.remove('hidden');
            if (options.onShow && typeof options.onShow === 'function') {
                options.onShow();
            }
        } else {
            modalElement.classList.add('hidden');
            if (options.onHide && typeof options.onHide === 'function') {
                options.onHide();
            }
        }
    };

    // Create card container
    const createCard = (options = {}) => {
        const {
            className = '',
            title = null,
            content = '',
            footer = null
        } = options;

        const card = document.createElement('div');
        card.className = `bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 ${className}`;

        if (title) {
            const titleElement = document.createElement('h3');
            titleElement.className = 'text-lg font-semibold mb-4 dark:text-white';
            titleElement.textContent = title;
            card.appendChild(titleElement);
        }

        if (content) {
            const contentElement = document.createElement('div');
            contentElement.className = 'text-gray-700 dark:text-gray-300';
            if (typeof content === 'string') {
                contentElement.innerHTML = content;
            } else {
                contentElement.appendChild(content);
            }
            card.appendChild(contentElement);
        }

        if (footer) {
            const footerElement = document.createElement('div');
            footerElement.className = 'mt-4 pt-4 border-t dark:border-gray-700';
            if (typeof footer === 'string') {
                footerElement.innerHTML = footer;
            } else {
                footerElement.appendChild(footer);
            }
            card.appendChild(footerElement);
        }

        return card;
    };

    // Create input field
    const createInput = (options = {}) => {
        const {
            type = 'text',
            placeholder = '',
            className = '',
            value = '',
            required = false,
            disabled = false
        } = options;

        const input = document.createElement('input');
        input.type = type;
        input.placeholder = placeholder;
        input.className = `w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 ${className}`;
        input.value = value;
        input.required = required;
        input.disabled = disabled;

        return input;
    };

    // Public API
    return {
        init,

        // Avatar functions
        getAvatarColor,
        getUserInitial,
        createAvatarElement,
        getAvatarHTML,
        updateAvatarDisplay,

        // User display functions
        createUserDisplay,
        getUserDisplayHTML,

        // Common UI components
        createButton,
        createLoadingSpinner,
        createBadge,
        createCard,
        createInput,

        // UI Utilities
        renderWelcomeMessage,
        toggleAdminButton,
        toggleModal,

        // Utilities
        get avatarColors() { return [...avatarColors]; }
    };
})();

// Auto-register with ModuleLoader when available
if (typeof ModuleLoader !== 'undefined') {
    if (ModuleLoader.isInitialized) {
        ModuleLoader.register('UIComponents', UIComponents);
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (typeof ModuleLoader !== 'undefined') {
                    ModuleLoader.register('UIComponents', UIComponents);
                }
            }, 100);
        });
    }
}