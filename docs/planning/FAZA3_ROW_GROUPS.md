# FAZA 3: Skupine vrstic (Row Groups)

## Cilj
Grupiranje vrstic po vrednosti stolpca z zložljivimi sekcijami in group summary.

## Vizualni primer

```
┌─────────────────────────────────────────────────────────────────────┐
│ ▼ Obratovalni stroški Januar                    15 items   1,516€  │
├─────────────────────────────────────────────────────────────────────┤
│   Elektrika        │ PLAČANO     │ Feb 24   │ 92.23€   │           │
│   Telefon          │ PLAČANO     │ Jan 22   │ 22€      │           │
│   Najemnina        │ PLAČANO     │ Feb 22   │ 180€     │           │
│   ...                                                               │
│   + Add item                                                        │
├─────────────────────────────────────────────────────────────────────┤
│                    │ ████████░░░ │          │ 1,516€   │           │
│                    │ 85% done    │          │ Σ SUM    │           │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│ ▶ Marketing Januar (collapsed)                   8 items   2,564€  │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│ ▼ Potrebščine Januar                             10 items    181€  │
├─────────────────────────────────────────────────────────────────────┤
│   Benzin           │ PLAČANO     │ Jan 22   │ 30€      │           │
│   ...                                                               │
```

## Arhitektura

### Podatkovni model za skupine

```javascript
// Struktura groupe
{
  id: 'group_value_hash',
  value: 'Marketing Januar',  // Vrednost stolpca po katerem grupiramo
  label: 'Marketing Januar',  // Prikazno ime
  color: '#2196F3',           // Barva (če je selection stolpec)
  rows: [...],                // Vrstice v tej skupini
  expanded: true,             // Ali je skupina odprta
  summary: {
    count: 8,
    sums: { columnId: value },
    selectionStats: { columnId: [...] }
  }
}
```

---

## Datoteke za ustvariti

### 1. GroupHeader.vue (NOVA)
**Pot:** `src/shared/components/ncTable/partials/GroupHeader.vue`

```vue
<template>
  <tr class="group-header" :class="{ collapsed: !expanded }" @click="$emit('toggle')">
    <td :colspan="columnsCount + 2" class="group-header-cell">
      <div class="group-header-content">
        <!-- Expand/Collapse icon -->
        <button class="expand-btn" :aria-label="expanded ? 'Collapse' : 'Expand'">
          <ChevronRightIcon v-if="!expanded" :size="20" />
          <ChevronDownIcon v-else :size="20" />
        </button>

        <!-- Group label with color -->
        <span
          class="group-label"
          :style="groupColor ? { backgroundColor: groupColor, color: getContrastColor(groupColor) } : {}"
        >
          {{ label }}
        </span>

        <!-- Row count -->
        <span class="group-count">
          ({{ rows.length }} {{ rows.length === 1 ? 'item' : 'items' }})
        </span>

        <!-- Quick sum preview -->
        <span v-if="primarySum !== null" class="group-sum">
          {{ formatNumber(primarySum) }}
        </span>
      </div>
    </td>
  </tr>
</template>

<script>
import ChevronRightIcon from 'vue-material-design-icons/ChevronRight.vue'
import ChevronDownIcon from 'vue-material-design-icons/ChevronDown.vue'

export default {
  name: 'GroupHeader',

  components: {
    ChevronRightIcon,
    ChevronDownIcon,
  },

  props: {
    label: {
      type: String,
      required: true,
    },
    rows: {
      type: Array,
      default: () => [],
    },
    columns: {
      type: Array,
      default: () => [],
    },
    expanded: {
      type: Boolean,
      default: true,
    },
    groupColor: {
      type: String,
      default: null,
    },
  },

  computed: {
    columnsCount() {
      return this.columns.length
    },

    // Get sum of first number column for preview
    primarySum() {
      const numberColumn = this.columns.find(col => col.type === 'number')
      if (!numberColumn) return null

      return this.rows.reduce((sum, row) => {
        const cell = row.data?.find(c => c.columnId === numberColumn.id)
        return sum + (parseFloat(cell?.value) || 0)
      }, 0)
    },
  },

  methods: {
    formatNumber(value) {
      return new Intl.NumberFormat('sl-SI', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
      }).format(value)
    },

    getContrastColor(hexColor) {
      if (!hexColor) return '#000000'
      const hex = hexColor.replace('#', '')
      const r = parseInt(hex.substr(0, 2), 16)
      const g = parseInt(hex.substr(2, 2), 16)
      const b = parseInt(hex.substr(4, 2), 16)
      const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
      return luminance > 0.5 ? '#000000' : '#FFFFFF'
    },
  },
}
</script>

<style lang="scss" scoped>
.group-header {
  cursor: pointer;
  user-select: none;
  background-color: var(--color-background-dark) !important;

  &:hover {
    background-color: var(--color-background-hover) !important;
  }

  &.collapsed {
    border-bottom: 2px solid var(--color-border);
  }
}

.group-header-cell {
  padding: 12px 16px !important;
}

.group-header-content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.expand-btn {
  background: none;
  border: none;
  padding: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;

  &:hover {
    background-color: var(--color-background-hover);
  }
}

.group-label {
  font-weight: 600;
  font-size: 14px;
  padding: 4px 12px;
  border-radius: 12px;
}

.group-count {
  color: var(--color-text-maxcontrast);
  font-size: 13px;
}

.group-sum {
  margin-left: auto;
  font-weight: 600;
  color: var(--color-main-text);
}
</style>
```

