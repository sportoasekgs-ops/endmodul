# SportOase - IServ Modul Installation

**Production-Ready Deployment Guide für IServ 3.0+**

---

## 📋 Voraussetzungen

### IServ Server
- **IServ Version:** 3.0+
- **PHP:** 8.0+ (8.2 empfohlen)
- **PostgreSQL:** Verfügbar über IServ
- **Admin-Zugriff:** Erforderlich

### Build-Maschine (Debian/Ubuntu)
```bash
sudo apt-get install -y build-essential debhelper dpkg-dev \
    php-cli php8.2 php8.2-xml php8.2-mbstring php8.2-curl \
    composer nodejs npm
```

---

## 📦 1. Debian-Paket erstellen

```bash
# In das Projekt-Verzeichnis wechseln
cd /path/to/sportoase

# Debian-Paket bauen
dpkg-buildpackage -us -uc

# Das Paket befindet sich in:
# ../iserv-sportoase_1.0.0_all.deb
```

**Der Build-Prozess:**
- ✅ Installiert PHP-Abhängigkeiten (Composer)
- ✅ Installiert Node.js-Abhängigkeiten (npm)
- ✅ Kompiliert CSS/JS-Assets (Webpack Encore)
- ✅ Erstellt das .deb-Paket

---

## 🚀 2. Installation auf IServ

### Über Admin-Panel (empfohlen)
1. IServ Admin-Panel öffnen
2. **System → Paketverwaltung**
3. **Paket hochladen** → `iserv-sportoase_1.0.0_all.deb`
4. **Installieren** klicken

### Über Kommandozeile
```bash
# Paket auf IServ-Server kopieren
scp iserv-sportoase_1.0.0_all.deb admin@your-iserv:/tmp/

# Auf IServ-Server installieren
ssh admin@your-iserv
sudo aptitude install /tmp/iserv-sportoase_1.0.0_all.deb
```

**Nach der Installation:**
- ✅ Berechtigungen automatisch gesetzt
- ✅ Verzeichnisse für Cache/Logs erstellt
- ✅ Config-Template: `/etc/iserv/sportoase.env`

---

## ⚙️ 3. Konfiguration

### OAuth2-Client in IServ registrieren

1. **IServ Admin → System → Single Sign-On**
2. **Hinzufügen** klicken
3. Konfigurieren:
   - **Name:** SportOase
   - **Redirect URI:** `https://ihre-schule.iserv.de/sportoase/oidc/callback`
   - **Scopes:** `openid`, `profile`, `email`
4. **Client-ID** und **Client-Geheimnis** kopieren

### Environment-Datei konfigurieren

```bash
sudo nano /etc/iserv/sportoase.env
```

**Wichtige Einstellungen:**

```bash
# IServ OAuth2
ISERV_BASE_URL=https://ihre-schule.iserv.de
ISERV_CLIENT_ID=ihre-client-id
ISERV_CLIENT_SECRET=ihr-client-secret

# Datenbank (meist auto-konfiguriert)
DATABASE_URL="postgresql://user:pass@localhost:5432/iserv_db"

# E-Mail
MAILER_DSN=smtp://user:pass@smtp.gmail.com:587
MAILER_FROM_ADDRESS=sportoase@ihre-schule.de
ADMIN_EMAIL=admin@ihre-schule.de

# Modul-Einstellungen
MAX_STUDENTS_PER_PERIOD=5
BOOKING_ADVANCE_MINUTES=60

# Application
APP_ENV=prod
APP_SECRET=$(php -r "echo bin2hex(random_bytes(32));")
APP_DEBUG=false
```

**Berechtigungen setzen:**
```bash
sudo chown www-data:www-data /etc/iserv/sportoase.env
sudo chmod 600 /etc/iserv/sportoase.env
```

---

## 🗄️ 4. Datenbank-Migration

```bash
cd /usr/share/iserv/modules/sportoase

# Migrationen ausführen
sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction

# Tabellen verifizieren
sudo -u postgres psql iserv_database -c "\dt sportoase_*"
```

**Erwartete Tabellen:**
- `sportoase_users`
- `sportoase_bookings`
- `sportoase_slot_names`
- `sportoase_blocked_slots`
- `sportoase_notifications`
- `sportoase_audit_log`
- `sportoase_fixed_offer_names`
- `sportoase_fixed_offer_placements`
- `sportoase_system_config`

---

## ✅ 5. Aktivierung & Test

