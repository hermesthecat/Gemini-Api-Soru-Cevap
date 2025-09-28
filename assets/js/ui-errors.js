/**
 * UI Errors Module - Error Handling and Logging
 *
 * This module provides centralized error handling, logging, user feedback
 * for errors, and debugging utilities for the entire application.
 *
 * Phase 6 of ui-handler.js modularization - Utility Modules
 * Created: 2025-09-28
 */

const UIErrors = (() => {
    let dom = {};
    let errorLog = [];
    let config = {
        maxErrorLogSize: 100,
        showDebugInfo: false,
        enableConsoleLogging: true,
        enableRemoteLogging: false,
        logLevel: 'error' // 'debug', 'info', 'warn', 'error'
    };

    const init = (domElements, userConfig = {}) => {
        dom = domElements;
        config = { ...config, ...userConfig };

        // Setup global error handlers
        setupGlobalErrorHandlers();

        console.log('UIErrors initialized with config:', config);
    };

    // Log levels
    const LogLevel = {
        DEBUG: 0,
        INFO: 1,
        WARN: 2,
        ERROR: 3
    };

    // Setup global error handlers
    const setupGlobalErrorHandlers = () => {
        // Unhandled JavaScript errors
        window.addEventListener('error', (event) => {
            logError('Global Error', {
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                error: event.error?.stack
            });
        });

        // Unhandled promise rejections
        window.addEventListener('unhandledrejection', (event) => {
            logError('Unhandled Promise Rejection', {
                reason: event.reason,
                promise: event.promise
            });
        });

        // Console error override for additional logging
        if (config.enableConsoleLogging) {
            const originalConsoleError = console.error;
            console.error = function(...args) {
                logError('Console Error', { args: args.join(' ') });
                originalConsoleError.apply(console, args);
            };
        }
    };

    // Central logging function
    const log = (level, category, message, data = null) => {
        const logLevelNum = LogLevel[level.toUpperCase()] || LogLevel.ERROR;
        const configLevelNum = LogLevel[config.logLevel.toUpperCase()] || LogLevel.ERROR;

        if (logLevelNum < configLevelNum) return;

        const logEntry = {
            timestamp: new Date().toISOString(),
            level: level.toUpperCase(),
            category,
            message,
            data,
            url: window.location.href,
            userAgent: navigator.userAgent,
            stackTrace: new Error().stack
        };

        // Add to local log
        errorLog.unshift(logEntry);
        if (errorLog.length > config.maxErrorLogSize) {
            errorLog.pop();
        }

        // Console logging
        if (config.enableConsoleLogging) {
            const consoleMethod = level === 'debug' ? 'log' : level;
            console[consoleMethod](`[${category}] ${message}`, data || '');
        }

        // Remote logging (if enabled)
        if (config.enableRemoteLogging) {
            sendToRemoteLogger(logEntry);
        }

        // Show debug info overlay (if enabled)
        if (config.showDebugInfo && level === 'error') {
            showDebugOverlay(logEntry);
        }
    };

    // Specific log level functions
    const logDebug = (category, message, data) => log('debug', category, message, data);
    const logInfo = (category, message, data) => log('info', category, message, data);
    const logWarn = (category, message, data) => log('warn', category, message, data);
    const logError = (category, message, data) => log('error', category, message, data);

    // Handle API errors with user feedback
    const handleApiError = (error, context = 'API Request') => {
        let userMessage = 'Bir hata oluştu. Lütfen tekrar deneyin.';
        let logData = {};

        if (error.response) {
            // Server responded with error status
            const status = error.response.status;
            logData = {
                status,
                statusText: error.response.statusText,
                data: error.response.data
            };

            switch (status) {
                case 400:
                    userMessage = 'Geçersiz istek. Lütfen bilgilerinizi kontrol edin.';
                    break;
                case 401:
                    userMessage = 'Oturum süreniz dolmuş. Lütfen tekrar giriş yapın.';
                    break;
                case 403:
                    userMessage = 'Bu işlem için yetkiniz bulunmuyor.';
                    break;
                case 404:
                    userMessage = 'İstenen kaynak bulunamadı.';
                    break;
                case 429:
                    userMessage = 'Çok fazla istek gönderdiniz. Lütfen bekleyin.';
                    break;
                case 500:
                    userMessage = 'Sunucu hatası oluştu. Lütfen daha sonra tekrar deneyin.';
                    break;
                case 503:
                    userMessage = 'Servis geçici olarak kullanılamıyor.';
                    break;
            }
        } else if (error.request) {
            // Network error
            logData = { request: error.request };
            userMessage = 'İnternet bağlantınızı kontrol edin.';
        } else {
            // Other error
            logData = { message: error.message };
        }

        logError(context, userMessage, logData);
        showUserError(userMessage);

        return { userMessage, logData };
    };

    // Handle module loading errors
    const handleModuleError = (moduleName, error, fallback = null) => {
        const message = `${moduleName} modülü yüklenirken hata oluştu`;
        logError('Module Loading', message, {
            moduleName,
            error: error.message,
            stack: error.stack
        });

        if (fallback && typeof fallback === 'function') {
            try {
                fallback();
                logInfo('Module Loading', `${moduleName} için fallback kullanıldı`);
            } catch (fallbackError) {
                logError('Module Loading', `${moduleName} fallback da başarısız`, fallbackError);
            }
        }

        showUserError(`${moduleName} yüklenirken sorun oluştu`);
    };

    // Show user-friendly error message
    const showUserError = (message, type = 'error') => {
        const UICore = ModuleLoader?.getModule('UICore');
        if (UICore && UICore.showToast) {
            UICore.showToast(message, type);
        } else {
            // Fallback to basic alert
            alert(message);
        }
    };

    // Try-catch wrapper for async functions
    const safeAsync = async (asyncFn, context = 'Async Operation', fallback = null) => {
        try {
            return await asyncFn();
        } catch (error) {
            logError(context, 'Async operation failed', {
                error: error.message,
                stack: error.stack
            });

            if (fallback && typeof fallback === 'function') {
                try {
                    return await fallback();
                } catch (fallbackError) {
                    logError(context, 'Fallback also failed', fallbackError);
                    throw fallbackError;
                }
            }

            throw error;
        }
    };

    // Try-catch wrapper for sync functions
    const safe = (fn, context = 'Operation', fallback = null) => {
        try {
            return fn();
        } catch (error) {
            logError(context, 'Operation failed', {
                error: error.message,
                stack: error.stack
            });

            if (fallback && typeof fallback === 'function') {
                try {
                    return fallback();
                } catch (fallbackError) {
                    logError(context, 'Fallback also failed', fallbackError);
                    return null;
                }
            }

            return null;
        }
    };

    // Validate required parameters
    const validateRequired = (params, requiredFields, context = 'Validation') => {
        const missing = [];

        requiredFields.forEach(field => {
            if (params[field] === undefined || params[field] === null || params[field] === '') {
                missing.push(field);
            }
        });

        if (missing.length > 0) {
            const message = `Gerekli alanlar eksik: ${missing.join(', ')}`;
            logError(context, message, { missing, params });
            throw new Error(message);
        }

        return true;
    };

    // Show debug overlay for developers
    const showDebugOverlay = (logEntry) => {
        if (!config.showDebugInfo) return;

        const overlay = document.createElement('div');
        overlay.className = 'fixed top-4 right-4 bg-red-600 text-white p-4 rounded-lg shadow-lg z-50 max-w-md';
        overlay.innerHTML = `
            <div class="flex justify-between items-start mb-2">
                <h4 class="font-bold text-sm">Debug Error</h4>
                <button class="text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="text-xs space-y-1">
                <div><strong>Category:</strong> ${logEntry.category}</div>
                <div><strong>Message:</strong> ${logEntry.message}</div>
                <div><strong>Time:</strong> ${new Date(logEntry.timestamp).toLocaleTimeString()}</div>
                ${logEntry.data ? `<div><strong>Data:</strong> ${JSON.stringify(logEntry.data, null, 2).substring(0, 100)}...</div>` : ''}
            </div>
        `;

        document.body.appendChild(overlay);

        // Auto-remove after 10 seconds
        setTimeout(() => {
            if (overlay.parentElement) {
                overlay.remove();
            }
        }, 10000);
    };

    // Send logs to remote server (placeholder)
    const sendToRemoteLogger = async (logEntry) => {
        if (!config.enableRemoteLogging) return;

        try {
            // This would typically send to a logging service
            // await fetch('/api/logs', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify(logEntry)
            // });
            console.log('Would send to remote logger:', logEntry);
        } catch (error) {
            console.error('Failed to send log to remote server:', error);
        }
    };

    // Get error statistics
    const getErrorStats = () => {
        const stats = {
            total: errorLog.length,
            byLevel: {},
            byCategory: {},
            recent: errorLog.slice(0, 10)
        };

        errorLog.forEach(entry => {
            stats.byLevel[entry.level] = (stats.byLevel[entry.level] || 0) + 1;
            stats.byCategory[entry.category] = (stats.byCategory[entry.category] || 0) + 1;
        });

        return stats;
    };

    // Export error log
    const exportErrorLog = (format = 'json') => {
        const data = {
            exported_at: new Date().toISOString(),
            config,
            stats: getErrorStats(),
            logs: errorLog
        };

        let content, filename, mimeType;

        switch (format) {
            case 'csv':
                const csvHeaders = ['Timestamp', 'Level', 'Category', 'Message', 'Data'];
                const csvRows = errorLog.map(entry => [
                    entry.timestamp,
                    entry.level,
                    entry.category,
                    entry.message,
                    JSON.stringify(entry.data || {})
                ]);
                content = [csvHeaders, ...csvRows].map(row => row.join(',')).join('\n');
                filename = `error-log-${new Date().toISOString().split('T')[0]}.csv`;
                mimeType = 'text/csv';
                break;

            default:
                content = JSON.stringify(data, null, 2);
                filename = `error-log-${new Date().toISOString().split('T')[0]}.json`;
                mimeType = 'application/json';
        }

        const blob = new Blob([content], { type: mimeType });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
    };

    // Clear error log
    const clearErrorLog = () => {
        errorLog = [];
        logInfo('UIErrors', 'Error log cleared');
    };

    // Performance monitoring integration
    const measurePerformance = (name, fn) => {
        const start = performance.now();
        try {
            const result = fn();
            const duration = performance.now() - start;
            logDebug('Performance', `${name} completed`, { duration: `${duration.toFixed(2)}ms` });
            return result;
        } catch (error) {
            const duration = performance.now() - start;
            logError('Performance', `${name} failed`, {
                duration: `${duration.toFixed(2)}ms`,
                error: error.message
            });
            throw error;
        }
    };

    // Public API
    return {
        init,

        // Logging functions
        log,
        logDebug,
        logInfo,
        logWarn,
        logError,

        // Error handling
        handleApiError,
        handleModuleError,
        showUserError,

        // Safety wrappers
        safeAsync,
        safe,
        validateRequired,

        // Utilities
        getErrorStats,
        exportErrorLog,
        clearErrorLog,
        measurePerformance,

        // Configuration
        setConfig: (newConfig) => { config = { ...config, ...newConfig }; },
        getConfig: () => ({ ...config }),

        // Access to error log (read-only)
        get errorLog() { return [...errorLog]; }
    };
})();

// Auto-register with ModuleLoader when available
if (typeof ModuleLoader !== 'undefined') {
    if (ModuleLoader.isInitialized) {
        ModuleLoader.register('UIErrors', UIErrors);
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (typeof ModuleLoader !== 'undefined') {
                    ModuleLoader.register('UIErrors', UIErrors);
                }
            }, 100);
        });
    }
}