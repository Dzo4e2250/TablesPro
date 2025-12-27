# TablesPro Dokumentacija

## Struktura

```
docs/
├── README.md                          # Ta datoteka
└── planning/                          # Načrti razvoja
    ├── DEVELOPMENT_PLAN.md            # Glavni razvojni načrt
    ├── FAZA1_BARVNI_STATUSI.md        # Podroben načrt za Fazo 1
    ├── FAZA2_SUMMARY_VRSTICE.md       # Podroben načrt za Fazo 2
    └── FAZA3_ROW_GROUPS.md            # Podroben načrt za Fazo 3
```

## Pregled projekta

**TablesPro** je fork Nextcloud Tables z naslednjimi novimi funkcijami:

| Faza | Funkcija | Kompleksnost | Status |
|------|----------|--------------|--------|
| 1 | Barvni statusi | ⭐⭐ | 🔴 TODO |
| 2 | Summary vrstice | ⭐⭐⭐ | 🔴 TODO |
| 3 | Row Groups | ⭐⭐⭐⭐ | 🔴 TODO |

## Hitri start

```bash
# 1. Inicializacija projekta
./init.sh

# 2. Development mode
npm run watch

# 3. Production build
npm run build
```

## Ključne datoteke

### Frontend (Vue.js)
- `src/shared/components/ncTable/` - Glavne table komponente
- `src/shared/components/ncTable/partials/` - Pod-komponente (cells, headers)
- `src/shared/components/ncTable/mixins/` - Vue mixins

### Backend (PHP)
- `lib/Db/` - Database modeli
- `lib/Controller/` - API endpoints
- `lib/Service/` - Business logika

## Git workflow

```bash
# Nova funkcija
git checkout -b feature/ime-funkcije
# ... development ...
git add .
git commit -m "Add feature description"
git push origin feature/ime-funkcije

# Merge v main
git checkout main
git merge feature/ime-funkcije
git push origin main
```

## Povezave

- **GitHub:** https://github.com/Dzo4e2250/TablesPro
- **Nextcloud Tables (original):** https://github.com/nextcloud/tables
- **Nextcloud Developer Docs:** https://docs.nextcloud.com/server/latest/developer_manual/
