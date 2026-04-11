import './bootstrap';

import JsBarcode from 'jsbarcode';
import html2canvas from 'html2canvas';
import JSZip from 'jszip';
import { saveAs } from 'file-saver';

window.JsBarcode = JsBarcode;
window.html2canvas = html2canvas;
window.JSZip = JSZip;
window.saveAs = saveAs;
