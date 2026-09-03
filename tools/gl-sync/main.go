package main

import (
	"flag"
	"fmt"
	"os"
	"path/filepath"
	"time"
)

func main() {
	execPath, err := os.Executable()
	if err != nil {
		execPath = "."
	}
	execDir := filepath.Dir(execPath)

	configFlag := flag.String("config", filepath.Join(execDir, "config.json"), "Path to config.json")
	onceFlag := flag.Bool("once", false, "Run sync once and exit (Command Line)")
	headlessFlag := flag.Bool("headless", false, "Run continuously in background without GUI")
	intervalFlag := flag.Int("interval", 0, "Sync interval in minutes (override config)")
	flag.Parse()

	// If GUI mode (default when double-clicked or without flags)
	if !*onceFlag && !*headlessFlag {
		runGUI(*configFlag)
		return
	}

	// Headless / Console Mode
	fmt.Println("==========================================================")
	fmt.Println("      H-RIMS : Hospital GL Synchronization Agent v1.2    ")
	fmt.Println("   Auto-Sync MS Access (GL69.accdb) -> HosFin Dashboard   ")
	fmt.Println("==========================================================")

	cfg, err := loadConfig(*configFlag)
	if err != nil {
		fmt.Printf("[ERROR] Failed to load configuration from %s: %v\n", *configFlag, err)
		os.Exit(1)
	}

	if *intervalFlag > 0 {
		cfg.SyncIntervalMinutes = *intervalFlag
	}

	logConsole := func(level, msg string) {
		fmt.Printf("[%s] [%-7s] %s\n", time.Now().Format("2006-01-02 15:04:05"), level, msg)
	}

	logConsole("INFO", fmt.Sprintf("Target API: %s", cfg.ApiUrl))
	logConsole("INFO", fmt.Sprintf("Source DB : %s", cfg.DbPath))

	if *headlessFlag {
		logConsole("INFO", fmt.Sprintf("Running in HEADLESS mode (every %d minutes)...", cfg.SyncIntervalMinutes))
		for {
			err := runSyncJob(cfg, logConsole)
			if err != nil {
				logConsole("ERROR", fmt.Sprintf("Sync cycle error: %v", err))
			}
			logConsole("INFO", fmt.Sprintf("Sleeping for %d minutes...", cfg.SyncIntervalMinutes))
			time.Sleep(time.Duration(cfg.SyncIntervalMinutes) * time.Minute)
		}
	} else {
		// Run once
		err := runSyncJob(cfg, logConsole)
		if err != nil {
			logConsole("ERROR", fmt.Sprintf("Sync failed: %v", err))
			os.Exit(1)
		}
		logConsole("SUCCESS", "Job completed successfully.")
	}
}
