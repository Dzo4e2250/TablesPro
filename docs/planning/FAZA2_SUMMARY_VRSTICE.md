# FAZA 2: Summary vrstice

## Cilj
Dodati summary vrstico na dnu tabele/skupine s kalkulacijami (SUM, COUNT, %).

## Vizualni primer

```
┌──────────────────────────────────────────────────────────────────┐
│ Item          │ Status      │ Datum       │ Vsota    │ Text     │
├──────────────────────────────────────────────────────────────────┤
│ Elektrika     │ PLAČANO     │ Feb 24      │ 92.23€   │          │
│ Telefon       │ PLAČANO     │ Jan 22      │ 22€      │          │
│ Najemnina     │ V ČAKANJU   │ Feb 22      │ 180€     │          │
│ ...           │ ...         │ ...         │ ...      │          │
├──────────────────────────────────────────────────────────────────┤
│ SUMMARY       │ ████████░░  │             │ 1,516€   │ 15 items │
│               │ 80% plačano │             │ Σ SUM    │          │
└──────────────────────────────────────────────────────────────────┘
```

## Datoteke za ustvariti

### 1. SummaryCell.vue (NOVA)
**Pot:** `src/shared/components/ncTable/partials/SummaryCell.vue`

```vue
<template>
  <div class="summary-cell">
    <!-- Number columns: SUM -->
    <template v-if="column.type === 'number'">
      <div class="number-summary">
        <span class="sum-label">Σ</span>
        <span class="sum-value">{{ formatNumber(sum) }}</span>
      </div>
    </template>

    <!-- Selection columns: Progress bar -->
    <template v-else-if="column.type === 'selection'">
      <div class="selection-summary">
        <div class="progress-bar">
          <div
            v-for="stat in selectionStats"
            :key="stat.id"
            class="progress-segment"
            :style="{
              width: stat.percentage + '%',
              backgroundColor: stat.color
            }"
            :title="`${stat.label}: ${stat.count} (${stat.percentage}%)`"
          />
        </div>
        <div class="stats-tooltip">
          <span v-for="stat in selectionStats" :key="stat.id" class="stat-item">
            <span class="stat-dot" :style="{ backgroundColor: stat.color }"></span>
            {{ stat.label }}: {{ stat.count }}
          </span>
        </div>
      </div>
    </template>

    <!-- Text columns: COUNT -->
    <template v-else-if="column.type === 'text' || column.type === 'text-rich'">
      <div class="count-summary">
        <span class="count-value">{{ rowCount }} items</span>
      </div>
    </template>

    <!-- Other columns: empty or count -->
    <template v-else>
      <div class="empty-summary">-</div>
    </template>
  </div>
</template>

<script>
export default {
  name: 'SummaryCell',

  props: {
    column: {
      type: Object,
      required: true,
    },
    rows: {
      type: Array,
      default: () => [],
    },
  },

  computed: {
    rowCount() {
      return this.rows.length
    },

    sum() {
      return this.rows.reduce((total, row) => {
        const cell = row.data?.find(c => c.columnId === this.column.id)
        const value = parseFloat(cell?.value) || 0
        return total + value
      }, 0)
    },

    selectionStats() {
      const counts = {}

      this.rows.forEach(row => {
        const cell = row.data?.find(c => c.columnId === this.column.id)
        const value = cell?.value
        if (value !== null && value !== undefined) {
          counts[value] = (counts[value] || 0) + 1
        }
      })

      const options = this.column.selectionOptions || []
      return options
        .filter(opt => !opt.deleted)
        .map(opt => ({
          id: opt.id,
          label: opt.label,
          color: opt.color || '#607D8B',
          count: counts[opt.id] || 0,
          percentage: this.rows.length > 0
            ? Math.round((counts[opt.id] || 0) / this.rows.length * 100)
            : 0
        }))
        .filter(stat => stat.count > 0)
    },
  },

  methods: {
    formatNumber(value) {
      // Format number with locale
      return new Intl.NumberFormat('sl-SI', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
      }).format(value)
    },
  },
}
</script>

<style lang="scss" scoped>
.summary-cell {
  padding: 8px 0;
}

.number-summary {
  display: flex;
  align-items: center;
  gap: 4px;
  font-weight: 600;

  .sum-label {
    color: var(--color-text-maxcontrast);
    font-size: 12px;
  }

  .sum-value {
    color: var(--color-main-text);
  }
}

.selection-summary {
  .progress-bar {
    display: flex;
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
    background: var(--color-background-dark);
    margin-bottom: 4px;
  }

  .progress-segment {
    transition: width 0.3s ease;
  }

  .stats-tooltip {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 11px;
    color: var(--color-text-maxcontrast);
  }

  .stat-item {
    display: flex;
    align-items: center;
    gap: 4px;
  }

  .stat-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
  }
}

.count-summary {
  color: var(--color-text-maxcontrast);
  font-size: 13px;
}

.empty-summary {
  color: var(--color-text-maxcontrast);
}
</style>
```

