/**
 * PDFProvider - Generate receipt as PDF using browser print or jsPDF
 */
import { PrintLogger } from './PrintLogger.js';

export class PDFProvider {
    static async printOrDownload(receiptWrapperId = 'receipt-wrapper', filename = 'receipt.pdf') {
        const method = 'PDF';
        PrintLogger.info(method, 'Starting PDF generation');
        
        try {
            // First try browser print to PDF
            // This leverages the browser's built-in print to PDF functionality
            await this._printToPDF();
            
            PrintLogger.success(method, 'PDF print initiated successfully');
            return { success: true, method };
        } catch (error) {
            PrintLogger.warn(method, 'Browser print to PDF failed', error);
            
            // Fallback: just trigger standard print which user can save as PDF
            try {
                PrintLogger.info(method, 'Falling back to standard browser print');
                window.print();
                return { success: true, method: 'PDF_Fallback' };
            } catch (e) {
                PrintLogger.error(method, 'Failed to generate PDF', e);
                return { success: false, method, error: e };
            }
        }
    }
    
    static async _printToPDF() {
        // Optimize for PDF printing
        this._optimizeForPDF();
        await new Promise(resolve => setTimeout(resolve, 100));
        window.print();
    }
    
    static _optimizeForPDF() {
        const styleId = 'pdf-optimization-style';
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
                    padding: 10mm !important;
                }
                
                * {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                
                @page {
                    size: auto;
                    margin: 0;
                }
            }
        `;
    }
}
