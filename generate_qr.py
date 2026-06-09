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
        phone_work = row.get('Work Phone', '').strip()
        phone_cell = row.get('Cell Phone', '').strip()
        email = row.get('Work_Email', '').strip()
        address = row.get('Primary Shipping Address', '').strip()
        city = row.get('City', '').strip()
        state = row.get('State', '').strip()
        zipc = row.get('Zipcode', '').strip()
        # Build vCard 3.0
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
            f'ADR;TYPE=WORK:;;{address};{city};{state};{zipc};USA',
            'END:VCARD'
        ]
        vcard_str = "\r\n".join(vcard)
        img = qrcode.make(vcard_str)
        fname = f"{first}_{last}.png".replace(' ', '_')
        img_path = output_dir / fname
        img.save(img_path)
        print(f'Generated {img_path}')
