package main

import (
	"fmt"
	"runtime"
	"strconv"
	"strings"
	"sync"
	"syscall"
	"time"
	"unsafe"
)

var (
	user32   = syscall.NewLazyDLL("user32.dll")
	kernel32 = syscall.NewLazyDLL("kernel32.dll")
	gdi32    = syscall.NewLazyDLL("gdi32.dll")
	comdlg32 = syscall.NewLazyDLL("comdlg32.dll")
	shell32  = syscall.NewLazyDLL("shell32.dll")

	registerClassExW     = user32.NewProc("RegisterClassExW")
	createWindowExW      = user32.NewProc("CreateWindowExW")
	defWindowProcW       = user32.NewProc("DefWindowProcW")
	getMessageW          = user32.NewProc("GetMessageW")
	translateMessage     = user32.NewProc("TranslateMessage")
	dispatchMessageW     = user32.NewProc("DispatchMessageW")
	postQuitMessage      = user32.NewProc("PostQuitMessage")
	showWindow           = user32.NewProc("ShowWindow")
	updateWindow         = user32.NewProc("UpdateWindow")
	sendMessageW         = user32.NewProc("SendMessageW")
	postMessageW         = user32.NewProc("PostMessageW")
	setWindowTextW       = user32.NewProc("SetWindowTextW")
	getWindowTextW       = user32.NewProc("GetWindowTextW")
	getWindowTextLengthW = user32.NewProc("GetWindowTextLengthW")
	messageBoxW          = user32.NewProc("MessageBoxW")
	enableWindow         = user32.NewProc("EnableWindow")
	loadIconW           = user32.NewProc("LoadIconW")
	createPopupMenu      = user32.NewProc("CreatePopupMenu")
	appendMenuW          = user32.NewProc("AppendMenuW")
	trackPopupMenu       = user32.NewProc("TrackPopupMenu")
	destroyMenu          = user32.NewProc("DestroyMenu")
	getCursorPos         = user32.NewProc("GetCursorPos")
	setForegroundWindow  = user32.NewProc("SetForegroundWindow")
	destroyWindow        = user32.NewProc("DestroyWindow")

	getModuleHandleW     = kernel32.NewProc("GetModuleHandleW")
	createFontW          = gdi32.NewProc("CreateFontW")
	getOpenFileNameW     = comdlg32.NewProc("GetOpenFileNameW")
	shell_NotifyIconW    = shell32.NewProc("Shell_NotifyIconW")
)

const (
	ID_BTN_BROWSE   = 1001
	ID_BTN_TEST_API = 1002
	ID_BTN_SAVE     = 1003
	ID_BTN_SYNC_NOW = 1004
	ID_BTN_CLEAR    = 1005

	ID_TRAY_RESTORE  = 2001
	ID_TRAY_SYNC_NOW = 2002
	ID_TRAY_EXIT     = 2003

	WM_SETFONT        = 0x0030
	WM_COMMAND        = 0x0111
	WM_USER_LOG       = 0x0400 + 101
	WM_USER_SYNC_DONE = 0x0400 + 102
	WM_TRAYICON       = 0x0400 + 201

	NIM_ADD    = 0x00000000
	NIM_MODIFY = 0x00000001
	NIM_DELETE = 0x00000002

	NIF_MESSAGE = 0x00000001
	NIF_ICON    = 0x00000002
	NIF_TIP     = 0x00000004
	NIF_INFO    = 0x00000010

	NIIF_INFO   = 0x00000001

	MF_STRING       = 0x00000000
	MF_SEPARATOR    = 0x00000800
	TPM_RIGHTBUTTON = 0x0002

	BM_GETCHECK   = 0x00F0
	BM_SETCHECK   = 0x00F1
	BST_CHECKED   = 1
	BST_UNCHECKED = 0

	EM_SETSEL     = 0x00B1
	EM_REPLACESEL = 0x00C2
)

