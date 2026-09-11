import sys
import os
import json
import zipfile

libs_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'libs')
if os.path.exists(libs_path):
    sys.path.insert(0, libs_path)

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def analyze_planfin(mdb_or_zip_path):
    if not os.path.exists(mdb_or_zip_path):
        print(json.dumps({"error": f"File not found at: {mdb_or_zip_path}"}))
        sys.exit(1)
        
    temp_dir = None
    mdb_path = mdb_or_zip_path
    
    if mdb_or_zip_path.lower().endswith('.zip'):
        temp_dir = mdb_or_zip_path + '_extracted'
        os.makedirs(temp_dir, exist_ok=True)
        with zipfile.ZipFile(mdb_or_zip_path, 'r') as z:
            for item in z.namelist():
                if item.lower().endswith('.mdb'):
                    z.extract(item, temp_dir)
                    mdb_path = os.path.join(temp_dir, item)
                    break
                    
    try:
        # Connect using pypyodbc or access_parser
        plans = []
        months = []
        hcode = ''
        
        try:
            import pypyodbc
            conn_str = f'Driver={{Microsoft Access Driver (*.mdb, *.accdb)}};Dbq={mdb_path};'
            conn = pypyodbc.connect(conn_str)
            cur = conn.cursor()
            
            # 1. Distinct plan periods in org_est_current
            cur.execute("SELECT DISTINCT period_no FROM org_est_current ORDER BY period_no")
            periods = [r[0] for r in cur.fetchall() if r[0]]
            
            # Check hospital code
            cur.execute("SELECT TOP 1 hcode FROM org_est_current WHERE hcode IS NOT NULL")
            h_row = cur.fetchone()
            if h_row:
                hcode = str(h_row[0])
                
            for p in periods:
                cur.execute("SELECT COUNT(*) FROM org_est_current WHERE period_no = ?", [p])
                cnt = cur.fetchone()[0]
                label = f"รอบ {p}"
                if str(p).endswith('01'):
                    label += " (แผนต้นปี)"
                elif str(p).endswith('02'):
                    label += " (แผนปรับปรุงกลางปี)"
                plans.append({
                    "period_no": str(p),
                    "label": label,
                    "count": cnt,
                    "is_recommended": str(p).endswith('02')
                })
                
            # 2. Distinct trial balance dates in DataIn
            cur.execute("SELECT DISTINCT PDate FROM DataIn ORDER BY PDate")
            pdates = [r[0] for r in cur.fetchall() if r[0]]
            
            thai_months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.']
            for d in pdates:
                d_str = str(d).split()[0]
                parts = d_str.split('-')
                if len(parts) == 3:
                    y, m = int(parts[0]), int(parts[1])
                    be_y = y + 543
                    months.append({
                        "pdate": d_str,
                        "label": f"{thai_months[m]} {be_y}"
                    })
                    
            conn.close()
            
        except Exception as e:
            # Fallback to AccessParser
            from access_parser import AccessParser
            db = AccessParser(mdb_path)
            if "org_est_current" in db.catalog:
                t_org = db.parse_table("org_est_current")
                p_list = sorted(list(set(t_org['period_no'])))
                for p in p_list:
                    plans.append({
                        "period_no": str(p),
                        "label": f"รอบ {p}",
                        "count": sum(1 for x in t_org['period_no'] if x == p),
                        "is_recommended": str(p).endswith('02')
                    })
                    
        result = {
            "success": True,
            "hcode": hcode,
            "plans": plans,
            "months": months,
            "latest_month": months[-1]['label'] if months else '',
            "latest_pdate": months[-1]['pdate'] if months else ''
        }
        print(json.dumps(result, ensure_ascii=False))
        
    finally:
        if temp_dir and os.path.exists(temp_dir):
            for f in os.listdir(temp_dir):
                try: os.remove(os.path.join(temp_dir, f))
                except: pass
            try: os.rmdir(temp_dir)
            except: pass

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No file path provided"}))
        sys.exit(1)
    analyze_planfin(sys.argv[1])
