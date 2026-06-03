/**
 * WebBluetoothProvider - Print via Web Bluetooth API (Chrome on Android)
 */
import { DeviceDetector } from './DeviceDetector.js';
import { PrintLogger } from './PrintLogger.js';

export class WebBluetoothProvider {
    static SERVICE_UUID = '000018f0-0000-1000-8000-00805f9b34fb';
    static CHARACTERISTIC_UUID = '00002af1-0000-1000-8000-00805f9b34fb';
    static ALTERNATE_SERVICE_UUID = '0000ffe0-0000-1000-8000-00805f9b34fb';
    static ALTERNATE_CHARACTERISTIC_UUID = '0000ffe1-0000-1000-8000-00805f9b34fb';
    
    static async print(escPosData, options = {}) {
        const method = 'WebBluetooth';
        PrintLogger.info(method, 'Starting Web Bluetooth print');
        
        let device = null;
        let server = null;
        let service = null;
        let characteristic = null;
        
        try {
            device = await navigator.bluetooth.requestDevice({
                filters: [
                    { services: [this.SERVICE_UUID] },
                    { services: [this.ALTERNATE_SERVICE_UUID] },
                    { namePrefix: 'Printer' },
                    { namePrefix: 'XP' },
                    { namePrefix: 'Mprint' },
                    { namePrefix: 'BIXOLON' },
                    { namePrefix: 'Star' }
                ],
                optionalServices: [
                    this.SERVICE_UUID,
                    this.ALTERNATE_SERVICE_UUID
                ]
            });
            
            PrintLogger.info(method, 'Device selected', { deviceName: device.name });
            
            server = await device.gatt.connect();
            PrintLogger.info(method, 'Connected to GATT server');
            
            // Try primary services
            try {
                service = await server.getPrimaryService(this.SERVICE_UUID);
                PrintLogger.info(method, 'Found primary service', { service: this.SERVICE_UUID });
            } catch (e) {
                service = await server.getPrimaryService(this.ALTERNATE_SERVICE_UUID);
                PrintLogger.info(method, 'Found alternate service', { service: this.ALTERNATE_SERVICE_UUID });
            }
            
            // Try characteristics
            try {
                characteristic = await service.getCharacteristic(this.CHARACTERISTIC_UUID);
                PrintLogger.info(method, 'Found characteristic', { char: this.CHARACTERISTIC_UUID });
            } catch (e) {
                characteristic = await service.getCharacteristic(this.ALTERNATE_CHARACTERISTIC_UUID);
                PrintLogger.info(method, 'Found alternate characteristic', { char: this.ALTERNATE_CHARACTERISTIC_UUID });
            }
            
            // Determine chunk size based on device
            const chunkSize = this._determineChunkSize();
            PrintLogger.info(method, 'Using chunk size', { chunkSize });
            
            // Send data in chunks
            await this._sendDataInChunks(characteristic, escPosData, chunkSize);
            
            PrintLogger.success(method, 'Print completed successfully');
            return { success: true, method, device: device.name };
        } catch (error) {
            PrintLogger.error(method, 'Web Bluetooth print failed', error);
            
            // Handle specific cases
            if (error.name === 'NotFoundError') {
                PrintLogger.warn(method, 'User cancelled device selection');
            } else if (error.name === 'NetworkError') {
                PrintLogger.warn(method, 'Printer disconnected or offline');
            }
            
            return { success: false, method, error };
        } finally {
            // Cleanup
            if (server && server.connected) {
                try {
                    server.disconnect();
                    PrintLogger.info(method, 'Disconnected from printer');
                } catch (e) {
                    PrintLogger.warn(method, 'Error disconnecting', e);
                }
            }
        }
    }
    
    static _determineChunkSize() {
        const device = DeviceDetector.detect();
        
        if (device.isIOS && device.hasIOSBridge) {
            return 128; // iOS native app chunk size
        } else if (device.isAndroid) {
            return 512; // Android Chrome chunk size
        }
        
        return 256; // Generic BLE chunk size
    }
    
    static async _sendDataInChunks(characteristic, data, chunkSize) {
        for (let i = 0; i < data.length; i += chunkSize) {
            const chunk = data.slice(i, i + chunkSize);
            
            // Try writeValueWithoutResponse first (faster)
            try {
                if (characteristic.writeValueWithoutResponse) {
                    await characteristic.writeValueWithoutResponse(chunk);
                } else {
                    await characteristic.writeValue(chunk);
                }
            } catch (e) {
                // Fallback to writeValue
                await characteristic.writeValue(chunk);
            }
            
            // Small delay between chunks for stability
            await this._sleep(20);
        }
        
        // Extra delay to ensure all data is sent
        await this._sleep(100);
    }
    
    static _sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    static isAvailable() {
        const device = DeviceDetector.detect();
        return device.hasBluetooth && device.isChrome && device.isAndroid;
    }
}
