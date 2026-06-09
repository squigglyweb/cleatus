#!/usr/bin/env python3
import csv
from pathlib import Path

input_path = Path('data.csv')
output_path = Path('data_with_qr.csv')
qr_dir = Path('qr_codes')

with input_path.open(newline='', encoding='utf-8') as fin, output_path.open('w', newline='', encoding='utf-8') as fout:
    reader = csv.DictReader(fin)
    fieldnames = reader.fieldnames + ['QR_Code']
    writer = csv.DictWriter(fout, fieldnames=fieldnames)
    writer.writeheader()
    for row in reader:
        first = row.get('First Name','').strip()
        last = row.get('Last Name','').strip()
        filename = f"{first}_{last}.png".replace(' ', '_')
        row['QR_Code'] = str(qr_dir / filename)
        writer.writerow(row)
print('Created', output_path)
