# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

TablesPro is a Nextcloud app - an enhanced fork of [Nextcloud Tables](https://github.com/nextcloud/tables) with Monday.com-inspired features. It provides table management with views, sharing, and data import/export capabilities.

**App ID:** `tablespro`
**Namespace:** `OCA\TablesPro`
**Nextcloud compatibility:** 31-33

## Build Commands

```bash
# Install all dependencies
composer install --no-dev
npm ci

# Development build (with source maps)
npm run dev

# Watch mode for frontend development
npm run watch

# Production build
npm run build

# Run all linting
npm run lint          # JS/Vue linting
npm run stylelint     # CSS/SCSS linting
composer run psalm    # PHP static analysis
composer run cs:check # PHP code style check

# Fix linting issues
npm run lint:fix
npm run stylelint:fix
composer run cs:fix

# Run tests
composer test:unit              # PHP unit tests
npm run tests                   # Cypress e2e tests
npm run tests:component         # Cypress component tests

# Generate OpenAPI spec and TypeScript types
composer run openapi
npm run typescript:generate
```

## Architecture

### Frontend (Vue 2 + Pinia)

Entry points in `src/`:
- `main.js` - Main app entry
- `file-actions.js` - Nextcloud Files integration
- `reference.js` - Smart picker/reference widget

**State Management** (`src/store/`):
- `store.js` - Main store: tables, views, contexts, navigation state
- `data.js` - Data store: rows and columns per table/view

**Component Structure** (`src/`):
- `modules/` - Feature modules (main, sidebar, navigation, modals)
- `shared/components/ncTable/` - Core table component with partials for cells, headers, forms
- `shared/components/ncTable/partials/columnTypePartials/` - Column type-specific form components
- `shared/components/ncTable/partials/rowTypePartials/` - Row editing forms per column type

### Backend (PHP)

**Key directories** (`lib/`):
- `Controller/` - API endpoints (Api1Controller for v1, ApiTablesController/ApiColumnsController for v2 OCS)
- `Service/` - Business logic (TableService, ColumnService, RowService, ViewService)
- `Service/ColumnTypes/` - Type-specific business logic (NumberBusiness, SelectionBusiness, etc.)
- `Db/` - Database entities and mappers
- `Db/ColumnTypes/` - Column type query builders

**Row Storage:** Uses "Row2" system with separate cell tables per data type:
- `RowCellText`, `RowCellNumber`, `RowCellDatetime`, `RowCellSelection`, `RowCellUsergroup`
- `RowSleeve` - Row metadata (table ID, created/updated timestamps)

### API Routes

Defined in `appinfo/routes.php`:
- **v1 API:** `/api/1/` - Legacy REST endpoints
- **v2 API:** `/api/2/` - OCS endpoints (tables, columns, rows, contexts)
- **Internal:** `/table`, `/view`, `/row`, `/share` - Frontend-facing endpoints

### Column Types

Each column type has:
1. Business class (`lib/Service/ColumnTypes/*Business.php`)
2. Query builder (`lib/Db/ColumnTypes/*ColumnQB.php`)
3. Vue form components for column settings and row editing

Types: `text-line`, `text-long`, `text-link`, `text-rich`, `number`, `number-stars`, `number-progress`, `selection`, `selection-multi`, `selection-check`, `datetime`, `datetime-date`, `datetime-time`, `usergroup`

## Development Notes

- App uses PHP-Scoper for dependency isolation (see `scoper.inc.php`)
- Vendor dependencies are organized under `lib/Vendor/` after composer install
- Uses Vite with `@nextcloud/vite-config` for frontend bundling
- Cypress tests in `cypress/` directory
- PHP unit tests in `tests/unit/`

## Key Files

- `appinfo/info.xml` - App metadata and Nextcloud integration
- `appinfo/routes.php` - All API route definitions
- `src/shared/components/ncTable/NcTable.vue` - Main table component
- `src/store/store.js` - Pinia store with all table/view/context operations
