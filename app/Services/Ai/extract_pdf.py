import sys
import json

def extract_pdf(pdf_path):
    pages_data = []

    # Try pdfplumber first for high fidelity text extraction
    try:
        import pdfplumber
        with pdfplumber.open(pdf_path) as pdf:
            for idx, page in enumerate(pdf.pages):
                text = page.extract_text() or ""
                if text.strip():
                    pages_data.append({
                        "page": idx + 1,
                        "text": text.strip()
                    })
        if pages_data:
            return pages_data
    except Exception as e:
        pass

    # Fallback to pypdf
    try:
        import pypdf
        reader = pypdf.PdfReader(pdf_path)
        for idx, page in enumerate(reader.pages):
            text = page.extract_text() or ""
            if text.strip():
                pages_data.append({
                    "page": idx + 1,
                    "text": text.strip()
                })
    except Exception as e:
        pass

    return pages_data

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps([]))
        sys.exit(0)

    pdf_path = sys.argv[1]
    result = extract_pdf(pdf_path)
    # Output standard ASCII JSON so Windows stdout encoding never corrupts Thai characters
    print(json.dumps(result))
