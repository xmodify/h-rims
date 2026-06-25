import json

# Define the 26 chronic diseases exactly as listed in the user's reference image
diseases_raw = [
    {"id": "01", "name": "โรคเบาหวาน (Diabetes mellitus)", "ranges": ["E10-E14"]},
    {"id": "02", "name": "โรคความดันโลหิตสูง (Hypertension)", "ranges": ["I10-I15"]},
    {"id": "03A", "name": "โรคตับอักเสบเรื้อรัง (Chronic hepatitis)", "ranges": ["K73", "B18"]},
    {"id": "03B", "name": "โรคตับแข็ง (Cirrhosis of liver)", "ranges": ["B18", "K74"]},
    {"id": "04", "name": "โรคภาวะหัวใจล้มเหลว (Congestive Heart failure)", "ranges": ["I50.0", "I50", "I500"]},
    {"id": "05", "name": "โรคเส้นเลือดสมองแตก/อุดตัน (Cerebrovascular accident)", "ranges": ["I69"]},
    {"id": "06", "name": "โรคมะเร็ง (Malignancy)", "ranges": ["C00-C97"]},
    {"id": "07", "name": "โรคภูมิคุ้มกันบกพร่อง (AIDS)", "ranges": ["B20-B24"]},
    {"id": "08", "name": "โรคถุงลมโป่งพอง (Emphysema)", "ranges": ["J43"]},
    {"id": "09", "name": "โรคไตวายเรื้อรัง (Chronic renal failure)", "ranges": ["N18"]},
    {"id": "10", "name": "โรคพาร์กินซั่น (Parkinson's disease)", "ranges": ["G20-G22"]},
    {"id": "11", "name": "โรคมายแอสทีเนีย เกรวิส (Myasthenia gravis)", "ranges": ["G70"]},
    {"id": "12", "name": "โรคเบาจืด (Diabetes insipidus)", "ranges": ["E23.2", "N25.1", "E232", "N251"]},
    {"id": "13", "name": "โรคมัลติเพิล สเคลอโรสิส (Multiple sclerosis)", "ranges": ["G35"]},
    {"id": "14", "name": "โรคไขมันในเลือดสูง (Dyslipidemia)", "ranges": ["E78"]},
    {"id": "15", "name": "โรคข้ออักเสบรูห์มาตอยด์ (Rheumatoid arthritis)", "ranges": ["M05-M06"]},
    {"id": "16", "name": "โรคต้อหิน (Glaucoma)", "ranges": ["H40", "H42"]},
    {"id": "17", "name": "โรคไต เนฟโฟรติค (Nephrotic syndrome)", "ranges": ["N04"]},
    {"id": "18", "name": "โรคลูปัส (SLE)", "ranges": ["M32"]},
    {"id": "19", "name": "โรคเลือดอะพลาสติค (Aplastic anemia)", "ranges": ["D61"]},
    {"id": "20", "name": "โรคทาลาสซีเมีย (Thalassemia)", "ranges": ["D56"]},  # Exclude D56.3 / D563
    {"id": "21", "name": "โรคฮีโมฟิลเลีย (Hemophilia)", "ranges": ["D66", "D67"]},
    {"id": "22", "name": "โรคเรื้อนกวาง (Psoriasis)", "ranges": ["L40"]},
    {"id": "23A", "name": "Pemphigus", "ranges": ["L10"]},
    {"id": "23B", "name": "Pemphigoid", "ranges": ["L12"]},
    {"id": "23C", "name": "Dermatitis herpetiformis", "ranges": ["L13", "L130", "L131", "L132", "L133", "L134", "L135", "L136", "L137"]},
    {"id": "23D", "name": "Linear immunoglobulin A (IgA) dermatosis", "ranges": ["L13.8", "L12.3", "L138", "L123"]},
    {"id": "23E", "name": "Epidermolysis bullosa acquisita", "ranges": ["L13.9", "L12.3", "L139", "L123"]},
    {"id": "24", "name": "โรคเลือด ไอทีพี (ITP)", "ranges": ["D69.3", "D693"]},
    {"id": "25", "name": "โรคต่อมธัยรอยด์เป็นพิษ (Thyrotoxicosis)", "ranges": ["E05"]},
    {"id": "26", "name": "โรคจิต (Schizophrenia / Delusional disorders)", "ranges": ["F20-F29"]}
]

def expand_range(r):
    r = r.replace(" ", "")
    if "-" in r:
        start_str, end_str = r.split("-")
        prefix = start_str[0]
        start_num = int(start_str[1:])
        end_num = int(end_str[1:])
        
        expanded = []
        for val in range(start_num, end_num + 1):
            expanded.append(f"{prefix}{val:02d}")
        return expanded
    else:
        return [r.replace(".", "")]

# Build lookup structures
prefixes_map = {}
diseases_list = []

for item in diseases_raw:
    expanded_prefixes = []
    for r in item["ranges"]:
        expanded_prefixes.extend(expand_range(r))
    
    for pref in expanded_prefixes:
        prefixes_map[pref] = True
        
    diseases_list.append({
        "id": item["id"],
        "name": item["name"],
        "prefixes": expanded_prefixes
    })

# Add exclusion rules
exclusions = ["D563"]

output_data = {
    "description": "ICD-10 Chronic Diseases for Social Security (SSS NCD) - 26 Diseases",
    "diseases": diseases_list,
    "prefixes": prefixes_map,
    "exclusions": exclusions
}

output_path = r"d:\Project Laravel\h-rims\docs\lookup\icd10_sss_ncd.json"
with open(output_path, "w", encoding="utf-8") as f:
    json.dump(output_data, f, ensure_ascii=False, indent=2)

print(f"Generated JSON with {len(prefixes_map)} prefixes and {len(exclusions)} exclusions at {output_path}")