type NOTIFYICONDATAW struct {
	CbSize           uint32
	_                uint32
	HWnd             uintptr
	UID              uint32
	UFlags           uint32
	UCallbackMessage uint32
	_                uint32
	HIcon            uintptr
	SzTip            [128]uint16
	DwState          uint32
	DwStateMask      uint32
	SzInfo           [256]uint16
	TimeoutOrVersion uint32
	SzInfoTitle      [64]uint16
	DwInfoFlags      uint32
	GuidItem         [16]byte
	HBalloonIcon     uintptr
}

type WNDCLASSEXW struct {
	CbSize        uint32
	Style         uint32
	LpfnWndProc   uintptr
	CbClsExtra    int32
	CbWndExtra    int32
	HInstance     uintptr
	HIcon         uintptr
	HCursor       uintptr
	HbrBackground uintptr
	LpszMenuName  *uint16
	LpszClassName *uint16
	HIconSm       uintptr
}

type MSG struct {
	Hwnd     uintptr
	Message  uint32
	WParam   uintptr
	LParam   uintptr
	Time     uint32
	Pt       struct{ X, Y int32 }
	LPrivate uint32
}

type OPENFILENAMEW struct {
	LStructSize       uint32
	HwndOwner         uintptr
	HInstance         uintptr
	LpstrFilter       *uint16
	LpstrCustomFilter *uint16
	NMaxCustFilter    uint32
	NFilterIndex      uint32
	LpstrFile         *uint16
	NMaxFile          uint32
	LpstrFileTitle    *uint16
	NMaxFileTitle     uint32
	LpstrInitialDir   *uint16
	LpstrTitle        *uint16
	Flags             uint32
	NFileOffset       uint16
	NFileExtension    uint16
	LpstrDefExt       *uint16
	LCustData         uintptr
	LpfnHook          uintptr
	LpTemplateName    *uint16
	PvReserved        uintptr
	DwReserved        uint32
	FlagsEx           uint32
}

type GUIApp struct {
	hInstance uintptr
	hMainWnd  uintptr

	txtApiUrl   uintptr
	txtApiToken uintptr
	btnTestApi  uintptr

	txtDbPath   uintptr
	btnBrowse   uintptr

	chkAutoStart uintptr
	chkAutoSync  uintptr
	txtInterval  uintptr

	btnSave    uintptr
	btnSyncNow uintptr
	btnClear   uintptr

	txtLog    uintptr
	lblStatus uintptr

	hFontRegular uintptr
	hFontBold    uintptr
	hFontTitle   uintptr
	hFontMono    uintptr

	cfgPath   string
	cfg       *Config
	isSyncing bool
	syncMu    sync.Mutex

	logQueue   []string
	logMu      sync.Mutex
	tickerStop chan struct{}

	nid       NOTIFYICONDATAW
	trayAdded bool
}

var globalApp *GUIApp

func makeFont(name string, size int, bold bool) uintptr {
	weight := 400
	if bold {
		weight = 700
	}
	fontName, _ := syscall.UTF16PtrFromString(name)
	h, _, _ := createFontW.Call(
		uintptr(size), 0, 0, 0,
		uintptr(weight),
		0, 0, 0, 0, 0, 0, 0, 0,
		uintptr(unsafe.Pointer(fontName)),
	)
	return h
}

func createControl(className, text string, style uint32, x, y, w, h int, parent uintptr, id uintptr, font uintptr) uintptr {
	cName, _ := syscall.UTF16PtrFromString(className)
	cText, _ := syscall.UTF16PtrFromString(text)
	hInstance, _, _ := getModuleHandleW.Call(0)

	hwnd, _, _ := createWindowExW.Call(
		0,
		uintptr(unsafe.Pointer(cName)),
		uintptr(unsafe.Pointer(cText)),
		uintptr(style|0x40000000|0x10000000), // WS_CHILD | WS_VISIBLE
		uintptr(x), uintptr(y), uintptr(w), uintptr(h),
		parent,
		id,
		hInstance,
		0,
	)

	if font != 0 {
		sendMessageW.Call(hwnd, WM_SETFONT, font, 1)
	}
	return hwnd
}

