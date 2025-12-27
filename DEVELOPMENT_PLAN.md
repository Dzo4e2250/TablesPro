# TablesPro - Razvojni načrt

## Pregled projekta

TablesPro je fork Nextcloud Tables z dodatnimi funkcijami podobnimi Monday.com:
1. **Barvni statusi** - Barvno označene opcije v selection stolpcih
2. **Skupine vrstic (Row Groups)** - Zložljive sekcije za organizacijo vrstic
3. **Summary vrstice** - SUM, COUNT, odstotki na dnu skupin/tabele

---

## FAZA 1: Barvni statusi (Kompleksnost: ⭐⭐ Srednje)

### 1.1 Opis funkcionalnosti
- Vsaka opcija v selection/dropdown stolpcu ima lahko barvo
- Barva se prikaže kot ozadje "badge" elementa
- Uporabnik izbere barvo pri ustvarjanju/urejanju stolpca

### 1.2 Datoteke za spremembo

#### Frontend (Vue.js)

**A) SelectionForm.vue** - Dodajanje barv opcijam
```
Lokacija: src/shared/components/ncTable/partials/columnTypePartials/forms/SelectionForm.vue
```

Spremembe:
- Dodaj color picker poleg vsakega input polja za opcijo
- Posodobi `loadDefaultOptions()` da vključuje privzete barve
- Posodobi `addOption()` da doda privzeto barvo

Trenutna struktura opcije:
```javascript
{ id: 0, label: "Option", deleted: false }
```

Nova struktura:
```javascript
{ id: 0, label: "Option", deleted: false, color: "#4CAF50" }
```

**B) TableCellSelection.vue** - Prikaz barvnega badge-a
```
Lokacija: src/shared/components/ncTable/partials/TableCellSelection.vue
```

Spremembe:
- V `non-edit-mode` div dodaj barvno ozadje
- Izračunaj kontrastno barvo teksta (bela/črna)

**C) NcSelect styling** - Barvne opcije v dropdownu
- Uporabi scoped slots za custom rendering opcij z barvo

#### Backend (PHP)

Ni potrebnih sprememb! Barva se shrani v obstoječe `selectionOptions` JSON polje.

### 1.3 Predlagane privzete barve

```javascript
const DEFAULT_COLORS = [
  '#4CAF50', // Zelena - Odobreno, Done
  '#F44336', // Rdeča - Zavrnjeno, Blocked
  '#FF9800', // Oranžna - V čakanju
  '#2196F3', // Modra - V obdelavi
  '#9C27B0', // Vijolična - Review
  '#607D8B', // Siva - Draft
  '#00BCD4', // Cyan - Info
  '#FFEB3B', // Rumena - Opozorilo
]
```

### 1.4 Implementacijski koraki

1. [ ] Posodobi `SelectionForm.vue` - dodaj color picker
2. [ ] Posodobi `TableCellSelection.vue` - prikaži barvni badge
3. [ ] Posodobi `TableCellMultiSelection.vue` - barvni badges za multi-select
4. [ ] Dodaj utility funkcijo za kontrast teksta
5. [ ] Dodaj CSS za barvne badge elemente
6. [ ] Testiranje

---

## FAZA 2: Summary vrstice (Kompleksnost: ⭐⭐⭐ Srednje-Težko)

### 2.1 Opis funkcionalnosti
- Na dnu tabele (ali skupine) se prikaže summary vrstica
- Za number stolpce: SUM, AVG, MIN, MAX
- Za selection stolpce: COUNT po vrednostih, progress bar z odstotki
- Uporabnik lahko vklopi/izklopi summary za vsak stolpec

### 2.2 Datoteke za spremembo

#### Frontend (Vue.js)

**A) CustomTable.vue** - Dodaj `<tfoot>` za summary
```
Lokacija: src/shared/components/ncTable/sections/CustomTable.vue
```

Dodaj po `</tbody>`:
```vue
<tfoot v-if="showSummary" class="summary-row">
  <tr>
    <td class="sticky"></td>
    <td v-for="column in columns" :key="column.id">
      <SummaryCell :column="column" :rows="getSearchedAndFilteredRows" />
    </td>
    <td class="sticky"></td>
  </tr>
</tfoot>
```

**B) Ustvari novo komponento: SummaryCell.vue**
```
Lokacija: src/shared/components/ncTable/partials/SummaryCell.vue
```

