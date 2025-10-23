// main.js
// Modules to control application life and create native browser window
const { app, BrowserWindow, session, net } = require('electron');
const path = require('path');
const url = require('url');

// --- Configuration ---
// IMPORTANT: Replace this with the actual base URL of your quiz application
const BASE_URL = 'http://localhost/nmims_quiz_app/';

// Define allowed URL patterns (URLs students ARE allowed to navigate to)
// This needs to be carefully configured based on your app's structure.
const ALLOWED_URL_PATTERNS = [
  BASE_URL + 'login.php',
  BASE_URL + 'index.php',
  BASE_URL + 'views/student/', // Allow anything under /views/student/
  BASE_URL + 'api/student/',   // Allow student API calls
  BASE_URL + 'assets/',        // Allow assets (CSS, JS, images)
  BASE_URL + 'lib/',           // Allow libraries if needed by frontend
  BASE_URL + 'logout.php'
];
// --- End Configuration ---

function isUrlAllowed(requestedUrl) {
  // Always allow data URLs (used sometimes for images/resources)
  if (requestedUrl.startsWith('data:')) {
    return true;
  }
  // Check against each pattern
  return ALLOWED_URL_PATTERNS.some(pattern => requestedUrl.startsWith(pattern));
}


// Disable hardware acceleration & other potentially heavy features
// Do this before the app is ready
app.disableHardwareAcceleration();
app.commandLine.appendSwitch('disable-gpu');
app.commandLine.appendSwitch('disable-software-rasterizer');
app.commandLine.appendSwitch('disable-gpu-compositing');
app.commandLine.appendSwitch('disable-gpu-rasterization');
app.commandLine.appendSwitch('disable-gpu-sandbox');
app.commandLine.appendSwitch('disable-accelerated-2d-canvas');
app.commandLine.appendSwitch('disable-breakpad'); // Disable crash reporting
// Might help reduce CPU usage slightly by preventing background throttling
app.commandLine.appendSwitch('disable-renderer-backgrounding');


function createWindow () {
  // Create the browser window.
  const mainWindow = new BrowserWindow({
    width: 800,
    height: 600,
    fullscreen: true, // Start in fullscreen for lockdown effect
    kiosk: true,      // Kiosk mode prevents exiting fullscreen easily
    alwaysOnTop: true, // Keep the window on top
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'), // If you need preload script later
      contextIsolation: true, // Recommended security practice
      nodeIntegration: false, // Recommended security practice
      devTools: !app.isPackaged, // Disable DevTools in packaged app
      webSecurity: true,
      allowRunningInsecureContent: false,
      // Disable features not needed for a simple web view
      plugins: false,
      experimentalFeatures: false,
    },
    // Make the window less prominent/removable
    frame: false, // Removes the window frame (title bar, etc.)
    closable: false, // Prevent closing via standard controls (use kiosk/frame:false)
    resizable: false,
    movable: false,
  });

  // Make the window truly fullscreen without window controls showing on hover (Windows/macOS)
  mainWindow.setFullScreenable(false); // Prevent user from exiting fullscreen easily
  mainWindow.setMenuBarVisibility(false); // Hide menu bar


  // --- Navigation Control ---
  const handleNavigation = (event, navigationUrl) => {
    const parsedUrl = url.parse(navigationUrl);

    console.log(`Attempting navigation to: ${navigationUrl}`); // Log navigation attempts

    if (!isUrlAllowed(navigationUrl)) {
      console.warn(`Blocked navigation to: ${navigationUrl}`); // Log blocked attempts
      event.preventDefault(); // Prevent navigation if URL is not allowed
    }
    // else: Allow navigation
  };

  mainWindow.webContents.on('will-navigate', handleNavigation);
  mainWindow.webContents.on('new-window', (event, navigationUrl) => {
    // Prevent opening new windows/tabs
    console.warn(`Blocked opening new window for: ${navigationUrl}`);
    event.preventDefault();
  });
  // --- End Navigation Control ---


  // Load the login page of your quiz app.
  console.log(`Loading initial URL: ${BASE_URL + 'login.php'}`);
  mainWindow.loadURL(BASE_URL + 'login.php');

  // Optional: Open the DevTools automatically if not packaged
  // if (!app.isPackaged) {
  //   mainWindow.webContents.openDevTools();
  // }
}

// This method will be called when Electron has finished
// initialization and is ready to create browser windows.
// Some APIs can only be used after this event occurs.
app.whenReady().then(() => {
  // Configure session to clear cache on start (optional, but good for exams)
  session.defaultSession.clearCache().then(() => {
    console.log('Cache cleared.');
  });

  // Intercept and potentially block resource requests (more granular control)
  // This is an alternative/addition to 'will-navigate'
  // session.defaultSession.webRequest.onBeforeRequest({ urls: ['*://*/*'] }, (details, callback) => {
  //   if (isUrlAllowed(details.url)) {
  //     callback({ cancel: false }); // Allow
  //   } else {
  //     console.warn(`Blocked resource request: ${details.url}`);
  //     callback({ cancel: true }); // Block
  //   }
  // });


  createWindow();

  app.on('activate', function () {
    // On macOS it's common to re-create a window in the app when the
    // dock icon is clicked and there are no other windows open.
    if (BrowserWindow.getAllWindows().length === 0) createWindow();
  });
});

// Quit when all windows are closed, except on macOS. There, it's common
// for applications and their menu bar to stay active until the user quits
// explicitly with Cmd + Q.
app.on('window-all-closed', function () {
  if (process.platform !== 'darwin') app.quit();
});

// In this file you can include the rest of your app's specific main process
// code. You can also put them in separate files and require them here.

// Optional: Basic security listeners
app.on('web-contents-created', (event, contents) => {
  contents.on('will-attach-webview', (event, webPreferences, params) => {
    // Disable Node.js integration in webviews
    webPreferences.nodeIntegration = false;
    // Verify URL being loaded
    // if (!params.src.startsWith(BASE_URL)) { // Be more specific if needed
    //   event.preventDefault();
    // }
  });
});
