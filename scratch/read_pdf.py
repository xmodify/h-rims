import pypdf

reader = pypdf.PdfReader('docs/LabCatFormatChi.pdf')
out_text = f"Total Pages: {len(reader.pages)}\n"

for i, page in enumerate(reader.pages):
    text = page.extract_text()
    out_text += f"\n--- PAGE {i+1} ---\n"
    out_text += text

with open('scratch/pdf_text.txt', 'w', encoding='utf-8') as f:
    f.write(out_text)

print("Saved to scratch/pdf_text.txt")
