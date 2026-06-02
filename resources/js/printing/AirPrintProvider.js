/**
 * AirPrintProvider - Browser native print via AirPrint (iOS) or standard browser print
 */
import { PrintLogger } from './PrintLogger.js';

export class AirPrintProvider {
    static async print() {
        const method = 'AirPrint';
        PrintLogger.info(method, 'Starting AirPrint');
        
        try {
            // Optimize for thermal printer before print
            this._optimizeForPrint();
            
            // Wait a moment for styles to apply
            await new Promise(resolve => setTimeout(resolve, 100));
            
            // Trigger print
            window.print();
            
            PrintLogger.success(method, 'AirPrint initiated successfully');
            return { success: true, method };
        } catch (error) {
            PrintLogger.error(method, 'Failed to print via AirPrint', error);
            return { success: false, method, error };
        }
    }
    
    static _optimizeForPrint() {
        const styleId = 'airprint-optimization-style';
        let style = document.getElementById(styleId);
        
        if (!style) {
            style = document.createElement('style');
            style.id = styleId;
            document.head.appendChild(style);
        }
        
        style.textContent = `
            @media print {
                body {
                    margin: 0 !important;
                    padding: 0 !important;
                    width: 80mm !important;
                    max-width: 80mm !important;
                    background: white !important;
                    color: black !important;
                    font-family: 'Courier New', Courier, monospace !important;
                }
                
                .no-print-area,
                .btn,
                button {
                    display: none !important;
                }
                
                #receipt-wrapper {
                    border: none !important;
                    box-shadow: none !important;
                    padding: 0 !important;
                    width: 100% !important;
                    max-width: 80mm !important;
                }
                
                * {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                
                @page {
                    size: 80mm auto;
                    margin: 0;
                }
            }
        `;
    }
}