func getControlText(hwnd uintptr) string {
	length, _, _ := getWindowTextLengthW.Call(hwnd)
	if length == 0 {
		return ""
	}
	buf := make([]uint16, length+1)
	getWindowTextW.Call(hwnd, uintptr(unsafe.Pointer(&buf[0])), length+1)
	return syscall.UTF16ToString(buf)
}

func setControlText(hwnd uintptr, text string) {
	uText, _ := syscall.UTF16PtrFromString(text)
	setWindowTextW.Call(hwnd, uintptr(unsafe.Pointer(uText)))
}

func isChecked(hwnd uintptr) bool {
	r, _, _ := sendMessageW.Call(hwnd, BM_GETCHECK, 0, 0)
	return r == BST_CHECKED
}

func setChecked(hwnd uintptr, checked bool) {
	val := uintptr(BST_UNCHECKED)
	if checked {
		val = uintptr(BST_CHECKED)
	}
	sendMessageW.Call(hwnd, BM_SETCHECK, val, 0)
}

func showMsg(title, msg string, isError bool) {
	flags := uintptr(0x00000040) // MB_ICONINFORMATION
	if isError {
		flags = uintptr(0x00000010) // MB_ICONERROR
	}
	uTitle, _ := syscall.UTF16PtrFromString(title)
	uMsg, _ := syscall.UTF16PtrFromString(msg)
	messageBoxW.Call(globalApp.hMainWnd, uintptr(unsafe.Pointer(uMsg)), uintptr(unsafe.Pointer(uTitle)), flags)
}

func (app *GUIApp) addTrayIcon(hIcon uintptr) {
	app.nid = NOTIFYICONDATAW{
		CbSize:           uint32(unsafe.Sizeof(NOTIFYICONDATAW{})),
		HWnd:             app.hMainWnd,
		UID:              1,
		UFlags:           NIF_MESSAGE | NIF_ICON | NIF_TIP,
		UCallbackMessage: WM_TRAYICON,
		HIcon:            hIcon,
	}
	copy(app.nid.SzTip[:], syscall.StringToUTF16("Rims GL Sync - ระบบซิงค์ข้อมูลบัญชีโรงพยาบาล"))
	shell_NotifyIconW.Call(NIM_ADD, uintptr(unsafe.Pointer(&app.nid)))
	app.trayAdded = true
}

func (app *GUIApp) removeTrayIcon() {
	if app.trayAdded {
		shell_NotifyIconW.Call(NIM_DELETE, uintptr(unsafe.Pointer(&app.nid)))
		app.trayAdded = false
	}
}

func (app *GUIApp) showTrayBalloon(title, msg string) {
	if !app.trayAdded {
		return
	}
	nid := app.nid
	nid.UFlags = NIF_INFO
	copy(nid.SzInfoTitle[:], syscall.StringToUTF16(title))
	copy(nid.SzInfo[:], syscall.StringToUTF16(msg))
	nid.DwInfoFlags = NIIF_INFO
	shell_NotifyIconW.Call(NIM_MODIFY, uintptr(unsafe.Pointer(&nid)))
}

func (app *GUIApp) appendLog(level, text string) {
	timestamp := time.Now().Format("15:04:05")
	formatted := fmt.Sprintf("[%s] [%s] %s\r\n", timestamp, level, text)

	app.logMu.Lock()
	app.logQueue = append(app.logQueue, formatted)
	app.logMu.Unlock()

	postMessageW.Call(app.hMainWnd, WM_USER_LOG, 0, 0)
}

func (app *GUIApp) flushLogsToUI() {
	app.logMu.Lock()
	if len(app.logQueue) == 0 {
		app.logMu.Unlock()
		return
	}
	logsToFlush := app.logQueue
	app.logQueue = nil
	app.logMu.Unlock()

	for _, line := range logsToFlush {
		uLine, _ := syscall.UTF16PtrFromString(line)
		// Move selection to end
		sendMessageW.Call(app.txtLog, EM_SETSEL, ^uintptr(0), ^uintptr(0))
		// Append text
		sendMessageW.Call(app.txtLog, EM_REPLACESEL, 0, uintptr(unsafe.Pointer(uLine)))
	}
}

