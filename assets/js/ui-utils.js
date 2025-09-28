/**
 * UI Utils Module - Shared Utility Functions
 *
 * This module provides common utility functions that are used across
 * multiple modules including date formatting, string manipulation,
 * validation helpers, and general-purpose utilities.
 *
 * Phase 6 of ui-handler.js modularization - Utility Modules
 * Created: 2025-09-28
 */

const UIUtils = (() => {
    let dom = {};

    const init = (domElements) => {
        dom = domElements;
        console.log('UIUtils initialized');
    };

    // Date and time utilities
    const formatDate = (date, format = 'dd.mm.yyyy') => {
        if (!date) return '';

        const d = new Date(date);
        if (isNaN(d.getTime())) return '';

        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        const hours = String(d.getHours()).padStart(2, '0');
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const seconds = String(d.getSeconds()).padStart(2, '0');

        const formats = {
            'dd.mm.yyyy': `${day}.${month}.${year}`,
            'dd/mm/yyyy': `${day}/${month}/${year}`,
            'yyyy-mm-dd': `${year}-${month}-${day}`,
            'dd.mm.yyyy hh:mm': `${day}.${month}.${year} ${hours}:${minutes}`,
            'dd/mm/yyyy hh:mm': `${day}/${month}/${year} ${hours}:${minutes}`,
            'yyyy-mm-dd hh:mm:ss': `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`,
            'relative': formatRelativeTime(d),
            'time': `${hours}:${minutes}`
        };

        return formats[format] || formats['dd.mm.yyyy'];
    };

    // Format relative time (e.g., "2 saat önce")
    const formatRelativeTime = (date) => {
        const now = new Date();
        const diffMs = now - date;
        const diffSeconds = Math.floor(diffMs / 1000);
        const diffMinutes = Math.floor(diffSeconds / 60);
        const diffHours = Math.floor(diffMinutes / 60);
        const diffDays = Math.floor(diffHours / 24);

        if (diffSeconds < 60) return 'şimdi';
        if (diffMinutes < 60) return `${diffMinutes} dakika önce`;
        if (diffHours < 24) return `${diffHours} saat önce`;
        if (diffDays < 7) return `${diffDays} gün önce`;
        if (diffDays < 30) return `${Math.floor(diffDays / 7)} hafta önce`;
        if (diffDays < 365) return `${Math.floor(diffDays / 30)} ay önce`;
        return `${Math.floor(diffDays / 365)} yıl önce`;
    };

    // String utilities
    const truncateString = (str, maxLength, suffix = '...') => {
        if (!str || str.length <= maxLength) return str;
        return str.substring(0, maxLength - suffix.length) + suffix;
    };

    const capitalizeFirst = (str) => {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    };

    const capitalizeWords = (str) => {
        if (!str) return '';
        return str.split(' ').map(word => capitalizeFirst(word)).join(' ');
    };

    const slugify = (str) => {
        if (!str) return '';
        return str
            .toLowerCase()
            .replace(/[ğĞ]/g, 'g')
            .replace(/[üÜ]/g, 'u')
            .replace(/[şŞ]/g, 's')
            .replace(/[ıİ]/g, 'i')
            .replace(/[öÖ]/g, 'o')
            .replace(/[çÇ]/g, 'c')
            .replace(/[^a-z0-9 -]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim('-');
    };

    // Number utilities
    const formatNumber = (num, decimals = 0, thousandsSep = '.', decimalSep = ',') => {
        if (num === null || num === undefined || isNaN(num)) return '0';

        const n = parseFloat(num).toFixed(decimals);
        const parts = n.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSep);

        return parts.join(decimalSep);
    };

    const formatCurrency = (amount, currency = '₺', position = 'after') => {
        const formatted = formatNumber(amount, 2);
        return position === 'before' ? `${currency}${formatted}` : `${formatted} ${currency}`;
    };

    const formatPercentage = (value, decimals = 1) => {
        return `${formatNumber(value, decimals)}%`;
    };

    // Validation utilities
    const isValidEmail = (email) => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    };

    const isValidUsername = (username) => {
        // Username: 3-20 karakter, sadece harf, rakam ve alt çizgi
        const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
        return usernameRegex.test(username);
    };

    const isValidPassword = (password) => {
        // En az 6 karakter
        return password && password.length >= 6;
    };

    const isValidUrl = (url) => {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    };

    // Array utilities
    const uniqueArray = (arr) => {
        return [...new Set(arr)];
    };

    const shuffleArray = (arr) => {
        const newArr = [...arr];
        for (let i = newArr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [newArr[i], newArr[j]] = [newArr[j], newArr[i]];
        }
        return newArr;
    };

    const groupBy = (arr, keyFn) => {
        return arr.reduce((groups, item) => {
            const key = keyFn(item);
            if (!groups[key]) groups[key] = [];
            groups[key].push(item);
            return groups;
        }, {});
    };

    const sortBy = (arr, keyFn, direction = 'asc') => {
        return [...arr].sort((a, b) => {
            const aVal = keyFn(a);
            const bVal = keyFn(b);

            if (aVal < bVal) return direction === 'asc' ? -1 : 1;
            if (aVal > bVal) return direction === 'asc' ? 1 : -1;
            return 0;
        });
    };

    // Object utilities
    const deepClone = (obj) => {
        if (obj === null || typeof obj !== 'object') return obj;
        if (obj instanceof Date) return new Date(obj.getTime());
        if (obj instanceof Array) return obj.map(item => deepClone(item));
        if (typeof obj === 'object') {
            const copy = {};
            Object.keys(obj).forEach(key => {
                copy[key] = deepClone(obj[key]);
            });
            return copy;
        }
    };

    const mergeDeep = (target, source) => {
        const result = { ...target };

        for (const key in source) {
            if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
                result[key] = mergeDeep(result[key] || {}, source[key]);
            } else {
                result[key] = source[key];
            }
        }

        return result;
    };

    const isEmpty = (obj) => {
        if (obj === null || obj === undefined) return true;
        if (typeof obj === 'string' || Array.isArray(obj)) return obj.length === 0;
        if (typeof obj === 'object') return Object.keys(obj).length === 0;
        return false;
    };

    // DOM utilities
    const createElement = (tag, attributes = {}, children = []) => {
        const element = document.createElement(tag);

        Object.entries(attributes).forEach(([key, value]) => {
            if (key === 'className') {
                element.className = value;
            } else if (key === 'innerHTML') {
                element.innerHTML = value;
            } else if (key === 'textContent') {
                element.textContent = value;
            } else if (key.startsWith('data-')) {
                element.setAttribute(key, value);
            } else {
                element[key] = value;
            }
        });

        children.forEach(child => {
            if (typeof child === 'string') {
                element.appendChild(document.createTextNode(child));
            } else if (child instanceof Node) {
                element.appendChild(child);
            }
        });

        return element;
    };

    const getScrollPosition = () => {
        return {
            x: window.pageXOffset || document.documentElement.scrollLeft,
            y: window.pageYOffset || document.documentElement.scrollTop
        };
    };

    const scrollToElement = (element, offset = 0, behavior = 'smooth') => {
        if (!element) return;

        const elementTop = element.offsetTop - offset;
        window.scrollTo({
            top: elementTop,
            behavior
        });
    };

    const isElementInViewport = (element) => {
        if (!element) return false;

        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    };

    // Local storage utilities
    const storage = {
        set: (key, value, expiry = null) => {
            try {
                const item = {
                    value,
                    expiry: expiry ? Date.now() + expiry : null
                };
                localStorage.setItem(key, JSON.stringify(item));
                return true;
            } catch (error) {
                console.error('LocalStorage set error:', error);
                return false;
            }
        },

        get: (key, defaultValue = null) => {
            try {
                const itemStr = localStorage.getItem(key);
                if (!itemStr) return defaultValue;

                const item = JSON.parse(itemStr);
                if (item.expiry && Date.now() > item.expiry) {
                    localStorage.removeItem(key);
                    return defaultValue;
                }

                return item.value;
            } catch (error) {
                console.error('LocalStorage get error:', error);
                return defaultValue;
            }
        },

        remove: (key) => {
            try {
                localStorage.removeItem(key);
                return true;
            } catch (error) {
                console.error('LocalStorage remove error:', error);
                return false;
            }
        },

        clear: () => {
            try {
                localStorage.clear();
                return true;
            } catch (error) {
                console.error('LocalStorage clear error:', error);
                return false;
            }
        }
    };

    // URL and query string utilities
    const getQueryParams = () => {
        const params = new URLSearchParams(window.location.search);
        const result = {};
        for (const [key, value] of params) {
            result[key] = value;
        }
        return result;
    };

    const setQueryParams = (params) => {
        const url = new URL(window.location);
        Object.entries(params).forEach(([key, value]) => {
            if (value === null || value === undefined) {
                url.searchParams.delete(key);
            } else {
                url.searchParams.set(key, value);
            }
        });
        window.history.replaceState({}, '', url);
    };

    // Color utilities
    const hexToRgb = (hex) => {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    };

    const rgbToHex = (r, g, b) => {
        return "#" + [r, g, b].map(x => {
            const hex = x.toString(16);
            return hex.length === 1 ? "0" + hex : hex;
        }).join("");
    };

    // Random utilities
    const randomInt = (min, max) => {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    };

    const randomFloat = (min, max, decimals = 2) => {
        return parseFloat((Math.random() * (max - min) + min).toFixed(decimals));
    };

    const randomString = (length = 8, charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') => {
        let result = '';
        for (let i = 0; i < length; i++) {
            result += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        return result;
    };

    const randomChoice = (arr) => {
        return arr[Math.floor(Math.random() * arr.length)];
    };

    // File utilities
    const formatFileSize = (bytes) => {
        if (bytes === 0) return '0 Bytes';

        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    const getFileExtension = (filename) => {
        return filename.slice((filename.lastIndexOf(".") - 1 >>> 0) + 2);
    };

    // Device detection utilities
    const device = {
        isMobile: () => /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
        isTablet: () => /iPad|Android(?=.*\bMobile\b)(?=.*\bSafari\b)/i.test(navigator.userAgent),
        isDesktop: () => !device.isMobile() && !device.isTablet(),
        isIOS: () => /iPad|iPhone|iPod/.test(navigator.userAgent),
        isAndroid: () => /Android/.test(navigator.userAgent),
        isSafari: () => /Safari/.test(navigator.userAgent) && !/Chrome/.test(navigator.userAgent),
        isChrome: () => /Chrome/.test(navigator.userAgent),
        isFirefox: () => /Firefox/.test(navigator.userAgent)
    };

    // Theme utilities
    const theme = {
        isDark: () => {
            return localStorage.getItem('theme') === 'dark' ||
                   (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
        },

        toggle: () => {
            const currentTheme = theme.isDark() ? 'dark' : 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            theme.set(newTheme);
        },

        set: (newTheme) => {
            localStorage.setItem('theme', newTheme);
            document.documentElement.classList.toggle('dark', newTheme === 'dark');
        }
    };

    // Public API
    return {
        init,

        // Date and time
        formatDate,
        formatRelativeTime,

        // String manipulation
        truncateString,
        capitalizeFirst,
        capitalizeWords,
        slugify,

        // Number formatting
        formatNumber,
        formatCurrency,
        formatPercentage,

        // Validation
        isValidEmail,
        isValidUsername,
        isValidPassword,
        isValidUrl,

        // Array utilities
        uniqueArray,
        shuffleArray,
        groupBy,
        sortBy,

        // Object utilities
        deepClone,
        mergeDeep,
        isEmpty,

        // DOM utilities
        createElement,
        getScrollPosition,
        scrollToElement,
        isElementInViewport,

        // Storage
        storage,

        // URL utilities
        getQueryParams,
        setQueryParams,

        // Color utilities
        hexToRgb,
        rgbToHex,

        // Random utilities
        randomInt,
        randomFloat,
        randomString,
        randomChoice,

        // File utilities
        formatFileSize,
        getFileExtension,

        // Device detection
        device,

        // Theme utilities
        theme
    };
})();

// Auto-register with ModuleLoader when available
if (typeof ModuleLoader !== 'undefined') {
    if (ModuleLoader.isInitialized) {
        ModuleLoader.register('UIUtils', UIUtils);
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (typeof ModuleLoader !== 'undefined') {
                    ModuleLoader.register('UIUtils', UIUtils);
                }
            }, 100);
        });
    }
}