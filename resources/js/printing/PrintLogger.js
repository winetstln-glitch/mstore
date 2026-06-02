/**
 * PrintLogger - Comprehensive logging for printing operations
 * Stores logs in localStorage and optionally sends to server
 */
export class PrintLogger {
    static STORAGE_KEY = 'mstore_print_logs';
    static MAX_LOGS = 100;
    
    static log(level, method, message, data = {}) {
        const log = {
            id: Date.now(),
            timestamp: new Date().toISOString(),
            level,
            method,
            message,
            data
        };
        
        // Log to console
        if (level === 'error') {
            console.error('[PrintLogger]', log);
        } else if (level === 'warn') {
            console.warn('[PrintLogger]', log);
        } else {
            console.log('[PrintLogger]', log);
        }
        
        // Store in localStorage
        this._storeLog(log);
        
        return log;
    }
    
    static _storeLog(log) {
        try {
            let logs = this.getLogs();
            logs.unshift(log);
            if (logs.length > this.MAX_LOGS) {
                logs = logs.slice(0, this.MAX_LOGS);
            }
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(logs));
        } catch (e) {
            console.error('[PrintLogger] Failed to store log:', e);
        }
    }
    
    static getLogs() {
        try {
            const stored = localStorage.getItem(this.STORAGE_KEY);
            return stored ? JSON.parse(stored) : [];
        } catch (e) {
            console.error('[PrintLogger] Failed to get logs:', e);
            return [];
        }
    }
    
    static clearLogs() {
        localStorage.removeItem(this.STORAGE_KEY);
    }
    
    static info(method, message, data = {}) {
        return this.log('info', method, message, data);
    }
    
    static success(method, message, data = {}) {
        return this.log('success', method, message, data);
    }
    
    static warn(method, message, data = {}) {
        return this.log('warn', method, message, data);
    }
    
    static error(method, message, error = null, data = {}) {
        return this.log('error', method, message, {
            ...data,
            errorMessage: error?.message,
            errorStack: error?.stack
        });
    }
}