---

### 2. summaryMixin.js (NOVA)
**Pot:** `src/shared/components/ncTable/mixins/summaryMixin.js`

```javascript
/**
 * Mixin for summary calculations
 */
export const summaryMixin = {
  data() {
    return {
      showSummary: true, // Can be toggled via settings
    }
  },

  computed: {
    /**
     * Check if any column supports summary
     */
    hasSummaryColumns() {
      return this.columns?.some(col =>
        ['number', 'selection', 'selection-multi'].includes(col.type)
      )
    },
  },

  methods: {
    /**
     * Calculate sum for a number column
     */
    calculateSum(columnId, rows) {
      return rows.reduce((sum, row) => {
        const cell = row.data?.find(c => c.columnId === columnId)
        return sum + (parseFloat(cell?.value) || 0)
      }, 0)
    },

    /**
     * Calculate average for a number column
     */
    calculateAverage(columnId, rows) {
      if (rows.length === 0) return 0
      return this.calculateSum(columnId, rows) / rows.length
    },

    /**
     * Calculate min for a number column
     */
    calculateMin(columnId, rows) {
      const values = rows
        .map(row => {
          const cell = row.data?.find(c => c.columnId === columnId)
          return parseFloat(cell?.value)
        })
        .filter(v => !isNaN(v))

      return values.length > 0 ? Math.min(...values) : 0
    },

    /**
     * Calculate max for a number column
     */
    calculateMax(columnId, rows) {
      const values = rows
        .map(row => {
          const cell = row.data?.find(c => c.columnId === columnId)
          return parseFloat(cell?.value)
        })
        .filter(v => !isNaN(v))

      return values.length > 0 ? Math.max(...values) : 0
    },

    /**
     * Calculate selection statistics
     */
    calculateSelectionStats(column, rows) {
      const counts = {}

      rows.forEach(row => {
        const cell = row.data?.find(c => c.columnId === column.id)
        const value = cell?.value
        if (value !== null && value !== undefined) {
          counts[value] = (counts[value] || 0) + 1
        }
      })

      const options = column.selectionOptions || []
      return options
        .filter(opt => !opt.deleted)
        .map(opt => ({
          id: opt.id,
          label: opt.label,
          color: opt.color || '#607D8B',
          count: counts[opt.id] || 0,
          percentage: rows.length > 0
            ? Math.round((counts[opt.id] || 0) / rows.length * 100)
            : 0
        }))
    },

    /**
     * Check if column can have summary
     */
    canHaveSummary(column) {
      return ['number', 'selection', 'selection-multi'].includes(column.type)
    },
  },
}
```

---

### 3. Posodobitev CustomTable.vue
**Pot:** `src/shared/components/ncTable/sections/CustomTable.vue`

**Dodaj import:**
```javascript
import SummaryCell from '../partials/SummaryCell.vue'
import { summaryMixin } from '../mixins/summaryMixin.js'
```

**Dodaj component:**
```javascript
components: {
  // ... obstoječi
  SummaryCell,
},

mixins: [summaryMixin],
```

**Dodaj v template po `</tbody>` (pred pagination-footer):**
```vue
<!-- Summary Row -->
<tfoot v-if="showSummary && hasSummaryColumns" class="summary-footer">
  <tr class="summary-row">
    <td class="sticky">
      <span class="summary-label">SUMMARY</span>
    </td>
    <td v-for="column in columns" :key="'summary-' + column.id">
      <SummaryCell
        :column="column"
        :rows="getSearchedAndFilteredAndSortedRows"
      />
    </td>
    <td class="sticky"></td>
  </tr>
</tfoot>
```

**Dodaj CSS:**
```scss
.summary-footer {
  position: sticky;
  bottom: 0;
  z-index: 5;

  .summary-row {
    background-color: var(--color-background-dark);
    border-top: 2px solid var(--color-border);

    td {
      padding: 12px 8px;
      font-weight: 500;
    }

    .summary-label {
      font-size: 11px;
      text-transform: uppercase;
      color: var(--color-text-maxcontrast);
      letter-spacing: 0.5px;
    }
  }
}
```

---

## Checklist

- [ ] Ustvari `SummaryCell.vue`
- [ ] Ustvari `summaryMixin.js`
- [ ] Posodobi `CustomTable.vue` - import in template
- [ ] Dodaj CSS za summary footer
- [ ] Dodaj toggle za show/hide summary (opcijsko)
- [ ] Build in test
- [ ] Test z number stolpci (SUM)
- [ ] Test s selection stolpci (progress bar)
- [ ] Git commit

---

## Testiranje

1. Ustvari tabelo z number in selection stolpci
2. Dodaj nekaj vrstic z različnimi vrednostmi
3. Preveri da se SUM pravilno izračuna
4. Preveri da progress bar prikazuje pravilne odstotke
5. Preveri da so barve iz Faze 1 pravilno prikazane
6. Preveri hover tooltip na progress baru