func (app *GUIApp) browseForFile() {
	buf := make([]uint16, 1024)
	curr := getControlText(app.txtDbPath)
	if curr != "" {
		copy(buf, syscall.StringToUTF16(curr))
	}

	filter, _ := syscall.UTF16PtrFromString("Access Database (*.accdb;*.mdb)\x00*.accdb;*.mdb\x00All Files (*.*)\x00*.*\x00\x00")
	title, _ := syscall.UTF16PtrFromString("เลือกไฟล์ฐานข้อมูล MS Access GL (.accdb)")

	ofn := OPENFILENAMEW{
		LStructSize: uint32(unsafe.Sizeof(OPENFILENAMEW{})),
		HwndOwner:   app.hMainWnd,
		LpstrFilter: filter,
		LpstrFile:   &buf[0],
		NMaxFile:    uint32(len(buf)),
		LpstrTitle:  title,
		Flags:       0x00000800 | 0x00001000, // OFN_PATHMUSTEXIST | OFN_FILEMUSTEXIST
	}

	r, _, _ := getOpenFileNameW.Call(uintptr(unsafe.Pointer(&ofn)))
	if r != 0 {
		selected := syscall.UTF16ToString(buf)
		setControlText(app.txtDbPath, selected)
		app.appendLog("INFO", fmt.Sprintf("เลือกไฟล์ฐานข้อมูล: %s", selected))
	}
}

func (app *GUIApp) testApi() {
	apiUrl := strings.TrimSpace(getControlText(app.txtApiUrl))
	token := strings.TrimSpace(getControlText(app.txtApiToken))

	if apiUrl == "" {
		showMsg("ข้อผิดพลาด", "กรุณาระบุ Server API URL ก่อนทำการทดสอบ", true)
		return
	}

	app.appendLog("INFO", fmt.Sprintf("กำลังทดสอบการเชื่อมต่อ API: %s...", apiUrl))
	enableWindow.Call(app.btnTestApi, 0)

	go func() {
		defer enableWindow.Call(app.btnTestApi, 1)
		err := testApiConnection(apiUrl, token)
		if err != nil {
			app.appendLog("ERROR", fmt.Sprintf("เชื่อมต่อ Server ไม่สำเร็จ: %v", err))
		} else {
			app.appendLog("SUCCESS", "เชื่อมต่อ Server API สำเร็จ (HTTP 200 OK)!")
		}
	}()
}

func (app *GUIApp) saveSettings() {
	apiUrl := strings.TrimSpace(getControlText(app.txtApiUrl))
	token := strings.TrimSpace(getControlText(app.txtApiToken))
	dbPath := strings.TrimSpace(getControlText(app.txtDbPath))
	intervalStr := strings.TrimSpace(getControlText(app.txtInterval))

	interval, err := strconv.Atoi(intervalStr)
	if err != nil || interval <= 0 {
		interval = 30
		setControlText(app.txtInterval, "30")
	}

	autoStart := isChecked(app.chkAutoStart)
	autoSync := isChecked(app.chkAutoSync)

	app.cfg.ApiUrl = apiUrl
	app.cfg.ApiToken = token
	app.cfg.DbPath = dbPath
	app.cfg.SyncIntervalMinutes = interval
	app.cfg.AutoStart = autoStart
	app.cfg.AutoSync = autoSync

	err = saveConfig(app.cfgPath, app.cfg)
	if err != nil {
		showMsg("บันทึกไม่สำเร็จ", fmt.Sprintf("เกิดข้อผิดพลาดในการบันทึก: %v", err), true)
		return
	}

	app.appendLog("SUCCESS", "บันทึกการตั้งค่าลง config.json เรียบร้อยแล้ว")
	if autoStart {
		app.appendLog("INFO", "เปิดใช้งาน Auto-Start กับ Windows (เมื่อเปิดเครื่องโปรแกรมจะเริ่มทำงานทันที)")
	} else {
		app.appendLog("INFO", "ปิดการใช้งาน Auto-Start กับ Windows")
	}

	app.restartScheduler()
	showMsg("สำเร็จ", "บันทึกการตั้งค่าเรียบร้อยแล้ว!", false)
}

