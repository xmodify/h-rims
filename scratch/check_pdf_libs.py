import sys

try:
    import pypdf
    print("pypdf available")
except ImportError:
    print("pypdf NOT available")

try:
    import pdfplumber
    print("pdfplumber available")
except ImportError:
    print("pdfplumber NOT available")

try:
    import PyPDF2
    print("PyPDF2 available")
except ImportError:
    print("PyPDF2 NOT available")
