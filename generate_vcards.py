#!/usr/bin/env python3
import csv, base64
from pathlib import Path

csv_path = Path('data.csv')
output_dir = Path('vcards')
output_dir.mkdir(exist_ok=True)

logo_path = Path('assets/chs_logo_1.png')
logo_b64 = base64.b64encode(logo_path.read_bytes()).decode()

with csv_path.open(newline='', encoding='utf-8') as f:
    reader = csv.DictReader(f)
    for row in reader:
        first = row.get('First Name', '').strip()
        last = row.get('Last Name', '').strip()
        title = row.get('Position', '').strip()
        org = 'Collegiate Housing Services'
        phone_work_ext = row.get('Work Phone', '').strip()
        base_work_number = '317-920-2600'
        phone_work = f"{base_work_number} x {phone_work_ext}" if phone_work_ext else ''
        phone_cell = row.get('Cell Phone', '').strip()
        email = row.get('Work_Email', '').strip()
        website = 'https://housingservices.com'
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
            f'URL:{website}',
            f'LOGO;ENCODING=b;TYPE=PNG:{logo_b64}',
            'END:VCARD'
        ]
        vcard_str = "\r\n".join(vcard)
        fname = f"{first}_{last}.vcf".replace(' ', '_')
        out_path = output_dir / fname
        out_path.write_text(vcard_str, encoding='utf-8')
        print(f'Created {out_path}')
