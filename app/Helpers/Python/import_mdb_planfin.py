import sys
import os
import json
import zipfile
import re

libs_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'libs')
if os.path.exists(libs_path):
    sys.path.insert(0, libs_path)

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

def import_planfin(mdb_or_zip_path, target_period_no):
    if not os.path.exists(mdb_or_zip_path):
        print(json.dumps({"error": f"File not found at: {mdb_or_zip_path}"}))
        sys.exit(1)
        
    temp_dir = None
    mdb_path = mdb_or_zip_path
    
    if mdb_or_zip_path.lower().endswith('.zip'):
        temp_dir = mdb_or_zip_path + '_extracted_imp'
        os.makedirs(temp_dir, exist_ok=True)
        with zipfile.ZipFile(mdb_or_zip_path, 'r') as z:
            for item in z.namelist():
                if item.lower().endswith('.mdb'):
                    z.extract(item, temp_dir)
                    mdb_path = os.path.join(temp_dir, item)
                    break
                    
    try:
        targets = []
        mappings = []
        tb_records = []
        budget_year = int(target_period_no[:4]) if len(target_period_no) >= 4 else 2569

        try:
            import pypyodbc
            conn_str = f'Driver={{Microsoft Access Driver (*.mdb, *.accdb)}};Dbq={mdb_path};'
            conn = pypyodbc.connect(conn_str)
            cur = conn.cursor()
            
            # 1. Targets from CalPlan1 filtered by target_period_no (Report5 source)
            try:
                cur.execute("SELECT groupid, amt2 FROM CalPlan1 WHERE period_no = ?", [target_period_no])
                rows = cur.fetchall()
                if not rows:
                    cur.execute("SELECT groupid, amt2 FROM CalPlan3 WHERE period_no = ?", [target_period_no])
                    rows = cur.fetchall()
                if not rows:
                    cur.execute("SELECT groupid, amt FROM Qry_orgplan")
                    rows = cur.fetchall()

                for row in rows:
                    gid = str(row[0]).strip() if row[0] else ''
                    val = float(row[1] or 0)
                    if not gid:
                        continue
                    norm_code = gid
                    if norm_code == 'P29S':
                        norm_code = 'P29'
                    elif norm_code == 'P291':
                        norm_code = 'P29-R'
                    elif norm_code == 'P292':
                        norm_code = 'P29-E'
                    targets.append({
                        "budget_year": budget_year,
                        "round_no": str(target_period_no),
                        "plan_code": norm_code,
                        "target_amount": val
                    })
            except Exception:
                # Fallback to org_est_current if view not available
                cur.execute("SELECT plan_id, plan_code, plan_title FROM PlaNFin")
                p_dict = {row[0]: (str(row[1]).strip() if row[1] else '', str(row[2]).strip() if row[2] else '') for row in cur.fetchall()}
                cur.execute("SELECT hcode, plan_id, org_value, period_no FROM org_est_current WHERE period_no = ?", [target_period_no])
                for row in cur.fetchall():
                    pid = row[1]
                    val = float(row[2] or 0)
                    if pid in p_dict and p_dict[pid][0]:
                        code = p_dict[pid][0]
                        targets.append({
                            "budget_year": budget_year,
                            "round_no": str(target_period_no),
                            "plan_code": code,
                            "target_amount": val
                        })

            # 2. Extract mappings from AccPlan
            cur.execute("SELECT a.account_code, a.account_title, p.plan_code, p.plan_title FROM AccPlan a LEFT JOIN PlaNFin p ON a.plan_id = p.plan_id")
            for row in cur.fetchall():
                acc_c = str(row[0]).strip() if row[0] else None
                acc_t = str(row[1]).strip() if row[1] else ''
                p_c = str(row[2]).strip() if row[2] else None
                p_t = str(row[3]).strip() if row[3] else ''
                if acc_c and p_c:
                    mappings.append({
                        "account_code": acc_c,
                        "account_name": acc_t,
                        "plan_code": p_c,
                        "plan_name": p_t
                    })
                    
            # 3. Extract DataIn rows for the latest trial balance
            cur.execute("SELECT DISTINCT PDate FROM DataIn ORDER BY PDate")
            all_pdates = [r[0] for r in cur.fetchall() if r[0]]
            latest_pdate = all_pdates[-1] if all_pdates else None
            
            if latest_pdate:
                d_str = str(latest_pdate).split()[0]
                parts = d_str.split('-')
                ce_y, mo = int(parts[0]), int(parts[1])
                be_y = ce_y + 543
                acc_period = f"{be_y}-{mo:02d}"
                
                cur.execute(f"SELECT PDate, AccCode, AccName, Dr, Cr, EndDr, EndCr FROM DataIn WHERE PDate = #{d_str}#")

                for row in cur.fetchall():
                    acc_code = str(row[1]).strip() if row[1] else ''
                    acc_name = str(row[2]).strip() if row[2] else ''
                    dr = float(row[3] or 0)
                    cr = float(row[4] or 0)
                    end_dr = float(row[5] or 0)
                    end_cr = float(row[6] or 0)
                    if acc_code:
                        tb_records.append({
                            "acc_year": be_y,
                            "acc_month": mo,
                            "acc_period": acc_period,
                            "main_account_code": acc_code.split('.')[0] if '.' in acc_code else acc_code[:4],
                            "account_code": acc_code,
                            "account_name": acc_name,
                            "debit_month": dr,
                            "credit_month": cr,
                            "debit_net": end_dr,
                            "credit_net": end_cr
                        })
                        
            conn.close()

        except Exception as odbc_ex:
            # Fallback to AccessParser if ODBC driver or pypyodbc fails
            try:
                from access_parser import AccessParser
                db = AccessParser(mdb_path)
                
                targets = []
                p_dict = {}
                if "PlaNFin" in db.catalog:
                    t_pf = db.parse_table("PlaNFin")
                    for pid, pcode, ptitle in zip(t_pf['plan_id'], t_pf['plan_code'], t_pf['plan_title']):
                        p_dict[pid] = (str(pcode).strip() if pcode else '', str(ptitle).strip() if ptitle else '')
                        
                if "org_est_current" in db.catalog:
                    t_org = db.parse_table("org_est_current")
                    for pid, oval, pno in zip(t_org['plan_id'], t_org['org_value'], t_org['period_no']):
                        if str(pno) == str(target_period_no) and pid in p_dict and p_dict[pid][0]:
                            norm_code = p_dict[pid][0]
                            if norm_code == 'P29S': norm_code = 'P29'
                            elif norm_code == 'P291': norm_code = 'P29-R'
                            elif norm_code == 'P292': norm_code = 'P29-E'
                            try:
                                val = float(oval or 0)
                            except:
                                val = 0.0
                            targets.append({
                                "budget_year": budget_year,
                                "round_no": str(target_period_no),
                                "plan_code": norm_code,
                                "target_amount": val
                            })

                mappings = []
                if "AccPlan" in db.catalog:
                    t_acc = db.parse_table("AccPlan")
                    for acc_c, acc_t, pid in zip(t_acc['account_code'], t_acc['account_title'], t_acc['plan_id']):
                        p_c, p_t = p_dict.get(pid, ('', ''))
                        if acc_c and p_c:
                            mappings.append({
                                "account_code": str(acc_c).strip(),
                                "account_name": str(acc_t).strip() if acc_t else '',
                                "plan_code": p_c,
                                "plan_name": p_t
                            })

                tb_records = []
            except Exception as parse_ex:
                print(json.dumps({"error": f"Import failed: {str(odbc_ex)} | Parser fallback: {str(parse_ex)}"}))
                sys.exit(1)
            
        result = {
            "success": True,
            "budget_year": budget_year,
            "period_no": target_period_no,
            "targets_count": len(targets),
            "targets": targets,
            "mappings_count": len(mappings),
            "mappings": mappings,
            "tb_records_count": len(tb_records),
            "tb_records": tb_records
        }
        print(json.dumps(result, ensure_ascii=False))
        return result
            
    finally:
        if temp_dir and os.path.exists(temp_dir):
            for f in os.listdir(temp_dir):
                try: os.remove(os.path.join(temp_dir, f))
                except: pass
            try: os.rmdir(temp_dir)
            except: pass

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print(json.dumps({"error": "Usage: import_mdb_planfin.py <mdb_or_zip_path> <period_no>"}))
        sys.exit(1)
    import_planfin(sys.argv[1], sys.argv[2])
