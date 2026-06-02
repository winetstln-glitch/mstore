/**
 * PNGProvider - Generate and share receipt as PNG
 */
import { PrintLogger } from './PrintLogger.js';

export class PNGProvider {
    static async share(receiptWrapperId = 'receipt-wrapper', filename = 'receipt.png') {
        const method = 'PNGShare';
        PrintLogger.info(method, 'Starting PNG generation and share');
        
        try {
            // Wait for html2canvas to be available
            if (typeof html2canvas === 'undefined') {
                throw new Error('html2canvas library not available');
            }
            
            const element = document.getElementById(receiptWrapperId);
            if (!element) {
                throw new Error(`Receipt element #${receiptWrapperId} not found`);
            }
            
            const canvas = await html2canvas(element, {
                scale: 2,
                backgroundColor: '#ffffff',
                useCORS: true,
                logging: false
            });
            
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
            
            if (!blob) {
                throw new Error('Failed to generate PNG blob');
            }
            
            const file = new File([blob], filename, { type: 'image/png' });
            
            // Try to share
            if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
                PrintLogger.info(method, 'Opening native share dialog');
                await navigator.share({ files: [file], title: 'Receipt', text: 'Share receipt' });
                PrintLogger.success(method, 'PNG shared successfully');
                return { success: true, method };
            } else {
                // Fallback to download
                PrintLogger.info(method, 'Share not available, falling back to download');
                this.download(canvas, filename);
                return { success: true, method: 'PNGDownload', downloaded: true };
            }
        } catch (error) {
            PrintLogger.error(method, 'Failed to generate/share PNG', error);
            return { success: false, method, error };
        }
    }
    
    static download(canvas, filename = 'receipt.png') {
        const link = document.createElement('a');
        link.download = filename;
        link.href = canvas.toDataURL('image/png');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        PrintLogger.success('PNGDownload', 'PNG downloaded successfully');
    }
}
