package main

import (
	"encoding/json"
	"os"
	"path/filepath"

	"golang.org/x/sys/windows/registry"
)

type Config struct {
	ApiUrl              string `json:"api_url"`
	ApiToken            string `json:"api_token"`
	DbPath              string `json:"db_path"`
	SyncIntervalMinutes int    `json:"sync_interval_minutes"`
	AutoStart           bool   `json:"auto_start"`
	AutoSync            bool   `json:"auto_sync"`
	BatchSize           int    `json:"batch_size"`
}

const (
	REG_RUN_KEY  = `Software\Microsoft\Windows\CurrentVersion\Run`
	REG_APP_NAME = "HosFinGLSyncAgent"
)

func getDefaultConfig() *Config {
	return &Config{
		ApiUrl:              "http://127.0.0.1/rims/api/hosfin/gl/sync",
		ApiToken:            "rims-gl-token-2569-secret",
		DbPath:              "",
		SyncIntervalMinutes: 30,
		AutoStart:           true,
		AutoSync:            true,
		BatchSize:           250,
	}
}

func loadConfig(cfgPath string) (*Config, error) {
	data, err := os.ReadFile(cfgPath)
	if err != nil {
		cfg := getDefaultConfig()
		cfg.AutoStart = isAutoStartEnabled()
		return cfg, nil
	}
	var cfg Config
	err = json.Unmarshal(data, &cfg)
	if err != nil {
		cfg := getDefaultConfig()
		cfg.AutoStart = isAutoStartEnabled()
		return cfg, nil
	}
	if cfg.BatchSize <= 0 {
		cfg.BatchSize = 250
	}
	if cfg.SyncIntervalMinutes <= 0 {
		cfg.SyncIntervalMinutes = 30
	}
	cfg.AutoStart = isAutoStartEnabled()
	return &cfg, nil
}

func saveConfig(cfgPath string, cfg *Config) error {
	dir := filepath.Dir(cfgPath)
	if dir != "" && dir != "." {
		_ = os.MkdirAll(dir, 0755)
	}
	data, err := json.MarshalIndent(cfg, "", "  ")
	if err != nil {
		return err
	}
	err = os.WriteFile(cfgPath, data, 0644)
	if err != nil {
		return err
	}
	_ = setAutoStartEnabled(cfg.AutoStart)
	return nil
}

func isAutoStartEnabled() bool {
	key, err := registry.OpenKey(registry.CURRENT_USER, REG_RUN_KEY, registry.QUERY_VALUE)
	if err != nil {
		return false
	}
	defer key.Close()

	val, _, err := key.GetStringValue(REG_APP_NAME)
	return err == nil && val != ""
}

func setAutoStartEnabled(enable bool) error {
	key, _, err := registry.CreateKey(registry.CURRENT_USER, REG_RUN_KEY, registry.ALL_ACCESS)
	if err != nil {
		return err
	}
	defer key.Close()

	if enable {
		exePath, err := os.Executable()
		if err != nil {
			return err
		}
		return key.SetStringValue(REG_APP_NAME, `"`+exePath+`"`)
	} else {
		_ = key.DeleteValue(REG_APP_NAME)
		return nil
	}
}