### Modul aktivieren
1. **IServ Admin → System → Module**
2. **SportOase** suchen
3. **Aktivieren**

### Funktionstest

**Als Lehrer:**
- Dashboard öffnen (`/sportoase`)
- Wochenplan sichtbar
- Buchung erstellen
- E-Mail-Benachrichtigung prüfen

**Als Admin:**
- Admin-Dashboard öffnen (`/sportoase/admin`)
- Alle Buchungen sehen
- Statistiken prüfen
- Zeitslots verwalten

### Logs prüfen
```bash
sudo tail -f /var/log/iserv/sportoase/sportoase.log
```

---

## 🔧 Fehlerbehebung

### Modul erscheint nicht im Menü
```bash
# IServ-Dienste neu laden
sudo /usr/sbin/iserv-reload

# Cache leeren
sudo rm -rf /var/cache/iserv/*
```

### Login-Probleme
- Client-ID und Secret prüfen
- Redirect-URI exakt prüfen: `https://ihre-schule.iserv.de/sportoase/oidc/callback`
- Scopes prüfen: `openid profile email`

### Assets nicht geladen
```bash
# Assets prüfen
ls -l /usr/share/iserv/modules/sportoase/public/build/

# Sollte enthalten:
# app.css, app.js, runtime.js, manifest.json
```

**Falls fehlend:** Paket neu bauen (Build-Fehler)

---

## 🔄 Updates

```bash
# Neue Version bauen (Version in debian/changelog erhöhen)
dpkg-buildpackage -us -uc

# Update installieren
sudo aptitude install /path/to/iserv-sportoase_1.1.0_all.deb

# Neue Migrationen ausführen
cd /usr/share/iserv/modules/sportoase
sudo -u www-data php bin/console doctrine:migrations:migrate --no-interaction

# IServ neu laden
sudo /usr/sbin/iserv-reload
```

---

## 🗑️ Deinstallation

```bash
# Modul entfernen (Config behalten)
sudo aptitude remove iserv-sportoase

# Komplett entfernen (inkl. Config)
sudo aptitude purge iserv-sportoase
```

**⚠️ Achtung:** Datenbanktabellen bleiben erhalten. Manuelle Löschung:

```bash
sudo -u postgres psql iserv_database << EOF
DROP TABLE IF EXISTS sportoase_notifications CASCADE;
DROP TABLE IF EXISTS sportoase_audit_log CASCADE;
DROP TABLE IF EXISTS sportoase_fixed_offer_placements CASCADE;
DROP TABLE IF EXISTS sportoase_fixed_offer_names CASCADE;
DROP TABLE IF EXISTS sportoase_system_config CASCADE;
DROP TABLE IF EXISTS sportoase_blocked_slots CASCADE;
DROP TABLE IF EXISTS sportoase_bookings CASCADE;
DROP TABLE IF EXISTS sportoase_slot_names CASCADE;
DROP TABLE IF EXISTS sportoase_users CASCADE;
EOF
```

---

## 📊 Monitoring

```bash
# Modul-Status prüfen
curl -I https://ihre-schule.iserv.de/sportoase

# Logs überwachen
sudo tail -f /var/log/iserv/sportoase/sportoase.log

# Datenbank-Health
sudo -u postgres psql iserv_database -c "SELECT COUNT(*) FROM sportoase_bookings;"
```

---

## 📞 Support

- **E-Mail:** sportoase.kg@gmail.com
- **Version:** 1.0.0
- **Dokumentation:** `/usr/share/doc/iserv-sportoase/`

---

## ✅ Deployment-Checkliste

**Vor dem Build:**
- [ ] Assets kompiliert (`public/build/` existiert)
- [ ] `debian/changelog` aktualisiert
- [ ] Build-Maschine vorbereitet

**Nach dem Build:**
- [ ] `.deb`-Datei erstellt (< 10 MB)
- [ ] Keine Build-Fehler

**Bei Installation:**
- [ ] OAuth2-Client konfiguriert
- [ ] `/etc/iserv/sportoase.env` konfiguriert
- [ ] Datenbank-Migrationen erfolgreich

**Nach Installation:**
- [ ] Modul im IServ-Menü sichtbar
- [ ] Lehrer können buchen
- [ ] Admins haben Zugriff
- [ ] E-Mail-Benachrichtigungen funktionieren
- [ ] Keine Fehler in Logs

---

**🎉 SportOase ist jetzt produktionsbereit!**
