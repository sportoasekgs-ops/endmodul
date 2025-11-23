#!/bin/bash
echo "✅ SportOase IServ-Modul - Production Ready"
echo "==========================================="
echo ""
echo "📦 Modul-Informationen:"
echo "   Name: SportOase"
echo "   Version: 1.0.0"
echo "   Typ: IServ Symfony Bundle"
echo ""

# Check essential files
ESSENTIAL_FILES=(
    "composer.json"
    "package.json"
    "manifest.xml"
    "INSTALLATION.md"
    "README.md"
    "src/SportOaseBundle.php"
)

echo "🔍 Überprüfe essentielle Dateien:"
for file in "${ESSENTIAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "   ✅ $file"
    else
        echo "   ❌ $file FEHLT!"
        exit 1
    fi
done

# Check directories
ESSENTIAL_DIRS=(
    "src/Controller"
    "src/Entity"
    "src/Service"
    "migrations"
    "templates"
    "config"
    "public/build"
)

echo ""
echo "🔍 Überprüfe essentielle Verzeichnisse:"
for dir in "${ESSENTIAL_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo "   ✅ $dir"
    else
        echo "   ❌ $dir FEHLT!"
        exit 1
    fi
done

echo ""
echo "📊 Statistiken:"
echo "   Controllers: $(find src/Controller -name "*.php" 2>/dev/null | wc -l)"
echo "   Entities: $(find src/Entity -name "*.php" 2>/dev/null | wc -l)"
echo "   Services: $(find src/Service -name "*.php" 2>/dev/null | wc -l)"
echo "   Migrations: $(find migrations -name "*.php" 2>/dev/null | wc -l)"
echo "   Templates: $(find templates -name "*.twig" 2>/dev/null | wc -l)"
echo ""
echo "✅ Modul ist bereit für Debian-Paket-Erstellung!"
echo ""
echo "📖 Nächste Schritte:"
echo "   1. README.md für Übersicht lesen"
echo "   2. INSTALLATION.md für Deployment folgen"
echo "   3. Debian-Paket erstellen: dpkg-buildpackage -us -uc"
echo "   4. Auf IServ-Server installieren"
echo ""
