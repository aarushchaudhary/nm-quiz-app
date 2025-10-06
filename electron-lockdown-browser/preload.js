// preload.js

const { contextBridge, ipcRenderer } = require('electron');

// Expose a secure API to the renderer process
contextBridge.exposeInMainWorld('electronAPI', {
  examSubmitted: () => ipcRenderer.send('exam-submitted'),
  requestQuit: () => ipcRenderer.send('request-quit'),
  
  // --- NEW: Functions to notify the main process about mouse events on the button ---
  mouseEnterButton: () => ipcRenderer.send('mouse-enter-button'),
  mouseLeaveButton: () => ipcRenderer.send('mouse-leave-button'),
});