---

### 2. GroupSummary.vue (NOVA)
**Pot:** `src/shared/components/ncTable/partials/GroupSummary.vue`

```vue
<template>
  <tr class="group-summary">
    <td class="sticky"></td>
    <td v-for="column in columns" :key="'gsummary-' + column.id">
      <SummaryCell :column="column" :rows="rows" />
    </td>
    <td class="sticky"></td>
  </tr>
</template>

<script>
import SummaryCell from './SummaryCell.vue'

export default {
  name: 'GroupSummary',

  components: {
    SummaryCell,
  },

  props: {
    columns: {
      type: Array,
      default: () => [],
    },
    rows: {
      type: Array,
      default: () => [],
    },
  },
}
</script>

<style lang="scss" scoped>
.group-summary {
  background-color: var(--color-background-dark) !important;
  border-bottom: 2px solid var(--color-border);

  td {
    padding: 8px !important;
    border-top: 1px solid var(--color-border);
  }
}
</style>
```

---

### 3. AddItemRow.vue (NOVA)
**Pot:** `src/shared/components/ncTable/partials/AddItemRow.vue`

```vue
<template>
  <tr class="add-item-row">
    <td :colspan="columnsCount + 2" class="add-item-cell">
      <NcButton type="tertiary" @click="$emit('add-item')">
        <template #icon>
          <PlusIcon :size="16" />
        </template>
        {{ t('tablespro', 'Add item') }}
      </NcButton>
    </td>
  </tr>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import { translate as t } from '@nextcloud/l10n'

export default {
  name: 'AddItemRow',

  components: {
    NcButton,
    PlusIcon,
  },

  props: {
    columnsCount: {
      type: Number,
      default: 0,
    },
  },

  methods: {
    t,
  },
}
</script>

<style lang="scss" scoped>
.add-item-row {
  background-color: var(--color-background-dark) !important;

  &:hover {
    background-color: var(--color-background-hover) !important;
  }
}

.add-item-cell {
  padding: 8px 16px !important;
}
</style>
```

---

### 4. groupingMixin.js (NOVA)
**Pot:** `src/shared/components/ncTable/mixins/groupingMixin.js`