func (app *GUIApp) triggerSync() {
	app.syncMu.Lock()
	if app.isSyncing {
		app.syncMu.Unlock()
		showMsg("แจ้งเตือน", "ระบบกำลังทำงานซิงค์ข้อมูลอยู่ กรุณารอสักครู่...", false)
		return
	}
	app.isSyncing = true
	app.syncMu.Unlock()

	// Update controls
	enableWindow.Call(app.btnSyncNow, 0)
	setControlText(app.btnSyncNow, "กำลังส่งข้อมูล...")
	setControlText(app.lblStatus, "สถานะ: กำลังส่งข้อมูลเข้าสู่ระบบ HosFin Dashboard...")

	// Copy current config from UI
	cfgCopy := *app.cfg
	cfgCopy.ApiUrl = strings.TrimSpace(getControlText(app.txtApiUrl))
	cfgCopy.ApiToken = strings.TrimSpace(getControlText(app.txtApiToken))
	cfgCopy.DbPath = strings.TrimSpace(getControlText(app.txtDbPath))

	go func() {
		defer func() {
			app.syncMu.Lock()
			app.isSyncing = false
			app.syncMu.Unlock()
			postMessageW.Call(app.hMainWnd, WM_USER_SYNC_DONE, 0, 0)
		}()

		err := runSyncJob(&cfgCopy, func(level, msg string) {
			app.appendLog(level, msg)
		})

		if err != nil {
			app.appendLog("ERROR", fmt.Sprintf("การส่งข้อมูลล้มเหลว: %v", err))
		}
	}()
}

func (app *GUIApp) restartScheduler() {
	if app.tickerStop != nil {
		close(app.tickerStop)
		app.tickerStop = nil
	}

	if !app.cfg.AutoSync {
		setControlText(app.lblStatus, "สถานะ: พร้อมทำงาน (ปิดการส่งอัตโนมัติ)")
		return
	}

	interval := app.cfg.SyncIntervalMinutes
	if interval <= 0 {
		interval = 30
	}

	app.tickerStop = make(chan struct{})
	setControlText(app.lblStatus, fmt.Sprintf("สถานะ: กำหนดรอบส่งอัตโนมัติทุกๆ %d นาที", interval))

	go func(stopCh chan struct{}, mins int) {
		ticker := time.NewTicker(time.Duration(mins) * time.Minute)
		defer ticker.Stop()

		for {
			select {
			case <-stopCh:
				return
			case <-ticker.C:
				app.appendLog("INFO", fmt.Sprintf("--- เริ่มรอบส่งข้อมูลอัตโนมัติ (ทุก %d นาที) ---", mins))
				app.triggerSync()
			}
		}
	}(app.tickerStop, interval)
}

