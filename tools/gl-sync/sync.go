package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"os"
	"strconv"
	"strings"
	"time"

	"github.com/go-ole/go-ole"
	"github.com/go-ole/go-ole/oleutil"
)

type AccountPayload struct {
	AccountCode string `json:"account_code"`
	AccountName string `json:"account_name"`
}

type SubledgerPayload struct {
	SubledgerCode string `json:"subledger_code"`
	VendorName    string `json:"vendor_name"`
	Category      string `json:"category"`
	RawNote       string `json:"raw_note"`
}

type JournalItemPayload struct {
	ItemNo      int     `json:"item_no"`
	AccountCode string  `json:"account_code"`
	AccountName string  `json:"account_name"`
	Description string  `json:"description"`
	Debit       float64 `json:"debit"`
	Credit      float64 `json:"credit"`
}

type JournalPayload struct {
	VoucherNo   string               `json:"voucher_no"`
	VoucherDate string               `json:"voucher_date"`
	JournalType string               `json:"journal_type"`
	Description string               `json:"description"`
	TotalDebit  float64              `json:"total_debit"`
	TotalCredit float64              `json:"total_credit"`
	FiscalYear  int                  `json:"fiscal_year"`
	FiscalMonth int                  `json:"fiscal_month"`
	ApAr        string               `json:"apar"`
	ExternalRef string               `json:"external_ref"`
	Items       []JournalItemPayload `json:"items"`
}

type SyncRequest struct {
	SyncType     string               `json:"sync_type"`
	AgentVersion string               `json:"agent_version"`
	Accounts     []AccountPayload     `json:"accounts,omitempty"`
	Subledgers   []SubledgerPayload   `json:"subledgers,omitempty"`
	Journals     []JournalPayload     `json:"journals,omitempty"`
}

type SyncResponse struct {
	Success         bool    `json:"success"`
	Message         string  `json:"message"`
	RecordsCount    int     `json:"records_count"`
	DurationSeconds float64 `json:"duration_seconds"`
}

func testApiConnection(apiUrl, token string) error {
	client := &http.Client{Timeout: 10 * time.Second}
	req, err := http.NewRequest("GET", apiUrl, nil)
	if err != nil {
		return err
	}
	req.Header.Set("Authorization", "Bearer "+token)
	req.Header.Set("X-GL-SYNC-TOKEN", token)

	resp, err := client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode == http.StatusOK || resp.StatusCode == http.StatusMethodNotAllowed || resp.StatusCode == http.StatusUnprocessableEntity {
		return nil
	}
	if resp.StatusCode == http.StatusUnauthorized {
		return fmt.Errorf("Token ไม่ถูกต้อง (HTTP 401 Unauthorized)")
	}
	return fmt.Errorf("Server ตอบกลับรหัส HTTP %d", resp.StatusCode)
}

func postToAPI(apiUrl, token string, reqPayload *SyncRequest) (*SyncResponse, error) {
	jsonBytes, err := json.Marshal(reqPayload)
	if err != nil {
		return nil, err
	}

	req, err := http.NewRequest("POST", apiUrl, bytes.NewBuffer(jsonBytes))
	if err != nil {
		return nil, err
	}

	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Bearer "+token)
	req.Header.Set("X-GL-SYNC-TOKEN", token)

	client := &http.Client{Timeout: 90 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	respBody, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, err
	}

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("HTTP %d: %s", resp.StatusCode, string(respBody))
	}

	var syncResp SyncResponse
	err = json.Unmarshal(respBody, &syncResp)
	if err != nil {
		return nil, fmt.Errorf("failed to parse response: %v, raw: %s", err, string(respBody))
	}

	return &syncResp, nil
}

