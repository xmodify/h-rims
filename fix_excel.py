import os
import re

directory = r"d:\Projec Laravel\h-rims\resources\views\debtor"
files = [f for f in os.listdir(directory) if f.endswith("indiv_excel.blade.php")]

# Pattern 1: mso-number-format error
# Old: mso-number-format:\"\@\";
# New: mso-number-format:"@"
pattern1 = re.compile(r'style=\'mso-number-format:\\"@\\";\'') 
# Wait, let's look at the literal content in the file.
# 50:                 <td align="center" style='mso-number-format:\"\@\";'>{{ $row->vn }}</td>
# That's a backslash followed by a double quote, then a backslash followed by an @.

for filename in files:
    path = os.path.join(directory, filename)
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Fix the formatting bug
    # We use a very specific string match to avoid multiple escapes issues
    old_fmt = 'style=\'mso-number-format:\"\\@\";\''
    new_fmt = 'style=\'mso-number-format:"@" \'' # Added space to be safe, or just "@"
    # Wait, let's just use a simple replace.
    
    # Also fix the sum bug
    old_sum = '<?php $sum_rcpt_money += $row->other ; ?>'
    new_sum = '<?php $sum_other += $row->other ; ?>'
    
    new_content = content.replace(old_fmt, 'style=\'mso-number-format:"@"\'')
    new_content = new_content.replace(old_sum, new_sum)
    
    if new_content != content:
        with open(path, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {filename}")
