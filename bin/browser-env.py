from pathlib import Path
import json, os
root = Path(__file__).resolve().parent.parent
database = json.loads((root / '.browser-fixtures.json').read_text())['database']
lines = [line for line in (root / '.env').read_text().splitlines() if not line.startswith(('MYSQL_ROOT_PASSWORD=', 'DB_NAME='))]
lines += [f'DB_NAME={database}', 'DB_USER=krishidisha_app', 'DB_HOST=db']
target = root / '.browser.env'
target.write_text('\n'.join(lines) + '\n')
os.chmod(target, 0o600)
print('Prepared private isolated browser-test configuration.')
