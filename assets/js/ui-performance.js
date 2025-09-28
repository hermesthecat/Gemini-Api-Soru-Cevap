/**
 * UI Performance Module - Performance Monitoring and Optimization
 *
 * This module provides performance monitoring, metrics collection,
 * optimization utilities, and real-time performance analysis.
 *
 * Phase 6 of ui-handler.js modularization - Utility Modules
 * Created: 2025-09-28
 */

const UIPerformance = (() => {
    let dom = {};
    let metrics = {
        pageLoad: {},
        moduleLoad: {},
        userInteractions: {},
        apiCalls: {},
        memoryUsage: [],
        renderTimes: []
    };

    let config = {
        enableMonitoring: true,
        enableMemoryTracking: true,
        enableRenderTracking: true,
        enableApiTracking: true,
        memoryCheckInterval: 5000, // 5 seconds
        maxMetricsHistory: 100,
        performanceThresholds: {
            moduleLoad: 1000, // 1 second
            apiCall: 3000, // 3 seconds
            render: 16, // 16ms for 60fps
            memory: 50 * 1024 * 1024 // 50MB
        }
    };

    const init = (domElements, userConfig = {}) => {
        dom = domElements;
        config = { ...config, ...userConfig };

        if (config.enableMonitoring) {
            startPerformanceMonitoring();
            trackPageLoad();

            if (config.enableMemoryTracking) {
                startMemoryTracking();
            }
        }

        console.log('UIPerformance initialized');
    };

    // Start performance monitoring
    const startPerformanceMonitoring = () => {
        // Performance observer for various performance entries
        if ('PerformanceObserver' in window) {
            // Navigation timing
            const navObserver = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.entryType === 'navigation') {
                        recordNavigationTiming(entry);
                    }
                }
            });
            navObserver.observe({ entryTypes: ['navigation'] });

            // Resource timing
            const resourceObserver = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.entryType === 'resource') {
                        recordResourceTiming(entry);
                    }
                }
            });
            resourceObserver.observe({ entryTypes: ['resource'] });

            // Measure timing
            const measureObserver = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.entryType === 'measure') {
                        recordMeasureTiming(entry);
                    }
                }
            });
            measureObserver.observe({ entryTypes: ['measure'] });
        }
    };

    // Track page load performance
    const trackPageLoad = () => {
        window.addEventListener('load', () => {
            setTimeout(() => {
                const navTiming = performance.getEntriesByType('navigation')[0];
                if (navTiming) {
                    metrics.pageLoad = {
                        timestamp: Date.now(),
                        domContentLoaded: navTiming.domContentLoadedEventEnd - navTiming.domContentLoadedEventStart,
                        pageLoad: navTiming.loadEventEnd - navTiming.loadEventStart,
                        totalLoad: navTiming.loadEventEnd - navTiming.navigationStart,
                        domInteractive: navTiming.domInteractive - navTiming.navigationStart,
                        firstPaint: getFirstPaint(),
                        firstContentfulPaint: getFirstContentfulPaint()
                    };

                    logPerformance('Page Load', metrics.pageLoad);
                }
            }, 100);
        });
    };

    // Get First Paint timing
    const getFirstPaint = () => {
        const paintEntries = performance.getEntriesByType('paint');
        const firstPaint = paintEntries.find(entry => entry.name === 'first-paint');
        return firstPaint ? firstPaint.startTime : null;
    };

    // Get First Contentful Paint timing
    const getFirstContentfulPaint = () => {
        const paintEntries = performance.getEntriesByType('paint');
        const fcp = paintEntries.find(entry => entry.name === 'first-contentful-paint');
        return fcp ? fcp.startTime : null;
    };

    // Track module loading performance
    const trackModuleLoad = (moduleName, startTime, endTime) => {
        const duration = endTime - startTime;

        if (!metrics.moduleLoad[moduleName]) {
            metrics.moduleLoad[moduleName] = [];
        }

        const loadMetric = {
            timestamp: Date.now(),
            duration,
            startTime,
            endTime
        };

        metrics.moduleLoad[moduleName].push(loadMetric);

        // Keep only recent entries
        if (metrics.moduleLoad[moduleName].length > config.maxMetricsHistory) {
            metrics.moduleLoad[moduleName].shift();
        }

        // Check if module load is slow
        if (duration > config.performanceThresholds.moduleLoad) {
            logPerformanceIssue('Slow Module Load', {
                module: moduleName,
                duration: `${duration.toFixed(2)}ms`,
                threshold: `${config.performanceThresholds.moduleLoad}ms`
            });
        }

        logPerformance('Module Load', { module: moduleName, duration: `${duration.toFixed(2)}ms` });
    };

    // Track API call performance
    const trackApiCall = (endpoint, method, startTime, endTime, success = true) => {
        const duration = endTime - startTime;
        const key = `${method} ${endpoint}`;

        if (!metrics.apiCalls[key]) {
            metrics.apiCalls[key] = [];
        }

        const apiMetric = {
            timestamp: Date.now(),
            duration,
            success,
            startTime,
            endTime
        };

        metrics.apiCalls[key].push(apiMetric);

        // Keep only recent entries
        if (metrics.apiCalls[key].length > config.maxMetricsHistory) {
            metrics.apiCalls[key].shift();
        }

        // Check if API call is slow
        if (duration > config.performanceThresholds.apiCall) {
            logPerformanceIssue('Slow API Call', {
                endpoint: key,
                duration: `${duration.toFixed(2)}ms`,
                threshold: `${config.performanceThresholds.apiCall}ms`,
                success
            });
        }

        logPerformance('API Call', {
            endpoint: key,
            duration: `${duration.toFixed(2)}ms`,
            success
        });
    };

    // Track user interaction performance
    const trackUserInteraction = (interaction, element, duration) => {
        if (!metrics.userInteractions[interaction]) {
            metrics.userInteractions[interaction] = [];
        }

        const interactionMetric = {
            timestamp: Date.now(),
            element,
            duration
        };

        metrics.userInteractions[interaction].push(interactionMetric);

        // Keep only recent entries
        if (metrics.userInteractions[interaction].length > config.maxMetricsHistory) {
            metrics.userInteractions[interaction].shift();
        }

        logPerformance('User Interaction', {
            interaction,
            element,
            duration: `${duration.toFixed(2)}ms`
        });
    };

    // Track render performance
    const trackRenderTime = (componentName, renderTime) => {
        if (!config.enableRenderTracking) return;

        const renderMetric = {
            timestamp: Date.now(),
            component: componentName,
            duration: renderTime
        };

        metrics.renderTimes.push(renderMetric);

        // Keep only recent entries
        if (metrics.renderTimes.length > config.maxMetricsHistory) {
            metrics.renderTimes.shift();
        }

        // Check if render is slow
        if (renderTime > config.performanceThresholds.render) {
            logPerformanceIssue('Slow Render', {
                component: componentName,
                duration: `${renderTime.toFixed(2)}ms`,
                threshold: `${config.performanceThresholds.render}ms`
            });
        }
    };

    // Start memory tracking
    const startMemoryTracking = () => {
        if (!('memory' in performance)) return;

        setInterval(() => {
            const memInfo = performance.memory;
            const memoryMetric = {
                timestamp: Date.now(),
                used: memInfo.usedJSHeapSize,
                total: memInfo.totalJSHeapSize,
                limit: memInfo.jsHeapSizeLimit
            };

            metrics.memoryUsage.push(memoryMetric);

            // Keep only recent entries
            if (metrics.memoryUsage.length > config.maxMetricsHistory) {
                metrics.memoryUsage.shift();
            }

            // Check for memory issues
            if (memInfo.usedJSHeapSize > config.performanceThresholds.memory) {
                logPerformanceIssue('High Memory Usage', {
                    used: `${(memInfo.usedJSHeapSize / 1024 / 1024).toFixed(2)}MB`,
                    threshold: `${(config.performanceThresholds.memory / 1024 / 1024).toFixed(2)}MB`
                });
            }
        }, config.memoryCheckInterval);
    };

    // Performance measurement wrapper
    const measure = (name, fn) => {
        const startTime = performance.now();
        performance.mark(`${name}-start`);

        try {
            const result = fn();

            if (result && typeof result.then === 'function') {
                // Async function
                return result.finally(() => {
                    const endTime = performance.now();
                    performance.mark(`${name}-end`);
                    performance.measure(name, `${name}-start`, `${name}-end`);

                    const duration = endTime - startTime;
                    logPerformance('Measure', { name, duration: `${duration.toFixed(2)}ms` });
                });
            } else {
                // Sync function
                const endTime = performance.now();
                performance.mark(`${name}-end`);
                performance.measure(name, `${name}-start`, `${name}-end`);

                const duration = endTime - startTime;
                logPerformance('Measure', { name, duration: `${duration.toFixed(2)}ms` });

                return result;
            }
        } catch (error) {
            const endTime = performance.now();
            const duration = endTime - startTime;
            logPerformanceIssue('Measure Error', {
                name,
                duration: `${duration.toFixed(2)}ms`,
                error: error.message
            });
            throw error;
        }
    };

    // Debounce function for performance
    const debounce = (fn, delay) => {
        let timeoutId;
        return (...args) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => fn.apply(null, args), delay);
        };
    };

    // Throttle function for performance
    const throttle = (fn, delay) => {
        let lastCall = 0;
        return (...args) => {
            const now = Date.now();
            if (now - lastCall >= delay) {
                lastCall = now;
                return fn.apply(null, args);
            }
        };
    };

    // Optimize DOM operations with requestAnimationFrame
    const optimizedDOMUpdate = (fn) => {
        return new Promise(resolve => {
            requestAnimationFrame(() => {
                const startTime = performance.now();
                fn();
                const endTime = performance.now();
                trackRenderTime('DOM Update', endTime - startTime);
                resolve();
            });
        });
    };

    // Get performance summary
    const getPerformanceSummary = () => {
        const summary = {
            pageLoad: metrics.pageLoad,
            moduleLoadAvg: calculateModuleLoadAverage(),
            apiCallsAvg: calculateApiCallsAverage(),
            currentMemory: getCurrentMemoryUsage(),
            renderAvg: calculateRenderAverage(),
            totalMetrics: getTotalMetricsCount()
        };

        return summary;
    };

    // Calculate average module load time
    const calculateModuleLoadAverage = () => {
        const allLoads = Object.values(metrics.moduleLoad).flat();
        if (allLoads.length === 0) return 0;

        const total = allLoads.reduce((sum, load) => sum + load.duration, 0);
        return total / allLoads.length;
    };

    // Calculate average API call time
    const calculateApiCallsAverage = () => {
        const allCalls = Object.values(metrics.apiCalls).flat();
        if (allCalls.length === 0) return 0;

        const total = allCalls.reduce((sum, call) => sum + call.duration, 0);
        return total / allCalls.length;
    };

    // Get current memory usage
    const getCurrentMemoryUsage = () => {
        if (metrics.memoryUsage.length === 0) return null;
        return metrics.memoryUsage[metrics.memoryUsage.length - 1];
    };

    // Calculate average render time
    const calculateRenderAverage = () => {
        if (metrics.renderTimes.length === 0) return 0;

        const total = metrics.renderTimes.reduce((sum, render) => sum + render.duration, 0);
        return total / metrics.renderTimes.length;
    };

    // Get total metrics count
    const getTotalMetricsCount = () => {
        return {
            moduleLoads: Object.values(metrics.moduleLoad).flat().length,
            apiCalls: Object.values(metrics.apiCalls).flat().length,
            userInteractions: Object.values(metrics.userInteractions).flat().length,
            renders: metrics.renderTimes.length,
            memorySnapshots: metrics.memoryUsage.length
        };
    };

    // Record performance timing entries
    const recordNavigationTiming = (entry) => {
        logPerformance('Navigation Timing', {
            type: entry.type,
            redirectCount: entry.redirectCount,
            transferSize: entry.transferSize,
            duration: entry.duration
        });
    };

    const recordResourceTiming = (entry) => {
        if (entry.name.includes('.js') || entry.name.includes('.css')) {
            logPerformance('Resource Timing', {
                name: entry.name.split('/').pop(),
                type: entry.initiatorType,
                transferSize: entry.transferSize,
                duration: entry.duration
            });
        }
    };

    const recordMeasureTiming = (entry) => {
        logPerformance('Custom Measure', {
            name: entry.name,
            duration: entry.duration
        });
    };

    // Export performance data
    const exportPerformanceData = (format = 'json') => {
        const data = {
            exported_at: new Date().toISOString(),
            config,
            summary: getPerformanceSummary(),
            metrics
        };

        let content, filename, mimeType;

        switch (format) {
            case 'csv':
                content = convertMetricsToCSV(data);
                filename = `performance-${new Date().toISOString().split('T')[0]}.csv`;
                mimeType = 'text/csv';
                break;
            default:
                content = JSON.stringify(data, null, 2);
                filename = `performance-${new Date().toISOString().split('T')[0]}.json`;
                mimeType = 'application/json';
        }

        const blob = new Blob([content], { type: mimeType });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
    };

    // Convert metrics to CSV format
    const convertMetricsToCSV = (data) => {
        const rows = [
            ['Metric Type', 'Name', 'Duration (ms)', 'Timestamp', 'Additional Info']
        ];

        // Add module load metrics
        Object.entries(data.metrics.moduleLoad).forEach(([module, loads]) => {
            loads.forEach(load => {
                rows.push(['Module Load', module, load.duration.toFixed(2), new Date(load.timestamp).toISOString(), '']);
            });
        });

        // Add API call metrics
        Object.entries(data.metrics.apiCalls).forEach(([endpoint, calls]) => {
            calls.forEach(call => {
                rows.push(['API Call', endpoint, call.duration.toFixed(2), new Date(call.timestamp).toISOString(), call.success ? 'Success' : 'Failed']);
            });
        });

        return rows.map(row => row.join(',')).join('\n');
    };

    // Log performance information
    const logPerformance = (category, data) => {
        const UIErrors = ModuleLoader?.getModule('UIErrors');
        if (UIErrors) {
            UIErrors.logDebug(category, 'Performance metric recorded', data);
        }
    };

    // Log performance issues
    const logPerformanceIssue = (category, data) => {
        const UIErrors = ModuleLoader?.getModule('UIErrors');
        if (UIErrors) {
            UIErrors.logWarn(category, 'Performance threshold exceeded', data);
        }
    };

    // Clear performance metrics
    const clearMetrics = () => {
        metrics = {
            pageLoad: {},
            moduleLoad: {},
            userInteractions: {},
            apiCalls: {},
            memoryUsage: [],
            renderTimes: []
        };
        logPerformance('UIPerformance', 'Metrics cleared');
    };

    // Public API
    return {
        init,

        // Performance tracking
        trackModuleLoad,
        trackApiCall,
        trackUserInteraction,
        trackRenderTime,

        // Performance utilities
        measure,
        debounce,
        throttle,
        optimizedDOMUpdate,

        // Data access
        getPerformanceSummary,
        exportPerformanceData,
        clearMetrics,

        // Configuration
        setConfig: (newConfig) => { config = { ...config, ...newConfig }; },
        getConfig: () => ({ ...config }),

        // Access to metrics (read-only)
        get metrics() { return JSON.parse(JSON.stringify(metrics)); }
    };
})();

// Auto-register with ModuleLoader when available
if (typeof ModuleLoader !== 'undefined') {
    if (ModuleLoader.isInitialized) {
        ModuleLoader.register('UIPerformance', UIPerformance);
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (typeof ModuleLoader !== 'undefined') {
                    ModuleLoader.register('UIPerformance', UIPerformance);
                }
            }, 100);
        });
    }
}