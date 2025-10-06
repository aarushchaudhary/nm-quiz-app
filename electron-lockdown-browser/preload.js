// preload.js

const { contextBridge, ipcRenderer } = require('electron');

// Expose a secure API to the renderer process
contextBridge.exposeInMainWorld('electronAPI', {
  examSubmitted: () => ipcRenderer.send('exam-submitted'),
  requestQuit: () => ipcRenderer.send('request-quit'),

  // --- Functions to notify the main process about mouse events on the button ---
  mouseEnterButton: () => ipcRenderer.send('mouse-enter-button'),
  mouseLeaveButton: () => ipcRenderer.send('mouse-leave-button'),

  // --- Function to show a confirmation dialog from the main process ---
  showConfirm: (message) => ipcRenderer.invoke('show-confirm', message),

  // --- NEW: Function to show an alert dialog from the main process ---
  showAlert: (message) => ipcRenderer.send('show-alert', message),
});