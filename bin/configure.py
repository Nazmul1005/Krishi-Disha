"""Generate private local credentials once. Never overwrite existing configuration."""
from pathlib import Path
import secrets
import os
root = Path(__file__).resolve().parent.parent
target = root / '.env'
if target.exists():
    raise SystemExit('Configuration already exists; edit .env deliberately.')
text = (root / '.env.example').read_text()
for key in ('APP_KEY', 'DB_PASSWORD', 'MYSQL_ROOT_PASSWORD'):
    text = text.replace(f'{key}=generate-with-bin-configure', f'{key}={secrets.token_hex(32)}')
descriptor = os.open(target, os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
with os.fdopen(descriptor, 'w') as file:
    file.write(text)
print('Created private .env with unique local credentials.')
