; ==============================================================================
; == Smart AHK Script to Disable Keys ONLY on a Specific Website
; ==============================================================================

; This part of the script tells AHK to check for the window title anywhere,
; not just at the beginning. It makes matching more reliable.
SetTitleMatchMode, 2

; This is the magic command. All hotkeys below this line will ONLY work
; if the active window's title contains "10.180.132.203".
; This will trigger when you are on your localhost website.
#If WinActive("10.180.132.203")

    ; --- Disabled Keys and Shortcuts ---
    !Tab::return      ; Disables Alt+Tab
    #Tab::return      ; Disables Win+Tab
    #d::return        ; Disables Win+D
    ^Tab::return      ; Disables Ctrl+Tab
    Esc::return       ; Disables Escape key
    LWin::return      ; Disables Left Windows key
    RWin::return      ; Disables Right Windows key
    LAlt::return      ; Disables Left Alt key
    RAlt::return      ; Disables Right Alt key

#If ; This ends the context-sensitive section.

; --- A universal hotkey to exit the script ---
; Press Ctrl+Alt+X at any time to exit.
^!x::
    ExitApp
return
