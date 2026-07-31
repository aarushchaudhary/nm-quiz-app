// main.js
// Modules to control application life and create native browser window
const { app, BrowserWindow, session, globalShortcut } = require('electron');
const { exec } = require('child_process');
const path = require('path');
const url = require('url');

// --- Configuration ---
// IMPORTANT: Update BASE_URL based on your deployment:
// - Built-in server (port 8080): 'http://localhost:8080/'
// - XAMPP subdirectory: 'http://localhost/nmims_quiz_app/'
// - Production domain: 'https://your-domain.com/'
const BASE_URL = 'http://localhost:8080/';

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
  // Only allow data: URLs for images (block data:text/html which can execute arbitrary JS)
  if (requestedUrl.startsWith('data:image/')) return true;
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
    'safari.exe', 'vivaldi.exe', 'waterfox.exe', 'tor.exe', 'ucbrowser.exe', 'yandex.exe',
    // Communication & Chat
    'discord.exe', 'skype.exe', 'teams.exe', 'whatsapp.exe', 'slack.exe', 'zoom.exe', 'telegram.exe',
    'viber.exe', 'line.exe', 'webexmta.exe', 'meet.exe',
    // Screen Capture & Recording
    'obs64.exe', 'obs32.exe', 'bdcam.exe', 'fraps.exe', 'xsplit.core.exe', 'camtasia.exe',
    'snagit32.exe', 'snagit64.exe', 'lightshot.exe', 'ShareX.exe', 'greenshot.exe', 'snippingtool.exe', 
    'SnippingTool.exe', 'ScreenClippingHost.exe', 'Loom.exe', 'screenrec.exe',
    'Movavi.Screen.Recorder.exe', 'GeForceOverlay.exe',
    // Remote Desktop & Virtualization
    'TeamViewer.exe', 'AnyDesk.exe', 'vncviewer.exe', 'vncserver.exe', 'mstsc.exe',
    'vmware.exe', 'VirtualBox.exe', 'vbox.exe',
    // AI Assistants
    'Copilot.exe', 'ChatGPT.exe',
    // Tools & Utilities
    'calc.exe', 'calculator.exe', 'notepad.exe', 'wordpad.exe', 'winword.exe', 'excel.exe', 
    'powerpnt.exe', 'onenote.exe', 'onenoteim.exe', 'stickynot.exe', 'Microsoft.Notes.exe',
    // Note-Taking Apps
    'Notion.exe', 'Obsidian.exe', 'Evernote.exe',
    // Clipboard Managers
    'ClipboardFusion.exe', 'Ditto.exe', '1clipboard.exe',
    // Script Runners & Automation
    'python.exe', 'pythonw.exe', 'cscript.exe', 'wscript.exe',
    'AutoHotkey.exe', 'AutoIt3.exe',
    // Accessibility Exploits
    'Magnify.exe', 'osk.exe',
    // File Transfer
    'WinSCP.exe', 'FileZilla.exe', 'putty.exe',
    // System Monitors
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
    minimizable: false, // Prevent minimizing via Show Desktop
    maximizable: false,
    skipTaskbar: true, // Hide from taskbar
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true,
      nodeIntegration: false,
      devTools: false, // Strict: Disable DevTools
      webSecurity: true,
      allowRunningInsecureContent: false,
      plugins: false,
      spellcheck: false, // Disable spell checker (could give hints)
      navigateOnDragDrop: false // Block drag-and-drop file loading
    }
  });

  mainWindow.setMenuBarVisibility(false);
  
  // Force the window to the absolute top layer in Windows (above Alt-Tab menu)
  mainWindow.setAlwaysOnTop(true, 'screen-saver');

  // --- 1.2. Anti-Minimize & Anti-Blur (Show Desktop Protection) ---
  let refocusThrottled = false;

  function safeRefocus() {
    if (refocusThrottled) return;
    refocusThrottled = true;

    mainWindow.show();
    mainWindow.focus();
    mainWindow.setSkipTaskbar(true); // Re-enforce after every state change

    setTimeout(() => { refocusThrottled = false; }, 300);
  }

  mainWindow.on('minimize', (e) => {
    e.preventDefault();
    safeRefocus();
  });

  mainWindow.on('blur', () => {
    safeRefocus();
  });

  // --- 1.5. Custom User Agent Injection ---
  // Append a unique identifier so the PHP server knows this is the secure browser
  mainWindow.webContents.userAgent = "NMIMS-Secure-Browser/1.0 " + mainWindow.webContents.userAgent;

  // --- 2. Strict Input Blocking Logic ---
  mainWindow.webContents.on('before-input-event', (event, input) => {
    if (input.type === 'keyDown') {
      
      // --- EMERGENCY EXIT FOR TESTING ---
      if (input.key === 'Q' && input.shift) {
        console.log("Emergency Exit Triggered!");
        app.exit(0); // Use exit(0) to bypass closable:false
        return;
      }

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

  // Block pop-up windows (replaces deprecated 'new-window' event)
  mainWindow.webContents.setWindowOpenHandler(() => {
    return { action: 'deny' };
  });

  // --- Right-Click Context Menu Blocking ---
  mainWindow.webContents.on('context-menu', (e) => {
    e.preventDefault();
  });

  // --- Block Permission Requests (camera, mic, notifications, etc.) ---
  session.defaultSession.setPermissionRequestHandler((webContents, permission, callback) => {
    console.log(`Blocked permission request: ${permission}`);
    callback(false);
  });

  // --- Clear Clipboard on Start ---
  const { clipboard } = require('electron');
  clipboard.clear();

  console.log(`Loading: ${BASE_URL + 'login.php'}`);
  mainWindow.loadURL(BASE_URL + 'login.php');
}

app.whenReady().then(() => {
  session.defaultSession.clearCache();

  // --- 3. Global Shortcut Blocking (System Level) ---
  // Attempts to swallow system shortcuts so they don't trigger OS actions
  const shortcuts = [
    // App Switching / Exiting
    'Alt+Tab', 'Alt+Space', 'Ctrl+Esc', 'Alt+F4', 
    'Ctrl+Shift+Esc', 'CommandOrControl+Tab', 
    'CommandOrControl+Q', 'CommandOrControl+W',
    // DevTools & Refresh
    'CommandOrControl+Shift+I', 'CommandOrControl+R', 'F5', 'F12',
    // Screen Capture & Printing
    'PrintScreen', 'Alt+PrintScreen', 'CommandOrControl+PrintScreen',
    'CommandOrControl+Shift+S', 'CommandOrControl+P',
    // Copy, Cut, Paste, Save, Select All
    'CommandOrControl+C', 'CommandOrControl+X', 'CommandOrControl+V', 'CommandOrControl+S',
    'CommandOrControl+A',
    // Browser-like shortcuts
    'CommandOrControl+N', 'CommandOrControl+T', 'CommandOrControl+L',
    'CommandOrControl+U', 'CommandOrControl+F', 'CommandOrControl+H',
    'CommandOrControl+J', 'CommandOrControl+Shift+N', 'CommandOrControl+Shift+Delete',
    'Alt+Enter',
    // Windows specific shortcuts (Super = Windows Key)
    'Super', 'Super+D', 'Super+M', 'Super+E', 'Super+R', 'Super+Tab',
    'Super+L', 'Super+I', 'Super+S', 'Super+A', 'Super+X', 'Super+G',
    // Zoom
    'CommandOrControl+Plus', 'CommandOrControl+-', 'CommandOrControl+0',
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