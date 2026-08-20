import sys
import os
import json
import re
import io
import logging

# Silence all logging
logging.basicConfig(level=logging.ERROR)
logging.getLogger().setLevel(logging.ERROR)

def analyze(mdb_path):
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
            # Restore stdout to print error
            sys.stdout = old_stdout
            sys.stderr = old_stderr
            print(json.dumps({"error": "Table 'DataIn' not found in MDB file."}))
            sys.exit(1)
            
        table = db.parse_table("DataIn")
        pdates = table['PDate']
        
        # Count rows per unique date
        counts = {}
        for p in pdates:
            p_str = str(p).split()[0]
            counts[p_str] = counts.get(p_str, 0) + 1
            
        sorted_pdates = sorted(counts.keys())
        
        results = []
        for p_str in sorted_pdates:
            match = re.match(r'^(\d{4})-(\d{2})-(\d{2})$', p_str)
            if not match:
                continue
            ce_year = int(match.group(1))
            month = int(match.group(2))
            
            be_year = ce_year + 543
            label = f"{thai_months[month]} {be_year}"
            period = f"{be_year}-{month:02d}"
            
            results.append({
                'pdate': p_str,
                'count': counts[p_str],
                'label': label,
                'period': period
            })
            
        # Restore stdout/stderr
        sys.stdout = old_stdout
        sys.stderr = old_stderr
        
        # Print final clean JSON to standard output
        print(json.dumps(results, ensure_ascii=False))
    except Exception as e:
        sys.stdout = old_stdout
        sys.stderr = old_stderr
        print(json.dumps({"error": str(e)}))
        sys.exit(1)

thai_months = {
    1: "มกราคม", 2: "กุมภาพันธ์", 3: "มีนาคม", 4: "เมษายน",
    5: "พฤษภาคม", 6: "มิถุนายน", 7: "กรกฎาคม", 8: "สิงหาคม",
    9: "กันยายน", 10: "ตุลาคม", 11: "พฤศจิกายน", 12: "ธันวาคม"
}

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No MDB path provided"}))
        sys.exit(1)
    analyze(sys.argv[1])
