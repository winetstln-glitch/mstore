/**
 * DeviceDetector - Comprehensive device detection for printing systems
 * Supports: Android, iOS, iPadOS, Windows, macOS, Chrome, Safari, PWA
 */
export class DeviceDetector {
    static detect() {
        const ua = navigator.userAgent || navigator.vendor || window.opera || '';
        const platform = navigator.platform || '';
        
        // Basic OS detection
        const isAndroid = /android/i.test(ua);
        const isIOS = /iPad|iPhone|iPod/.test(ua) || 
                      (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        
        // Browser detection
        const isSafari = /^((?!chrome|android).)*safari/i.test(ua);
        const isChrome = /chrome/i.test(ua) && !isSafari;
        
        // PWA detection
        const isPWA = window.matchMedia('(display-mode: standalone)').matches ||
                      (window.navigator.standalone === true);
        
        // Bluetooth capability
        const hasBluetooth = 'bluetooth' in navigator;
        
        // iOS bridge detection
        const hasIOSBridge = isIOS && 
                            window.webkit && 
                            window.webkit.messageHandlers && 
                            (
                                window.webkit.messageHandlers.printBluetooth ||
                                window.webkit.messageHandlers.bluetoothPrint ||
                                window.webkit.messageHandlers.printReceipt ||
                                window.webkit.messageHandlers.printStruk ||
                                window.webkit.messageHandlers.cetakBluetooth ||
                                window.webkit.messageHandlers.handleBluetoothPrint ||
                                window.webkit.messageHandlers.printViaBluetooth ||
                                window.webkit.messageHandlers.sendPrintJob ||
                                window.webkit.messageHandlers.print
                            );
        
        // Android bridge detection
        const hasAndroidBridge = isAndroid && (
            (window.AndroidPrinter && typeof window.AndroidPrinter.printText === 'function') ||
            typeof window.printBluetoothAction === 'function' ||
            typeof window.printBluetooth === 'function' ||
            typeof window.printReceipt === 'function' ||
            typeof window.printStruk === 'function' ||
            typeof window.cetakBluetooth === 'function' ||
            typeof window.handleBluetoothPrint === 'function' ||
            typeof window.printViaBluetooth === 'function' ||
            typeof window.sendPrintJob === 'function' ||
            typeof window.bluetoothPrint === 'function' ||
            window.Android ||
            window.android ||
            window.MstoreAndroid ||
            window.NativeAndroid ||
            window.JsBridge
        );
        
        return {
            isAndroid,
            isIOS,
            isSafari,
            isChrome,
            isPWA,
            hasBluetooth,
            hasIOSBridge,
            hasAndroidBridge,
            isMobile: isAndroid || isIOS,
            isDesktop: !isAndroid && !isIOS,
            userAgent: ua,
            platform
        };
    }
    
    static getIOSBridgeHandler() {
        if (!window.webkit || !window.webkit.messageHandlers) {
            return null;
        }
        
        const handlerNames = [
            'printBluetooth',
            'bluetoothPrint', 
            'printReceipt',
            'printStruk',
            'cetakBluetooth',
            'handleBluetoothPrint',
            'printViaBluetooth',
            'sendPrintJob',
            'print'
        ];
        
        for (const name of handlerNames) {
            if (window.webkit.messageHandlers[name] && 
                typeof window.webkit.messageHandlers[name].postMessage === 'function') {
                return window.webkit.messageHandlers[name];
            }
        }
        
        return null;
    }
    
    static getAndroidBridgeHandler() {
        const methodNames = [
            'printBluetoothAction',
            'printBluetooth',
            'printReceipt',
            'printStruk',
            'cetakBluetooth',
            'handleBluetoothPrint',
            'printViaBluetooth',
            'sendPrintJob',
            'bluetoothPrint'
        ];
        
        for (const name of methodNames) {
            if (typeof window[name] === 'function') {
                return window[name];
            }
        }
        
        if (window.AndroidPrinter && typeof window.AndroidPrinter.printText === 'function') {
            return (data) => window.AndroidPrinter.printText(data);
        }
        
        const bridgeCandidates = [
            window.Android, 
            window.android, 
            window.MstoreAndroid, 
            window.NativeAndroid, 
            window.JsBridge
        ].filter(Boolean);
        
        for (const bridge of bridgeCandidates) {
            for (const name of methodNames) {
                if (typeof bridge[name] === 'function') {
                    return (data) => {
                        try {
                            return bridge[name](data);
                        } catch (e) {
                            try {
                                return bridge[name](JSON.stringify(data));
                            } catch (e2) {
                                return false;
                            }
                        };
                    };
                }
            }
            if (typeof bridge.postMessage === 'function') {
                return (data) => {
                    try {
                        bridge.postMessage(data);
                        return true;
                    } catch (e) {
                        try {
                            bridge.postMessage(JSON.stringify(data));
                            return true;
                        } catch (e2) {
                            return false;
                        }
                    };
                };
            }
        }
        
        return null;
    }
}