```vue
<template>
  <div class="summary-cell">
    <!-- Za number stolpce -->
    <div v-if="isNumberColumn" class="number-summary">
      <span class="sum">Σ {{ formatNumber(sum) }}</span>
    </div>

    <!-- Za selection stolpce -->
    <div v-else-if="isSelectionColumn" class="selection-summary">
      <div class="progress-bar">
        <div v-for="stat in selectionStats"
             :key="stat.id"
             :style="{ width: stat.percentage + '%', backgroundColor: stat.color }"
             :title="stat.label + ': ' + stat.count + ' (' + stat.percentage + '%)'">
        </div>
      </div>
      <span class="total">{{ totalCount }} items</span>
    </div>
  </div>
</template>
```

**C) Dodaj summaryMixin.js**
```
Lokacija: src/shared/components/ncTable/mixins/summaryMixin.js
```

```javascript
export const summaryMixin = {
  methods: {
    calculateSum(columnId, rows) {
      return rows.reduce((sum, row) => {
        const cell = row.data?.find(c => c.columnId === columnId)
        return sum + (parseFloat(cell?.value) || 0)
      }, 0)
    },

    calculateSelectionStats(column, rows) {
      const counts = {}
      rows.forEach(row => {
        const cell = row.data?.find(c => c.columnId === column.id)
        const value = cell?.value
        counts[value] = (counts[value] || 0) + 1
      })

      return column.selectionOptions.map(opt => ({
        id: opt.id,
        label: opt.label,
        color: opt.color || '#607D8B',
        count: counts[opt.id] || 0,
        percentage: Math.round((counts[opt.id] || 0) / rows.length * 100)
      }))
    }
  }
}
```

**D) Posodobi View settings UI**
- Dodaj toggle za "Show summary row"
- Opcijsko: izbira tipa summary per stolpec

#### Backend (PHP)

**Opcijsko:** Dodaj `summarySettings` v View model če želiš server-side shranjevanje nastavitev.

### 2.3 Implementacijski koraki

1. [ ] Ustvari `SummaryCell.vue` komponento
2. [ ] Ustvari `summaryMixin.js`
3. [ ] Posodobi `CustomTable.vue` - dodaj tfoot
4. [ ] Dodaj CSS za summary row in progress bar
5. [ ] Dodaj toggle v View settings
6. [ ] Testiranje z različnimi tipi stolpcev

---

## FAZA 3: Skupine vrstic - Row Groups (Kompleksnost: ⭐⭐⭐⭐ Težko)

### 3.1 Opis funkcionalnosti
- Vrstice se grupirajo po vrednosti izbranega stolpca
- Vsaka skupina ima header z imenom in count
- Skupine so zložljive (expand/collapse)
- Vsaka skupina ima svoj summary (SUM, %)
- "+ Add item" gumb znotraj vsake skupine

### 3.2 Arhitektura

```
┌─────────────────────────────────────────────────────┐
│ ▼ Obratovalni stroški Januar (15 items)     1,516€ │ <- Group Header
├─────────────────────────────────────────────────────┤
│   Elektrika & plantrade    PLAČANO    92.23€       │
│   Telefon                  PLAČANO    22€          │
│   ...                                              │
│   + Add item                                       │ <- Add within group
├─────────────────────────────────────────────────────┤
│                            ████████  1,516.72€     │ <- Group Summary
│                            100%                    │
└─────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────┐
│ ▶ Marketing Januar (8 items)              2,564€   │ <- Collapsed group
└─────────────────────────────────────────────────────┘
```

### 3.3 Datoteke za spremembo

#### Frontend (Vue.js)

**A) CustomTable.vue** - Glavna logika grupiranja
```
Lokacija: src/shared/components/ncTable/sections/CustomTable.vue
```

Dodaj v `data()`:
```javascript
data() {
  return {
    // ... existing
    groupByColumnId: null,
    expandedGroups: {}, // { 'groupValue': true/false }
  }
}
```

Dodaj computed:
```javascript
computed: {
  groupedRows() {
    if (!this.groupByColumnId) return null

    const groups = {}
    this.getSearchedAndFilteredAndSortedRows.forEach(row => {
      const cell = row.data?.find(c => c.columnId === this.groupByColumnId)
      const groupValue = cell?.value ?? '__ungrouped__'

      if (!groups[groupValue]) {
        groups[groupValue] = {
          value: groupValue,
          label: this.getGroupLabel(groupValue),
          rows: [],
          expanded: this.expandedGroups[groupValue] !== false
        }
      }
      groups[groupValue].rows.push(row)
    })

    return Object.values(groups)
  }
}
```

**B) Ustvari GroupHeader.vue komponento**
```
Lokacija: src/shared/components/ncTable/partials/GroupHeader.vue
```