```javascript
/**
 * Mixin for row grouping functionality
 */
export const groupingMixin = {
  data() {
    return {
      groupByColumnId: null,
      expandedGroups: {},  // { groupValue: boolean }
    }
  },

  computed: {
    /**
     * Get the column we're grouping by
     */
    groupByColumn() {
      if (!this.groupByColumnId) return null
      return this.columns?.find(col => col.id === this.groupByColumnId)
    },

    /**
     * Check if grouping is enabled
     */
    isGrouped() {
      return this.groupByColumnId !== null
    },

    /**
     * Get rows organized into groups
     */
    groupedRows() {
      if (!this.groupByColumnId) return null

      const groups = {}
      const ungroupedKey = '__ungrouped__'

      this.getSearchedAndFilteredAndSortedRows.forEach(row => {
        const cell = row.data?.find(c => c.columnId === this.groupByColumnId)
        let groupValue = cell?.value

        // Handle null/undefined values
        if (groupValue === null || groupValue === undefined) {
          groupValue = ungroupedKey
        }

        // Create group if doesn't exist
        if (!groups[groupValue]) {
          groups[groupValue] = {
            value: groupValue,
            label: this.getGroupLabel(groupValue),
            color: this.getGroupColor(groupValue),
            rows: [],
            expanded: this.expandedGroups[groupValue] !== false, // Default expanded
          }
        }

        groups[groupValue].rows.push(row)
      })

      // Sort groups by label
      return Object.values(groups).sort((a, b) => {
        if (a.value === ungroupedKey) return 1
        if (b.value === ungroupedKey) return -1
        return a.label.localeCompare(b.label)
      })
    },

    /**
     * Get columns that can be used for grouping
     */
    groupableColumns() {
      return this.columns?.filter(col =>
        ['selection', 'text', 'text-line'].includes(col.type)
      ) || []
    },
  },

  methods: {
    /**
     * Get display label for a group value
     */
    getGroupLabel(groupValue) {
      if (groupValue === '__ungrouped__') {
        return this.t('tablespro', 'Ungrouped')
      }

      // If grouping by selection column, get the option label
      if (this.groupByColumn?.type === 'selection') {
        const option = this.groupByColumn.selectionOptions?.find(
          opt => opt.id === groupValue
        )
        return option?.label || groupValue
      }

      return String(groupValue)
    },

    /**
     * Get color for a group (if selection column)
     */
    getGroupColor(groupValue) {
      if (this.groupByColumn?.type === 'selection') {
        const option = this.groupByColumn.selectionOptions?.find(
          opt => opt.id === groupValue
        )
        return option?.color || null
      }
      return null
    },

    /**
     * Toggle group expanded/collapsed state
     */
    toggleGroup(groupValue) {
      this.$set(
        this.expandedGroups,
        groupValue,
        !this.expandedGroups[groupValue] ?? false
      )
    },

    /**
     * Expand all groups
     */
    expandAllGroups() {
      if (!this.groupedRows) return
      this.groupedRows.forEach(group => {
        this.$set(this.expandedGroups, group.value, true)
      })
    },

    /**
     * Collapse all groups
     */
    collapseAllGroups() {
      if (!this.groupedRows) return
      this.groupedRows.forEach(group => {
        this.$set(this.expandedGroups, group.value, false)
      })
    },

    /**
     * Set the column to group by
     */
    setGroupBy(columnId) {
      this.groupByColumnId = columnId
      this.expandedGroups = {} // Reset expanded state
    },

    /**
     * Clear grouping
     */
    clearGrouping() {
      this.groupByColumnId = null
      this.expandedGroups = {}
    },

    /**
     * Add item to a specific group
     */
    addItemToGroup(groupValue) {
      // Emit event with pre-filled column value
      this.$emit('create-row', {
        prefill: {
          [this.groupByColumnId]: groupValue
        }
      })
    },
  },
}
```

---

### 5. Posodobitev CustomTable.vue

**Dodaj importe:**
```javascript
import GroupHeader from '../partials/GroupHeader.vue'
import GroupSummary from '../partials/GroupSummary.vue'
import AddItemRow from '../partials/AddItemRow.vue'
import { groupingMixin } from '../mixins/groupingMixin.js'
```

**Dodaj komponente in mixin:**
```javascript
components: {
  // ... obstoječi
  GroupHeader,
  GroupSummary,
  AddItemRow,
},

mixins: [summaryMixin, groupingMixin],
```

