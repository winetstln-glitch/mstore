/**
 * BrowserPrintProvider - Standard browser printing for desktop
 */
import { PrintLogger } from './PrintLogger.js';
import { AirPrintProvider } from './AirPrintProvider.js';

// Reuse AirPrint logic since it's the same mechanism
export class BrowserPrintProvider {
    static async print() {
        const method = 'BrowserPrint';
        PrintLogger.info(method, 'Starting browser print');
        
        try {
            const result = await AirPrintProvider.print();
            return {
                ...result,
                method
            };
        } catch (error) {
            PrintLogger.error(method, 'Failed to print via browser', error);
            return { success: false, method, error };
        }
    }
}
