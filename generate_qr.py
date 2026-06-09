#!/usr/bin/env python3
import csv
from pathlib import Path
import qrcode

csv_path = Path('data.csv')
output_dir = Path('qr_codes')
output_dir.mkdir(exist_ok=True)

with csv_path.open(newline='', encoding='utf-8') as f:
    reader = csv.DictReader(f)
    for row in reader:
        first = row.get('First Name', '').strip()
        last = row.get('Last Name', '').strip()
        title = row.get('Position', '').strip()
        org = 'Collegiate Housing Services'
        phone_work_ext = row.get('Work Phone', '').strip()
        # Base work number is constant for CHS
        base_work_number = '317-920-2600'
        phone_work = f"{base_work_number} x {phone_work_ext}" if phone_work_ext else ''
        phone_cell = row.get('Cell Phone', '').strip()
        email = row.get('Work_Email', '').strip()
        # Build vCard 3.0 (no address)
        vcard = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            f'N:{last};{first};;;',
            f'FN:{first} {last}',
            f'ORG:{org}',
            f'TITLE:{title}',
            f'TEL;TYPE=WORK,VOICE:{phone_work}',
            f'TEL;TYPE=CELL,VOICE:{phone_cell}',
            f'EMAIL:{email}',
            'END:VCARD'
        ]
        vcard_str = "\r\n".join(vcard)
        img = qrcode.make(vcard_str)
        fname = f"{first}_{last}.png".replace(' ', '_')
        img_path = output_dir / fname
        img.save(img_path)
        print(f'Generated {img_path}')
