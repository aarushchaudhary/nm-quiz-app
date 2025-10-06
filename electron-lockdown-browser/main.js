// main.js - FINAL VERSION

const { app, BrowserWindow, ipcMain, dialog } = require('electron');
const path = require('path');

// URL of your hosted PHP quiz application's login page
const QUIZ_APP_URL = 'http://localhost/nmims_quiz_app/login.php'; // <-- IMPORTANT: Ensure this URL is correct

let mainWindow;
let exitButtonWindow;
let isClosable = false;
let isDialogShowing = false; // Flag to manage dialog focus

function createMainWindow() {
  mainWindow = new BrowserWindow({
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

  // Trap focus to prevent Alt+Tab or using the Windows key.
  mainWindow.on('blur', () => {
    if (!isClosable && !isDialogShowing) {
      mainWindow.focus();
    }
  });

  // Block specific key combinations
  mainWindow.webContents.on('before-input-event', (event, input) => {
    if (input.alt || input.meta || input.control) {
      // Allows Ctrl+C, Ctrl+V, etc., within text fields but blocks system combos.
      if (input.alt || input.meta) {
        event.preventDefault();
      }
    }
  });

  // Logic to show/hide the exit button based on URL
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
    if (!isClosable) { event.preventDefault(); }
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
    // --- CRITICAL FIX: Make the window click-through by default ---
    exitButtonWindow.setIgnoreMouseEvents(true, { forward: true });

    exitButtonWindow.on('closed', () => {
        exitButtonWindow = null;
    });
}

// --- IPC HANDLERS FOR MOUSE EVENTS ---
ipcMain.on('mouse-enter-button', () => {
    exitButtonWindow.setIgnoreMouseEvents(false); // Make button clickable
});

ipcMain.on('mouse-leave-button', () => {
    exitButtonWindow.setIgnoreMouseEvents(true, { forward: true }); // Make window click-through again
});

// --- NEW: IPC HANDLER FOR ALERT DIALOG ---
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

// --- UPDATED: IPC HANDLER FOR CONFIRMATION DIALOG ---
ipcMain.handle('show-confirm', async (event, message) => {
    isDialogShowing = true;
    const result = await dialog.showMessageBox(mainWindow, { // <-- FIX: Added mainWindow
        type: 'question',
        title: 'Confirm Action',
        message: message,
        buttons: ['OK', 'Cancel'],
        defaultId: 0,
        cancelId: 1,
    });
    isDialogShowing = false;
    mainWindow.focus();
    return result.response === 0; // Return true if OK was clicked, false otherwise
});


// --- OTHER IPC HANDLERS ---
ipcMain.on('exam-submitted', () => {
  isClosable = true;
  app.quit();
});

ipcMain.on('request-quit', () => {
    isDialogShowing = true;
    dialog.showMessageBox(mainWindow, { // <-- FIX: Added mainWindow
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

// App lifecycle
app.whenReady().then(() => {
  createMainWindow();
  createExitButtonWindow();
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