func guiWndProc(hwnd uintptr, msg uint32, wParam, lParam uintptr) uintptr {
	switch msg {
	case WM_COMMAND:
		id := LOWORD(wParam)
		switch id {
		case ID_BTN_BROWSE:
			globalApp.browseForFile()
		case ID_BTN_TEST_API:
			globalApp.testApi()
		case ID_BTN_SAVE:
			globalApp.saveSettings()
		case ID_BTN_SYNC_NOW:
			globalApp.triggerSync()
		case ID_BTN_CLEAR:
			setControlText(globalApp.txtLog, "")

		// Tray context menu actions
		case ID_TRAY_RESTORE:
			showWindow.Call(hwnd, 9 /* SW_RESTORE */)
			setForegroundWindow.Call(hwnd)
		case ID_TRAY_SYNC_NOW:
			globalApp.triggerSync()
		case ID_TRAY_EXIT:
			globalApp.removeTrayIcon()
			destroyWindow.Call(hwnd)
			postQuitMessage.Call(0)
		}
		return 0

	case WM_TRAYICON:
		switch lParam {
		case 0x0202, 0x0203: // WM_LBUTTONUP, WM_LBUTTONDBLCLK
			showWindow.Call(hwnd, 9 /* SW_RESTORE */)
			setForegroundWindow.Call(hwnd)
		case 0x0205: // WM_RBUTTONUP
			hMenu, _, _ := createPopupMenu.Call()
			if hMenu != 0 {
				uOpen, _ := syscall.UTF16PtrFromString("📌 เปิดหน้าต่าง Rims GL Sync")
				uSync, _ := syscall.UTF16PtrFromString("🚀 ซิงค์ข้อมูลเดี๋ยวนี้ (Sync Now)")
				uExit, _ := syscall.UTF16PtrFromString("❌ ออกจากโปรแกรม (Exit)")

				appendMenuW.Call(hMenu, MF_STRING, ID_TRAY_RESTORE, uintptr(unsafe.Pointer(uOpen)))
				appendMenuW.Call(hMenu, MF_STRING, ID_TRAY_SYNC_NOW, uintptr(unsafe.Pointer(uSync)))
				appendMenuW.Call(hMenu, MF_SEPARATOR, 0, 0)
				appendMenuW.Call(hMenu, MF_STRING, ID_TRAY_EXIT, uintptr(unsafe.Pointer(uExit)))

				var pt struct{ X, Y int32 }
				getCursorPos.Call(uintptr(unsafe.Pointer(&pt)))
				setForegroundWindow.Call(hwnd)
				trackPopupMenu.Call(hMenu, TPM_RIGHTBUTTON, uintptr(pt.X), uintptr(pt.Y), 0, hwnd, 0)
				destroyMenu.Call(hMenu)
			}
		}
		return 0

	case WM_USER_LOG:
		globalApp.flushLogsToUI()
		return 0

	case WM_USER_SYNC_DONE:
		enableWindow.Call(globalApp.btnSyncNow, 1)
		setControlText(globalApp.btnSyncNow, "🚀 ซิงค์ข้อมูลทันที (Sync Now)")
		nowStr := time.Now().Format("15:04:05")
		setControlText(globalApp.lblStatus, fmt.Sprintf("สถานะ: พร้อมทำงาน | ซิงค์ล่าสุด: %s", nowStr))
		return 0

	case 0x0010: // WM_CLOSE (กดปุ่มกากบาท X ที่หน้าต่าง)
		showWindow.Call(hwnd, 0 /* SW_HIDE */)
		globalApp.showTrayBalloon("Rims GL Sync", "โปรแกรมยังคงทำงานอยู่เบื้องหลังแถบนาฬิกา\n(ดับเบิ้ลคลิกที่ไอคอนเพื่อเปิดหน้าต่างอีกครั้ง)")
		return 0

	case 0x0002: // WM_DESTROY
		globalApp.removeTrayIcon()
		postQuitMessage.Call(0)
		return 0
	}

	r, _, _ := defWindowProcW.Call(hwnd, uintptr(msg), wParam, lParam)
	return r
}

func LOWORD(l uintptr) uintptr {
	return l & 0xFFFF
}

