/**
 * AndroidBridgeProvider - Print via Android WebView bridge handlers
 */
import { DeviceDetector } from './DeviceDetector.js';
import { PrintLogger } from './PrintLogger.js';

export class AndroidBridgeProvider {
    static async print(data) {
        const method = 'AndroidBridge';
        PrintLogger.info(method, 'Attempting to print via Android bridge');
        
        try {
            const handler = DeviceDetector.getAndroidBridgeHandler();
            if (!handler) {
                throw new Error('No Android bridge handler available');
            }
            
            PrintLogger.info(method, 'Using Android bridge handler');
            
            const result = handler(data);
            
            if (result === false) {
                throw new Error('Android bridge returned false');
            }
            
            PrintLogger.success(method, 'Data sent to Android bridge successfully');
            return { success: true, method };
        } catch (error) {
            PrintLogger.error(method, 'Failed to print via Android bridge', error);
            return { success: false, method, error };
        }
    }
    
    static isAvailable() {
        const device = DeviceDetector.detect();
        return device.isAndroid && device.hasAndroidBridge;
    }
}