```vue
<template>
  <tr class="group-header" @click="$emit('toggle')">
    <td :colspan="columnsCount">
      <div class="group-header-content">
        <ChevronIcon :class="{ expanded }" />
        <span class="group-label" :style="{ backgroundColor: groupColor }">
          {{ label }}
        </span>
        <span class="group-count">({{ rows.length }} items)</span>
        <span class="group-sum">{{ groupSum }}</span>
      </div>
    </td>
  </tr>
</template>
```

**C) Ustvari GroupSummary.vue komponento**
```
Lokacija: src/shared/components/ncTable/partials/GroupSummary.vue
```

Podobno kot SummaryCell, ampak za skupino.

**D) Posodobi template v CustomTable.vue**

```vue
<template>
  <!-- Brez grupiranja - obstoječa logika -->
  <tbody v-if="!groupByColumnId">
    <TableRow v-for="row in currentPageRows" ... />
  </tbody>

  <!-- Z grupiranjem -->
  <template v-else>
    <tbody v-for="group in groupedRows" :key="group.value">
      <GroupHeader
        :label="group.label"
        :rows="group.rows"
        :expanded="group.expanded"
        @toggle="toggleGroup(group.value)"
      />
      <template v-if="group.expanded">
        <TableRow v-for="row in group.rows" :key="row.id" ... />
        <tr class="add-item-row">
          <td :colspan="columnsCount">
            <NcButton @click="addItemToGroup(group.value)">
              + Add item
            </NcButton>
          </td>
        </tr>
        <GroupSummary :rows="group.rows" :columns="columns" />
      </template>
    </tbody>
  </template>
</template>
```

**E) Dodaj Group By selector v UI**
- Dropdown za izbiro stolpca po katerem grupiramo
- "None" opcija za izklop grupiranja

#### Backend (PHP)

**Opcijsko:** Shrani `groupByColumnId` v View settings.

### 3.4 Implementacijski koraki

1. [ ] Dodaj grouping logiko v CustomTable.vue
2. [ ] Ustvari GroupHeader.vue komponento
3. [ ] Ustvari GroupSummary.vue komponento
4. [ ] Posodobi template za grouped view
5. [ ] Dodaj Group By selector v toolbar
6. [ ] Implementiraj expand/collapse state
7. [ ] Implementiraj "Add item" znotraj skupine
8. [ ] Dodaj CSS za group styling
9. [ ] Testiranje z različnimi stolpci
10. [ ] Pagination handling za groupe

---

## Prioriteta implementacije

| # | Funkcija | Kompleksnost | Odvisnosti |
|---|----------|--------------|------------|
| 1 | Barvni statusi | ⭐⭐ | Nobene |
| 2 | Summary vrstice | ⭐⭐⭐ | Barvni statusi (za progress bar) |
| 3 | Row Groups | ⭐⭐⭐⭐ | Summary (za group summary) |

**Priporočen vrstni red:** 1 → 2 → 3

---

## Tehnične reference

### Uporabne Vue knjižnice

- [vue-good-table](https://xaksis.github.io/vue-good-table/guide/advanced/grouped-table.html) - Referenca za grouped tables
- [PrimeVue DataTable](https://primevue.org/datatable/) - Row grouping in summary
- [Element Plus Table](https://element-plus.org/en-US/component/table) - Summary row implementation

### Nextcloud Vue komponente

- `NcSelect` - Za dropdowne
- `NcButton` - Gumbi
- `NcColorPicker` - Za izbiro barv (če obstaja, sicer uporabi HTML5 input type="color")

### Obstoječa koda za referenco

- `src/shared/components/ncTable/mixins/columnClass.js` - Definicije tipov stolpcev
- `src/shared/components/ncTable/mixins/columnsTypes/selection.js` - Selection logika
- `lib/Db/Column.php` - Backend model za stolpce

---

## Testiranje

### Unit testi
- Summary kalkulacije
- Grouping logika
- Color contrast utility

### E2E testi (Cypress)
- Dodajanje barve opciji
- Prikaz summary
- Expand/collapse skupin
- Add item v skupino

---

## Git workflow

```bash
# Za vsako funkcijo naredi svojo branch
git checkout -b feature/colored-status
git checkout -b feature/summary-rows
git checkout -b feature/row-groups

# Po končani funkciji
git checkout main
git merge feature/colored-status
git push origin main
```

---

## Naslednji koraki

1. Namesti development dependencies:
   ```bash
   cd ~/Applications/AppProject/TablesPro
   npm ci
   composer install --no-dev
   ```

2. Zaženi development build:
   ```bash
   npm run dev
   ```

3. Začni s Fazo 1 (Barvni statusi)