**Posodobi template (tbody section):**
```vue
<!-- NON-GROUPED VIEW -->
<template v-if="!isGrouped">
  <transition-group
    name="table-row"
    tag="tbody"
    :css="rowAnimation"
    @after-leave="disableRowAnimation">
    <TableRow v-for="row in currentPageRows"
      :key="row.id"
      :row="row"
      :columns="columns"
      ... />
  </transition-group>
</template>

<!-- GROUPED VIEW -->
<template v-else>
  <tbody
    v-for="group in groupedRows"
    :key="'group-' + group.value"
    class="row-group"
  >
    <!-- Group Header -->
    <GroupHeader
      :label="group.label"
      :rows="group.rows"
      :columns="columns"
      :expanded="group.expanded"
      :group-color="group.color"
      @toggle="toggleGroup(group.value)"
    />

    <!-- Group Rows (when expanded) -->
    <template v-if="group.expanded">
      <TableRow
        v-for="row in group.rows"
        :key="row.id"
        :row="row"
        :columns="columns"
        :selected="isRowSelected(row?.id)"
        :view-setting.sync="localViewSetting"
        :config="config"
        :element-id="elementId"
        :is-view="isView"
        @update-row-selection="updateRowSelection"
        @edit-row="rowId => $emit('edit-row', rowId)"
      />

      <!-- Add Item Button -->
      <AddItemRow
        :columns-count="columns.length"
        @add-item="addItemToGroup(group.value)"
      />

      <!-- Group Summary -->
      <GroupSummary
        :columns="columns"
        :rows="group.rows"
      />
    </template>
  </tbody>
</template>
```

---

### 6. GroupBySelector.vue (NOVA)
**Pot:** `src/shared/components/ncTable/partials/GroupBySelector.vue`

Za toolbar - dropdown za izbiro stolpca po katerem grupiramo.

```vue
<template>
  <div class="group-by-selector">
    <NcSelect
      v-model="selectedColumn"
      :options="groupableOptions"
      :placeholder="t('tablespro', 'Group by...')"
      :clearable="true"
      @input="$emit('update:groupBy', $event?.id || null)"
    />
  </div>
</template>

<script>
import { NcSelect } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'

export default {
  name: 'GroupBySelector',

  components: {
    NcSelect,
  },

  props: {
    columns: {
      type: Array,
      default: () => [],
    },
    groupBy: {
      type: Number,
      default: null,
    },
  },

  computed: {
    selectedColumn: {
      get() {
        return this.groupableOptions.find(opt => opt.id === this.groupBy) || null
      },
      set(value) {
        // Handled by @input
      },
    },

    groupableOptions() {
      return this.columns
        .filter(col => ['selection', 'text', 'text-line'].includes(col.type))
        .map(col => ({
          id: col.id,
          label: col.title,
        }))
    },
  },

  methods: {
    t,
  },
}
</script>
```

---

## Checklist

- [ ] Ustvari `GroupHeader.vue`
- [ ] Ustvari `GroupSummary.vue`
- [ ] Ustvari `AddItemRow.vue`
- [ ] Ustvari `groupingMixin.js`
- [ ] Ustvari `GroupBySelector.vue`
- [ ] Posodobi `CustomTable.vue` - grouped view template
- [ ] Dodaj Group By selector v toolbar
- [ ] Implementiraj expand/collapse animacije
- [ ] Implementiraj "Add item" z prefill vrednostjo
- [ ] Dodaj CSS za vse komponente
- [ ] Test z selection stolpci
- [ ] Test z text stolpci
- [ ] Test collapse/expand
- [ ] Test pagination z groupami (opcijsko)
- [ ] Git commit

---

## Testiranje

1. Ustvari tabelo s selection stolpcem (npr. "Mesec" ali "Kategorija")
2. Dodaj vrstice z različnimi vrednostmi
3. Izberi "Group by" stolpec
4. Preveri da se vrstice grupirajo pravilno
5. Preveri expand/collapse delovanje
6. Preveri da se summary prikaže za vsako skupino
7. Preveri "Add item" funkcionalnost
8. Preveri da nova vrstica dobi prefilled vrednost
