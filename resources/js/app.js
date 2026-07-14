import './bootstrap';

import JsBarcode from 'jsbarcode';
import html2canvas from 'html2canvas';
import JSZip from 'jszip';
import { saveAs } from 'file-saver';

// Import printing modules
import { DeviceDetector } from './printing/DeviceDetector.js';
import { PrintLogger } from './printing/PrintLogger.js';
import { PrintManager } from './printing/PrintManager.js';
import { WebBluetoothProvider } from './printing/WebBluetoothProvider.js';
import { IOSBridgeProvider } from './printing/IOSBridgeProvider.js';
import { AndroidBridgeProvider } from './printing/AndroidBridgeProvider.js';
import { AirPrintProvider } from './printing/AirPrintProvider.js';
import { BrowserPrintProvider } from './printing/BrowserPrintProvider.js';
import { PDFProvider } from './printing/PDFProvider.js';
import { PNGProvider } from './printing/PNGProvider.js';

// Expose to window
window.JsBarcode = JsBarcode;
window.html2canvas = html2canvas;
window.JSZip = JSZip;
window.saveAs = saveAs;

window.DeviceDetector = DeviceDetector;
window.PrintLogger = PrintLogger;
window.PrintManager = PrintManager;
window.WebBluetoothProvider = WebBluetoothProvider;
window.IOSBridgeProvider = IOSBridgeProvider;
window.AndroidBridgeProvider = AndroidBridgeProvider;
window.AirPrintProvider = AirPrintProvider;
window.BrowserPrintProvider = BrowserPrintProvider;
window.PDFProvider = PDFProvider;
window.PNGProvider = PNGProvider;
