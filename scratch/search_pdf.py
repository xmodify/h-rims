import sys

with open('scratch/pdf_text.txt', 'r', encoding='utf-8') as f:
    text = f.read()

lines = text.split('\n')
results = []
for i, line in enumerate(lines):
    if any(kwd in line.lower() for kwd in ['lccode', 'billgroup', 'cscode', 'tmlt', 'loinc', 'panel', 'name', 'sflag', 'chargecat', 'unitprice', 'benefitplan', 'reimbprice', 'updateflag']):
        results.append(f"Line {i+1}: {line}")

with open('scratch/search_results.txt', 'w', encoding='utf-8') as f:
    f.write('\n'.join(results))

print("Search results written to scratch/search_results.txt")
