/**
 * PrintManager - Main orchestrator for all printing operations
 * Handles fallback logic, device detection, and print flow
 */
import { DeviceDetector } from './DeviceDetector.js';
import { PrintLogger } from './PrintLogger.js';
import { WebBluetoothProvider } from './WebBluetoothProvider.js';
import { IOSBridgeProvider } from './IOSBridgeProvider.js';
import { AndroidBridgeProvider } from './AndroidBridgeProvider.js';
import { AirPrintProvider } from './AirPrintProvider.js';
import { BrowserPrintProvider } from './BrowserPrintProvider.js';
import { PDFProvider } from './PDFProvider.js';
import { PNGProvider } from './PNGProvider.js';

export class PrintManager {
    constructor(printData, options = {}) {
        this.device = DeviceDetector.detect();
        this.printData = printData;
        this.options = {
            receiptWrapperId: 'receipt-wrapper',
            receiptFilename: 'receipt',
            ...options
        };
        
        PrintLogger.info('PrintManager', 'Initialized', { device: this.device });
    }
    
    /**
     * Main print method with automatic fallback chain
     */
    async print() {
        PrintLogger.info('PrintManager', 'Starting automatic print flow');
        
        // Get available providers for this device
        const providers = this._getPrioritizedProviders();
        
        for (const providerInfo of providers) {
            PrintLogger.info('PrintManager', `Trying provider: ${providerInfo.name}`);
            
            try {
                const result = await this._tryProvider(providerInfo);
                
                if (result.success) {
                    PrintLogger.success('PrintManager', `Print successful via ${providerInfo.name}`);
                    this._updateStatus('Cetak berhasil!', 'success');
                    return result;
                } else {
                    PrintLogger.warn('PrintManager', `Provider ${providerInfo.name} failed, trying next...`);
                }
            } catch (error) {
                PrintLogger.error('PrintManager', `Provider ${providerInfo.name} threw error`, error);
            }
        }
        
        // If all providers failed
        PrintLogger.error('PrintManager', 'All providers failed');
        this._updateStatus('Gagal mencetak. Silakan coba lagi.', 'error');
        return { success: false, error: 'All providers failed' };
    }
    
    /**
     * Print using a specific method
     */
    async printViaMethod(method) {
        PrintLogger.info('PrintManager', `Printing via specific method: ${method}`);
        
        const providers = this._getAllProviders();
        const providerInfo = providers.find(p => p.name === method);
        
        if (!providerInfo) {
            PrintLogger.error('PrintManager', `Unknown method: ${method}`);
            return { success: false, error: 'Unknown method' };
        }
        
        try {
            const result = await this._tryProvider(providerInfo);
            
            if (result.success) {
                this._updateStatus('Cetak berhasil!', 'success');
            }
            
            return result;
        } catch (error) {
            PrintLogger.error('PrintManager', `Failed to print via ${method}`, error);
            return { success: false, error };
        }
    }
    
    /**
     * Get list of available methods for current device
     */
    getAvailableMethods() {
        const methods = [];
        const providers = this._getPrioritizedProviders();
        
        for (const provider of providers) {
            if (provider.isAvailable()) {
                methods.push({
                    name: provider.name,
                    label: this._getMethodLabel(provider.name),
                    icon: this._getMethodIcon(provider.name)
                });
            }
        }
        
        // Always add PDF as fallback
        if (!methods.find(m => m.name === 'PDF')) {
            methods.push({
                name: 'PDF',
                label: 'Simpan PDF',
                icon: 'fa-file-pdf'
            });
        }
        
        // Always add PNG Share
        if (!methods.find(m => m.name === 'PNGShare')) {
            methods.push({
                name: 'PNGShare',
                label: 'Bagikan PNG',
                icon: 'fa-share-alt'
            });
        }
        
        return methods;
    }
    
    /**
     * Check if device is iOS without bridge (needs special UI)
     */
    isIOSWithoutBridge() {
        return this.device.isIOS && !this.device.hasIOSBridge;
    }
    
    /**
     * Show iOS fallback dialog
     */
    showIOSFallbackDialog() {
        if (!this.isIOSWithoutBridge()) {
            return;
        }
        
        // Create modal
        this._createIOSDialog();
    }
    
    _createIOSDialog() {
        const existing = document.getElementById('ios-print-dialog');
        if (existing) {
            existing.style.display = 'block';
            return;
        }
        
        const dialog = document.createElement('div');
        dialog.id = 'ios-print-dialog';
        dialog.className = 'modal fade show';
        dialog.style.display = 'block';
        dialog.style.backgroundColor = 'rgba(0,0,0,0.5)';
        dialog.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Pilih Metode Cetak</h5>
                        <button type="button" class="btn-close" onclick="document.getElementById('ios-print-dialog').style.display='none'"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">Printer Bluetooth tidak tersedia pada Safari iPhone. Silakan pilih metode lain:</p>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="window.printManager.printViaMethod('AirPrint'); document.getElementById('ios-print-dialog').style.display='none';">
                                <i class="fas fa-print me-2"></i>AirPrint
                            </button>
                            <button class="btn btn-secondary" onclick="window.printManager.printViaMethod('PDF'); document.getElementById('ios-print-dialog').style.display='none';">
                                <i class="fas fa-file-pdf me-2"></i>Simpan PDF
                            </button>
                            <button class="btn btn-outline-secondary" onclick="window.printManager.printViaMethod('PNGShare'); document.getElementById('ios-print-dialog').style.display='none';">
                                <i class="fas fa-share-alt me-2"></i>Bagikan PNG
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(dialog);
    }
    
