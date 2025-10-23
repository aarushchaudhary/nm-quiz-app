// preload.js

// All the Node.js APIs are available in the preload process.
// It has the same sandbox as a Chrome extension.
// We are not exposing anything to the renderer process in this basic setup.
// You might add code here later if you need secure communication
// between the main process and the renderer (web page).
window.addEventListener('DOMContentLoaded', () => {
  // Example: You could manipulate the DOM of the loaded page here if necessary
  // Be cautious with this in a lockdown browser context.
});

// Basic contextBridge example (uncomment and use if needed)
/*
const { contextBridge, ipcRenderer } = require('electron')

contextBridge.exposeInMainWorld('electronAPI', {
  // Example function to send data to main process
  // sendData: (data) => ipcRenderer.send('some-channel', data),
  // Example function to receive data from main process
  // onDataReceived: (callback) => ipcRenderer.on('reply-channel', (_event, value) => callback(value))
})
*/
