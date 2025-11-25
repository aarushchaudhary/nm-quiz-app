// main.js
// Modules to control application life and create native browser window
const { app, BrowserWindow, session, globalShortcut } = require('electron');
const { exec } = require('child_process');
const path = require('path');
const url = require('url');

// --- Configuration ---
// IMPORTANT: Replace this with the actual base URL of your quiz application
const BASE_URL = 'http://localhost/nmims_quiz_app/';

// Define allowed URL patterns
const ALLOWED_URL_PATTERNS = [
  BASE_URL + 'login.php',
  BASE_URL + 'index.php',
  BASE_URL + 'views/student/',
  BASE_URL + 'api/student/',
  BASE_URL + 'assets/',
  BASE_URL + 'lib/',
  BASE_URL + 'logout.php'
];

function isUrlAllowed(requestedUrl) {
  if (requestedUrl.startsWith('data:')) return true;
  return ALLOWED_URL_PATTERNS.some(pattern => requestedUrl.startsWith(pattern));
}

// Optimization Switches
app.disableHardwareAcceleration();
app.commandLine.appendSwitch('disable-renderer-backgrounding');

// --- 1. Background Process Killer ---
function startBackgroundCleaner() {
  // Expanded blacklist of process names to terminate
  const blacklist = [
    // Browsers
    'chrome.exe', 'firefox.exe', 'msedge.exe', 'brave.exe', 'opera.exe', 'iexplore.exe',
    // Communication
    'discord.exe', 'skype.exe', 'teams.exe', 'whatsapp.exe', 'slack.exe', 'zoom.exe', 'telegram.exe',
    // Tools & Utilities
    'calc.exe', 'calculator.exe', 'snippingtool.exe', 'SnippingTool.exe', 'ScreenClippingHost.exe', 
    'notepad.exe', 'wordpad.exe', 'winword.exe', 'excel.exe', 'powerpnt.exe', 'onenote.exe', 'onenoteim.exe',
    'stickynot.exe', 'Microsoft.Notes.exe',
    // System Monitors (Task Manager, etc)
    'Taskmgr.exe', 'procmon.exe', 'perfmon.exe', 'resmon.exe'
  ];

  // Run this check every 3 seconds
  setInterval(() => {
    blacklist.forEach(processName => {
      // /F = Force, /IM = Image Name, /T = Tree (child processes)
      // We execute this blindly; if the app isn't running, it just errors silently (which we ignore)
      exec(`taskkill /F /IM ${processName} /T`, (error) => {
        if (!error) console.log(`[Security Enforcement] Killed restricted app: ${processName}`);
      });
    });
  }, 3000); 
}

function createWindow () {
  const mainWindow = new BrowserWindow({
    width: 800,
    height: 600,
    fullscreen: true,
    kiosk: true,       // Kiosk mode
    alwaysOnTop: true, // Keep on top
    frame: false,      // No window frame
    closable: false,   // Prevent closing
    resizable: false,
    movable: false,
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true,
      nodeIntegration: false,
      devTools: false, // Strict: Disable DevTools
      webSecurity: true,
      allowRunningInsecureContent: false,
      plugins: false
    }
  });

  mainWindow.setFullScreenable(false);
  mainWindow.setMenuBarVisibility(false);

  // --- 2. Strict Input Blocking Logic ---
  mainWindow.webContents.on('before-input-event', (event, input) => {
    if (input.type === 'keyDown') {
      
      // A. Block Escape Key
      if (input.key === 'Escape') {
        event.preventDefault();
        console.log('Blocked Escape Key');
        return;
      }

      // B. Block All Function Keys (F1 - F12)
      if (input.key.startsWith('F') && input.key.length > 1) {
        event.preventDefault();
        console.log(`Blocked Function Key: ${input.key}`);
        return;
      }

      // C. Block Modifiers: Control, Alt, Windows (Meta)
      // NOTE: We do NOT check input.shift here, so Shift is allowed.
      if (input.control || input.alt || input.meta) {
        event.preventDefault();
        console.log(`Blocked Key Combo: ${input.key} + [Ctrl:${input.control} Alt:${input.alt} Win:${input.meta}]`);
        return;
      }
    }
  });

  // --- Navigation Blocking ---
  const handleNavigation = (event, navigationUrl) => {
    if (!isUrlAllowed(navigationUrl)) {
      console.warn(`Blocked navigation: ${navigationUrl}`);
      event.preventDefault();
    }
  };

  mainWindow.webContents.on('will-navigate', handleNavigation);
  mainWindow.webContents.on('new-window', (e) => e.preventDefault());

  console.log(`Loading: ${BASE_URL + 'login.php'}`);
  mainWindow.loadURL(BASE_URL + 'login.php');
}

app.whenReady().then(() => {
  session.defaultSession.clearCache();

  // --- 3. Global Shortcut Blocking (System Level) ---
  // Attempts to swallow system shortcuts so they don't trigger OS actions
  const shortcuts = [
    'Alt+Tab', 'Alt+Space', 'Ctrl+Esc', 'Alt+F4', 
    'Ctrl+Shift+Esc', 'CommandOrControl+Tab', 
    'CommandOrControl+Shift+I', 'CommandOrControl+R',
    'Escape' // Try to register global Escape (may not work on all OSs, but worth trying)
  ];

  shortcuts.forEach(key => {
    try {
      globalShortcut.register(key, () => {
        console.log(`System shortcut blocked: ${key}`);
        return false;
      });
    } catch (e) {
      // Some keys (like Escape alone) might fail to register globally on some OSs
      console.log(`Could not register global block for ${key}`);
    }
  });

  createWindow();
  startBackgroundCleaner();

  app.on('activate', function () {
    if (BrowserWindow.getAllWindows().length === 0) createWindow();
  });
});

// Unregister shortcuts on quit to restore system normality
app.on('will-quit', () => {
  globalShortcut.unregisterAll();
});

app.on('window-all-closed', function () {
  if (process.platform !== 'darwin') app.quit();
});