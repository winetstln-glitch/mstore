/**
 * IOSBridgeProvider - Print via iOS WKWebView bridge handlers
 */
import { DeviceDetector } from './DeviceDetector.js';
import { PrintLogger } from './PrintLogger.js';

export class IOSBridgeProvider {
    static async print(data) {
        const method = 'IOSBridge';
        PrintLogger.info(method, 'Attempting to print via iOS bridge');
        
        try {
            const handler = DeviceDetector.getIOSBridgeHandler();
            if (!handler) {
                throw new Error('No iOS bridge handler available');
            }
            
            PrintLogger.info(method, 'Using iOS bridge handler');
            
            // Try both direct data and stringified JSON
            try {
                handler.postMessage(data);
            } catch (e) {
                try {
                    handler.postMessage(JSON.stringify(data));
                } catch (e2) {
                    throw new Error('Failed to send data to iOS bridge');
                }
            }
            
            PrintLogger.success(method, 'Data sent to iOS bridge successfully');
            return { success: true, method };
        } catch (error) {
            PrintLogger.error(method, 'Failed to print via iOS bridge', error);
            return { success: false, method, error };
        }
    }
    
    static isAvailable() {
        const device = DeviceDetector.detect();
        return device.isIOS && device.hasIOSBridge;
    }
}