    _getPrioritizedProviders() {
        if (this.device.isAndroid) {
            return [
                { name: 'WebBluetooth', provider: WebBluetoothProvider, requiresEscPos: true },
                { name: 'AndroidBridge', provider: AndroidBridgeProvider, requiresEscPos: true },
                { name: 'PDF', provider: PDFProvider, requiresEscPos: false },
                { name: 'PNGShare', provider: PNGProvider, requiresEscPos: false }
            ];
        }
        
        if (this.device.isIOS) {
            if (this.device.hasIOSBridge) {
                return [
                    { name: 'IOSBridge', provider: IOSBridgeProvider, requiresEscPos: true },
                    { name: 'AirPrint', provider: AirPrintProvider, requiresEscPos: false },
                    { name: 'PDF', provider: PDFProvider, requiresEscPos: false },
                    { name: 'PNGShare', provider: PNGProvider, requiresEscPos: false }
                ];
            } else {
                return [
                    { name: 'AirPrint', provider: AirPrintProvider, requiresEscPos: false },
                    { name: 'PDF', provider: PDFProvider, requiresEscPos: false },
                    { name: 'PNGShare', provider: PNGProvider, requiresEscPos: false }
                ];
            }
        }
        
        // Desktop
        return [
            { name: 'BrowserPrint', provider: BrowserPrintProvider, requiresEscPos: false },
            { name: 'PDF', provider: PDFProvider, requiresEscPos: false }
        ];
    }
    
    _getAllProviders() {
        return [
            { name: 'WebBluetooth', provider: WebBluetoothProvider, requiresEscPos: true },
            { name: 'IOSBridge', provider: IOSBridgeProvider, requiresEscPos: true },
            { name: 'AndroidBridge', provider: AndroidBridgeProvider, requiresEscPos: true },
            { name: 'AirPrint', provider: AirPrintProvider, requiresEscPos: false },
            { name: 'BrowserPrint', provider: BrowserPrintProvider, requiresEscPos: false },
            { name: 'PDF', provider: PDFProvider, requiresEscPos: false },
            { name: 'PNGShare', provider: PNGProvider, requiresEscPos: false }
        ];
    }
    
    async _tryProvider(providerInfo) {
        const { name, provider, requiresEscPos } = providerInfo;
        
        if (requiresEscPos) {
            if (provider.print) {
                if (name === 'IOSBridge' || name === 'AndroidBridge') {
                    return await provider.print(this.printData);
                } else if (name === 'WebBluetooth') {
                    const escPos = this._generateEscPos();
                    return await provider.print(escPos);
                }
            }
        } else {
            if (name === 'AirPrint' || name === 'BrowserPrint') {
                return await provider.print();
            } else if (name === 'PDF') {
                return await provider.printOrDownload(this.options.receiptWrapperId, `${this.options.receiptFilename}.pdf`);
            } else if (name === 'PNGShare') {
                return await provider.share(this.options.receiptWrapperId, `${this.options.receiptFilename}.png`);
            }
        }
        
        return { success: false, error: 'Provider not compatible' };
    }
    
    _generateEscPos() {
        // Reuse the existing buildEscPosText or create new one
        // We'll implement this in receipt.blade.php or keep using existing
        if (window.buildEscPosText) {
            return window.buildEscPosText(this.printData);
        }
        
        // Fallback simple ESC/POS
        const encoder = new TextEncoder();
        let result = [];
        result.push(...encoder.encode('\x1b\x40')); // Initialize
        result.push(...encoder.encode('\x1b\x61\x01')); // Center align
        result.push(...encoder.encode('RECEIPT\n'));
        result.push(...encoder.encode('\x1b\x61\x00')); // Left align
        result.push(...encoder.encode('\n'));
        result.push(...encoder.encode('\n'));
        result.push(...encoder.encode('\n'));
        result.push(...encoder.encode('\n'));
        return new Uint8Array(result);
    }
    
    _getMethodLabel(name) {
        const labels = {
            WebBluetooth: 'Cetak Bluetooth',
            IOSBridge: 'Cetak Bluetooth',
            AndroidBridge: 'Cetak Bluetooth',
            AirPrint: 'AirPrint',
            BrowserPrint: 'Cetak Browser',
            PDF: 'Simpan PDF',
            PNGShare: 'Bagikan PNG'
        };
        return labels[name] || name;
    }
    
    _getMethodIcon(name) {
        const icons = {
            WebBluetooth: 'fa-bluetooth',
            IOSBridge: 'fa-bluetooth',
            AndroidBridge: 'fa-bluetooth',
            AirPrint: 'fa-print',
            BrowserPrint: 'fa-print',
            PDF: 'fa-file-pdf',
            PNGShare: 'fa-share-alt'
        };
        return icons[name] || 'fa-print';
    }
    
    _updateStatus(message, type = 'info') {
        const statusEl = document.getElementById('status');
        if (statusEl) {
            statusEl.textContent = message;
            statusEl.className = type === 'success' 
                ? 'text-success' 
                : type === 'error' 
                    ? 'text-danger' 
                    : 'text-muted';
        }
    }
}
