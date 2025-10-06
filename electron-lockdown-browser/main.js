// main.js - FINAL VERSION with app closing and blocking

const { app, BrowserWindow, ipcMain, dialog, globalShortcut } = require('electron');
const { exec } = require('child_process');
const path = require('path');

// URL of your hosted PHP quiz application's login page
const QUIZ_APP_URL = 'http://10.180.139.41/nmims_quiz_app/login.php';

// --- NEW: PROCESS WHITELIST ---
const PROCESS_WHITELIST = [
    'electron.exe',
    'NMIMS Quiz App.exe',
    'svchost.exe',
    'wininit.exe',
    'winlogon.exe',
    'csrss.exe',
    'smss.exe',
    'lsass.exe',
    'services.exe',
    'explorer.exe',
    'conhost.exe',
    'dwm.exe',
    'spoolsv.exe',
    'tasklist.exe',
    'taskkill.exe',
    'cmd.exe',
    'wmic.exe',
    'fontdrvhost.exe',
    'sihost.exe',
    'ctfmon.exe',
    'RuntimeBroker.exe',
];

let mainWindow;
let exitButtonWindow;
let isClosable = false;
let isDialogShowing = false;


function closeNonWhitelistedApps() {
    if (process.platform !== 'win32') {
        return; // Feature only for Windows
    }
    const command = 'wmic process get Name,ProcessId';
    exec(command, (err, stdout) => {
        if (err) return;
        const lines = stdout.trim().split('\r\n');
        for (let i = 1; i < lines.length; i++) {
            const line = lines[i].trim();
            if (!line) continue;
            const parts = line.split(/\s+/);
            if (parts.length < 2) continue;
            const pid = parts.pop();
            const name = parts.join(' ');
            if (name && pid && !isNaN(parseInt(pid))) {
                const isWhitelisted = PROCESS_WHITELIST.some(whitelistedName =>
                    name.toLowerCase().trim() === whitelistedName.toLowerCase()
                );
                if (!isWhitelisted) {
                    exec(`taskkill /F /PID ${pid}`);
                }
            }
        }
    });
}

function createMainWindow() {
  mainWindow = new BrowserWindow({
    icon: path.join(__dirname, '../assets/images/favicon.jpg'), // <-- Set the window icon
    fullscreen: true,
    kiosk: true,
    resizable: false,
    movable: false,
    alwaysOnTop: true,
    autoHideMenuBar: true,
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true,
      nodeIntegration: false,
    },
  });

  mainWindow.loadURL(QUIZ_APP_URL);

  mainWindow.on('blur', () => {
    if (!isClosable && !isDialogShowing) {
      mainWindow.focus();
    }
  });

  mainWindow.webContents.on('before-input-event', (event, input) => {
    if (input.alt && input.key.toLowerCase() === 'f4') {
        event.preventDefault();
    }
    if (input.control && input.key.toLowerCase() === 'r') {
        event.preventDefault();
    }
  });

  mainWindow.webContents.on('did-navigate', (event, url) => {
    if (exitButtonWindow) {
      if (url.includes('views/student/exam.php')) {
        exitButtonWindow.hide();
      } else {
        exitButtonWindow.show();
      }
    }
  });

  mainWindow.on('close', (event) => {
    if (!isClosable) {
        event.preventDefault();
    }
  });

  mainWindow.on('closed', () => {
    mainWindow = null;
  });
}

function createExitButtonWindow() {
    const screen = require('electron').screen;
    const primaryDisplay = screen.getPrimaryDisplay();
    const { width } = primaryDisplay.workAreaSize;

    exitButtonWindow = new BrowserWindow({
        parent: mainWindow,
        width: 200,
        height: 50,
        x: Math.floor((width - 200) / 2),
        y: 0,
        frame: false,
        alwaysOnTop: true,
        resizable: false,
        movable: false,
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
        },
        transparent: true,
    });

    exitButtonWindow.loadFile('exit_button.html');
    exitButtonWindow.setIgnoreMouseEvents(true, { forward: true });

    exitButtonWindow.on('closed', () => {
        exitButtonWindow = null;
    });
}

// --- IPC HANDLERS ---
ipcMain.on('mouse-enter-button', () => {
    exitButtonWindow.setIgnoreMouseEvents(false);
});

ipcMain.on('mouse-leave-button', () => {
    exitButtonWindow.setIgnoreMouseEvents(true, { forward: true });
});

ipcMain.on('show-alert', (event, message) => {
    isDialogShowing = true;
    dialog.showMessageBox(mainWindow, {
        type: 'info',
        title: 'Alert',
        message: message,
        buttons: ['OK']
    }).then(() => {
        isDialogShowing = false;
        mainWindow.focus();
    });
});

ipcMain.handle('show-confirm', async (event, message) => {
    isDialogShowing = true;
    const result = await dialog.showMessageBox(mainWindow, {
        type: 'question',
        title: 'Confirm Action',
        message: message,
        buttons: ['OK', 'Cancel'],
        defaultId: 0,
        cancelId: 1,
    });
    isDialogShowing = false;
    mainWindow.focus();
    return result.response === 0;
});

ipcMain.on('exam-submitted', () => {
  isClosable = true;
  app.quit();
});

ipcMain.on('request-quit', () => {
    isDialogShowing = true;
    dialog.showMessageBox(mainWindow, {
        type: 'question',
        title: 'Confirm Exit',
        message: 'Are you sure you want to exit the application?',
        buttons: ['Yes', 'No'],
        defaultId: 1,
    }).then(result => {
        if (result.response === 0) {
            isClosable = true;
            app.quit();
        } else {
            isDialogShowing = false;
            mainWindow.focus();
        }
    });
});


// --- App Lifecycle ---
app.whenReady().then(() => {
  createMainWindow();
  createExitButtonWindow();

  // --- CLOSE BACKGROUND APPS & PREVENT NEW ONES ---
  if (process.platform === 'win32') {
    closeNonWhitelistedApps();
    setInterval(closeNonWhitelistedApps, 2500); 
  }

  // Register global shortcuts
  globalShortcut.register('Super', () => {});
  const altTab = globalShortcut.register('Alt+Tab', () => {
    mainWindow.focus();
  });
  if (!altTab) {
      console.log('Could not register Alt+Tab. Relying on blur event.');
  }

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
      createMainWindow();
      createExitButtonWindow();
    }
  });
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('will-quit', () => {
  globalShortcut.unregisterAll();
});