func runSyncJob(cfg *Config, logFn func(level, msg string)) error {
	if cfg.ApiUrl == "" {
		return fmt.Errorf("กรุณาระบุ Server API URL")
	}
	if cfg.DbPath == "" {
		return fmt.Errorf("กรุณาเลือกไฟล์ฐานข้อมูล GL (.accdb)")
	}
	if _, err := os.Stat(cfg.DbPath); os.IsNotExist(err) {
		return fmt.Errorf("ไม่พบไฟล์ฐานข้อมูล GL ที่ระบุ: %s", cfg.DbPath)
	}

	logFn("INFO", fmt.Sprintf("กำลังเชื่อมต่อฐานข้อมูล Access: %s", cfg.DbPath))

	ole.CoInitialize(0)
	defer ole.CoUninitialize()

	unknown, err := oleutil.CreateObject("ADODB.Connection")
	if err != nil {
		return fmt.Errorf("ไม่สามารถสร้าง ADODB.Connection: %v", err)
	}
	conn, err := unknown.QueryInterface(ole.IID_IDispatch)
	if err != nil {
		return fmt.Errorf("QueryInterface failed: %v", err)
	}
	defer conn.Release()

	connStr := fmt.Sprintf("Provider=Microsoft.ACE.OLEDB.12.0;Data Source=%s;Persist Security Info=False;", cfg.DbPath)
	_, err = oleutil.CallMethod(conn, "Open", connStr)
	if err != nil {
		connStrJet := fmt.Sprintf("Provider=Microsoft.Jet.OLEDB.4.0;Data Source=%s;", cfg.DbPath)
		_, errJet := oleutil.CallMethod(conn, "Open", connStrJet)
		if errJet != nil {
			return fmt.Errorf("ไม่สามารถเปิดไฟล์ฐานข้อมูล: ACE err: %v | Jet err: %v", err, errJet)
		}
	}
	defer oleutil.CallMethod(conn, "Close")
	logFn("SUCCESS", "เชื่อมต่อฐานข้อมูล Access (OLEDB) สำเร็จ!")

	// 1. Read ChartOfAccounts
	logFn("INFO", "กำลังดึงข้อมูลผังบัญชี (ChartOfAccounts)...")
	rsAcc, err := oleutil.CallMethod(conn, "Execute", "SELECT AccCode, AccName FROM ChartOfAccounts WHERE AccCode IS NOT NULL")
	if err != nil {
		return fmt.Errorf("เกิดข้อผิดพลาดในการอ่าน ChartOfAccounts: %v", err)
	}
	rsAccDisp := rsAcc.ToIDispatch()
	defer rsAccDisp.Release()

	var accounts []AccountPayload
	accountsMap := make(map[string]string)

	accFields := oleutil.MustGetProperty(rsAccDisp, "Fields").ToIDispatch()
	defer accFields.Release()

	itemAccCode := oleutil.MustGetProperty(accFields, "Item", "AccCode").ToIDispatch()
	defer itemAccCode.Release()
	itemAccName := oleutil.MustGetProperty(accFields, "Item", "AccName").ToIDispatch()
	defer itemAccName.Release()

	for {
		eof := oleutil.MustGetProperty(rsAccDisp, "EOF").Value().(bool)
		if eof {
			break
		}
		cCode := fmt.Sprintf("%v", oleutil.MustGetProperty(itemAccCode, "Value").Value())
		cName := fmt.Sprintf("%v", oleutil.MustGetProperty(itemAccName, "Value").Value())
		if cCode != "<nil>" && cCode != "" {
			cleanCode := strings.TrimSpace(cCode)
			cleanName := strings.TrimSpace(cName)
			accounts = append(accounts, AccountPayload{
				AccountCode: cleanCode,
				AccountName: cleanName,
			})
			accountsMap[cleanCode] = cleanName
		}
		oleutil.CallMethod(rsAccDisp, "MoveNext")
	}
	logFn("INFO", fmt.Sprintf("พบผังบัญชี %d รายการ", len(accounts)))

	// 2. Read SubLedTbl
	logFn("INFO", "กำลังดึงข้อมูลทะเบียนเจ้าหนี้-ลูกหนี้ (SubLedTbl)...")
	rsSub, err := oleutil.CallMethod(conn, "Execute", "SELECT SubLedger, SubNote FROM SubLedTbl WHERE SubLedger IS NOT NULL")
	if err != nil {
		return fmt.Errorf("เกิดข้อผิดพลาดในการอ่าน SubLedTbl: %v", err)
	}
	rsSubDisp := rsSub.ToIDispatch()
	defer rsSubDisp.Release()

	var subledgers []SubledgerPayload
	subFields := oleutil.MustGetProperty(rsSubDisp, "Fields").ToIDispatch()
	defer subFields.Release()

	itemSubLed := oleutil.MustGetProperty(subFields, "Item", "SubLedger").ToIDispatch()
	defer itemSubLed.Release()
	itemSubNote := oleutil.MustGetProperty(subFields, "Item", "SubNote").ToIDispatch()
	defer itemSubNote.Release()

	for {
		eof := oleutil.MustGetProperty(rsSubDisp, "EOF").Value().(bool)
		if eof {
			break
		}
		sLed := fmt.Sprintf("%v", oleutil.MustGetProperty(itemSubLed, "Value").Value())
		sNote := fmt.Sprintf("%v", oleutil.MustGetProperty(itemSubNote, "Value").Value())
		if sLed != "<nil>" && sLed != "" {
			parts := strings.Split(sNote, "/")
			vName := strings.TrimSpace(parts[0])
			cat := ""
			if len(parts) > 1 {
				cat = strings.TrimSpace(parts[1])
			}
			subledgers = append(subledgers, SubledgerPayload{
				SubledgerCode: strings.TrimSpace(sLed),
				VendorName:    vName,
				Category:      cat,
				RawNote:       strings.TrimSpace(sNote),
			})
		}
		oleutil.CallMethod(rsSubDisp, "MoveNext")
	}
	logFn("INFO", fmt.Sprintf("พบทะเบียนเจ้าหนี้-ลูกหนี้ %d รายการ", len(subledgers)))

	// Send Metadata
	logFn("INFO", "กำลังส่งข้อมูลผังบัญชีและเจ้าหนี้-ลูกหนี้เข้า Server...")
	initReq := &SyncRequest{
		SyncType:     "metadata",
		AgentVersion: "1.2.0",
		Accounts:     accounts,
		Subledgers:   subledgers,
	}
	resp, err := postToAPI(cfg.ApiUrl, cfg.ApiToken, initReq)
	if err != nil {
		return fmt.Errorf("Metadata sync error: %v", err)
	}
	logFn("SUCCESS", fmt.Sprintf("ส่งผังบัญชีสำเร็จ (%d รายการ)", resp.RecordsCount))

	// 3. Read Journals
	logFn("INFO", "กำลังอ่านสมุดรายวันทั่วไป (DataTbl + DataTbl1)...")
	sqlJournals := `SELECT d.ID, 
	                       IIF(d.AccDate IS NULL, 0, Year(d.AccDate)) AS AccYear, 
	                       IIF(d.AccDate IS NULL, 0, Month(d.AccDate)) AS AccMonth, 
	                       IIF(d.AccDate IS NULL, 0, Day(d.AccDate)) AS AccDay, 
	                       d.DocID, d.Note, d.BookID, d.ApAr, d1.AccCode1, 
	                       IIF(d1.Dr IS NULL, '0', CStr(d1.Dr)) AS DrStr, IIF(d1.Cr IS NULL, '0', CStr(d1.Cr)) AS CrStr 
	                FROM DataTbl d INNER JOIN DataTbl1 d1 ON d.ID = d1.ID 
	                ORDER BY d.ID ASC`
	rsJ, err := oleutil.CallMethod(conn, "Execute", sqlJournals)
	if err != nil {
		return fmt.Errorf("ไม่สามารถอ่านสมุดรายวัน: %v", err)
	}
	rsJDisp := rsJ.ToIDispatch()
	defer rsJDisp.Release()

	jFields := oleutil.MustGetProperty(rsJDisp, "Fields").ToIDispatch()
	defer jFields.Release()

	fID := oleutil.MustGetProperty(jFields, "Item", "ID").ToIDispatch(); defer fID.Release()
	fY := oleutil.MustGetProperty(jFields, "Item", "AccYear").ToIDispatch(); defer fY.Release()
	fM := oleutil.MustGetProperty(jFields, "Item", "AccMonth").ToIDispatch(); defer fM.Release()
	fD := oleutil.MustGetProperty(jFields, "Item", "AccDay").ToIDispatch(); defer fD.Release()
	fDoc := oleutil.MustGetProperty(jFields, "Item", "DocID").ToIDispatch(); defer fDoc.Release()
	fNote := oleutil.MustGetProperty(jFields, "Item", "Note").ToIDispatch(); defer fNote.Release()
	fApAr := oleutil.MustGetProperty(jFields, "Item", "ApAr").ToIDispatch(); defer fApAr.Release()
	fAcc := oleutil.MustGetProperty(jFields, "Item", "AccCode1").ToIDispatch(); defer fAcc.Release()
	fDr := oleutil.MustGetProperty(jFields, "Item", "DrStr").ToIDispatch(); defer fDr.Release()
	fCr := oleutil.MustGetProperty(jFields, "Item", "CrStr").ToIDispatch(); defer fCr.Release()

	journalMap := make(map[string]*JournalPayload)
	var journalOrder []string

	for {
		eof := oleutil.MustGetProperty(rsJDisp, "EOF").Value().(bool)
		if eof {
			break
		}

		rawID := fmt.Sprintf("%v", oleutil.MustGetProperty(fID, "Value").Value())
		rawY := fmt.Sprintf("%v", oleutil.MustGetProperty(fY, "Value").Value())
		rawM := fmt.Sprintf("%v", oleutil.MustGetProperty(fM, "Value").Value())
		rawD := fmt.Sprintf("%v", oleutil.MustGetProperty(fD, "Value").Value())
		rawDoc := fmt.Sprintf("%v", oleutil.MustGetProperty(fDoc, "Value").Value())
		rawNote := fmt.Sprintf("%v", oleutil.MustGetProperty(fNote, "Value").Value())
		rawApAr := fmt.Sprintf("%v", oleutil.MustGetProperty(fApAr, "Value").Value())
		rawAcc := fmt.Sprintf("%v", oleutil.MustGetProperty(fAcc, "Value").Value())
		rawDrStr := fmt.Sprintf("%v", oleutil.MustGetProperty(fDr, "Value").Value())
		rawCrStr := fmt.Sprintf("%v", oleutil.MustGetProperty(fCr, "Value").Value())

		drVal, _ := strconv.ParseFloat(rawDrStr, 64)
		crVal, _ := strconv.ParseFloat(rawCrStr, 64)

		yVal, _ := strconv.Atoi(rawY)
		mVal, _ := strconv.Atoi(rawM)
		dVal, _ := strconv.Atoi(rawD)

		if yVal <= 0 {
			yVal = 2025
			mVal = 9
			dVal = 30
		}
		if mVal <= 0 { mVal = 1 }
		if dVal <= 0 { dVal = 1 }

		thaiYear := yVal
		if thaiYear < 2400 {
			thaiYear += 543
		}

		var fy, fm int
		jType := "JV"

		// Detect opening balance (ยอดยกมา ณ 30 ก.ย.)
		if (mVal == 9 && dVal >= 28) || strings.Contains(rawNote, "ยอดยกมา") {
			fy = thaiYear + 1 // e.g. 2568 + 1 = 2569
			fm = 0            // 0 = Opening balance
			jType = "OB"
		} else if mVal >= 10 {
			fy = thaiYear + 1
			fm = mVal - 9 // 1, 2, 3
		} else {
			fy = thaiYear
			fm = mVal + 3 // 4, 5, 6, 7, 8, 9, 10, 11, 12
		}

		dateStr := fmt.Sprintf("%04d-%02d-%02d", yVal, mVal, dVal)

		voucherKey := "GL-" + rawID
		cleanDoc := ""
		if rawDoc != "<nil>" {
			cleanDoc = strings.TrimSpace(rawDoc)
		}

		if rawNote == "<nil>" { rawNote = "" }
		if rawApAr == "<nil>" { rawApAr = "" }

		j, exists := journalMap[voucherKey]
		if !exists {
			j = &JournalPayload{
				VoucherNo:   voucherKey,
				VoucherDate: dateStr,
				JournalType: jType,
				Description: strings.TrimSpace(rawNote),
				FiscalYear:  fy,
				FiscalMonth: fm,
				ApAr:        strings.TrimSpace(rawApAr),
				ExternalRef: cleanDoc,
				Items:       []JournalItemPayload{},
			}
			journalMap[voucherKey] = j
			journalOrder = append(journalOrder, voucherKey)
		}

		cleanAcc := strings.TrimSpace(rawAcc)
		if cleanAcc != "<nil>" && cleanAcc != "" {
			itemNo := len(j.Items) + 1
			accName := accountsMap[cleanAcc]
			j.Items = append(j.Items, JournalItemPayload{
				ItemNo:      itemNo,
				AccountCode: cleanAcc,
				AccountName: accName,
				Description: strings.TrimSpace(rawNote),
				Debit:       drVal,
				Credit:      crVal,
			})
			j.TotalDebit += drVal
			j.TotalCredit += crVal
		}

		oleutil.CallMethod(rsJDisp, "MoveNext")
	}

	logFn("INFO", fmt.Sprintf("รวบรวมใบสำคัญสมุดรายวันได้ทั้งหมด %d ใบ", len(journalOrder)))

	batchSize := cfg.BatchSize
	if batchSize <= 0 {
		batchSize = 250
	}
	totalBatches := (len(journalOrder) + batchSize - 1) / batchSize

	for i := 0; i < len(journalOrder); i += batchSize {
		end := i + batchSize
		if end > len(journalOrder) {
			end = len(journalOrder)
		}
		batchKeys := journalOrder[i:end]
		var batchJournals []JournalPayload
		for _, k := range batchKeys {
			batchJournals = append(batchJournals, *journalMap[k])
		}

		batchNum := (i / batchSize) + 1
		logFn("INFO", fmt.Sprintf("กำลังส่งชุดที่ %d/%d (%d ใบสำคัญ)...", batchNum, totalBatches, len(batchJournals)))

		chunkReq := &SyncRequest{
			SyncType:     "journals_chunk",
			AgentVersion: "1.2.0",
			Journals:     batchJournals,
		}

		resp, err := postToAPI(cfg.ApiUrl, cfg.ApiToken, chunkReq)
		if err != nil {
			return fmt.Errorf("ล้มเหลวในการส่งชุดที่ %d: %v", batchNum, err)
		}
		logFn("SUCCESS", fmt.Sprintf("ชุดที่ %d/%d ส่งสำเร็จ (%d รายการ ใน %.2f วิ)", batchNum, totalBatches, resp.RecordsCount, resp.DurationSeconds))
	}

	// 4. Finalize
	logFn("INFO", "กำลังประมวลผลคำนวณยอดสรุปหนี้สิน AP, ลูกหนี้ AR และต้นทุนบน Server...")
	finalizeReq := &SyncRequest{
		SyncType:     "finalize",
		AgentVersion: "1.2.0",
	}
	finResp, err := postToAPI(cfg.ApiUrl, cfg.ApiToken, finalizeReq)
	if err != nil {
		logFn("WARN", fmt.Sprintf("Finalize warning: %v", err))
	} else {
		logFn("SUCCESS", fmt.Sprintf("ประมวลผลสรุปยอดบน Server สำเร็จใน %.2f วิ", finResp.DurationSeconds))
	}

	logFn("SUCCESS", "=== ส่งข้อมูลบัญชีทั้งหมดเข้า HosFin Dashboard สำเร็จเรียบร้อย! ===")
	return nil
}
