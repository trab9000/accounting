#\!/bin/bash
# Fake sendmail - captures outgoing mail to files for testing
MAIL_DIR="/var/www/html/mail_capture"
mkdir -p "$MAIL_DIR"
FILENAME="$MAIL_DIR/$(date +%Y%m%d_%H%M%S_$$).eml"
cat > "$FILENAME"
chmod 644 "$FILENAME"
