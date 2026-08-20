import sys
import os
import json
import re
import io
import logging

# Silence all logging
logging.basicConfig(level=logging.ERROR)
logging.getLogger().setLevel(logging.ERROR)

def get_scaled_val(val):
    if val is None or val == []:
        return 0.0
    try:
        return float(val) / 10000.0
    except Exception:
        return 0.0

def parse_period(mdb_path, target_pdate):
    if not os.path.exists(mdb_path):
        print(json.dumps({"error": f"MDB file not found at: {mdb_path}"}))
        sys.exit(1)
        
    try:
        # Redirect stdout and stderr to suppress library print warnings
        old_stdout = sys.stdout
        old_stderr = sys.stderr
        sys.stdout = io.StringIO()
        sys.stderr = io.StringIO()
        
        # Import AccessParser inside to prevent any import-time prints
        from access_parser import AccessParser
        db = AccessParser(mdb_path)
        
        if "DataIn" not in db.catalog:
            sys.stdout = old_stdout
            sys.stderr = old_stderr
            print(json.dumps({"error": "Table 'DataIn' not found in MDB file."}))
            sys.exit(1)
            
        table = db.parse_table("DataIn")
        
        pdates = table['PDate']
        codes = table['AccCode']
        names = table['AccName']
        drs = table['Dr']
        crs = table['Cr']
        enddrs = table['EndDr']
        endcrs = table['EndCr']
        prevlastnets = table['PrevLastNet']
        
        records = []
        
        for i in range(len(pdates)):
            pdate_str = str(pdates[i]).split()[0]
            if pdate_str != target_pdate:
                continue
                
            code = str(codes[i]).strip()
            name = str(names[i]).strip() if names[i] is not None else ""
            
            dr = get_scaled_val(drs[i])
            cr = get_scaled_val(crs[i])
            enddr = get_scaled_val(enddrs[i])
            endcr = get_scaled_val(endcrs[i])
            prevlastnet = get_scaled_val(prevlastnets[i])
            
            match = re.match(r'^(\d{4})-(\d{2})-(\d{2})$', pdate_str)
            if not match:
                continue
            ce_year = int(match.group(1))
            month = int(match.group(2))
            be_year = ce_year + 543
            period = f"{be_year}-{month:02d}"
            
            main_code = ""
            if len(code) >= 10:
                prefix6 = code[:6]
                prefix8 = code[:8]
                if prefix6 == "110102":
                    if prefix8 in ["11010205", "11010206"]:
                        main_code = prefix8 + "00.000"
                    else:
                        main_code = "1101020000.000"
                elif prefix6 == "110101":
                    main_code = "1101010000.000"
                elif prefix6 == "110103":
                    main_code = "1101030000.000"
                else:
                    main_code = prefix6 + "0000.000"
            else:
                main_code = code
                
            debit_bf = 0.0
            credit_bf = 0.0
            first_digit = code[0] if len(code) > 0 else ""
            
            if first_digit in ['1', '5', '6']:
                if prevlastnet >= 0:
                    debit_bf = prevlastnet
                    credit_bf = 0.0
                else:
                    debit_bf = 0.0
                    credit_bf = -prevlastnet
            elif first_digit in ['2', '3', '4']:
                if prevlastnet >= 0:
                    debit_bf = 0.0
                    credit_bf = prevlastnet
                else:
                    debit_bf = -prevlastnet
                    credit_bf = 0.0
                    
            records.append({
                'acc_year': be_year,
                'acc_month': month,
                'acc_period': period,
                'main_account_code': main_code,
                'account_code': code,
                'account_name': name,
                'debit_bf': debit_bf,
                'credit_bf': credit_bf,
                'debit_month': dr,
                'credit_month': cr,
                'debit_net': enddr,
                'credit_net': endcr,
                'import_filename': os.path.basename(mdb_path)
            })
            
        sys.stdout = old_stdout
        sys.stderr = old_stderr
        print(json.dumps(records, ensure_ascii=False))
    except Exception as e:
        sys.stdout = old_stdout
        sys.stderr = old_stderr
        print(json.dumps({"error": str(e)}))
        sys.exit(1)

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({"error": "Usage: python import_mdb_period.py <path_to_mdb> <pdate_yyyy-mm-dd>"}))
        sys.exit(1)
    parse_period(sys.argv[1], sys.argv[2])