func runGUI(cfgPath string) {
	runtime.LockOSThread()

	cfg, err := loadConfig(cfgPath)
	if err != nil {
		cfg = getDefaultConfig()
	}

	hInstance, _, _ := getModuleHandleW.Call(0)
	hIcon, _, _ := loadIconW.Call(hInstance, 1)

	className, _ := syscall.UTF16PtrFromString("RimsGLSyncWinClass")
	windowTitle, _ := syscall.UTF16PtrFromString("Rims GL Sync - ระบบเชื่อมต่อฐานข้อมูลบัญชีโรงพยาบาล")

	wc := WNDCLASSEXW{
		CbSize:        uint32(unsafe.Sizeof(WNDCLASSEXW{})),
		Style:         0x0002 | 0x0001, // CS_HREDRAW | CS_VREDRAW
		LpfnWndProc:   syscall.NewCallback(guiWndProc),
		HInstance:     hInstance,
		HIcon:         hIcon,
		HIconSm:       hIcon,
		HbrBackground: 16, // COLOR_BTNFACE + 1
		LpszClassName: className,
	}
	registerClassExW.Call(uintptr(unsafe.Pointer(&wc)))

	// Window: Width 690, Height 670
	hwnd, _, _ := createWindowExW.Call(
		0,
		uintptr(unsafe.Pointer(className)),
		uintptr(unsafe.Pointer(windowTitle)),
		0x00C00000|0x00080000|0x00040000|0x00020000, // WS_CAPTION | WS_SYSMENU | WS_MINIMIZEBOX
		150, 80, 690, 670,
		0, 0, hInstance, 0,
	)

	if hIcon != 0 {
		sendMessageW.Call(hwnd, 0x0080 /* WM_SETICON */, 1 /* ICON_BIG */, hIcon)
		sendMessageW.Call(hwnd, 0x0080 /* WM_SETICON */, 0 /* ICON_SMALL */, hIcon)
	}

	app := &GUIApp{
		hInstance: hInstance,
		hMainWnd:  hwnd,
		cfgPath:   cfgPath,
		cfg:       cfg,
	}
	globalApp = app

	if hIcon == 0 {
		hIcon, _, _ = loadIconW.Call(0, 32512)
	}
	app.addTrayIcon(hIcon)

	// Fonts
	app.hFontTitle = makeFont("Segoe UI", 20, true)
	app.hFontBold = makeFont("Segoe UI", 15, true)
	app.hFontRegular = makeFont("Segoe UI", 14, false)
	app.hFontMono = makeFont("Consolas", 13, false)

	// 1. Header
	createControl("STATIC", "🏥 Rims GL Sync", 0, 20, 12, 450, 24, hwnd, 0, app.hFontTitle)
	createControl("STATIC", "โปรแกรมเชื่อมต่อและส่งข้อมูลบัญชีจาก MS Access เข้าสู่ระบบ HosFin Dashboard อัตโนมัติ", 0, 22, 38, 550, 18, hwnd, 0, app.hFontRegular)

	// 2. Group 1: Server API
	createControl("BUTTON", "🌐 ตั้งค่า Server API (HosFin Platform)", 0x00000007, 15, 65, 645, 95, hwnd, 0, app.hFontBold) // BS_GROUPBOX
	createControl("STATIC", "API Sync URL:", 0, 30, 92, 95, 20, hwnd, 0, app.hFontRegular)
	app.txtApiUrl = createControl("EDIT", cfg.ApiUrl, 0x0080|0x00800000, 130, 90, 380, 23, hwnd, 0, app.hFontRegular)
	app.btnTestApi = createControl("BUTTON", "ทดสอบเชื่อมต่อ", 0, 520, 89, 125, 25, hwnd, ID_BTN_TEST_API, app.hFontBold)

	createControl("STATIC", "API Token:", 0, 30, 124, 95, 20, hwnd, 0, app.hFontRegular)
	app.txtApiToken = createControl("EDIT", cfg.ApiToken, 0x0080|0x00800000, 130, 122, 515, 23, hwnd, 0, app.hFontRegular)

	// 3. Group 2: Access Database
	createControl("BUTTON", "📁 ที่อยู่ไฟล์ฐานข้อมูล GL (MS Access .accdb / .mdb)", 0x00000007, 15, 170, 645, 68, hwnd, 0, app.hFontBold)
	createControl("STATIC", "ไฟล์ฐานข้อมูล GL:", 0, 30, 197, 100, 20, hwnd, 0, app.hFontRegular)
	app.txtDbPath = createControl("EDIT", cfg.DbPath, 0x0080|0x00800000, 135, 195, 390, 23, hwnd, 0, app.hFontRegular)
	app.btnBrowse = createControl("BUTTON", "📂 Browse...", 0, 535, 194, 110, 25, hwnd, ID_BTN_BROWSE, app.hFontBold)

	// 4. Group 3: Schedule & Auto-Start
	createControl("BUTTON", "⏱️ การตั้งเวลาและเริ่มต้นทำงานอัตโนมัติ (Automation)", 0x00000007, 15, 248, 645, 80, hwnd, 0, app.hFontBold)
	app.chkAutoStart = createControl("BUTTON", "เริ่มทำงานอัตโนมัติเมื่อเปิดเครื่อง (Auto-Start with Windows)", 0x00000003, 30, 274, 390, 22, hwnd, 0, app.hFontRegular) // BS_AUTOCHECKBOX
	setChecked(app.chkAutoStart, cfg.AutoStart)

	app.chkAutoSync = createControl("BUTTON", "เปิดใช้งานการส่งตามรอบเวลาอัตโนมัติ (Background Schedule)", 0x00000003, 30, 298, 390, 22, hwnd, 0, app.hFontRegular)
	setChecked(app.chkAutoSync, cfg.AutoSync)

	createControl("STATIC", "ความถี่:", 0, 440, 287, 50, 20, hwnd, 0, app.hFontRegular)
	app.txtInterval = createControl("EDIT", strconv.Itoa(cfg.SyncIntervalMinutes), 0x0080|0x00800000, 490, 285, 45, 23, hwnd, 0, app.hFontRegular)
	createControl("STATIC", "นาที / ครั้ง", 0, 545, 287, 80, 20, hwnd, 0, app.hFontRegular)

	// 5. Action Buttons Bar
	app.btnSave = createControl("BUTTON", "💾 บันทึกการตั้งค่า (Save)", 0, 15, 338, 170, 36, hwnd, ID_BTN_SAVE, app.hFontBold)
	app.btnSyncNow = createControl("BUTTON", "🚀 ซิงค์ข้อมูลทันที (Sync Now)", 0, 195, 338, 230, 36, hwnd, ID_BTN_SYNC_NOW, app.hFontBold)
	app.btnClear = createControl("BUTTON", "🗑️ ล้าง Log", 0, 555, 338, 105, 36, hwnd, ID_BTN_CLEAR, app.hFontRegular)

	// 6. Group 4: Log Window
	createControl("BUTTON", "📋 บันทึกการทำงานสด (Live Logs)", 0x00000007, 15, 384, 645, 215, hwnd, 0, app.hFontBold)
	app.txtLog = createControl("EDIT", "", 0x0004|0x0040|0x0800|0x00200000|0x00800000, 25, 408, 625, 180, hwnd, 0, app.hFontMono) // ES_MULTILINE | ES_AUTOVSCROLL | ES_READONLY | WS_VSCROLL | WS_BORDER

	// 7. Status Footer
	app.lblStatus = createControl("STATIC", "สถานะ: พร้อมทำงาน", 0, 20, 606, 640, 20, hwnd, 0, app.hFontRegular)

	showWindow.Call(hwnd, 5) // SW_SHOW
	updateWindow.Call(hwnd)

	app.appendLog("INFO", "โปรแกรม HosFin GL Sync Agent พร้อมใช้งาน")
	if cfg.DbPath != "" {
		app.appendLog("INFO", fmt.Sprintf("ไฟล์ GL ที่ตั้งค่าไว้: %s", cfg.DbPath))
	} else {
		app.appendLog("WARN", "ยังไม่ได้เลือกไฟล์ฐานข้อมูล GL กรุณากดปุ่ม [เลือกไฟล์...] และกด [บันทึกการตั้งค่า]")
	}

	app.restartScheduler()

	var msg MSG
	for {
		r, _, _ := getMessageW.Call(uintptr(unsafe.Pointer(&msg)), 0, 0, 0)
		if r == 0 || int32(r) == -1 {
			break
		}
		translateMessage.Call(uintptr(unsafe.Pointer(&msg)))
		dispatchMessageW.Call(uintptr(unsafe.Pointer(&msg)))
	}
	app.removeTrayIcon()
}
